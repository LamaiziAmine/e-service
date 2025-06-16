<?php
// On suppose que ce fichier fait partie d'un espace sécurisé, donc on démarre la session
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

// Récupération des filtres depuis l'URL
$code = $_GET['code'] ?? '';
$semester = $_GET['semester'] ?? '';

// --- Requête SQL avec le nom de table corrigé ---
// *** CORRECTION DU NOM DE LA TABLE ICI ***
$sql = "SELECT id, code_module, intitule_module, semestre, V_h_cours, V_h_TD, V_h_TP, V_h_Autre, V_h_Evaluation 
        FROM `unités_ensignement` 
        WHERE 1";

// Utilisation des requêtes préparées pour la sécurité
$params = [];
$types = '';

if (!empty($code)) {
    $sql .= " AND code_module LIKE ?";
    $params[] = "%" . $code . "%";
    $types .= 's';
}
if (!empty($semester)) {
    $sql .= " AND semestre LIKE ?";
    $params[] = "%" . $semester . "%";
    $types .= 's';
}

$stmt = $connection->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        die("Erreur lors de l'exécution de la requête : " . $stmt->error);
    }
} else {
    die("Erreur de préparation de la requête : " . $connection->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Liste des Unités d'Enseignement</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <style>
        .main-content { padding: 20px; }
        .container-ue {
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }
        h2 { color: #0d6efd; font-weight: 700; margin-bottom: 2rem; }
        .table-ue {
            border-radius: 0.6rem;
            overflow: hidden;
            box-shadow: 0 4px 8px rgb(13 110 253 / 0.1);
        }
        .table-ue thead { background-color: #343a40; color: white; }
        .table-ue tbody tr:hover { background-color: #f1f7ff; }
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
            <div class="container-ue">
                <h2><i class="bi bi-journal-bookmark-fill"></i> Unités d'Enseignement</h2>

                <!-- Formulaire de recherche -->
                <form class="row g-3 mb-4 p-3 bg-light border rounded" method="get" autocomplete="off">
                    <div class="col-md-5">
                        <label for="codeInput" class="form-label">Code Module</label>
                        <input type="text" id="codeInput" class="form-control" name="code" placeholder="Ex: UE101" value="<?= htmlspecialchars($code) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="semesterInput" class="form-label">Semestre</label>
                        <input type="text" id="semesterInput" class="form-control" name="semester" placeholder="Ex: S1" value="<?= htmlspecialchars($semester) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100">Voir tout</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-ue">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Intitulé du Module</th>
                                <th>Semestre</th>
                                <th class="text-center">Volume Horaire Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()) :
                                $volume_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                            ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['code_module']) ?></td>
                                    <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                    <td><?= htmlspecialchars($row['semestre']) ?></td>
                                    <td class="text-center"><strong><?= $volume_total ?> h</strong></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center p-4">Aucune unité d'enseignement trouvée pour les critères sélectionnés.</td>
                            </tr>
                        <?php endif; ?>
                        <?php $stmt->close(); $connection->close(); ?>
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