<?php
session_start();
include '../config.php';

// Vérifier que l'utilisateur est connecté et que department_id est défini dans la session
if (!isset($_SESSION['user_id'], $_SESSION['user_department'])) {
    die("Vous devez être connecté avec un département défini.");
}

$department_id = $_SESSION['user_department'];

// Optionally filter by year (if you want)
$annee = isset($_GET['annee']) ? $_GET['annee'] : '';

// List of years for filter dropdown, adjust as needed
$years = [2025, 2024];

// Build query with optional year filter
$params = [$department_id];
$paramTypes = "i";
$whereClauses = ["ue.department_id = ?"];

if ($annee !== '') {
    $whereClauses[] = "NOT EXISTS (
        SELECT 1 FROM affectations a 
        WHERE a.id_ue = ue.id 
        AND a.annee_univ = ?
    )";
    $params[] = $annee;
    $paramTypes .= "s";
} else {
    $whereClauses[] = "NOT EXISTS (
        SELECT 1 FROM affectations a 
        WHERE a.id_ue = ue.id
    )";
}

$whereSQL = "WHERE " . implode(" AND ", $whereClauses);

$sql = "
    SELECT ue.code_module, ue.intitule_module
    FROM unités_enseignement ue
    $whereSQL
    ORDER BY ue.code_module ASC
";

$stmt = $connection->prepare($sql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modules non affectés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "../sidebar.php" ?>
        <div class="main-wrapper">
            <?php include "../navbar.php" ?><br>
<div class="container my-5">
    <h2>Modules non affectés - Département ID <?= htmlspecialchars($department_id) ?></h2>

    <form method="get" class="mb-4 row g-3 align-items-center">
        <div class="col-auto">
            <label for="annee" class="form-label">Filtrer par année universitaire :</label>
            <select name="annee" id="annee" class="form-select">
                <option value="">Toutes</option>
                <?php foreach ($years as $year) : ?>
                    <option value="<?= $year ?>" <?= ($annee == $year) ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto mt-4">
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Code Module</th>
                <th>Intitulé Module</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['code_module']) ?></td>
                        <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2" class="text-center text-muted">Tous les modules sont affectés.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
</div>
</div>
<script src="/elegant/plugins/chart.min.js"></script>
    <script src="/elegant/plugins/feather.min.js"></script>
    <script src="/elegant/js/script.js"></script>
</body>
</html>
