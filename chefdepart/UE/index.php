<?php
include_once __DIR__ . '/../config.php';

$code = $_GET['code'] ?? '';
$semester = $_GET['semester'] ?? '';

// Récupération des données avec filtres
$sql = "SELECT id, code_module, intitule_module, semestre, V_h_cours, V_h_TD, V_h_TP, V_h_Autre, V_h_Evaluation FROM `unités_enseignement` WHERE 1";

if (!empty($code)) {
    $codeEscaped = $connection->real_escape_string($code);
    $sql .= " AND code_module LIKE '%$codeEscaped%'";
}
if (!empty($semester)) {
    $semesterEscaped = $connection->real_escape_string($semester);
    $sql .= " AND semestre LIKE '%$semesterEscaped%'";
}

$result = $connection->query($sql);
if (!$result) {
    die("Requête invalide : " . $connection->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>UE - Recherche</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #f0f5ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #222;
        }
        h2 {
            color: #0d6efd;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        .form-control, .btn {
            border-radius: 0.5rem;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #0845c6;
            border-color: #0845c6;
        }
        .btn-outline-secondary {
            border-radius: 0.5rem;
        }
        table {
            border-radius: 0.6rem;
            overflow: hidden;
            box-shadow: 0 4px 8px rgb(13 110 253 / 0.1);
            background: white;
        }
        thead {
            background-color: #0d6efd;
            color: white;
        }
        thead th {
            border: none;
            font-weight: 600;
        }
        tbody tr:hover {
            background-color: #e9f0ff;
            cursor: pointer;
        }
        tbody td {
            vertical-align: middle;
            padding: 0.75rem 1.25rem;
        }
        .container {
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "../sidebar.php" ?>
        <div class="main-wrapper">
            <?php include "../navbar.php" ?><br>
<div class="container my-5">
    <h2><i class="bi bi-journal-bookmark-fill"></i> Unités d'enseignement</h2>

    <!-- Formulaire de recherche -->
    <form class="row g-3 mb-4" method="get" autocomplete="off">
        <div class="col-md-5">
            <input type="text" class="form-control" name="code" placeholder="Rechercher par code" value="<?= htmlspecialchars($code) ?>">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" name="semester" placeholder="Rechercher par semestre" value="<?= htmlspecialchars($semester) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Rechercher</button>
            <a href="index.php" class="btn btn-outline-secondary flex-grow-1">Réinitialiser</a>
        </div>
    </form>

    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>CODE</th>
                <th>NOM</th>
                <th>SEMESTRE</th>
                <th>VOLUME HORAIRE TOTAL</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) :
            $volume_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
        ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['code_module']) ?></td>
                <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                <td><?= htmlspecialchars($row['semestre']) ?></td>
                <td><?= $volume_total ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>
</div>
</div>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<script src="/elegant/plugins/chart.min.js"></script>
    <script src="/elegant/plugins/feather.min.js"></script>
    <script src="/elegant/js/script.js"></script>

</body>
</html>
