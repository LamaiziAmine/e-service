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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 20px 60px rgba(0,0,0,0.15);
            --border-radius: 20px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        

        .page-flex {
            background: transparent;
        }

        .main-wrapper {
            background: transparent;
        }

        .container-report {
            padding: 40px 25px;
            background: transparent;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .dashboard-header h2 {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 25px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            color: #1a202c;
            font-weight: 700;
            font-size: 2.2rem;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header h2::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .dashboard-header h2 i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 15px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            height: 100%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card h5 {
            color: #1a202c;
            font-weight: 700;
            font-size: 1.3rem;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-card h5 i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.4rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
            border-radius: 8px;
            margin: 0 -15px;
            padding-left: 15px;
            padding-right: 15px;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: translateX(5px);
        }

        .stat-item-label {
            font-weight: 500;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-item-label i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 20px;
            background: var(--primary-gradient);
            color: white;
            min-width: 60px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-value.danger {
            background: var(--danger-gradient);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
        }

        .stat-value.success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .stat-value.warning {
            background: var(--warning-gradient);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--secondary-gradient);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .chart-container:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }

        .chart-container:hover::before {
            transform: scaleX(1);
        }

        .chart-container h5 {
            color: #1a202c;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-container h5 i {
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.4rem;
        }

        #topUEChart {
            height: 350px !important;
            width: 100% !important;
        }

        .stats-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .container-report {
                padding: 20px 15px;
            }
            
            .dashboard-header h2 {
                font-size: 1.8rem;
                padding: 20px 25px;
            }
            
            .stat-card, .chart-container {
                padding: 20px;
            }
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

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .stat-card {
            animation: slideInLeft 0.6s ease-out;
        }

        .chart-container {
            animation: slideInRight 0.6s ease-out;
        }

        .dashboard-header {
            animation: slideInUp 0.6s ease-out;
        }

        .pulse-effect {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }

        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .metric-card {
            position: relative;
            overflow: hidden;
        }

        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0.3));
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
    </style>
</head>
<body>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?><br>

        <main class="main users" id="skip-target">
            <div class="container-report">
                <div class="dashboard-header">
                    <h2>
                        <i class="fas fa-chart-line"></i>
                        Dashboard de Reporting
                    </h2>
                </div>

                <div class="stats-grid">
                    <div class="stat-card metric-card">
                        <h5>
                            <i class="fas fa-info-circle"></i>
                            Statistiques Générales
                        </h5>
                        
                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-user-tie"></i>
                                Professeurs Permanents
                            </div>
                            <div class="stat-value success"><?= $nb_profs ?></div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-user-clock"></i>
                                Enseignants Vacataires
                            </div>
                            <div class="stat-value warning"><?= $nb_vacataires ?></div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-book-open"></i>
                                Unités d'Enseignement (UE)
                            </div>
                            <div class="stat-value"><?= $nb_ue ?></div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-tasks"></i>
                                Affectations Totales
                            </div>
                            <div class="stat-value"><?= $nb_affectations ?></div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-clock"></i>
                                Moyenne Heures/Professeur
                            </div>
                            <div class="stat-value success"><?= $moyenne_heures ?> h</div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-item-label">
                                <i class="fas fa-exclamation-triangle"></i>
                                Professeurs Sous-chargés (&lt;96h)
                            </div>
                            <div class="stat-value danger pulse-effect"><?= $nb_souscharges ?></div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h5>
                            <i class="fas fa-trophy"></i>
                            Top 3 des UEs les plus demandées
                        </h5>
                        <div style="position: relative; height: 350px;">
                            <canvas id="topUEChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('topUEChart').getContext('2d');
    
    // Configuration du dégradé pour le graphique
    const gradient1 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient1.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
    gradient1.addColorStop(1, 'rgba(102, 126, 234, 0.1)');
    
    const gradient2 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient2.addColorStop(0, 'rgba(240, 147, 251, 0.8)');
    gradient2.addColorStop(1, 'rgba(240, 147, 251, 0.1)');
    
    const gradient3 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient3.addColorStop(0, 'rgba(79, 172, 254, 0.8)');
    gradient3.addColorStop(1, 'rgba(79, 172, 254, 0.1)');

    const data = {
        labels: <?= json_encode($ue_labels) ?>,
        datasets: [{
            label: 'Nombre d\'affectations',
            data: <?= json_encode($ue_counts) ?>,
            backgroundColor: [gradient1, gradient2, gradient3],
            borderColor: [
                'rgba(102, 126, 234, 1)',
                'rgba(240, 147, 251, 1)',
                'rgba(79, 172, 254, 1)'
            ],
            borderWidth: 3,
            borderRadius: 10,
            borderSkipped: false,
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: false 
                },
                title: { 
                    display: true, 
                    text: 'UEs avec le plus d\'interventions affectées',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#1a202c',
                    padding: 20
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        precision: 0,
                        color: '#64748b',
                        font: {
                            weight: '500'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        lineWidth: 1
                    }
                },
                x: {
                    ticks: {
                        color: '#64748b',
                        font: {
                            weight: '600'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutBounce'
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    new Chart(ctx, config);

    // Animation pour les statistiques
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach((stat, index) => {
        const finalValue = parseInt(stat.textContent);
        if (!isNaN(finalValue)) {
            let currentValue = 0;
            const increment = finalValue / 50;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    stat.textContent = finalValue + (stat.textContent.includes('h') ? ' h' : '');
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(currentValue) + (stat.textContent.includes('h') ? ' h' : '');
                }
            }, 40);
        }
    });
});
</script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>