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

// --- Filtres ---
$code_filter = isset($_GET['code']) ? trim($_GET['code']) : '';

// --- Requête SQL ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$sql = "
    SELECT ue.code_module, ue.intitule_module, t.type_enseignement
    FROM unités_ensignement ue
    CROSS JOIN (SELECT 'Cours' AS type_enseignement UNION ALL SELECT 'TD' UNION ALL SELECT 'TP') t
    WHERE ue.department_id = ?
    AND NOT EXISTS (
        SELECT 1 FROM affectations a
        WHERE a.id_ue = ue.id
          AND a.type_enseignement = t.type_enseignement
    )
";

$params = [$department_id];
$paramTypes = "i";

// Appliquer le filtre sur le code du module si fourni
if ($code_filter !== '') {
    $sql .= " AND ue.code_module LIKE ?";
    $params[] = "%$code_filter%";
    $paramTypes .= "s";
}

$sql .= " ORDER BY ue.code_module ASC, FIELD(t.type_enseignement, 'Cours', 'TD', 'TP')";

$stmt = $connection->prepare($sql);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $connection->error);
}
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Unités d'Enseignement Vacantes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --shadow-light: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 12px 48px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .page-flex {
            display: flex;
            min-height: 100vh;
        }

        .main-wrapper {
            flex: 1;
            padding: 0;
        }

        .main-content {
            padding: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }

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

        .container-vacant {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .container-vacant::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            position: relative;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .filter-section {
            background: rgba(248, 249, 250, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .filter-section:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .filter-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table-vacant {
            margin: 0;
            font-size: 0.95rem;
        }

        .table-vacant thead {
            background: var(--primary-gradient);
            color: white;
        }

        .table-vacant thead th {
            border: none;
            padding: 1.2rem 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            position: relative;
        }

        .table-vacant tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table-vacant tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .table-vacant tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            border: none;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .badge:hover::before {
            left: 100%;
        }

        .badge.bg-warning {
            background: var(--warning-gradient) !important;
            color: white !important;
            border: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .stats-card {
            background: var(--success-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 20%, transparent 20%);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateX(-50px) translateY(-50px); }
            100% { transform: translateX(50px) translateY(50px); }
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .container-vacant {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .filter-section {
                padding: 1.5rem;
            }
            
            .table-responsive {
                border-radius: 12px;
                overflow: hidden;
            }
        }

        /* Animation pour l'apparition des lignes du tableau */
        .table-vacant tbody tr {
            animation: slideInLeft 0.6s ease-out;
            animation-fill-mode: both;
        }

        .table-vacant tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table-vacant tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .table-vacant tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .table-vacant tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .table-vacant tbody tr:nth-child(5) { animation-delay: 0.5s; }

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
    </style>
</head>
<body>
<div class="page-flex">
    <?php 
    // Assurez-vous que ce chemin est correct
    // Si ce fichier est dans /chefdepart/UE/, le chemin est ../sidebar.php
    include "../sidebar.php"; 
    ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?>
        <main class="main users main-content" id="skip-target">
            <div class="container-vacant">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-graduation-cap me-3"></i>
                        Unités d'Enseignement Vacantes
                    </h1>
                    <p class="page-subtitle">
                        Découvrez toutes les interventions (Cours, TD, TP) qui n'ont pas encore été affectées à un enseignant dans votre département.
                    </p>
                </div>

                <?php 
                $total_vacant = $result ? $result->num_rows : 0;
                if ($total_vacant > 0):
                ?>
                <div class="stats-card">
                    <div class="stats-number"><?= $total_vacant ?></div>
                    <div class="stats-label">Interventions Vacantes</div>
                </div>
                <?php endif; ?>

                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filtres de recherche
                    </div>
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="code" class="form-label">
                                <i class="fas fa-search me-2"></i>
                                Filtrer par code de module
                            </label>
                            <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code_filter) ?>" placeholder="Ex: UE101, INFO, MATH..." />
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>
                                Filtrer
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="vacantes.php" class="btn btn-secondary w-100">
                                <i class="fas fa-undo me-2"></i>
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-vacant">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="fas fa-code me-2"></i>
                                        Code Module
                                    </th>
                                    <th>
                                        <i class="fas fa-book me-2"></i>
                                        Intitulé du Module
                                    </th>
                                    <th>
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        Type d'intervention
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['code_module']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?php
                                                    $type = htmlspecialchars($row['type_enseignement']);
                                                    $icon = '';
                                                    switch($type) {
                                                        case 'Cours': $icon = 'fas fa-chalkboard'; break;
                                                        case 'TD': $icon = 'fas fa-users'; break;
                                                        case 'TP': $icon = 'fas fa-flask'; break;
                                                    }
                                                    echo "<i class='$icon me-2'></i>$type";
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <h3>Excellent travail !</h3>
                                            <p>Toutes les interventions ont été affectées dans votre département.</p>
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

<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
<script>
    // Animation d'apparition progressive
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.container-vacant > *');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.6s ease-out';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
</body>
</html>