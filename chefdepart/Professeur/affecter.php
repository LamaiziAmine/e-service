<?php
session_start();
include '../config.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$chef_id = $_SESSION['user_id'];

// Récupérer le département du chef
$stmt = $connection->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->bind_param("i", $chef_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

// Récupérer les professeurs (internes et vacataires) du même département
$professeurs = $connection->query("
    SELECT id, nom, prenom, role AS type 
    FROM users 
    WHERE (role = 'professeur' OR role = 'vacataire') AND department_id = $department_id
");

// Récupérer les unités d’enseignement du département du chef
$ues = $connection->query("
    SELECT * FROM `unités_enseignement` WHERE department_id = $department_id ORDER BY code_module
");

// Récupérer les types d'enseignement déjà affectés par UE
$ue_ids = [];
foreach ($ues as $ue) {
    $ue_ids[] = $ue['id'];
}
$ues->data_seek(0); // Reset pointer to start to reuse later

$assigned_map = [];
if (!empty($ue_ids)) {
    $ue_ids_str = implode(',', $ue_ids);
    $assigned_types_query = $connection->query("
        SELECT id_ue, type_enseignement 
        FROM affectations 
        WHERE id_ue IN ($ue_ids_str)
    ");
    while ($row = $assigned_types_query->fetch_assoc()) {
        $ue_id = $row['id_ue'];
        $type = $row['type_enseignement'];
        if (!isset($assigned_map[$ue_id])) {
            $assigned_map[$ue_id] = [];
        }
        $assigned_map[$ue_id][] = $type;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Affecter des unités d’enseignement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .ue-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 0 10px rgb(0 0 0 / 0.1);
            background: white;
            transition: box-shadow 0.3s ease;
        }
        .ue-card:hover {
            box-shadow: 0 0 15px rgb(0 0 0 / 0.2);
        }
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 25px rgb(0 0 0 / 0.05);
            max-width: 800px;
            margin: 40px auto;
        }
    </style>
</head>
<body>
<div class="layer"></div>
<div class="page-flex">
    <?php include "../sidebar.php" ?>
    <div class="main-wrapper">
        <?php include "../navbar.php" ?><br>

        <div class="container form-section">
            <h2 class="mb-4 text-center">Affecter des unités d’enseignement</h2>

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

            <form method="post" action="traitement_affectation.php" novalidate>
                <div class="mb-4">
                    <label for="prof_id" class="form-label fw-bold">Sélectionner un professeur :</label>
                    <select name="prof_id" id="prof_id" class="form-select" required>
                        <option value="" disabled selected>-- Choisir un professeur --</option>
                        <?php while ($prof = $professeurs->fetch_assoc()): ?>
                            <option value="<?= $prof['id'] ?>" data-type="<?= htmlspecialchars($prof['type']) ?>">
                                <?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom'] . ' (' . $prof['type'] . ')') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" name="is_vacataire" id="is_vacataire" value="0" />
                    <div class="invalid-feedback">Veuillez sélectionner un professeur.</div>
                </div>

                <!-- Add academic year select -->
                <div class="mb-4">
                    <label for="annee_univ" class="form-label fw-bold">Année universitaire :</label>
                    <select name="annee_univ" id="annee_univ" class="form-select" required>
                        <?php
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear - 1; $y <= $currentYear + 3; $y++) {
                            $start = $y;
                            $end = $y + 1;
                            $value = "$start-$end";
                            echo "<option value=\"$value\">$value</option>";
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Veuillez sélectionner une année universitaire.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Unités d’enseignement :</label>
                    <small class="d-block mb-3 text-muted">Cochez les unités et choisissez les types d’enseignement à affecter.</small>

                    <?php
                    $all_types = ['Cours', 'TD', 'TP'];
                    while ($ue = $ues->fetch_assoc()):
                        $ue_id = $ue['id'];
                        $assigned = isset($assigned_map[$ue_id]) ? $assigned_map[$ue_id] : [];
                        $remaining = array_diff($all_types, $assigned);
                        if (empty($remaining)) continue; // Skip if fully assigned
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
                    <?php endwhile; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Affecter les unités</button>
            </form>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Affichage des types quand UE cochée
    document.querySelectorAll('.ue-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const group = this.closest('.ue-card').querySelector('.type-enseignement-group');
            if (this.checked) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                group.querySelectorAll('input[type=checkbox]').forEach(chk => chk.checked = false);
            }
        });
    });

    // Détection vacataire ou interne via role
    document.getElementById('prof_id').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const type = selected.getAttribute('data-type');
        document.getElementById('is_vacataire').value = (type === 'vacataire') ? '1' : '0';
    });

    // Validation
    (() => {
        'use strict';
        const form = document.querySelector('form');
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                let valid = false;
                document.querySelectorAll('.ue-checkbox:checked').forEach(ueCheckbox => {
                    const ueId = ueCheckbox.value;
                    const checkedTypes = document.querySelectorAll(`input[name="type_${ueId}[]"]:checked`);
                    if (checkedTypes.length > 0) valid = true;
                });

                if (!valid) {
                    alert('Veuillez sélectionner au moins une unité avec un type d\'enseignement.');
                    event.preventDefault();
                    event.stopPropagation();
                }
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>
<script src="/elegant/plugins/chart.min.js"></script>
<script src="/elegant/plugins/feather.min.js"></script>
<script src="/elegant/js/script.js"></script>

</body>
</html>
