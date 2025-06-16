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
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_department'])) {
    die("Accès non autorisé. Vous devez être connecté en tant que chef de département.");
}

$department_id = $_SESSION['user_department'];

// --- Initialisation des variables ---
$total_rows = 0;
$years = [];
$affectations_data = [];
$total_pages = 1;

// --- Pagination et Filtres ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$annee = isset($_GET['annee']) ? trim($_GET['annee']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Préparation des données pour le formulaire ---
try {
    $years_result = $connection->query("SELECT DISTINCT annee_univ FROM unités_ensignement WHERE annee_univ <> '' ORDER BY annee_univ DESC");
    if ($years_result) {
        $years = $years_result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des années : " . $e->getMessage());
    $years = [];
}

// --- Construction de la requête SQL dynamique ---
$whereClauses = ["ue.department_id = ?"];
$params = [$department_id];
$paramTypes = "i";

if ($annee !== '') {
    $whereClauses[] = "ue.annee_univ = ?";
    $params[] = $annee;
    $paramTypes .= "s";
}

if ($search !== '') {
    $whereClauses[] = "(CONCAT(u.nom, ' ', u.prenom) LIKE ? OR ue.code_module LIKE ? OR ue.intitule_module LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $paramTypes .= "sss";
}

$whereSQL = "WHERE " . implode(" AND ", $whereClauses);

// --- Requête pour le comptage total ---
try {
    $sql_count = "
        SELECT COUNT(DISTINCT a.id) AS total
        FROM affectations a
        JOIN users u ON a.id_user = u.id
        JOIN unités_ensignement ue ON a.id_ue = ue.id
        $whereSQL
    ";

    $stmt_count = $connection->prepare($sql_count);
    if ($stmt_count) {
        $stmt_count->bind_param($paramTypes, ...$params);
        $stmt_count->execute();
        $res_count = $stmt_count->get_result();
        if ($res_count && $row_count = $res_count->fetch_assoc()) {
            $total_rows = intval($row_count['total']);
        }
        $stmt_count->close();
    }
} catch (Exception $e) {
    error_log("Erreur lors du comptage : " . $e->getMessage());
    $total_rows = 0;
}

$total_pages = ceil($total_rows / $limit);

// --- Requête principale pour l'affichage ---
try {
    $sql_base = "
        SELECT CONCAT(u.nom, ' ', u.prenom) AS enseignant,
               ue.code_module,
               ue.intitule_module,
               ue.annee_univ,
               a.type_enseignement
        FROM affectations a
        JOIN users u ON a.id_user = u.id
        JOIN unités_ensignement ue ON a.id_ue = ue.id
        $whereSQL
        ORDER BY ue.annee_univ DESC, enseignant ASC, ue.code_module
        LIMIT ? OFFSET ?
    ";

    $stmt = $connection->prepare($sql_base);
    if ($stmt) {
        $params_with_limit = $params;
        $paramTypes_with_limit = $paramTypes . "ii";
        $params_with_limit[] = $limit;
        $params_with_limit[] = $offset;

        $stmt->bind_param($paramTypes_with_limit, ...$params_with_limit);
        $stmt->execute();
        $result_data = $stmt->get_result();
        if ($result_data) {
            $affectations_data = $result_data->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $affectations_data = [];
}

// --- Logique d'Export CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $sql_export = "
            SELECT CONCAT(u.nom, ' ', u.prenom) AS enseignant,
                   ue.code_module,
                   ue.intitule_module,
                   ue.annee_univ,
                   a.type_enseignement
            FROM affectations a
            JOIN users u ON a.id_user = u.id
            JOIN unités_ensignement ue ON a.id_ue = ue.id
            $whereSQL
            ORDER BY ue.annee_univ DESC, enseignant ASC, ue.code_module
        ";
        
        $stmt_export = $connection->prepare($sql_export);
        if ($stmt_export) {
            $stmt_export->bind_param($paramTypes, ...$params);
            $stmt_export->execute();
            $result_export = $stmt_export->get_result();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=historique_affectations_' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Enseignant', 'Code UE', 'Intitulé UE', 'Type enseignement', 'Année universitaire']);
            
            while ($row = $result_export->fetch_assoc()) {
                fputcsv($output, [
                    $row['enseignant'],
                    $row['code_module'],
                    $row['intitule_module'],
                    $row['type_enseignement'],
                    $row['annee_univ']
                ]);
            }
            fclose($output);
            $stmt_export->close();
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'export CSV : " . $e->getMessage());
    }
    $connection->close();
    exit;
}

