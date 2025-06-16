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
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$department_id = $_SESSION['user_department'] ?? null;
if (!$department_id) {
    die("Erreur : département non défini pour l'utilisateur.");
}

// --- Requête principale : charge horaire totale par professeur ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$sql = "
SELECT 
    u.id, u.nom, u.prenom,
    COALESCE(SUM(
        ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation
    ), 0) AS total_heures
FROM users u
LEFT JOIN affectations a ON u.id = a.id_user
LEFT JOIN unités_ensignement ue ON a.id_ue = ue.id
WHERE u.role = 'professeur' AND u.department_id = ?
GROUP BY u.id, u.nom, u.prenom
ORDER BY 
    (COALESCE(SUM(ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation), 0) < 96) DESC,
    COALESCE(SUM(ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation), 0) ASC
";

$stmt = $connection->prepare($sql);
$stmt->bind_param('i', $department_id);
$stmt->execute();
$result = $stmt->get_result();

// --- Requête secondaire : unités par professeur ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$units_sql = "
SELECT 
    a.id_user,
    ue.code_module,
    ue.intitule_module,
    (COALESCE(ue.V_h_cours, 0) + COALESCE(ue.V_h_TD, 0) + COALESCE(ue.V_h_TP, 0) + COALESCE(ue.V_h_Autre, 0) + COALESCE(ue.V_h_Evaluation, 0)) AS total_volume_hours
FROM affectations a
JOIN unités_ensignement ue ON a.id_ue = ue.id
JOIN users u ON a.id_user = u.id
WHERE u.department_id = ?
";

$stmt_units = $connection->prepare($units_sql);
$stmt_units->bind_param('i', $department_id);
$stmt_units->execute();
$units_result = $stmt_units->get_result();

$prof_units = [];
while ($row = $units_result->fetch_assoc()) {
    $prof_units[$row['id_user']][] = $row;
}
$connection->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Charge Horaire des Professeurs</title>
    <!-- Utilisons les styles de votre template -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
