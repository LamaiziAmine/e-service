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
    <style>
        .main-content { padding: 20px; }
        .container-vacant {
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }
        .table-vacant thead { background-color: #343a40; color: white; }
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
        <?php include "../navbar.php"; ?><br>
        <main class="main users" id="skip-target">
            <div class="container-vacant">
                <h2 class="text-primary mb-4">Unités d'Enseignement Vacantes</h2>
                <p class="text-muted mb-4">Cette page liste toutes les interventions (Cours, TD, TP) qui n'ont pas encore été affectées à un enseignant dans votre département.</p>

                <form method="get" class="row g-3 align-items-end mb-4 p-3 bg-light border rounded">
                    <div class="col-md-6">
                        <label for="code" class="form-label">Filtrer par code de module :</label>
                        <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code_filter) ?>" placeholder="Ex: UE101" />
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                    <div class="col-md-3">
                        <a href="vacantes.php" class="btn btn-secondary w-100">Réinitialiser</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vacant">
                        <thead>
                            <tr>
                                <th>Code Module</th>
                                <th>Intitulé du Module</th>
                                <th>Type d'intervention non affecté</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['code_module']) ?></td>
                                        <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['type_enseignement']) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center p-4">Félicitations ! Toutes les interventions ont été affectées dans votre département.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>