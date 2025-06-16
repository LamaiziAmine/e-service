<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<?php
session_start();

$host = 'localhost'; $db = 'projet_web'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erreur de connexion : " . $conn->connect_error); }
$department_id = $_SESSION['user_department'];

// 2. FILTRES ET STATISTIQUES
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Compter les modules
$stmt_total = $conn->prepare("SELECT COUNT(*) FROM unités_ensignement WHERE department_id = ?");
$stmt_total->bind_param("i", $department_id); $stmt_total->execute(); $stmt_total->bind_result($mod_count);
$stmt_total->fetch(); $stmt_total->close();

$stmt_assigned = $conn->prepare("SELECT COUNT(*) FROM unités_ensignement WHERE department_id = ? AND responsable IS NOT NULL");
$stmt_assigned->bind_param("i", $department_id); $stmt_assigned->execute(); $stmt_assigned->bind_result($assigned_count);
$stmt_assigned->fetch(); $stmt_assigned->close();

// 3. REQUÊTE PRINCIPALE OPTIMISÉE
$query = "SELECT ue.*, u.prenom AS resp_prenom, u.nom AS resp_nom FROM unités_ensignement ue LEFT JOIN users u ON ue.responsable = u.id WHERE ue.department_id = ?";
$params = [$department_id]; $types = "i";
// ... (logique de filtre inchangée) ...
if ($search) { $query .= " AND (ue.code_module LIKE ? OR ue.intitule_module LIKE ?)"; $search_param = "%$search%"; array_push($params, $search_param, $search_param); $types .= "ss"; }
if ($semester_filter) { $query .= " AND ue.semestre = ?"; $params[] = $semester_filter; $types .= "s"; }
if ($status_filter === 'assigned') { $query .= " AND ue.responsable IS NOT NULL"; } elseif ($status_filter === 'unassigned') { $query .= " AND ue.responsable IS NULL"; }

$stmt = $conn->prepare($query);
if ($stmt) { $stmt->bind_param($types, ...$params); $stmt->execute(); $result = $stmt->get_result(); }
else { die("Erreur de requête : " . $conn->error); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Chef Département</title>
    <!-- On ne garde QUE la feuille de style du template -->
    <link rel="stylesheet" href="/e-service/css/style.min.css" />
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    
    <!-- Styles personnalisés pour le dashboard -->
    <style>
        .main-content { padding: 20px; }
        .dashboard-header { margin-bottom: 2rem; }
        
        /* Style pour les cartes de résumé */
        .stat-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-left: 5px solid;
        }
        .stat-card h3 { font-size: 1rem; color: #555; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { font-size: 2.5rem; font-weight: 700; margin: 0; }
        
        .card-total { border-color: #007bff; } .card-total p { color: #007bff; }
        .card-assigned { border-color: #28a745; } .card-assigned p { color: #28a745; }
        .card-unassigned { border-color: #dc3545; } .card-unassigned p { color: #dc3545; }

        /* Style pour le conteneur du graphique et du tableau */
        .data-container {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .data-container-header { font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem; }
        .chart-container { position: relative; height: 280px; margin-bottom: 2rem; }

        /* Style pour le formulaire de filtre */
        .filter-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
        }
        .filter-form input, .filter-form select {
            border: 1px solid #dce1e7;
            border-radius: 5px;
            padding: 0.75rem;
        }
        .filter-form button {
            background-color: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        /* Style pour le tableau */
        .modules-table { width: 100%; border-collapse: collapse; }
        .modules-table th, .modules-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        .modules-table thead th { background-color: #f8f9fa; font-weight: 600; }
        .badge-danger { background-color: #dc3545; color: white; padding: 0.3em 0.6em; border-radius: 0.25rem; font-size: 0.8em; }
    </style>
</head>
<body>
<div class="layer"></div>
<div class="page-flex">
    <?php include "coordonnateur/sidebar.php" ?> 
    <div class="main-wrapper">
        <?php include "coordonnateur/navbar.php" ?><br>

        <main class="main-content">
            <div class="dashboard-header">
                <h2 class="main-title">Dashboard du coordonnateur</h2>
            </div>
            
            <!-- Filtres -->
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                <select name="semester">
                    <option value="">Tous les semestres</option>
                    <?php foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6'] as $sem): ?>
                    <option value="<?= $sem ?>" <?= $semester_filter === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="assigned" <?= $status_filter === 'assigned' ? 'selected' : '' ?>>Assignés</option>
                    <option value="unassigned" <?= $status_filter === 'unassigned' ? 'selected' : '' ?>>Non assignés</option>
                </select>
                <button type="submit">Filtrer</button>
            </form>

            <!-- Cartes de résumé -->
            <div class="stat-cards-container">
                <div class="stat-card card-total">
                    <h3>Modules Totaux</h3>
                    <p><?= $mod_count ?></p>
                </div>
                <div class="stat-card card-assigned">
                    <h3>Modules Assignés</h3>
                    <p><?= $assigned_count ?></p>
                </div>
                <div class="stat-card card-unassigned">
                    <h3>Modules Non Assignés</h3>
                    <p><?= $mod_count - $assigned_count ?></p>
                </div>
            </div>

            <!-- Graphique et Tableau -->
            <div class="data-container">
                <h3 class="data-container-header">Répartition des Modules</h3>
                <div class="chart-container">
                    <canvas id="assignChart"></canvas>
                </div>

                <h3 class="data-container-header">Liste des Modules</h3>
                <table class="modules-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitulé du Module</th>
                            <th>Semestre</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['code_module']) ?></td>
                                    <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                    <td><?= htmlspecialchars($row['semestre']) ?></td>
                                    <td>
                                        <?php if ($row['responsable']): ?>
                                            <?= htmlspecialchars($row['resp_prenom'] . ' ' . $row['resp_nom']) ?>
                                        <?php else: ?>
                                            <span class="badge-danger">Non assigné</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 20px;">Aucun module trouvé pour ce département.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
<script>
    feather.replace();

    const ctx = document.getElementById('assignChart').getContext('2d');
    const assignChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Assignés', 'Non assignés'],
            datasets: [{
                label: 'Modules',
                data: [<?= $assigned_count ?>, <?= $mod_count - $assigned_count ?>],
                backgroundColor: ['#28a745', '#dc3545'],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, boxWidth: 15 } }
            }
        }
    });
</script>
</body>
</html>