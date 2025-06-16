<?php
session_start();
// Assurez-vous que le chemin vers config.php est correct
// Je vais utiliser la connexion directe pour garantir le fonctionnement.
$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';
$connection = new mysqli($host, $user, $pass, $db);
if ($connection->connect_error) {
    die("Erreur de connexion : " . $connection->connect_error);
}

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$chef_id = $_SESSION['user_id'];
$department_id = null; // Initialisation

// Récupérer le département du chef
$stmt = $connection->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->bind_param("i", $chef_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

if ($department_id === null) {
    die("Impossible de récupérer le département pour cet utilisateur.");
}

// Récupérer les professeurs (internes et vacataires) du même département
$professeurs_query = $connection->prepare("
    SELECT id, nom, prenom, role AS type 
    FROM users 
    WHERE (role = 'professeur' OR role = 'vacataire') AND department_id = ?
");
$professeurs_query->bind_param("i", $department_id);
$professeurs_query->execute();
$professeurs = $professeurs_query->get_result();


// *** CORRECTION DU NOM DE LA TABLE ICI ***
// Récupérer les unités d’enseignement du département du chef
$ues_query = $connection->prepare("
    SELECT * FROM `unités_ensignement` WHERE department_id = ? ORDER BY code_module
");
$ues_query->bind_param("i", $department_id);
$ues_query->execute();
$ues = $ues_query->get_result();


// Récupérer les types d'enseignement déjà affectés par UE
$ue_ids = [];
if ($ues->num_rows > 0) {
    foreach ($ues as $ue) {
        $ue_ids[] = $ue['id'];
    }
    $ues->data_seek(0); // Remettre le pointeur au début pour l'utiliser plus tard
}

$assigned_map = [];
if (!empty($ue_ids)) {
    $ue_ids_str = implode(',', $ue_ids);
    $assigned_types_query = $connection->query("
        SELECT id_ue, type_enseignement 
        FROM affectations 
        WHERE id_ue IN ($ue_ids_str)
    ");
    if ($assigned_types_query) {
        while ($row = $assigned_types_query->fetch_assoc()) {
            $ue_id = $row['id_ue'];
            $type = $row['type_enseignement'];
            if (!isset($assigned_map[$ue_id])) {
                $assigned_map[$ue_id] = [];
            }
            $assigned_map[$ue_id][] = $type;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Affecter des unités d’enseignement</title>
    <!-- Liens vers les styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <style>
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 25px rgb(0 0 0 / 0.05);
            max-width: 900px;
            margin: 40px auto;
        }
        .ue-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
            background: #f8f9fa;
            transition: box-shadow 0.3s ease;
        }
        .ue-card:hover {
            box-shadow: 0 0 15px rgb(0 0 0 / 0.15);
        }
    </style>
</head>
<body>
<div class="page-flex">
    <?php 
    // Assurez-vous que ce chemin est correct. Si ce fichier est dans /chefdepart/, le chemin est bon.
    include "../sidebar.php"; 
    ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?><br>

        <div class="container form-section">
            <h2 class="mb-4 text-center">Affecter des Unités d’Enseignement</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="traitement_affectation.php" novalidate class="needs-validation">
                <div class="mb-4">
                    <label for="prof_id" class="form-label fw-bold">Sélectionner un enseignant :</label>
                    <select name="prof_id" id="prof_id" class="form-select" required>
                        <option value="" disabled selected>-- Choisir un enseignant --</option>
                        <?php while ($prof = $professeurs->fetch_assoc()): ?>
                            <option value="<?= $prof['id'] ?>" data-type="<?= htmlspecialchars($prof['type']) ?>">
                                <?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom'] . ' (' . ucfirst($prof['type']) . ')') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" name="is_vacataire" id="is_vacataire" value="0" />
                    <div class="invalid-feedback">Veuillez sélectionner un enseignant.</div>
                </div>

                <div class="mb-4">
                    <label for="annee_univ" class="form-label fw-bold">Année universitaire :</label>
                    <select name="annee_univ" id="annee_univ" class="form-select" required>
                        <?php
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                            $academicYear = $y . '-' . ($y + 1);
                            echo "<option value=\"$academicYear\">$academicYear</option>";
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Veuillez sélectionner une année universitaire.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Unités d’enseignement disponibles :</label>
                    <small class="d-block mb-3 text-muted">Cochez les UEs et les types d’enseignement à affecter.</small>

                    <?php
                    $all_types = ['Cours', 'TD', 'TP'];
                    if ($ues->num_rows > 0) {
                        while ($ue = $ues->fetch_assoc()):
                            $ue_id = $ue['id'];
                            $assigned = isset($assigned_map[$ue_id]) ? $assigned_map[$ue_id] : [];
                            $remaining = array_diff($all_types, $assigned);
                            // On affiche la carte uniquement s'il reste des types à affecter
                            if (empty($remaining)) continue;
                        ?>
                            <div class="ue-card">
                                <div class="form-check mb-2">
                                    <input class="form-check-input ue-checkbox" type="checkbox"
                                           name="ue_ids[]" value="<?= $ue_id ?>" id="ue_<?= $ue_id ?>">
                                    <label class="form-check-label fw-semibold" for="ue_<?= $ue_id ?>">
                                        <?= htmlspecialchars($ue['code_module'] . ' - ' . $ue['intitule_module']) ?>
                                    </label>
                                </div>
                                <div class="ms-4 type-enseignement-group" style="display:none;">
                                    <?php foreach ($remaining as $type): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="type_<?= $ue_id ?>[]" value="<?= $type ?>" id="<?= strtolower($type) . '_' . $ue_id ?>">
                                            <label class="form-check-label" for="<?= strtolower($type) . '_' . $ue_id ?>"><?= $type ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endwhile;
                    } else {
                        echo '<p class="text-center text-muted">Aucune unité d\'enseignement disponible pour ce département.</p>';
                    }
                    ?>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Affecter les unités sélectionnées</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Le JavaScript reste le même, il est correct.
    document.querySelectorAll('.ue-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const group = this.closest('.ue-card').querySelector('.type-enseignement-group');
            group.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) {
                group.querySelectorAll('input[type=checkbox]').forEach(chk => chk.checked = false);
            }
        });
    });

    document.getElementById('prof_id').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const type = selected.getAttribute('data-type');
        document.getElementById('is_vacataire').value = (type === 'vacataire') ? '1' : '0';
    });

    (() => {
        'use strict';
        const form = document.querySelector('form');
        form.addEventListener('submit', event => {
            let oneUeTypeSelected = false;
            document.querySelectorAll('.ue-checkbox:checked').forEach(ueCheckbox => {
                const ueId = ueCheckbox.value;
                if (document.querySelectorAll(`input[name="type_${ueId}[]"]:checked`).length > 0) {
                    oneUeTypeSelected = true;
                }
            });

            if (!form.checkValidity() || (document.querySelectorAll('.ue-checkbox:checked').length > 0 && !oneUeTypeSelected)) {
                if (!oneUeTypeSelected && document.querySelectorAll('.ue-checkbox:checked').length > 0) {
                    alert('Pour chaque unité cochée, veuillez sélectionner au moins un type d\'enseignement.');
                }
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>

</body>
</html>