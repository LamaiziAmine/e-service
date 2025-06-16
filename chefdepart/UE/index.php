<?php
session_start();

// Vérification de la session et du département
if (!isset($_SESSION['user_department'])) {
   die("Département non défini, merci de vous reconnecter.");
}
$department_id = $_SESSION['user_department'];

// Connexion à la base de données
$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';
$connection = new mysqli($host, $user, $pass, $db);
if ($connection->connect_error) {
    die("Erreur de connexion : " . $connection->connect_error);
}

// Récupération des filtres GET
$code = $_GET['code'] ?? '';
$semester = $_GET['semester'] ?? '';

// Construction de la requête SQL avec jointure filiere
$sql = "SELECT ue.id, ue.code_module, ue.intitule_module, ue.semestre, 
               ue.V_h_cours, ue.V_h_TD, ue.V_h_TP, ue.V_h_Autre, ue.V_h_Evaluation,
               f.nom
        FROM unités_ensignement ue
        LEFT JOIN filiere f ON ue.filiere_id = f.id
        WHERE ue.department_id = ?";

$params = [$department_id];
$types = 'i';

// Ajout des filtres si définis
if (!empty($code)) {
    $sql .= " AND ue.code_module LIKE ?";
    $params[] = "%$code%";
    $types .= 's';
}
if (!empty($semester)) {
    $sql .= " AND ue.semestre LIKE ?";
    $params[] = "%$semester%";
    $types .= 's';
}

$stmt = $connection->prepare($sql);
if (!$stmt) {
    die("Erreur de préparation : " . $connection->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calcul des statistiques
$stats_sql = "SELECT 
                COUNT(*) as total_modules,
                COUNT(DISTINCT semestre) as total_semestres,
                SUM(V_h_cours + V_h_TD + V_h_TP + V_h_Autre + V_h_Evaluation) as total_heures,
                COUNT(DISTINCT filiere_id) as total_filieres
              FROM unités_ensignement ue
              WHERE ue.department_id = ?";
$stats_stmt = $connection->prepare($stats_sql);
$stats_stmt->bind_param('i', $department_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Unités d'Enseignement du Département</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

      

        .page-flex {
            display: flex;
            min-height: 100vh;
        }

        .main-wrapper {
            flex: 1;
            padding: 2rem;
        }

        .main-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-50%, 50%);
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-subtitle {
            opacity: 0.9;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        .search-section {
            padding: 2rem;
            background: var(--light-bg);
            border-bottom: 1px solid #e2e8f0;
        }

        .search-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .form-floating {
            position: relative;
        }

        .form-floating > .form-control {
            height: 3.5rem;
            line-height: 1.25;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: var(--transition);
            background: #f8fafc;
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .form-floating > label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        .btn-modern {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .btn-outline-modern {
            background: white;
            border: 2px solid #e2e8f0;
            color: var(--secondary-color);
        }

        .btn-outline-modern:hover {
            background: var(--light-bg);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .table-section {
            padding: 2rem;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .modern-table {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: none;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        }

        .modern-table thead th {
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .modern-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: var(--transition);
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: scale(1.005);
        }

        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .badge-semester {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
        }

        .badge-hours {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
            color: white;
        }

        .module-code {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .main-wrapper { padding: 1rem; }
            .header-title { font-size: 1.5rem; }
            .stats-cards { grid-template-columns: 1fr; }
            .search-section { padding: 1rem; }
            .table-section { padding: 1rem; }
        }
    </style>
</head>
<body>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?>
        <main class="main users" id="skip-target">
            <div class="main-card fade-in">
                <!-- Header Section -->
                <div class="header-section">
                    <div class="header-content">
                        <h1 class="header-title">
                            <i class="bi bi-journal-bookmark-fill"></i>
                            Unités d'Enseignement
                        </h1>
                        <p class="header-subtitle">Gestion des modules du département</p>
                    </div>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                    <div class="search-card">
                        <form class="row g-3" method="get" autocomplete="off">
                            <div class="col-md-5">
                                <div class="form-floating">
                                    <input type="text" id="codeInput" name="code" class="form-control" placeholder="Code Module" value="<?= htmlspecialchars($code) ?>">
                                    <label for="codeInput"><i class="bi bi-code-square me-2"></i>Code Module</label>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-floating">
                                    <input type="text" id="semesterInput" name="semester" class="form-control" placeholder="Semestre" value="<?= htmlspecialchars($semester) ?>">
                                    <label for="semesterInput"><i class="bi bi-calendar3 me-2"></i>Semestre</label>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-primary-modern btn-modern">
                                    <i class="bi bi-search me-2"></i>Rechercher
                                </button>
                                <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-modern btn-modern">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <!-- Stats Cards -->
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_modules'] ?: '0' ?></div>
                            <div class="stat-label">Total Modules</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_semestres'] ?: '0' ?></div>
                            <div class="stat-label">Semestres</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format($stats['total_heures'] ?: 0) ?></div>
                            <div class="stat-label">Heures Total</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_filieres'] ?: '0' ?></div>
                            <div class="stat-label">Filières</div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash me-2"></i>ID</th>
                                    <th><i class="bi bi-code-square me-2"></i>Code</th>
                                    <th><i class="bi bi-book me-2"></i>Intitulé du Module</th>
                                    <th><i class="bi bi-calendar3 me-2"></i>Semestre</th>
                                    <th><i class="bi bi-diagram-3 me-2"></i>Filière</th>
                                    <th class="text-center"><i class="bi bi-clock me-2"></i>Volume Horaire</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()):
                                    $volume_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                                ?>
                                    <tr>
                                        <td><strong><?= $row['id'] ?></strong></td>
                                        <td><span class="module-code"><?= htmlspecialchars($row['code_module']) ?></span></td>
                                        <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                        <td><span class="badge badge-semester"><?= htmlspecialchars($row['semestre']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nom'] ?: 'Non assignée') ?></td>
                                        <td class="text-center"><span class="badge badge-hours"><?= $volume_total ?> h</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <div>
                                            <h5>Aucune unité d'enseignement trouvée</h5>
                                            <p>Aucun module ne correspond aux critères de recherche pour ce département.</p>
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

<?php
$stmt->close();
$connection->close();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
<script>
    // Animation d'entrée pour les lignes du tableau
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Effets de focus sur les champs de recherche
    const searchInputs = document.querySelectorAll('input[type="text"]');
    searchInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Animation des cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const card = entry.target;
                const number = card.querySelector('.stat-number');
                const finalValue = parseInt(number.textContent.replace(/,/g, ''));
                
                if (finalValue > 0) {
                    animateNumber(number, 0, finalValue, 1000);
                }
                
                observer.unobserve(card);
            }
        });
    }, observerOptions);

    statCards.forEach(card => {
        observer.observe(card);
    });

    // Animation des chiffres
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const originalText = element.textContent;
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * easeOutQuart(progress));
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                element.textContent = originalText;
            }
        }
        
        requestAnimationFrame(update);
    }

    function easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }
</script>
</body>
</html>