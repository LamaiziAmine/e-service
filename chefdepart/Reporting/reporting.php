<?php
session_start();

// --- Connexion à la base de données ---
$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';
$connection = new mysqli($host, $user, $pass, $db);
if ($connection->connect_error) {
    die("Erreur de connexion : " . $connection->connect_error);
}

// --- Sécurité et Initialisation ---
if (!isset($_SESSION['user_id'], $_SESSION['user_department'])) {
    die("Vous devez être connecté en tant que chef de département pour voir cette page.");
}
$department_id = $_SESSION['user_department'];


// --- Requêtes pour les Statistiques (corrigées et sécurisées) ---
function getSingleValue($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $value = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    return $value;
}
$nb_profs = getSingleValue($connection, "SELECT COUNT(*) AS total FROM users WHERE role = 'professeur' AND department_id = ?", [$department_id], 'i');
$nb_vacataires = getSingleValue($connection, "SELECT COUNT(*) AS total FROM users WHERE role = 'vacataire' AND department_id = ?", [$department_id], 'i');
$nb_ue = getSingleValue($connection, "SELECT COUNT(*) AS total FROM unités_ensignement WHERE department_id = ?", [$department_id], 'i');
$nb_affectations = getSingleValue($connection, "SELECT COUNT(*) AS total FROM affectations a JOIN unités_ensignement ue ON a.id_ue = ue.id WHERE ue.department_id = ?", [$department_id], 'i');
$sql_moyenne = "SELECT AVG(total_heures) AS total FROM (SELECT SUM(ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation) AS total_heures FROM affectations a JOIN unités_ensignement ue ON a.id_ue = ue.id JOIN users us ON a.id_user = us.id WHERE ue.department_id = ? AND us.role = 'professeur' GROUP BY a.id_user) t";
$moyenne_heures = round(getSingleValue($connection, $sql_moyenne, [$department_id], 'i'), 2);
$sql_souscharges = "SELECT COUNT(*) AS total FROM (SELECT a.id_user FROM affectations a JOIN unités_ensignement ue ON a.id_ue = ue.id JOIN users us ON a.id_user = us.id WHERE ue.department_id = ? AND us.role = 'professeur' GROUP BY a.id_user HAVING SUM(ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation) < 96) t";
$nb_souscharges = getSingleValue($connection, $sql_souscharges, [$department_id], 'i');
$sql_top_ue = "SELECT ue.code_module, ue.intitule_module, COUNT(a.id) AS nb FROM affectations a JOIN unités_ensignement ue ON a.id_ue = ue.id WHERE ue.department_id = ? GROUP BY ue.id, ue.code_module, ue.intitule_module ORDER BY nb DESC LIMIT 3";
$stmt_top_ue = $connection->prepare($sql_top_ue);
$stmt_top_ue->bind_param('i', $department_id);
$stmt_top_ue->execute();
$top_ue_result = $stmt_top_ue->get_result();
$ue_labels = [];
$ue_counts = [];
while ($ue = $top_ue_result->fetch_assoc()) {
    $ue_labels[] = htmlspecialchars($ue['code_module']);
    $ue_counts[] = (int)$ue['nb'];
}
$stmt_top_ue->close();
$connection->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Reporting du Département</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <style>
        .container-report {
            padding: 25px;
            background: #f8f9fa; /* Fond légèrement gris pour le conteneur principal */
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            height: 100%; /* Important pour que les cartes de la même rangée aient la même hauteur */
        }
        .stat-card h5 {
            color: #0d6efd;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .stat-card .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-card .list-group-item:last-child {
            border-bottom: none;
        }
        .text-danger strong { color: #dc3545; font-weight: bold; }
        .row { --bs-gutter-y: 1.5rem; /* Ajoute un espacement vertical entre les lignes sur mobile */ }
    </style>
</head>
<body>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?><br>

        <!-- ========================================================== -->
        <!-- DÉBUT DE LA CORRECTION : Ajout de la balise <main>          -->
        <!-- ========================================================== -->
        <main class="main users" id="skip-target">
            <div class="container-report">
                <h2 class="text-center text-primary mb-4"><i class="bi bi-graph-up-arrow"></i> Reporting du Département</h2>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5><i class="bi bi-info-circle"></i> Statistiques Générales</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Professeurs Permanents : <strong><?= $nb_profs ?></strong></li>
                                <li class="list-group-item">Enseignants Vacataires : <strong><?= $nb_vacataires ?></strong></li>
                                <li class="list-group-item">Unités d'Enseignement (UE) : <strong><?= $nb_ue ?></strong></li>
                                <li class="list-group-item">Affectations Totales (Cours/TD/TP) : <strong><?= $nb_affectations ?></strong></li>
                                <li class="list-group-item">Moyenne Heures/Professeur : <strong><?= $moyenne_heures ?> h</strong></li>
                                <li class="list-group-item text-danger">Professeurs Sous-chargés (<96h) : <strong><?= $nb_souscharges ?></strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="stat-card">
                            <h5><i class="bi bi-bar-chart-line-fill"></i> Top 3 des UEs les plus demandées</h5>
                            <canvas id="topUEChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- ========================================================== -->
        <!-- FIN DE LA CORRECTION                                         -->
        <!-- ========================================================== -->

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('topUEChart').getContext('2d');
    const data = {
        labels: <?= json_encode($ue_labels) ?>,
        datasets: [{
            label: 'Nombre d\'affectations (Cours/TD/TP)',
            data: <?= json_encode($ue_counts) ?>,
            backgroundColor: [
                'rgba(54, 162, 235, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 159, 64, 0.6)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            },
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'UEs avec le plus d\'interventions affectées' }
            }
        }
    };
    new Chart(ctx, config);
});
</script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>