<style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #1e293b;
            --white: #ffffff;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-card: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
            --border-radius: 16px;
            --border-radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        

        .page-container {
            min-height: 100vh;
            padding: 2rem;
        }

        .main-card {
            background: var(--gradient-card);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 3rem;
            margin: 0 auto;
            max-width: 1400px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--info));
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
        }

        /* Header Styles */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            line-height: 1.2;
        }

        .page-title i {
            color: var(--primary);
            background: none;
            -webkit-text-fill-color: var(--primary);
        }

        .page-subtitle {
            color: var(--secondary);
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        /* Info Alert */
        .info-alert {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(59, 130, 246, 0.04));
            border: 2px solid rgba(6, 182, 212, 0.15);
            border-radius: var(--border-radius);
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #0891b2;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .info-alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--info);
        }

        .info-alert i {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--secondary);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .stat-card.danger .stat-value { color: var(--danger); }
        .stat-card.success .stat-value { color: var(--success); }
        .stat-card.primary .stat-value { color: var(--primary); }

        /* Table Container */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        /* Table Styles */
        .modern-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .modern-table thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            position: relative;
        }

        .modern-table thead::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        .modern-table th {
            color: var(--white);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1.5rem 2rem;
            text-align: left;
            border: none;
            white-space: nowrap;
        }

        .modern-table th i {
            margin-right: 0.75rem;
            opacity: 0.9;
        }

        .modern-table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid #e2e8f0;
            background: var(--white);
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(248, 250, 252, 0.8));
            transform: scale(1.001);
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table td {
            padding: 1.5rem 2rem;
            border: none;
            vertical-align: middle;
        }

        /* Row Status Colors */
        .row-danger {
            border-left: 4px solid var(--danger) !important;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.03), rgba(254, 242, 242, 0.8)) !important;
        }

        .row-danger td {
            background: transparent !important;
        }

        .row-success {
            border-left: 4px solid var(--success) !important;
        }

        /* Professor Name */
        .professor-name {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .professor-name::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Hours Display */
        .hours-display {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hours-display i {
            color: var(--primary);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid;
            transition: var(--transition);
        }

        .badge-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: var(--danger);
            border-color: #fecaca;
        }

        .badge-success {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            color: var(--success);
            border-color: #bbf7d0;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        /* Action Button */
        .action-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            background: linear-gradient(135deg, var(--primary-dark), #3730a3);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        /* Units Details Row */
        .units-row {
            display: none;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
            border-top: 2px solid #e2e8f0 !important;
        }

        .units-row td {
            background: transparent !important;
            padding: 2.5rem !important;
        }

        .units-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
        }

        .unit-item {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.04), rgba(248, 250, 252, 0.8));
            border: 2px solid rgba(99, 102, 241, 0.1);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .unit-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
            opacity: 0;
            transition: var(--transition);
        }

        .unit-item:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .unit-item:hover::before {
            opacity: 1;
        }

        .unit-info {
            flex: 1;
        }

        .unit-code {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .unit-title {
            color: var(--dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .unit-hours {
            background: var(--primary);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .no-units {
            text-align: center;
            color: var(--secondary);
            font-style: italic;
            padding: 3rem;
            position: relative;
        }

        .no-units::before {
            content: '\f5c3';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 3rem;
            opacity: 0.2;
            display: block;
            margin-bottom: 1rem;
            font-style: normal;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-card {
                padding: 2rem;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }
            
            .main-card {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .unit-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .unit-hours {
                align-self: flex-start;
            }
        }

        @media (max-width: 576px) {
            .modern-table th,
            .modern-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .professor-name {
                font-size: 0.9rem;
            }
            
            .hours-display {
                font-size: 1rem;
            }
            
            .action-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .main-card {
            animation: fadeInUp 0.8s ease-out;
        }

        .stat-card {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .modern-table tbody tr:not(.units-row) {
            animation: slideIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        .modern-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .modern-table tbody tr:nth-child(3) { animation-delay: 0.15s; }
        .modern-table tbody tr:nth-child(5) { animation-delay: 0.2s; }
        .modern-table tbody tr:nth-child(7) { animation-delay: 0.25s; }
        .modern-table tbody tr:nth-child(9) { animation-delay: 0.3s; }
    </style>
    <script>
        function toggleUnits(id) {
            const row = document.getElementById('units-' + id);
            const btn = event.target.closest('button');
            
            if (row) {
                if (row.style.display === 'table-row') {
                    row.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-eye"></i> Voir / Cacher';
                } else {
                    row.style.display = 'table-row';
                    btn.innerHTML = '<i class="fas fa-eye-slash"></i> Masquer';
                }
            }
        }
    </script>
</head>
<body>
    <div class="page-flex">
        <?php 
        // Assurez-vous que ce chemin est correct. Si ce fichier est dans /chefdepart/Professeur/
        // et la sidebar dans /chefdepart/, le chemin est ../sidebar.php
        include "../sidebar.php"; 
        ?>
        <div class="main-wrapper">
            <?php include "../navbar.php"; ?><br>
            <main class="main users" id="skip-target">
                <div class="page-container">
        <div class="main-card">
            <!-- Header Section -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clock"></i>
                    Charge Horaire des Professeurs
                </h1>
                <p class="page-subtitle">
                    Gestion et suivi des charges horaires d'enseignement
                </p>
            </div>

            <!-- Info Alert -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Référence :</strong> La charge horaire de référence est de 96 heures. 
                    Les professeurs en sous-charge sont prioritaires dans l'affichage.
                </div>
            </div>

            <?php
            // Calculer les statistiques
            $total_profs = 0;
            $sous_charge = 0;
            $charge_correcte = 0;
            $total_heures = 0;

            $result->data_seek(0); // Remettre le curseur au début
            while ($row = $result->fetch_assoc()) {
                $total_profs++;
                $charge = (float)$row['total_heures'];
                $total_heures += $charge;
                
                if ($charge < 96) {
                    $sous_charge++;
                } else {
                    $charge_correcte++;
                }
            }
            $result->data_seek(0); // Remettre le curseur au début pour l'affichage du tableau
            ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value"><?= $total_profs ?></div>
                    <div class="stat-label">Total Professeurs</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?= $sous_charge ?></div>
                    <div class="stat-label">Sous-chargés</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?= $charge_correcte ?></div>
                    <div class="stat-label">Charge Correcte</div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Professeur</th>
                            <th><i class="fas fa-clock"></i> Total Heures</th>
                            <th><i class="fas fa-chart-line"></i> Statut</th>
                            <th><i class="fas fa-eye"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $id = $row['id'];
                                $charge = (float)$row['total_heures'];
                                $isUnder = $charge < 96;
                            ?>
                                <tr class="<?= $isUnder ? 'row-danger' : '' ?>">
                                    <td>
                                        <div class="professor-name">
                                            <?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hours-display">
                                            <i class="fas fa-clock"></i>
                                            <?= $charge ?> h
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $isUnder ? 'badge-danger' : 'badge-success' ?>">
                                            <i class="fas <?= $isUnder ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                                            <?= $isUnder ? 'Sous-chargé' : 'Charge Correcte' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="toggleUnits(<?= $id ?>)">
                                            <i class="fas fa-eye"></i>
                                            <span class="btn-text">Détails</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="units-row" id="units-<?= $id ?>">
                                    <td colspan="4">
                                        <?php if (!empty($prof_units[$id])): ?>
                                            <ul class="units-list">
                                                <?php foreach ($prof_units[$id] as $u): ?>
                                                    <li class="unit-item">
                                                        <div class="unit-info">
                                                            <div class="unit-code"><?= htmlspecialchars($u['code_module']) ?></div>
                                                            <div class="unit-title"><?= htmlspecialchars($u['intitule_module']) ?></div>
                                                        </div>
                                                        <div class="unit-hours"><?= $u['total_volume_hours'] ?> h</div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <div class="no-units">
                                                Aucune unité d'enseignement n'a encore été affectée à ce professeur.
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <div>Aucun professeur trouvé dans ce département.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
            </main>
        </div>
    </div>

<script>
    function toggleUnits(id) {
        const row = document.getElementById('units-' + id);
        if (row) {
            row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
        }
    }
</script>

<script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>
</body>
</html>