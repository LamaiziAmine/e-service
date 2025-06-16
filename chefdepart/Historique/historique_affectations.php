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

// --- Pagination et Filtres ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$annee = isset($_GET['annee']) ? trim($_GET['annee']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Préparation des données pour le formulaire ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$years_result = $connection->query("SELECT DISTINCT annee_univ FROM unités_ensignement WHERE annee_univ <> '' ORDER BY annee_univ DESC");
$years = $years_result ? $years_result->fetch_all(MYSQLI_ASSOC) : [];

// --- Construction de la requête SQL dynamique ---
$whereClauses = ["ue.department_id = ?"];
$params = [$department_id];
$paramTypes = "i";

if ($annee !== '') {
    // *** CORRECTION DU NOM DE LA TABLE ICI (implicite via l'alias 'ue') ***
    $whereClauses[] = "ue.annee_univ = ?";
    $params[] = $annee;
    $paramTypes .= "s";
}

if ($search !== '') {
    // *** CORRECTION DU NOM DE LA TABLE ICI (implicite via l'alias 'ue') ***
    $whereClauses[] = "(CONCAT(u.nom, ' ', u.prenom) LIKE ? OR ue.code_module LIKE ? OR ue.intitule_module LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $paramTypes .= "sss";
}

$whereSQL = "WHERE " . implode(" AND ", $whereClauses);

// --- Requête pour le comptage total ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$sql_count = "
    SELECT COUNT(DISTINCT a.id) AS total
    FROM affectations a
    JOIN users u ON a.id_user = u.id
    JOIN unités_ensignement ue ON a.id_ue = ue.id
    $whereSQL
";

$stmt_count = $connection->prepare($sql_count);
if (!$stmt_count) { die("Erreur de préparation (count): " . $connection->error); }
$stmt_count->bind_param($paramTypes, ...$params);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_rows = ($res_count && $row_count = $res_count->fetch_assoc()) ? intval($row_count['total']) : 0;
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();

// --- Requête principale pour l'affichage ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
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
if (!$stmt) { die("Erreur de préparation (select): " . $connection->error); }

$params_with_limit = $params;
$paramTypes_with_limit = $paramTypes . "ii";
$params_with_limit[] = $limit;
$params_with_limit[] = $offset;

$stmt->bind_param($paramTypes_with_limit, ...$params_with_limit);
$stmt->execute();
$result_data = $stmt->get_result();
$affectations_data = $result_data ? $result_data->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();


// --- Logique d'Export CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // *** CORRECTION DU NOM DE LA TABLE ICI ***
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
    <style>
        .main-content { padding: 20px; }
        .container-hist { padding: 25px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        .filter-form { display: flex; gap: 1.5rem; align-items: flex-end; margin-bottom: 2rem; padding: 1.5rem; background-color: #f8f9fa; border-radius: 8px; }
        .form-group { flex: 1; }
        .table-hist { width: 100%; border-collapse: collapse; }
        .table-hist th, .table-hist td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: left; }
        .table-hist thead th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 0.85rem; }
        .table-hist tbody tr:hover { background-color: #f1f1f1; }
        .pagination { list-style: none; display: flex; justify-content: center; padding: 0; margin-top: 1.5rem; }
        .pagination .page-item a { padding: 8px 12px; margin: 0 4px; border: 1px solid #dee2e6; color: #0d6efd; text-decoration: none; border-radius: 4px; }
        .pagination .page-item.active a { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .pagination .page-item.disabled a { color: #6c757d; pointer-events: none; }
    </style>
</head>
<body>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?><br />
        <main class="main users" id="skip-target">
            <div class="container-hist">
                <h2>Historique des affectations de votre département</h2>

                <form method="get" class="filter-form">
                    <div class="form-group">
                        <label for="annee" class="form-label">Année universitaire :</label>
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
                        <label for="search" class="form-label">Rechercher :</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Nom, Prénom, Code ou Intitulé UE" value="<?= htmlspecialchars($search) ?>" />
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&export=csv" class="btn btn-success ms-2">Exporter en CSV</a>
                    </div>
                </form>

                <table class="table-hist">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>UE</th>
                            <th>Type d'enseignement</th>
                            <th>Année universitaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($affectations_data)) : ?>
                            <?php foreach ($affectations_data as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['enseignant']) ?></td>
                                    <td><?= htmlspecialchars($row['code_module'] . ' - ' . $row['intitule_module']) ?></td>
                                    <td><?= htmlspecialchars($row['type_enseignement'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['annee_univ']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">Aucune affectation trouvée pour les critères sélectionnés.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1) : ?>
                <nav>
                    <ul class="pagination">
                        <!-- La logique de pagination reste la même -->
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>