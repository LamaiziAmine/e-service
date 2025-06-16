<?php
$currentPage = basename($_SERVER['PHP_SELF']);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: ../login.php");
    exit;
}

$professeur_id = $_SESSION['user_id'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

// Connexion à la base de données
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$_SESSION['prof_id'] = 1;

// On vérifie maintenant que la session existe (bonne pratique)
if (!isset($_SESSION['prof_id'])) {
    die("Accès refusé. Vous devez être connecté en tant que professeur.");
}

// On assigne la valeur à la variable utilisée dans les requêtes
$professeur_id = $_SESSION['prof_id'];

// --- GESTIONNAIRE DE REQUÊTES AJAX POUR ENREGISTRER LES SOUHAITS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enregistrer_souhaits') {
    header('Content-Type: application/json');

    $module_ids = isset($_POST['modules']) ? $_POST['modules'] : [];

    // Utiliser une transaction pour assurer l'intégrité des données
    $conn->begin_transaction();
    try {
        // 1. Supprimer tous les anciens souhaits de ce professeur
        $stmt_delete = $conn->prepare("DELETE FROM choix_ues WHERE id_professeur = ?");
        $stmt_delete->bind_param("i", $professeur_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 2. Insérer les nouveaux souhaits s'il y en a
        if (!empty($module_ids)) {
            $stmt_insert = $conn->prepare("INSERT INTO choix_ues (id_professeur, id_module) VALUES (?, ?)");
            foreach ($module_ids as $module_id) {
                // Assurez-vous que les IDs sont bien des entiers
                $safe_module_id = (int) $module_id;
                $stmt_insert->bind_param("ii", $professeur_id, $safe_module_id);
                $stmt_insert->execute();
            }
            $stmt_insert->close();
        }

        // Valider la transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Vos souhaits ont été enregistrés avec succès.']);
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'enregistrement.']);
    }
    exit; // Arrêter le script pour ne pas afficher le HTML
}