$connection->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Historique des affectations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/e-service/css/style.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

       

        .page-flex {
            display: flex;
            min-height: 100vh;
        }

        .main-wrapper {
            flex: 1;
            padding: 20px;
        }

        .main-content {
            padding: 0;
        }

        .container-hist {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            margin: 1rem auto;
            border-radius: 2px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .table-hist {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table-hist thead {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        .table-hist thead th {
            padding: 1rem 1.25rem;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
            position: relative;
        }

        .table-hist thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .table-hist tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .table-hist tbody tr {
            transition: all 0.3s ease;
        }

        .table-hist tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            transform: scale(1.01);
        }

        .table-hist tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .table-hist tbody tr:nth-child(even):hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
        }

        .enseignant-cell {
            font-weight: 600;
            color: #2c3e50;
        }

        .ue-cell {
            color: #495057;
        }

        .type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-cours {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .type-td {
            background: rgba(86, 171, 47, 0.1);
            color: #56ab2f;
        }

        .type-tp {
            background: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .page-item {
            list-style: none;
        }

        .page-item a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .page-item a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .page-item.active a {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .page-item.disabled a {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .container-hist {
                padding: 20px;
                margin: 10px;
            }

            .filter-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .btn-group {
                justify-content: center;
            }

            .table-container {
                overflow-x: auto;
            }

            .table-hist {
                min-width: 600px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        /* Animation d'entrée */
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

        .container-hist {
            animation: fadeInUp 0.6s ease-out;
        }

        .table-hist tbody tr {
            animation: fadeInUp 0.4s ease-out;
            animation-fill-mode: both;
        }

        .table-hist tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table-hist tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .table-hist tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .table-hist tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .table-hist tbody tr:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?>
        <main class="main-content" id="skip-target">
            <div class="container-hist">
                <h1 class="page-title">
                    <i class="fas fa-history"></i>
                    Historique des Affectations
                </h1>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <div class="stat-number"><?= $total_rows ?></div>
                        <div class="stat-label">Affectations totales</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="stat-number"><?= count($years) ?></div>
                        <div class="stat-label">Années universitaires</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-file-export"></i>
                        <div class="stat-number">CSV</div>
                        <div class="stat-label">Export disponible</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        Filtres de recherche
                    </div>
                    <form method="get" class="filter-form">
                        <div class="form-group">
                            <label for="annee" class="form-label">
                                <i class="fas fa-calendar"></i> Année universitaire
                            </label>
                            <select name="annee" id="annee" class="form-select">
                                <option value="">Toutes les années</option>
                                <?php foreach ($years as $year_row) : ?>
                                    <option value="<?= htmlspecialchars($year_row['annee_univ']) ?>" <?= ($annee === $year_row['annee_univ']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($year_row['annee_univ']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="search" class="form-label">
                                <i class="fas fa-search"></i> Rechercher
                            </label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Nom, Prénom, Code ou Intitulé UE" 
                                   value="<?= htmlspecialchars($search) ?>" />
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                            <a href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&export=csv" 
                               class="btn btn-success">
                                <i class="fas fa-download"></i> Exporter CSV
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table class="table-hist">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user-tie"></i> Enseignant</th>
                                <th><i class="fas fa-graduation-cap"></i> Unité d'Enseignement</th>
                                <th><i class="fas fa-chalkboard-teacher"></i> Type d'enseignement</th>
                                <th><i class="fas fa-calendar-alt"></i> Année universitaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($affectations_data)) : ?>
                                <?php foreach ($affectations_data as $row) : ?>
                                    <tr>
                                        <td class="enseignant-cell">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($row['enseignant']) ?>
                                        </td>
                                        <td class="ue-cell">
                                            <strong><?= htmlspecialchars($row['code_module']) ?></strong><br>
                                            <small><?= htmlspecialchars($row['intitule_module']) ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $type = strtolower($row['type_enseignement'] ?? 'N/A');
                                            $badge_class = 'type-badge';
                                            if (strpos($type, 'cours') !== false) $badge_class .= ' type-cours';
                                            elseif (strpos($type, 'td') !== false) $badge_class .= ' type-td';
                                            elseif (strpos($type, 'tp') !== false) $badge_class .= ' type-tp';
                                            ?>
                                            <span class="<?= $badge_class ?>">
                                                <?= htmlspecialchars($row['type_enseignement'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar"></i>
                                            <?= htmlspecialchars($row['annee_univ']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="fas fa-search"></i>
                                        <h3>Aucune affectation trouvée</h3>
                                        <p>Aucune affectation ne correspond aux critères sélectionnés.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1) : ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1) : ?>
                            <li class="page-item">
                                <a href="?page=<?= $page - 1 ?>&annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) : ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a href="?page=<?= $i ?>&annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages) : ?>
                            <li class="page-item">
                                <a href="?page=<?= $page + 1 ?>&annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>
<script>
    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);

    // Observer les lignes du tableau
    document.querySelectorAll('.table-hist tbody tr').forEach(row => {
        observer.observe(row);
    });

    // Amélioration de l'UX pour les filtres
    document.getElementById('search').addEventListener('input', function(e) {
        if (e.target.value.length > 2 || e.target.value.length === 0) {
            // Auto-submit après 500ms d'inactivité
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                document.querySelector('.filter-form').submit();
            }, 500);
        }
    });
</script>
</body>
</html>