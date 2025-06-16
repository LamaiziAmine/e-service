<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: ../login.php");
    exit; 
}

$professeur_id = $_SESSION['user_id'];
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Requête 1: Obtenir les années universitaires distinctes ---
$years_sql = "SELECT DISTINCT annee_univ FROM unités_ensignement WHERE annee_univ IS NOT NULL AND annee_univ != '' ORDER BY annee_univ DESC";
$years_result = $conn->query($years_sql);
$annees_disponibles = [];
if ($years_result->num_rows > 0) {
    while($row = $years_result->fetch_assoc()) {
        $annees_disponibles[] = $row;
    }
}

// --- Vérifier si une année a été sélectionnée ---
$selected_year = isset($_GET['annee']) ? $_GET['annee'] : null;
$modules = [];

// --- Requête 2: Si une année est sélectionnée, obtenir les modules correspondants ---
if ($selected_year) {
    $modules_sql = "SELECT
                        ue.code_module,
                        ue.intitule_module,
                        ue.semestre,
                        ue.filiere,
                        ue.V_h_cours,
                        ue.V_h_TD,
                        ue.V_h_TP,
                        ue.V_h_Autre,
                        ue.V_h_Evaluation,
                        GROUP_CONCAT(DISTINCT ti.type ORDER BY ti.id SEPARATOR ' / ') AS interventions_groupees
                    FROM
                        affectations a
                    JOIN
                        unités_ensignement ue ON a.id_ue = ue.id
                    LEFT JOIN
                        types_intervention ti ON a.id_type = ti.id
                    WHERE
                        a.id_user = ? AND ue.annee_univ = ?
                    GROUP BY
                        ue.id, ue.code_module, ue.intitule_module, ue.semestre, ue.filiere
                    ORDER BY ue.semestre, ue.code_module";

    $stmt = $conn->prepare($modules_sql);
    $stmt->bind_param("is", $professeur_id, $selected_year);
    $stmt->execute();
    $modules_result = $stmt->get_result();
    
    if ($modules_result) {
        $modules = $modules_result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Modules Assurés</title>
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary-color: #0d9488; /* MODIFIÉ: Thème couleur cyan/teal */
            --primary-dark: #0f766e;
            --primary-light: #99f6e4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-800: #1f2937;
        }

        /* --- RESET & GLOBAL --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* --- STRUCTURE DE LA PAGE --- */
        .page-container {
            min-height: 100vh;
            background: var(--gray-50);
            position: relative;
            overflow: hidden;
        }
        .page-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 250px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            z-index: 0;
        }
        .content-wrapper {
            position: relative;
            z-index: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* --- EN-TÊTE --- */
        .header-section {
            margin-bottom: 2rem;
            padding-top: 2rem;
            text-align: center;
        }
        .main-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 2rem;
        }
        
        /* --- CARTE & TABLEAU --- */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.07);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            display: flex; align-items: center; gap: 0.75rem;
            margin-bottom: 0.25rem;
        }
        .card-title i {
            font-size: 1.6rem;
            color: var(--primary-color);
        }
        .card-subtitle {
            color: var(--gray-500);
            font-size: 1rem;
            font-weight: 400;
        }
        .table-container { overflow-x: auto; }
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .modern-table thead th {
            background: var(--gray-50);
            color: var(--gray-600);
            padding: 1rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.8rem;
            border-bottom: 2px solid var(--gray-200);
            text-align: left;
        }
        .modern-table tbody td {
            padding: 1rem 1.5rem;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
            transition: background-color 0.2s ease;
        }
        .modern-table tbody tr:last-child td { border-bottom: none; }
        .modern-table tbody tr:hover { background-color: var(--gray-50); }
        .module-title { font-weight: 600; color: var(--gray-800); }
        
        /* --- COMPOSANTS --- */
        .badge {
            display: inline-flex; align-items: center;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-primary { background-color: #cffafe; color: #155e75; } /* Cyan */
        .badge-success { background-color: #dcfce7; color: #166534; } /* Green */

        /* --- FORMULAIRE DE FILTRE --- */
        .history-form-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            border: 1px solid var(--gray-200);
        }
        .history-form {
            display: flex;
            align-items: center;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .history-form label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .history-form select {
            padding: 0.7rem 1.1rem;
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            font-size: 1rem;
            min-width: 250px;
            background-color: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .history-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
        }

        /* --- ÉTAT VIDE --- */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: var(--gray-400);
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .content-wrapper { padding: 1rem; }
            .main-title { font-size: 2rem; }
            .subtitle { font-size: 1rem; }
            .card-header, .modern-table thead th, .modern-table tbody td, .history-form-container { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    
    <div class="page-flex">
        <?php include "sidebar_prof.php"; ?>
        
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php"; ?>
            
            <main class="page-container">
                <div class="content-wrapper">
                    <header class="header-section">
                        <h1 class="main-title">Historique des Services</h1>
                        <p class="subtitle">Consultez les modules que vous avez assurés les années précédentes.</p>
                    </header>
                    
                    <!-- Formulaire de sélection d'année -->
                    <section class="history-form-container">
                        <form action="historique_page.php" method="GET" class="history-form">
                            <label for="annee"><i class='bx bx-calendar-event'></i> Année Universitaire :</label>
                            <select name="annee" id="annee" onchange="this.form.submit()">
                                <option value="">-- Veuillez sélectionner une année --</option>
                                <?php foreach ($annees_disponibles as $annee): ?>
                                    <option value="<?= htmlspecialchars($annee['annee_univ']) ?>" <?= ($selected_year == $annee['annee_univ']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($annee['annee_univ']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </section>

                    <!-- Affichage des résultats -->
                    <div id="skip-target">
                    <?php if ($selected_year): ?>
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">
                                    <i class='bx bx-archive'></i>
                                    Modules Assurés en <?= htmlspecialchars($selected_year) ?>
                                </h2>
                                <p class="card-subtitle">Détail des enseignements pour l'année sélectionnée.</p>
                            </header>
                            
                            <div class="table-container">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Code Module</th>
                                            <th>Intitulé</th>
                                            <th>Semestre</th>
                                            <th>Filière</th>
                                            <th>Interventions</th>
                                            <th>Vol. Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($modules)): ?>
                                            <?php foreach ($modules as $module): ?>
                                                <?php $vh_total = $module['V_h_cours'] + $module['V_h_TD'] + $module['V_h_TP'] + $module['V_h_Autre'] + $module['V_h_Evaluation']; ?>
                                                <tr>
                                                    <td><span class="badge badge-primary"><?= htmlspecialchars($module['code_module']) ?></span></td>
                                                    <td><div class="module-title"><?= htmlspecialchars($module['intitule_module']) ?></div></td>
                                                    <td><span class="badge badge-success">S<?= htmlspecialchars($module['semestre']) ?></span></td>
                                                    <td><strong><?= htmlspecialchars($module['filiere']) ?></strong></td>
                                                    <td><strong><?= htmlspecialchars($module['interventions_groupees'] ?: 'N/A') ?></strong></td>
                                                    <td><?= htmlspecialchars($vh_total) ?>h</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6">
                                                    <div class="empty-state">
                                                        <i class='bx bx-search-alt'></i>
                                                        <h3>Aucun résultat</h3>
                                                        <p>Aucun module ne vous a été affecté pour l'année <strong><?= htmlspecialchars($selected_year) ?></strong>.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php else: ?>
                        <div class="card">
                           <div class="empty-state">
                                <i class='bx bx-calendar-week'></i>
                                <h3>Consulter votre historique</h3>
                                <p>Pour commencer, veuillez sélectionner une année universitaire dans le menu ci-dessus.</p>
                           </div>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</body>
</html>