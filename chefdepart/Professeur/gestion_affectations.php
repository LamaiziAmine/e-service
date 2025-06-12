<?php
session_start();
include '../config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['user_role'] !== 'chef de departement') {
    header('Location: ../unauthorized.php');
    exit;
}

// Récupérer l'id du département depuis la session
$department_id = $_SESSION['user_department'] ?? null;
if (!$department_id) {
    die("Erreur : département non défini pour l'utilisateur.");
}

// Préparer la requête SQL avec filtre sur le département
$sql = "
    SELECT a.id, u.nom, u.prenom, ue.code_module, ue.intitule_module, a.status, a.type_enseignement
    FROM affectations a
    JOIN users u ON a.id_user = u.id
    JOIN `unités_enseignement` ue ON a.id_ue = ue.id
    WHERE ue.department_id = ?
    ORDER BY a.status ASC, u.nom ASC, ue.code_module ASC
";

$stmt = $connection->prepare($sql);
if (!$stmt) {
    die("Erreur de préparation : " . $connection->error);
}

$stmt->bind_param("i", $department_id);
$stmt->execute();
$affectations = $stmt->get_result();

if (!$affectations) {
    die("Erreur lors de la récupération des affectations : " . $connection->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des affectations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border: none; box-shadow: 0 0 12px rgba(0,0,0,0.08); }
        .table-hover tbody tr:hover { background-color: #f1f1f1; }
    </style>
    <script>
        function confirmerAction(action, statutActuel) {
            let message = "Voulez-vous vraiment " + action + " cette affectation ?";
            if (statutActuel === "1" && action === "valider") {
                message = "Cette affectation est déjà validée. Voulez-vous la revalider ?";
            } else if (statutActuel === "-1" && action === "refuser") {
                message = "Cette affectation est déjà refusée. Voulez-vous la refuser à nouveau ?";
            } else if (statutActuel === "1" && action === "refuser") {
                message = "Cette affectation est validée. Voulez-vous vraiment la refuser ?";
            } else if (statutActuel === "-1" && action === "valider") {
                message = "Cette affectation est refusée. Voulez-vous vraiment la valider ?";
            }
            return confirm(message);
        }
    </script>
</head>
<body>
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "../sidebar.php" ?>
        <div class="main-wrapper">
            <?php include "../navbar.php" ?><br>
            <div class="container my-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary"><i class="bi bi-clipboard-check"></i> Gestion des Affectations</h2>
                    <a href="index.php" class="btn btn-outline-secondary">↩ Retour</a>
                </div>

                <?php if (!empty($_SESSION['message'])) : ?>
                    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="card p-4">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Professeur</th>
                                <th>Unité</th>
                                <th>Type d'enseignement</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($affectations->num_rows > 0) : ?>
                                <?php while ($aff = $affectations->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aff['nom'] . ' ' . $aff['prenom']) ?></td>
                                        <td><?= htmlspecialchars($aff['code_module'] . ' - ' . $aff['intitule_module']) ?></td>
                                        <td><?= htmlspecialchars($aff['type_enseignement']) ?></td>
                                        <td>
                                            <?php
                                            switch ((int)$aff['status']) {
                                                case 1:
                                                    echo "<span class='badge bg-success'><i class='bi bi-check-circle'></i> Validée</span>";
                                                    break;
                                                case -1:
                                                    echo "<span class='badge bg-danger'><i class='bi bi-x-circle'></i> Refusée</span>";
                                                    break;
                                                default:
                                                    echo "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> En attente</span>";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="traiter_validation.php?id=<?= $aff['id'] ?>&action=valider"
                                               class="btn btn-success btn-sm"
                                               onclick="return confirmerAction('valider', '<?= $aff['status'] ?>')">
                                                <i class="bi bi-check-circle"></i> Valider
                                            </a>
                                            <a href="traiter_validation.php?id=<?= $aff['id'] ?>&action=refuser"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirmerAction('refuser', '<?= $aff['status'] ?>')">
                                                <i class="bi bi-x-circle"></i> Refuser
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucune affectation trouvée.</td>
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
