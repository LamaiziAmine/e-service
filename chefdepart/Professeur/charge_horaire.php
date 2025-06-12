<?php
session_start();
include '../config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Récupérer le department_id de l'utilisateur courant
$department_id = $_SESSION['user_department'] ?? null;

if (!$department_id) {
    die("Erreur : département non défini pour l'utilisateur.");
}

// Requête principale : charge horaire totale par professeur du même département
$sql = "
SELECT 
    u.id, u.nom, u.prenom,
    COALESCE(SUM(
        ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation
    ), 0) AS total_heures
FROM users u
LEFT JOIN affectations a ON u.id = a.id_user
LEFT JOIN unités_enseignement ue ON a.id_ue = ue.id
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

// Requête secondaire : unités par professeur du même département
$units_sql = "
SELECT 
    a.id_user,
    ue.code_module,
    ue.intitule_module,
    (COALESCE(ue.V_h_cours, 0) + COALESCE(ue.V_h_TD, 0) + COALESCE(ue.V_h_TP, 0) + COALESCE(ue.V_h_Autre, 0) + COALESCE(ue.V_h_Evaluation, 0)) AS total_volume_hours
FROM affectations a
JOIN unités_enseignement ue ON a.id_ue = ue.id
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Charge Horaire des Professeurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <style>
        .units-row {
            display: none;
        }
    </style>
    <script>
        function toggleUnits(id) {
            const row = document.getElementById('units-' + id);
            row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
        }
    </script>
</head>
<body class="bg-light">
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "../sidebar.php" ?>
        <div class="main-wrapper">
            <?php include "../navbar.php" ?><br>
            <div class="container my-5">
                <h2 class="mb-4 text-center text-primary">Charge Horaire des Professeurs</h2>
                <div class="alert alert-info text-center">
                    Les professeurs en rouge ont une charge inférieure à 96 heures.
                </div>
                <table class="table table-hover table-bordered shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Professeur</th>
                            <th>Total Heures</th>
                            <th>Statut</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $id = $row['id'];
                        $charge = (float)$row['total_heures'];
                        $isUnder = $charge < 96;
                    ?>
                        <tr class="<?= $isUnder ? 'table-danger' : 'table-success' ?>">
                            <td><?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?></td>
                            <td><?= $charge ?> h</td>
                            <td><?= $isUnder ? 'Sous-chargé' : 'Correct' ?></td>
                            <td>
                                <button class="btn btn-outline-info btn-sm" onclick="toggleUnits(<?= $id ?>)">
                                    Voir les unités
                                </button>
                            </td>
                        </tr>
                        <tr class="units-row" id="units-<?= $id ?>">
                            <td colspan="4" class="bg-white">
                                <?php if (!empty($prof_units[$id])): ?>
                                    <ul class="mb-0">
                                    <?php foreach ($prof_units[$id] as $u): ?>
                                        <li>
                                            <strong><?= htmlspecialchars($u['code_module']) ?></strong> - 
                                            <?= htmlspecialchars($u['intitule_module']) ?> 
                                            (<?= $u['total_volume_hours'] ?> h)
                                        </li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em>Aucune unité affectée</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
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
