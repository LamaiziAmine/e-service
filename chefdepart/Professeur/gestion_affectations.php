<?php
session_start();

// --- Sécurité et Initialisation ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'chef de departement') {
    header('Location: ../login.php');
    exit;
}
$department_id = $_SESSION['user_department'] ?? die("Erreur : département non défini.");

// --- Connexion à la base de données ---
$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';
$connection = new mysqli($host, $user, $pass, $db);
if ($connection->connect_error) {
    die("Erreur de connexion : " . $connection->connect_error);
}

// --- Récupérer les choix EN ATTENTE ---
$sql_choix = "
    SELECT c.id as choix_id, c.id_professeur, c.id_module, 
           u.nom, u.prenom, 
           ue.code_module, ue.intitule_module
    FROM choix_ues c
    JOIN users u ON c.id_professeur = u.id
    JOIN unités_ensignement ue ON c.id_module = ue.id
    WHERE ue.department_id = ?
    ORDER BY u.nom ASC, ue.code_module ASC";
$stmt_choix = $connection->prepare($sql_choix);
$stmt_choix->bind_param("i", $department_id);
$stmt_choix->execute();
$choix_result = $stmt_choix->get_result();

// --- Statistiques simplifiées ---

// 1. En attente
$stats_pending_sql = "SELECT COUNT(*) as count FROM choix_ues c JOIN unités_ensignement ue ON c.id_module = ue.id WHERE ue.department_id = ?";
$stats_pending_stmt = $connection->prepare($stats_pending_sql);
$stats_pending_stmt->bind_param("i", $department_id);
$stats_pending_stmt->execute();
$stats['pending'] = $stats_pending_stmt->get_result()->fetch_assoc()['count'] ?? 0;

$connection->close();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Affectations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">

    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);

            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --bg-glass: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b8b8d1;
            --text-muted: #7c7c9a;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-glass: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-elevated: 0 20px 40px rgba(0, 0, 0, 0.4);
            --border-radius: 20px;
            --border-radius-sm: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }



        @keyframes bgAnimation {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        .container-gest {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-glass);
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
        }

        .container-gest::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: var(--text-primary);
            font-weight: 800;
            font-size: 2.2rem;
            margin: 0;
            color: #1a1a2e;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-title i {
            background: var(--primary-gradient);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: var(--shadow-elevated);
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--bg-glass);
            backdrop-filter: blur(15px);
            border: 1px solid var(--border-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-glass);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-elevated);
        }

        .stat-card:hover::before {
            transform: translateX(100%);
        }

        .stat-icon {
            font-size: 1.8rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .stat-icon.pending {
            background: var(--warning-gradient);
            animation: rotate 3s linear infinite;
        }

        .stat-icon.validated {
            background: var(--success-gradient);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .stat-details {
            text-align: left;
            flex: 1;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #fff, #e0e0ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .alert {
            border-radius: var(--border-radius-sm);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.2), rgba(0, 242, 254, 0.2));
            color: #00f2fe;
            border-left: 4px solid #00f2fe;
            box-shadow: 0 8px 32px rgba(0, 242, 254, 0.2);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.2), rgba(238, 90, 36, 0.2));
            color: #ff6b6b;
            border-left: 4px solid #ff6b6b;
            box-shadow: 0 8px 32px rgba(255, 107, 107, 0.2);
        }

        .table-container {
            background: var(--bg-glass);
            backdrop-filter: blur(15px);
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-glass);
        }

        .table {
            margin: 0;
            font-size: 0.95rem;
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            color: var(--text-primary);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            padding: 1.5rem;
            border: none;
            position: relative;
        }

        .table thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-gradient);
        }

        .table td {
            padding: 1.5rem;
            vertical-align: middle;
            border-top: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.02);
            transition: background 0.3s ease;
        }

        .table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }

        .teacher-info {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.1rem;
            background: linear-gradient(45deg, #fff, #e0e0ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .module-info {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .module-code {
            font-weight: 700;
            color: #fff;
            background: var(--primary-gradient);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-validate {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-validate:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
        }

        .btn-reject {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }

        .btn-reject:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse 2s ease-in-out infinite;
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

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .container-gest {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.8rem;
                gap: 1rem;
            }

            .page-title i {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .stats-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-action {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="page-flex">
        <?php include "../sidebar.php"; ?>
        <div class="main-wrapper">
            <?php include "../navbar.php"; ?>
            <main class="main-content">
                <div class="container-gest">
                    <header class="page-header">
                        <h1 class="page-title"><i class='bx bxs-user-check'></i> Choix des Enseignants à Traiter</h1>
                    </header>

                    <section class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-icon pending"><i class='bx bx-hourglass'></i></div>
                            <div class="stat-details">
                                <div class="stat-number"><?= $stats['pending'] ?></div>
                                <div class="stat-label">Choix en Attente</div>
                            </div>
                        </div>
                </div>
                </section>

                <?php if (!empty($_SESSION['message'])): ?>
                    <div class="alert <?= str_contains($_SESSION['message'], 'Erreur') ? 'alert-danger' : 'alert-success' ?>"
                        role="alert">
                        <i
                            class='bx <?= str_contains($_SESSION['message'], 'Erreur') ? 'bxs-error-circle' : 'bxs-check-circle' ?>'></i>
                        <span><?= htmlspecialchars($_SESSION['message']) ?></span>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Enseignant</th>
                                <th>Module Souhaité</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($choix_result->num_rows > 0): ?>
                                <?php while ($choix = $choix_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="teacher-info">
                                                <?= htmlspecialchars($choix['nom'] . ' ' . $choix['prenom']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="module-code"><?= htmlspecialchars($choix['code_module']) ?></div>
                                            <div class="module-info"><?= htmlspecialchars($choix['intitule_module']) ?></div>
                                        </td>
                                        <td class="text-end">
                                            <div class="action-buttons">
                                                <a href="traiter_validation.php?choix_id=<?= $choix['choix_id'] ?>&action=valider"
                                                    class="btn-action btn-validate"
                                                    onclick="return confirmAction(event, 'valider')">
                                                    <i class='bx bx-check'></i> Valider
                                                </a>
                                                <a href="traiter_validation.php?choix_id=<?= $choix['choix_id'] ?>&action=refuser"
                                                    class="btn-action btn-reject"
                                                    onclick="return confirmAction(event, 'refuser')">
                                                    <i class='bx bx-x'></i> Refuser
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class='bx bx-check-double'></i>
                                            <h3>Tout est traité !</h3>
                                            <p>Il n'y a actuellement aucun nouveau choix à valider.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>
        </main>
    </div>
    </div>
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
    <script>
        function confirmAction(event, action) {
            const message = action === 'valider' ? 'Voulez-vous VALIDER cette affectation ?' : 'Voulez-vous REFUSER ce choix ?\nCette action est définitive.';
            if (!confirm(message)) { event.preventDefault(); return false; }
            const button = event.currentTarget;
            button.innerHTML = '<span class="loading-spinner"></span>';
            button.disabled = true;
            const otherButton = action === 'valider' ? button.nextElementSibling : button.previousElementSibling;
            if (otherButton) otherButton.style.display = 'none';
            return true;
        }
    </script>
</body>

</html>