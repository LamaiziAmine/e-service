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
// Récupérer les unités d'enseignement du département du chef
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
    <title>Affecter des unités d'enseignement</title>
    <!-- Liens vers les styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }



        .page-flex {
            background: transparent;
        }

        .main-wrapper {
            background: transparent;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            max-width: 1000px;
            margin: 40px auto;
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .form-section h2 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 2rem;
            position: relative;
        }

        .form-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .form-label {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #667eea;
        }

        .form-select,
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
        }

        .form-select:focus,
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .ue-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .ue-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: var(--transition);
        }

        .ue-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }

        .ue-card:hover::before {
            transform: scaleY(1);
        }

        .ue-card.selected {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-color: #667eea;
        }

        .ue-card.selected::before {
            transform: scaleY(1);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e0;
            border-radius: 6px;
            transition: var(--transition);
        }

        .form-check-input:checked {
            background: var(--primary-gradient);
            border-color: #667eea;
        }

        .form-check-label {
            font-weight: 500;
            color: #2d3748;
            margin-left: 0.5rem;
        }

        .ue-checkbox+.form-check-label {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1a202c;
        }

        .type-enseignement-group {
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .type-badge.cours {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #8b5cf6;
        }

        .type-badge.td {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #06b6d4;
        }

        .type-badge.tp {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #f59e0b;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 2rem;
            position: relative;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: #047857;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-label {
            color: #64748b;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ue-card {
            animation: slideInUp 0.6s ease-out forwards;
        }

        .ue-card:nth-child(odd) {
            animation-delay: 0.1s;
        }

        .ue-card:nth-child(even) {
            animation-delay: 0.2s;
        }

        .form-floating {
            position: relative;
        }

        .form-floating>.form-select {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem 0.25rem;
        }

        .form-floating>label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out, transform .1s ease-in-out;
        }

        .no-ue-message {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
        }

        .no-ue-message i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }

        .scrollable-ue-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .scrollable-ue-list::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-ue-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .scrollable-ue-list::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 10px;
        }

        .selection-summary {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
            display: none;
        }

        .selection-summary.show {
            display: block;
            animation: slideInUp 0.3s ease-out;
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
                <h2 class="mb-4 text-center">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Affecter des Unités d'Enseignement
                </h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_GET['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>

                <form method="post" action="traitement_affectation.php" novalidate class="needs-validation">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="prof_id" class="form-label fw-bold">
                                <i class="fas fa-user-tie"></i>
                                Sélectionner un enseignant
                            </label>
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

                        <div class="col-md-6">
                            <label for="annee_univ" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt"></i>
                                Année universitaire
                            </label>
                            <select name="annee_univ" id="annee_univ" class="form-select" required>
                                <?php
                                $currentYear = (int) date('Y');
                                for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                                    $academicYear = $y . '-' . ($y + 1);
                                    echo "<option value=\"$academicYear\">$academicYear</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Veuillez sélectionner une année universitaire.</div>
                        </div>
                    </div>

                    <div class="selection-summary" id="selection-summary">
                        <h5><i class="fas fa-clipboard-list me-2"></i>Résumé de la sélection</h5>
                        <div id="summary-content"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-book-open"></i>
                            Unités d'enseignement disponibles
                        </label>
                        <small class="d-block mb-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Cochez les UEs et les types d'enseignement à affecter.
                        </small>

                        <div class="scrollable-ue-list">
                            <?php
                            $all_types = ['Cours', 'TD', 'TP'];
                            $type_icons = ['Cours' => 'fas fa-chalkboard', 'TD' => 'fas fa-users', 'TP' => 'fas fa-flask'];

                            if ($ues->num_rows > 0) {
                                while ($ue = $ues->fetch_assoc()):
                                    $ue_id = $ue['id'];
                                    $assigned = isset($assigned_map[$ue_id]) ? $assigned_map[$ue_id] : [];
                                    $remaining = array_diff($all_types, $assigned);
                                    // On affiche la carte uniquement s'il reste des types à affecter
                                    if (empty($remaining))
                                        continue;
                                    ?>
                                    <div class="ue-card" data-ue-id="<?= $ue_id ?>">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input ue-checkbox" type="checkbox" name="ue_ids[]"
                                                value="<?= $ue_id ?>" id="ue_<?= $ue_id ?>">
                                            <label class="form-check-label fw-semibold" for="ue_<?= $ue_id ?>">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                <?= htmlspecialchars($ue['code_module'] . ' - ' . $ue['intitule_module']) ?>
                                            </label>
                                        </div>
                                        <div class="ms-4 type-enseignement-group" style="display:none;">
                                            <div class="row">
                                                <?php foreach ($remaining as $type): ?>
                                                    <div class="col-md-4 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input type-checkbox" type="checkbox"
                                                                name="type_<?= $ue_id ?>[]" value="<?= $type ?>"
                                                                id="<?= strtolower($type) . '_' . $ue_id ?>"
                                                                data-type="<?= $type ?>">
                                                            <label class="form-check-label type-badge <?= strtolower($type) ?>"
                                                                for="<?= strtolower($type) . '_' . $ue_id ?>">
                                                                <i class="<?= $type_icons[$type] ?> me-1"></i>
                                                                <?= $type ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                            } else {
                                echo '<div class="no-ue-message">
                                    <i class="fas fa-inbox"></i>
                                    <h4>Aucune unité d\'enseignement disponible</h4>
                                    <p class="text-muted">Il n\'y a actuellement aucune unité d\'enseignement disponible pour ce département.</p>
                                  </div>';
                            }
                            ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold py-3">
                        <i class="fas fa-paper-plane me-2"></i>
                        Affecter les unités sélectionnées
                    </button>
                </form>
            </div>
        </div>
    </div>
    <!-- These scripts are for  theme's general functionality -->
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            'use strict';

            // Function to update the selection summary
            function updateSelectionSummary() {
                const selectedUEs = document.querySelectorAll('.ue-checkbox:checked');
                const summaryElement = document.getElementById('selection-summary');
                const summaryContent = document.getElementById('summary-content');

                if (selectedUEs.length === 0) {
                    summaryElement.classList.remove('show');
                    return;
                }

                let hasSelections = false;
                let summaryHTML = '<div class="row">';
                selectedUEs.forEach(ueCheckbox => {
                    const ueCard = ueCheckbox.closest('.ue-card');
                    const ueLabel = ueCheckbox.nextElementSibling.textContent.trim();
                    const ueId = ueCheckbox.value;
                    const selectedTypes = Array.from(document.querySelectorAll(`input[name="type_${ueId}[]"]:checked`))
                        .map(input => input.value);

                    if (selectedTypes.length > 0) {
                        hasSelections = true;
                        summaryHTML += `
                    <div class="col-md-6 mb-2">
                        <strong>${ueLabel}</strong><br>
                        <small class="text-muted">Types: ${selectedTypes.join(', ')}</small>
                    </div>
                `;
                    }
                });
                summaryHTML += '</div>';

                if (hasSelections) {
                    summaryContent.innerHTML = summaryHTML;
                    summaryElement.classList.add('show');
                } else {
                    summaryElement.classList.remove('show');
                }
            }

            // Event listener for UE checkboxes
            document.querySelectorAll('.ue-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const ueCard = this.closest('.ue-card');
                    const group = ueCard.querySelector('.type-enseignement-group');

                    if (this.checked) {
                        // *** FIX: Use classList.add instead of addClass ***
                        ueCard.classList.add('selected');
                        group.style.display = 'block';
                    } else {
                        // *** FIX: Use classList.remove instead of removeClass ***
                        ueCard.classList.remove('selected');
                        group.style.display = 'none';
                        // Uncheck all type checkboxes within this UE when it's unchecked
                        group.querySelectorAll('input[type=checkbox]').forEach(chk => chk.checked = false);
                    }
                    updateSelectionSummary();
                });
            });

            // Event listener for type checkboxes
            document.querySelectorAll('.type-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectionSummary);
            });

            // Event listener for professor selection to set vacataire status
            const profSelect = document.getElementById('prof_id');
            if (profSelect) {
                profSelect.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    const type = selected.getAttribute('data-type');
                    document.getElementById('is_vacataire').value = (type === 'vacataire') ? '1' : '0';
                });
            }

            // Form validation logic
            const form = document.querySelector('form.needs-validation');
            if (form) {
                form.addEventListener('submit', event => {
                    let oneUeTypeSelected = false;
                    const checkedUes = document.querySelectorAll('.ue-checkbox:checked');

                    if (checkedUes.length > 0) {
                        checkedUes.forEach(ueCheckbox => {
                            const ueId = ueCheckbox.value;
                            if (document.querySelectorAll(`input[name="type_${ueId}[]"]:checked`).length > 0) {
                                oneUeTypeSelected = true;
                            }
                        });
                        // If UEs are checked, but no types are selected for any of them, it's an error
                        if (!oneUeTypeSelected) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
                            alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Pour chaque unité cochée, veuillez sélectionner au moins un type d'enseignement.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                            form.insertBefore(alertDiv, form.firstChild);

                            setTimeout(() => alertDiv.remove(), 5000);

                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            }

            // Initial animation for UE cards
            const cards = document.querySelectorAll('.ue-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
            });
        });
    </script>



</body>

</html>