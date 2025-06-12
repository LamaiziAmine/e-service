<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_department'])) {
    die("Département non spécifié.");
}

$department_id = $_SESSION['user_department'];

// Count professeurs in the department
$nb_profs = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'professeur' AND department_id = $department_id")->fetch_assoc()['total'];

// Count vacataires in the department
$nb_vacataires = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'vacataire' AND department_id = $department_id")->fetch_assoc()['total'];

// Count units in the department
$nb_ue = $connection->query("SELECT COUNT(*) AS total FROM unités_enseignement WHERE department_id = $department_id")->fetch_assoc()['total'];

// Count affectations in the department
$nb_affectations = $connection->query("SELECT COUNT(*) AS total FROM affectations a JOIN unités_enseignement u ON a.id_ue = u.id WHERE u.department_id = $department_id")->fetch_assoc()['total'];

// Average total volume hours per professeur
$sql_moyenne = "
SELECT AVG(total_heures) AS moyenne FROM (
    SELECT 
      a.id_user AS teacher_id,
      SUM(u.V_h_cours + u.V_h_TD + u.V_h_TP + u.V_h_Autre + u.V_h_Evaluation) AS total_heures
    FROM affectations a
    JOIN unités_enseignement u ON a.id_ue = u.id
    JOIN users us ON a.id_user = us.id
    WHERE u.department_id = $department_id AND us.role = 'professeur'
    GROUP BY a.id_user
) t
";

$moyenne_heures = round($connection->query($sql_moyenne)->fetch_assoc()['moyenne'] ?? 0, 2);

// Number of professeurs with less than 96h
$sql_souscharges = "
SELECT COUNT(*) AS total FROM (
    SELECT 
      a.id_user AS teacher_id,
      SUM(u.V_h_cours + u.V_h_TD + u.V_h_TP + u.V_h_Autre + u.V_h_Evaluation) AS total_heures
    FROM affectations a
    JOIN unités_enseignement u ON a.id_ue = u.id
    JOIN users us ON a.id_user = us.id
    WHERE u.department_id = $department_id AND us.role = 'professeur'
    GROUP BY a.id_user
    HAVING total_heures < 96
) t
";
$nb_souscharges = $connection->query($sql_souscharges)->fetch_assoc()['total'];

// Top 3 most affected units in department
$top_ue_result = $connection->query("
SELECT u.code_module, u.intitule_module, COUNT(*) AS nb
FROM affectations a
JOIN unités_enseignement u ON a.id_ue = u.id
WHERE u.department_id = $department_id
GROUP BY a.id_ue
ORDER BY nb DESC
LIMIT 3
");

$ue_labels = [];
$ue_counts = [];
while ($ue = $top_ue_result->fetch_assoc()) {
    $ue_labels[] = htmlspecialchars($ue['code_module'] . ' - ' . $ue['intitule_module']);
    $ue_counts[] = (int)$ue['nb'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Reporting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
</head>
<body>
<div class="layer"></div>
<div class="page-flex">
    <?php include "../sidebar.php" ?>
    <div class="main-wrapper">
        <?php include "../navbar.php" ?><br>
        <div class="container py-5">
            <h2 class="text-center text-primary"><i class="bi bi-graph-up-arrow"></i> Reporting du Département</h2><br>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="bi bi-info-circle icon"></i> Statistiques générales</h5>
                        <ul class="list-group">
                            <li class="list-group-item">👨‍🏫 Professeurs : <strong><?= $nb_profs ?></strong></li>
                            <li class="list-group-item">🧑‍💼 Vacataires : <strong><?= $nb_vacataires ?></strong></li>
                            <li class="list-group-item">📚 UE : <strong><?= $nb_ue ?></strong></li>
                            <li class="list-group-item">📋 Affectations : <strong><?= $nb_affectations ?></strong></li>
                            <li class="list-group-item">⏱ Moyenne heures/professeur : <strong><?= $moyenne_heures ?> h</strong></li>
                            <li class="list-group-item text-danger">⚠ Professeurs sous-chargés (<96h) : <strong><?= $nb_souscharges ?></strong></li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="bi bi-star-fill icon"></i> Top 3 UE les plus affectées</h5>
                        <canvas id="topUEChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('topUEChart').getContext('2d');
const data = {
    labels: <?= json_encode($ue_labels) ?>,
    datasets: [{
        label: 'Nombre d\'affectations',
        data: <?= json_encode($ue_counts) ?>,
        backgroundColor: ['#0d6efd', '#198754', '#dc3545'],
        borderWidth: 1
    }]
};
const config = {
    type: 'bar',
    data: data,
    options: {
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        },
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        }
    }
};
new Chart(ctx, config);
</script>
<script src="/elegant/plugins/chart.min.js"></script>
<script src="/elegant/plugins/feather.min.js"></script>
<script src="/elegant/js/script.js"></script>
</body>
</html>
