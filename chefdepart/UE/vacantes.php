<?php
session_start();
include '../config.php';

// Vérifier que l'utilisateur est connecté et que department_id est défini dans la session
if (!isset($_SESSION['user_id'], $_SESSION['user_department'])) {
    die("Vous devez être connecté avec un département défini.");
}

$department_id = $_SESSION['user_department'];

// Get the module code filter if set
$code_filter = isset($_GET['code']) ? trim($_GET['code']) : '';

// List of types to check
$types = ['TP', 'TD', 'Cours'];

// Prepare the SQL with CROSS JOIN to list all types per module and check if assigned
$sql = "
    SELECT ue.code_module, ue.intitule_module, t.type_enseignement
    FROM unités_enseignement ue
    CROSS JOIN (SELECT 'TP' AS type_enseignement UNION SELECT 'TD' UNION SELECT 'Cours') t
    WHERE ue.department_id = ?
    AND NOT EXISTS (
        SELECT 1 FROM affectations a
        WHERE a.id_ue = ue.id
          AND a.type_enseignement = t.type_enseignement
    )
";

$params = [$department_id];
$paramTypes = "i";

// Apply filter by code_module if provided
if ($code_filter !== '') {
    $sql .= " AND ue.code_module LIKE ?";
    $params[] = "%$code_filter%";
    $paramTypes .= "s";
}

$sql .= " ORDER BY ue.code_module ASC, t.type_enseignement ASC";

$stmt = $connection->prepare($sql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modules non affectés par type</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/elegant/css/style.min.css">
</head>
<body>
<div class="layer"></div>
<div class="page-flex">
    <?php include "../sidebar.php" ?>
    <div class="main-wrapper">
        <?php include "../navbar.php" ?><br>
        <div class="container my-5">
            <h2 class="text-primary">Modules non affectés par type</h2>

            <form method="get" class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <label for="code" class="form-label">Filtrer par code module :</label>
                    <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code_filter) ?>" placeholder="Ex: INF123" />
                </div>
                <div class="col-auto mt-4">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="vacantes.php" class="btn btn-secondary ms-2">Réinitialiser</a>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Code Module</th>
                        <th>Intitulé Module</th>
                        <th>Type non affecté</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['code_module']) ?></td>
                            <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                            <td><?= htmlspecialchars($row['type_enseignement']) ?></td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Tous les types de modules sont affectés.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="/elegant/plugins/chart.min.js"></script>
<script src="/elegant/plugins/feather.min.js"></script>
<script src="/elegant/js/script.js"></script>
</body>
</html>
