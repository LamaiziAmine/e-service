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
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$department_id = $_SESSION['user_department'] ?? null;
if (!$department_id) {
    die("Erreur : département non défini pour l'utilisateur.");
}

// --- Requête principale : charge horaire totale par professeur ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$sql = "
SELECT 
    u.id, u.nom, u.prenom,
    COALESCE(SUM(
        ue.V_h_cours + ue.V_h_TD + ue.V_h_TP + ue.V_h_Autre + ue.V_h_Evaluation
    ), 0) AS total_heures
FROM users u
LEFT JOIN affectations a ON u.id = a.id_user
LEFT JOIN unités_ensignement ue ON a.id_ue = ue.id
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

// --- Requête secondaire : unités par professeur ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$units_sql = "
SELECT 
    a.id_user,
    ue.code_module,
    ue.intitule_module,
    (COALESCE(ue.V_h_cours, 0) + COALESCE(ue.V_h_TD, 0) + COALESCE(ue.V_h_TP, 0) + COALESCE(ue.V_h_Autre, 0) + COALESCE(ue.V_h_Evaluation, 0)) AS total_volume_hours
FROM affectations a
JOIN unités_ensignement ue ON a.id_ue = ue.id
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
$connection->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Charge Horaire des Professeurs</title>
    <!-- Utilisons les styles de votre template -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <style>
        .units-row {
            display: none;
        }
        .main-content { padding: 20px; }
        .container-charge {
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }
        .table th, .table td { vertical-align: middle; }
        .table-danger td { color: #842029; background-color: #f8d7da; }
        .table-success td { color: #0f5132; background-color: #d1e7dd; }
        .units-row { display: none; }
        .units-row td { background-color: #f8f9fa !important; padding: 1rem 1.5rem; }
        .units-row ul { padding-left: 20px; }
        .units-row li { margin-bottom: 5px; }
    </style>
    <script>
        function toggleUnits(id) {
            const row = document.getElementById('units-' + id);
            row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
        }
    </script></head>
<body>
    <div class="page-flex">
        <?php 
        // Assurez-vous que ce chemin est correct. Si ce fichier est dans /chefdepart/Professeur/
        // et la sidebar dans /chefdepart/, le chemin est ../sidebar.php
        include "../sidebar.php"; 
        ?>
        <div class="main-wrapper">
            <?php include "../navbar.php"; ?><br>
            <main class="main users" id="skip-target">
                <div class="container-charge">
                    <h2 class="mb-4 text-center">Charge Horaire des Professeurs</h2>
                    <div class="alert alert-info text-center">
                        La charge horaire de référence est de 96 heures. Les professeurs en sous-charge sont affichés en premier.
                    </div>
                    <table class="table table-hover table-bordered shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Professeur</th>
                                <th>Total Heures Affectées</th>
                                <th>Statut</th>
                                <th>Détails des Unités</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $id = $row['id'];
                                $charge = (float)$row['total_heures'];
                                $isUnder = $charge < 96;
                            ?>
                                <tr class="<?= $isUnder ? 'table-danger' : '' ?>">
                                    <td><?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?></td>
                                    <td><strong><?= $charge ?> h</strong></td>
                                    <td>
                                        <span class="badge <?= $isUnder ? 'bg-danger' : 'bg-success' ?>">
                                            <?= $isUnder ? 'Sous-chargé' : 'Charge Correcte' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleUnits(<?= $id ?>)">
                                            Voir / Cacher
                                        </button>
                                    </td>
                                </tr>
                                <tr class="units-row" id="units-<?= $id ?>">
                                    <td colspan="4">
                                        <?php if (!empty($prof_units[$id])): ?>
                                            <ul class="list-unstyled mb-0">
                                            <?php foreach ($prof_units[$id] as $u): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($u['code_module']) ?></strong>: 
                                                    <?= htmlspecialchars($u['intitule_module']) ?> 
                                                    <span class="badge bg-secondary"><?= $u['total_volume_hours'] ?> h</span>
                                                </li>
                                            <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <em class="text-muted">Aucune unité n'a encore été affectée à ce professeur.</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucun professeur trouvé dans ce département.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

<script>
    function toggleUnits(id) {
        const row = document.getElementById('units-' + id);
        if (row) {
            row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
        }
    }
</script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>