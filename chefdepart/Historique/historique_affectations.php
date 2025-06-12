<?php
session_start();
include '../config.php';

// Vérifier que l'utilisateur est connecté et que department_id est défini dans la session
if (!isset($_SESSION['user_id'], $_SESSION['user_department'])) {
    die("Vous devez être connecté avec un département défini.");
}

$department_id = $_SESSION['user_department'];
$user_id = $_SESSION['user_id'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filtres
$annee = isset($_GET['annee']) ? $_GET['annee'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Générer une liste d'années au format "2025-2024", "2024-2023", etc.
$currentYear = (int)date('Y');
$years = [];
for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
    $years[] = ($y) . '-' . ($y - 1);
}

// Construction de la clause WHERE et des paramètres
$whereClauses = ["ue.department_id = ?"];
$params = [$department_id];
$paramTypes = "i";

if ($annee !== '') {
    $whereClauses[] = "a.annee_univ = ?";
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

// Récupérer le nombre total pour la pagination
$sql_count = "
    SELECT COUNT(*) AS total
    FROM affectations a
    JOIN users u ON a.id_user = u.id
    JOIN unités_enseignement ue ON a.id_ue = ue.id
    $whereSQL
";

$stmt_count = $connection->prepare($sql_count);
$stmt_count->bind_param($paramTypes, ...$params);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_rows = ($res_count && $row_count = $res_count->fetch_assoc()) ? intval($row_count['total']) : 0;
$total_pages = ceil($total_rows / $limit);
$stmt_count->close();

// Requête principale avec pagination
$sql_base = "
    SELECT CONCAT(u.nom, ' ', u.prenom) AS enseignant,
           ue.code_module,
           ue.intitule_module,
           a.annee_univ,
           a.type_enseignement
    FROM affectations a
    JOIN users u ON a.id_user = u.id
    JOIN unités_enseignement ue ON a.id_ue = ue.id
    $whereSQL
    ORDER BY enseignant ASC
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($sql_base);

// Ajouter les paramètres limit et offset
$params_with_limit = $params;
$paramTypes_with_limit = $paramTypes . "ii";
$params_with_limit[] = $limit;
$params_with_limit[] = $offset;

$stmt->bind_param($paramTypes_with_limit, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=historique_affectations_' . ($annee ?: 'all') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enseignant', 'Code UE', 'Intitulé UE', 'Type enseignement', 'Année universitaire']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['enseignant'],
            $row['code_module'],
            $row['intitule_module'],
            $row['type_enseignement'],
            $row['annee_univ']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Historique des affectations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon" />
    <link rel="stylesheet" href="/elegant/css/style.min.css" />
</head>
<body>
<div class="layer"></div>
<div class="page-flex">
    <?php include "../sidebar.php"; ?>
    <div class="main-wrapper">
        <?php include "../navbar.php"; ?><br />
        <div class="container my-5">
            <h2>Historique des affectations - Département ID <?= htmlspecialchars($department_id) ?></h2>

            <form method="get" class="mb-4 row g-3 align-items-center">
                <div class="col-auto">
                    <label for="annee" class="form-label">Choisir une année universitaire :</label>
                    <select name="annee" id="annee" class="form-select">
                        <option value="">Toutes</option>
                        <?php foreach ($years as $year) : ?>
                            <option value="<?= htmlspecialchars($year) ?>" <?= ($annee === $year) ? 'selected' : '' ?>><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="search" class="form-label">Rechercher :</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Enseignant ou UE"
                        value="<?= htmlspecialchars($search) ?>"
                    />
                </div>
                <div class="col-auto mt-4">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                    <a href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&export=csv" class="btn btn-success ms-2">Exporter CSV</a>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Enseignant</th>
                        <th>UE</th>
                        <th>Type enseignement</th>
                        <th>Année universitaire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0) : ?>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['enseignant']) ?></td>
                                <td><?= htmlspecialchars($row['code_module'] . ' - ' . $row['intitule_module']) ?></td>
                                <td><?= htmlspecialchars($row['type_enseignement']) ?></td>
                                <td><?= htmlspecialchars($row['annee_univ']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Aucune affectation trouvée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>"
                                aria-label="Précédent"
                            >
                                &laquo; Précédent
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>"
                                    ><?= $p ?></a
                                >
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?annee=<?= urlencode($annee) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>"
                                aria-label="Suivant"
                            >
                                Suivant &raquo;
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/elegant/plugins/chart.min.js"></script>
<script src="/elegant/plugins/feather.min.js"></script>
<script src="/elegant/js/script.js"></script>
</body>
</html>
