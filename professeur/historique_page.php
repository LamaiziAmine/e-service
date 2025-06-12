<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: ../login.php");
    exit; 
}

$professeur_id = $_SESSION['user_id'];
$_SESSION['role'] = 'professeur';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$professeur_id = $_SESSION['user_id'];

// --- Query 1: Get all distinct academic years to populate the dropdown ---
$years_sql = "SELECT DISTINCT annee_univ FROM unités_ensignement ORDER BY annee_univ DESC";
$years_result = $conn->query($years_sql);
$annees_disponibles = [];
if ($years_result->num_rows > 0) {
    while($row = $years_result->fetch_assoc()) {
        $annees_disponibles[] = $row;
    }
}

// --- Check if a year has been selected from the form ---
$selected_year = isset($_GET['annee']) ? $_GET['annee'] : null;
$modules = [];

// --- Query 2: If a year is selected, get the modules for that year ---
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
                        GROUP_CONCAT(ti.type ORDER BY ti.id SEPARATOR ' / ') AS interventions_groupees
                    FROM
                        affectations a
                    JOIN
                        unités_ensignement ue ON a.id_ue = ue.id
                    LEFT JOIN
                        types_intervention ti ON a.id_type = ti.id
                    WHERE
                        a.id_user = ? AND ue.annee_univ = ?
                    GROUP BY
                        ue.id";

    $stmt = $conn->prepare($modules_sql);
    $stmt->bind_param("is", $professeur_id, $selected_year);
    $stmt->execute();
    $modules_result = $stmt->get_result();
    
    if ($modules_result->num_rows > 0) {
        while($row = $modules_result->fetch_assoc()) {
            $modules[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Modules Assurés</title>
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Modern Professional Style -->
    <style>
      body { font-family: 'Roboto', sans-serif; background-color: #f4f7fc; }
      .main-title { color: #2c3e50; text-align: left; margin-bottom: 25px; margin-left: 2.5%; font-weight: 700; }
      .table-style { width: 95%; margin: auto; border-collapse: collapse; text-align: left; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); overflow: hidden; }
      .table-style thead th { background-color: #f8f9fa; color: #34495e; padding: 16px 20px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e3e6f0; }
      .table-style tbody td { padding: 16px 20px; color: #5a6a7e; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
      .table-style tbody tr:last-child td { border-bottom: none; }
      .table-style tbody tr:hover { background-color: #f8f9fc; }
      .intervention-pill { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; background-color: #e7f3ff; color: #007bff; }
      .history-form { width: 95%; margin: 0 auto 30px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
      .history-form label { font-weight: 700; color: #34495e; }
      .history-form select { padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 16px; flex-grow: 1; max-width: 300px; }
      .info-box { text-align: center; padding: 40px; margin: 20px auto; background-color: #fff; border-radius: 8px; width: 95%; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    <div class="page-flex">
        <?php include "sidebar_prof.php"; ?>
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php"; ?><br>
            <main class="main users" id="skip-target">
                <div class="container">
                    <h2 class="main-title">Historique des Modules Assurés</h2>

                    <!-- Year Selection Form -->
                    <form action="historique_page.php" method="GET" class="history-form">
                        <label for="annee">Choisir une année académique :</label>
                        <select name="annee" id="annee" onchange="this.form.submit()">
                            <option value="">-- Veuillez sélectionner --</option>
                            <?php foreach ($annees_disponibles as $annee): ?>
                                <option value="<?= htmlspecialchars($annee['annee_univ']) ?>" <?= ($selected_year == $annee['annee_univ']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($annee['annee_univ']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- Results Display Area -->
                    <?php if ($selected_year): ?>
                        <?php if (!empty($modules)): ?>
                            <table class="table-style">
                                <thead>
                                    <tr>
                                        <th>Code Module</th>
                                        <th>Intitulé</th>
                                        <th>Semestre</th>
                                        <th>Filière</th>
                                        <th>Type(s) d'Intervention</th>
                                        <th>Volume Horaire Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $module): ?>
                                        <?php $vh_total = $module['V_h_cours'] + $module['V_h_TD'] + $module['V_h_TP'] + $module['V_h_Autre'] + $module['V_h_Evaluation']; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($module['code_module']) ?></td>
                                            <td><?= htmlspecialchars($module['intitule_module']) ?></td>
                                            <td><?= htmlspecialchars($module['semestre']) ?></td>
                                            <td><?= htmlspecialchars($module['filiere']) ?></td>
                                            <td>
                                                <span class="intervention-pill">
                                                    <?= htmlspecialchars($module['interventions_groupees']) ?>
                                                </span>
                                            </td>
                                            <td><strong><?= htmlspecialchars($vh_total) ?> heures</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="info-box">
                                <p>Aucun module ne vous a été affecté pour l'année académique <strong><?= htmlspecialchars($selected_year) ?></strong>.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="info-box">
                            <p>Veuillez sélectionner une année académique dans le menu ci-dessus pour consulter l'historique.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</html>