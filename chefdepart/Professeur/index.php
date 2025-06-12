<?php
include '../config.php';

$result = $connection->query("SELECT * FROM users WHERE role = 'professeur' ORDER BY nom, prenom");
if (!$result) {
    die("Erreur lors de la récupération des professeurs : " . $connection->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Liste des professeurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="/elegant/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/elegant/css/style.min.css">
    <style>
        body {
            background: #f5f8fa;
        }
        .table-hover tbody tr:hover {
            background-color: #f0f0f0;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="bi bi-person-lines-fill"></i> Liste des Professeurs</h2>
        <a href="index.php" class="btn btn-outline-secondary">↩ Retour</a>
    </div>

    <div class="card p-4">
        <div class="mb-3">
            <input type="search" class="form-control" id="searchInput" placeholder="🔍 Rechercher un professeur..." aria-label="Rechercher un professeur" />
        </div>

        <table class="table table-hover table-bordered">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Nom complet</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody id="profTable">
                <?php while ($prof = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?= (int)$prof['id'] ?></td>
                    <td><?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']) ?></td>
                    <td><?= htmlspecialchars($prof['email']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const searchValue = this.value.trim().toLowerCase();
    document.querySelectorAll('#profTable tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>
<script src="/elegant/plugins/chart.min.js"></script>
    <script src="/elegant/plugins/feather.min.js"></script>
    <script src="/elegant/js/script.js"></script>
</body>
</html>
