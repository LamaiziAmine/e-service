<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VÉRIFICATION DE SÉCURITÉ : Doit être un professeur connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: /e-service/login.php");
    exit;
}

// 3. RÉCUPÉRATION DES INFORMATIONS UTILES DE LA SESSION
// On utilise directement l'ID de l'utilisateur connecté. C'est plus sûr.
$professeur_id = $_SESSION['user_id'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: ../login.php");
    exit;
}


// ===================================================================
// *** MODIFICATION 1: THE NEW SQL QUERY WITH GROUP_CONCAT ***
// ===================================================================
// We group results by module ID and use GROUP_CONCAT to merge the intervention types.
$sql = "SELECT
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
            a.id_user = ?
        GROUP BY
            ue.id"; // Group all results for the same module into one row

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Modules Assurés</title>
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    
    <!-- Using the Modern Professional Style for a clean look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Roboto', sans-serif; background-color: #f4f7fc; }
      .main-title { color: #2c3e50; text-align: left; margin-bottom: 25px; margin-left: 2.5%; font-weight: 700; }
      .table-style { width: 95%; margin: auto; border-collapse: collapse; text-align: left; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); overflow: hidden; }
      .table-style thead th { background-color:rgb(25, 60, 255); color:rgb(255, 255, 255); padding: 16px 20px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e3e6f0; }
      .table-style tbody td { padding: 16px 20px; color: #5a6a7e; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
      .table-style tbody tr:last-child td { border-bottom: none; }
      .table-style tbody tr:hover { background-color: #f8f9fc; }
      .intervention-pill { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; background-color: #e7f3ff; color: #007bff; }
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
                    <h2 class="main-title">Liste de mes modules assurés</h2>
                    <table class="table-style">
                        <!-- Simplified and cleaner header -->
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
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                // ===================================================================
                                // *** MODIFICATION 2: SIMPLIFIED PHP LOOP ***
                                // ===================================================================
                                while ($row = $result->fetch_assoc()):
                                    // The total volume calculation remains the same for the entire module
                                    $vh_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['code_module']) ?></td>
                                        <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                        <td><?= htmlspecialchars($row['semestre']) ?></td>
                                        <td><?= htmlspecialchars($row['filiere']) ?></td>
                                        <!-- We now simply echo the grouped result from the database -->
                                        <td>
                                            <span class="intervention-pill">
                                                <?= htmlspecialchars($row['interventions_groupees']) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($vh_total) ?> heures</strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style='text-align:center; padding: 40px;'>Aucun module ne vous est actuellement affecté.</td></tr>
                            <?php endif; ?>
                            <?php
                            $stmt->close();
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</body>
</html>