// --- RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE DE LA PAGE ---
$sql = "SELECT u.*, s.id_module IS NOT NULL AS est_selectionne
        FROM unités_ensignement u
        LEFT JOIN choix_ues s ON u.id = s.id_module AND s.id_professeur = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souhaits d'enseignement</title>
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }



        .layer1 {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            pointer-events: none;
            z-index: -1;
        }

        .page-flex1 {
            display: flex;
            min-height: 100vh;
        }

        .main-wrapper1 {
            flex: 1;
            margin-left: 20px;
            padding: 2rem;
            background: var(--bg-secondary);
            min-height: 100vh;
        }

        .header-section1 {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .header-section1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .main-title1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .title-icon1 {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .subtitle1 {
            font-size: 1.125rem;
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 70%;
        }

        .stats-grid1 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card1 {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .stat-card1:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header1 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .stat-icon1 {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon1.total {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .stat-icon1.selected {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }

        .stat-icon1.hours {
            background: linear-gradient(135deg, var(--accent-color), #d97706);
        }

        .stat-value1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-label1 {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .table-container1 {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 8rem;
        }

        .table-header1 {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-title1 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .select-all-container1 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .custom-checkbox1 {
            position: relative;
            width: 1.5rem;
            height: 1.5rem;
        }

        .custom-checkbox1 input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .checkmark1 {
            position: absolute;
            top: 0;
            left: 0;
            height: 1.5rem;
            width: 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: var(--radius-sm);
            transition: all 0.3s ease;
        }

        .custom-checkbox1 input:checked~.checkmark1 {
            background: var(--success-color);
            border-color: var(--success-color);
        }

        .checkmark1:after {
            content: "";
            position: absolute;
            display: none;
            left: 0.375rem;
            top: 0.125rem;
            width: 0.375rem;
            height: 0.75rem;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox1 input:checked~.checkmark1:after {
            display: block;
        }

        .table-style1 {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-style1 thead th {
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-style1 tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .table-style1 tbody tr:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), rgba(16, 185, 129, 0.05));
            transform: scale(1.01);
        }

        .table-style1 tbody tr.selected1 {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1));
            border-left: 4px solid var(--primary-color);
        }

        .table-style1 td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            font-size: 0.925rem;
        }

        .module-info1 {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .module-name1 {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .module-code1 {
            font-size: 0.825rem;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            display: inline-block;
            font-family: 'Courier New', monospace;
        }

        .semester-badge1 {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--accent-color), #d97706);
            color: white;
        }

        .filiere-badge1 {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--secondary-color), #059669);
            color: white;
        }

        .hours-display1 {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-bar1 {
            position: fixed;
            bottom: 0;
            left: 250px;
            right: 0;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .summary-content1 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .charge-info1 {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .total-hours1 {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .hours-counter1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
        }

        .hours-unit1 {
            font-size: 1.25rem;
            color: var(--text-secondary);
        }

        .charge-warning1 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--warning-color);
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s ease;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .summary-bar1.warning1 .charge-warning1 {
            opacity: 1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .save-button1 {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-md);
            min-width: 200px;
            justify-content: center;
        }

        .save-button1:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, #059669, #047857);
        }

        .save-button1:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
            box-shadow: var(--shadow-sm);
        }

        .loading-spinner1 {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .notification1 {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            min-width: 300px;
            transform: translateX(400px);
            transition: all 0.3s ease;
            z-index: 2000;
        }

        .notification1.show {
            transform: translateX(0);
        }

        .notification1.success {
            border-left: 4px solid var(--success-color);
        }

        .notification1.error {
            border-left: 4px solid var(--danger-color);
        }

        .empty-state1 {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-icon1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .main-wrapper1 {
                margin-left: 0;
                padding: 1rem;
            }

            .summary-bar1 {
                left: 0;
            }

            .summary-content1 {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .main-title1 {
                font-size: 1.75rem;
            }

            .stats-grid1 {
                grid-template-columns: 1fr;
            }

            .table-style1 {
                font-size: 0.875rem;
            }

            .table-style1 td {
                padding: 1rem;
            }
        }

        .fade-in1 {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "sidebar_prof.php" ?>
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php" ?>
            <div class="main-wrapper1">
                <div class="header-section1 fade-in1">
                    <h1 class="main-title1">
                        <div class="title-icon1">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        Sélection des souhaits d'enseignement
                    </h1>
                    <p class="subtitle1">
                        Choisissez les modules que vous souhaitez enseigner cette année académique.
                        Votre charge horaire est calculée en temps réel pour vous aider dans votre planification.
                    </p>
                </div>

                <div class="stats-grid1 fade-in1">
                    <div class="stat-card1">
                        <div class="stat-header1">
                            <div class="stat-icon1 total">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <div class="stat-value1" id="total-modules1">
                                    <?php echo $result->num_rows; ?>
                                </div>
                                <div class="stat-label1">Modules disponibles</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card1">
                        <div class="stat-header1">
                            <div class="stat-icon1 selected">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="stat-value1" id="selected-modules1">0</div>
                                <div class="stat-label1">Modules sélectionnés</div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card1">
                        <div class="stat-header1">
                            <div class="stat-icon1 hours">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="stat-value1" id="current-hours1">0h</div>
                                <div class="stat-label1">Charge horaire actuelle</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container1 fade-in1">
                    <div class="table-header1">
                        <h3 class="table-title1">
                            <i class="fas fa-list"></i>
                            Liste des modules
                        </h3>
                        <div class="select-all-container1">
                            <label class="custom-checkbox1">
                                <input type="checkbox" id="select-all1">
                                <span class="checkmark1"></span>
                            </label>
                            <span>Tout sélectionner</span>
                        </div>
                    </div>

                    <table class="table-style1">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">
                                    <i class="fas fa-check"></i>
                                </th>
                                <th>Module</th>
                                <th>Semestre</th>
                                <th>Filière</th>
                                <th>Volume Horaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $vh_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                                    $est_coche = $row['est_selectionne'] ? 'checked' : '';
                                    $row_class = $row['est_selectionne'] ? 'selected1' : '';

                                    echo "<tr class='{$row_class}'>";
                                    echo "<td style='text-align: center;'>
                                        <label class='custom-checkbox1'>
                                            <input type='checkbox' class='module-checkbox1' value='" . $row['id'] . "' data-heures='" . $vh_total . "' " . $est_coche . ">
                                            <span class='checkmark1'></span>
                                        </label>
                                      </td>";
                                    echo "<td>
                                        <div class='module-info1'>
                                            <div class='module-name1'>" . htmlspecialchars($row['intitule_module']) . "</div>
                                            <div class='module-code1'>" . htmlspecialchars($row['code_module']) . "</div>
                                        </div>
                                      </td>";
                                    echo "<td>
                                        <div class='semester-badge1'>
                                            <i class='fas fa-calendar-alt'></i>
                                            " . htmlspecialchars($row['semestre']) . "
                                        </div>
                                      </td>";
                                    echo "<td>
                                        <div class='filiere-badge1'>
                                            <i class='fas fa-graduation-cap'></i>
                                            " . htmlspecialchars($row['filiere']) . "
                                        </div>
                                      </td>";
                                    echo "<td>
                                        <div class='hours-display1'>
                                            <i class='fas fa-hourglass-half'></i>
                                            " . $vh_total . "h
                                        </div>
                                      </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='5' class='empty-state1'>
                                        <div class='empty-icon1'>
                                            <i class='fas fa-inbox'></i>
                                        </div>
                                        <div>Aucun module n'est disponible pour le moment.</div>
                                    </td>
                                  </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-bar1" id="summary-bar1">
                    <div class="summary-content1">
                        <div class="charge-info1">
                            <div class="total-hours1">
                                <i class="fas fa-stopwatch" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 500;">
                                        Charge horaire totale
                                    </div>
                                    <div class="hours-counter1">
                                        <span id="total-heures1">0</span>
                                        <span class="hours-unit1">heures</span>
                                    </div>
                                </div>
                            </div>
                            <div class="charge-warning1" id="charge-warning1">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Minimum requis : 72h</span>
                            </div>
                        </div>
                        <button class="save-button1" id="enregistrer-souhaits1" disabled>
                            <i class="fas fa-save"></i>
                            <span class="button-text1">Enregistrer mes souhaits</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.module-checkbox1');
            const selectAllCheckbox = document.getElementById('select-all1');
            const totalHeuresEl = document.getElementById('total-heures1');
            const currentHoursEl = document.getElementById('current-hours1');
            const selectedModulesEl = document.getElementById('selected-modules1');
            const summaryBar = document.getElementById('summary-bar1');
            const enregistrerBtn = document.getElementById('enregistrer-souhaits1');
            const buttonText = document.querySelector('.button-text1');
            const MIN_HOURS = 72;

            let aChangeEteFait = false;

            // Fonction pour afficher les notifications
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification1 ${type}`;
                notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" 
                   style="color: var(--${type === 'success' ? 'success' : 'danger'}-color); font-size: 1.25rem;"></i>
                <span style="font-weight: 500;">${message}</span>
            </div>
        `;

                document.body.appendChild(notification);

                // Afficher la notification
                setTimeout(() => notification.classList.add('show'), 100);

                // Masquer et supprimer après 4 secondes
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => document.body.removeChild(notification), 300);
                }, 4000);
            }

            // Fonction d'animation des compteurs CORRIGÉE
            function animateCounter(element, targetValue) {
                const currentValue = parseInt(element.textContent) || 0;

                // Si pas de changement, sortir immédiatement
                if (currentValue === targetValue) {
                    return;
                }

                // Animation simple avec requestAnimationFrame
                const startValue = currentValue;
                const difference = targetValue - startValue;
                const duration = 300; // 300ms
                const startTime = performance.now();

                function animate(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    // Interpolation linéaire
                    const currentAnimatedValue = Math.round(startValue + (difference * progress));
                    element.textContent = currentAnimatedValue;

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        // S'assurer que la valeur finale est exacte
                        element.textContent = targetValue;
                    }
                }

                requestAnimationFrame(animate);
            }

            // Fonction pour mettre à jour les statistiques et l'interface
            function mettreAJourInterface() {
                const selectedCheckboxes = document.querySelectorAll('.module-checkbox1:checked');
                let totalHeures = 0;

                selectedCheckboxes.forEach(checkbox => {
                    totalHeures += parseInt(checkbox.dataset.heures, 10);
                    // Ajouter la classe selected à la ligne
                    checkbox.closest('tr').classList.add('selected1');
                });

                // Retirer la classe selected des lignes non sélectionnées
                document.querySelectorAll('.module-checkbox1:not(:checked)').forEach(checkbox => {
                    checkbox.closest('tr').classList.remove('selected1');
                });

                // Mettre à jour les statistiques avec animation uniquement si nécessaire
                const currentTotalHeures = parseInt(totalHeuresEl.textContent) || 0;
                const currentSelectedModules = parseInt(selectedModulesEl.textContent) || 0;

                // Animer seulement si les valeurs ont changé
                if (currentTotalHeures !== totalHeures) {
                    animateCounter(totalHeuresEl, totalHeures);
                }

                if (currentSelectedModules !== selectedCheckboxes.length) {
                    animateCounter(selectedModulesEl, selectedCheckboxes.length);
                }

                // Mettre à jour immédiatement les heures actuelles (pas d'animation nécessaire)
                currentHoursEl.textContent = totalHeures + 'h';

                // Gestion de l'avertissement
                if (totalHeures > 0 && totalHeures < MIN_HOURS) {
                    summaryBar.classList.add('warning1');
                } else {
                    summaryBar.classList.remove('warning1');
                }

                // Activer/désactiver le bouton d'enregistrement
                enregistrerBtn.disabled = !aChangeEteFait;
            }

            // Fonction pour marquer les changements
            function marquerChangement() {
                aChangeEteFait = true;
                mettreAJourInterface();
            }

            // Initialisation
            mettreAJourInterface();

            // Gestionnaires d'événements
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    marquerChangement();

                    // Animation de la ligne
                    const row = this.closest('tr');
                    row.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        row.style.transform = '';
                    }, 200);
                });
            });

            // Gestion du "Tout sélectionner"
            selectAllCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;

                checkboxes.forEach(checkbox => {
                    if (checkbox.checked !== isChecked) {
                        checkbox.checked = isChecked;

                        // Animation échelonnée
                        setTimeout(() => {
                            const row = checkbox.closest('tr');
                            row.style.transform = 'scale(1.01)';
                            setTimeout(() => {
                                row.style.transform = '';
                            }, 150);
                        }, Math.random() * 200);
                    }
                });

                marquerChangement();
            });

            // Mise à jour du checkbox "Tout sélectionner"
            function updateSelectAllCheckbox() {
                const totalCheckboxes = checkboxes.length;
                const checkedCheckboxes = document.querySelectorAll('.module-checkbox1:checked').length;

                selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
                selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes;
            }

            // Écouter les changements pour mettre à jour le checkbox "Tout sélectionner"
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllCheckbox);
            });

            // Gestion de l'enregistrement
            enregistrerBtn.addEventListener('click', function () {
                const totalActuel = parseInt(totalHeuresEl.textContent, 10);

                // Vérification de la charge minimale
                if (totalActuel > 0 && totalActuel < MIN_HOURS) {
                    const confirmMessage = `⚠️ Attention : Votre charge horaire (${totalActuel}h) est inférieure au minimum requis de ${MIN_HOURS}h.\n\nVoulez-vous quand même enregistrer vos souhaits ?`;
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                }

                // Désactiver le bouton et afficher le loader
                enregistrerBtn.disabled = true;
                buttonText.textContent = 'Enregistrement...';
                enregistrerBtn.insertAdjacentHTML('afterbegin', '<div class="loading-spinner1"></div>');

                // Préparer les données
                const selectedModules = Array.from(document.querySelectorAll('.module-checkbox1:checked')).map(cb => cb.value);
                const formData = new FormData();
                formData.append('action', 'enregistrer_souhaits');
                selectedModules.forEach(id => formData.append('modules[]', id));

                // Envoyer la requête
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        // Supprimer le spinner
                        const spinner = enregistrerBtn.querySelector('.loading-spinner1');
                        if (spinner) spinner.remove();

                        // Afficher la notification
                        showNotification(data.message, data.status === 'success' ? 'success' : 'error');

                        if (data.status === 'success') {
                            aChangeEteFait = false;
                            buttonText.textContent = 'Enregistrer mes souhaits';

                            // Animation de succès
                            enregistrerBtn.style.background = 'linear-gradient(135deg, var(--success-color), #059669)';
                            enregistrerBtn.style.transform = 'scale(1.05)';
                            setTimeout(() => {
                                enregistrerBtn.style.transform = '';
                            }, 200);
                        } else {
                            enregistrerBtn.disabled = false;
                            buttonText.textContent = 'Enregistrer mes souhaits';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);

                        // Supprimer le spinner
                        const spinner = enregistrerBtn.querySelector('.loading-spinner1');
                        if (spinner) spinner.remove();

                        showNotification('Une erreur de communication est survenue. Veuillez réessayer.', 'error');
                        enregistrerBtn.disabled = false;
                        buttonText.textContent = 'Enregistrer mes souhaits';
                    });
            });

            // Animation d'entrée pour les éléments
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in1');
                        observer.unobserve(entry.target);
                    }
                });
            });

            // Observer les lignes du tableau
            document.querySelectorAll('.table-style1 tbody tr').forEach(row => {
                observer.observe(row);
            });

            // Gestion du redimensionnement de la fenêtre
            window.addEventListener('resize', function () {
                // Ajuster la barre de résumé si nécessaire
                const summaryBar = document.getElementById('summary-bar1');
                if (window.innerWidth <= 768) {
                    summaryBar.style.left = '0';
                } else {
                    summaryBar.style.left = '250px';
                }
            });

            // Initialiser la position de la barre de résumé
            window.dispatchEvent(new Event('resize'));
        });

        // Fonction utilitaire pour le debouncing
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Gestion du défilement pour des effets visuels
        window.addEventListener('scroll', debounce(function () {
            const scrollY = window.scrollY;
            const summaryBar = document.getElementById('summary-bar1');

            if (scrollY > 100) {
                summaryBar.style.boxShadow = 'var(--shadow-xl)';
            } else {
                summaryBar.style.boxShadow = 'var(--shadow-lg)';
            }
        }, 10));
    </script>

    <!-- Libraries de votre projet -->
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>

</body>

</html>
<?php
$conn->close();
?>