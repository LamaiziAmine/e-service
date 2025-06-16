<?php 
$currentPage = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de la session avant toute chose
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cordonnateur') {
    header("Location: ../login.php");
    exit; 
}

// RÉCUPÉRATION DES INFORMATIONS UTILES DE LA SESSION
$cordonnateur_id = $_SESSION['user_id'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug: Vérifier si l'utilisateur existe
$debug_sql = "SELECT id, nom, prenom, department_id FROM users WHERE id = ?";
$debug_stmt = $conn->prepare($debug_sql);
$debug_stmt->bind_param("i", $cordonnateur_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
$user_info = $debug_result->fetch_assoc();
$debug_stmt->close();

// Requête modifiée pour regrouper par enseignant et UE avec calcul des volumes horaires
$sql = "SELECT 
            a.id_ue,
            a.id_user,
            ue.code_module,
            ue.intitule_module,
            ue.semestre,
            ue.filiere,
            GROUP_CONCAT(DISTINCT ti.type ORDER BY ti.type SEPARATOR '/') AS types_intervention,
            u.nom AS nom_enseignant,
            u.prenom AS prenom_enseignant,
            COUNT(a.id) as nb_affectations,
            -- Calcul du volume horaire basé sur les types d'intervention
            SUM(
                CASE 
                    WHEN ti.type = 'Cours' THEN COALESCE(ue.V_h_cours, 0)
                    WHEN ti.type = 'TD' THEN COALESCE(ue.V_h_TD, 0) 
                    WHEN ti.type = 'TP' THEN COALESCE(ue.V_h_TP, 0)
                    WHEN ti.type = 'Autre' THEN COALESCE(ue.V_h_Autre, 0)
                    WHEN ti.type = 'Evaluation' THEN COALESCE(ue.V_h_Evaluation, 0)
                    ELSE 0
                END
            ) as volume_horaire_total
            
        FROM affectations a
        LEFT JOIN unités_ensignement ue ON a.id_ue = ue.id 
        LEFT JOIN types_intervention ti ON a.id_type = ti.id
        LEFT JOIN users u ON a.id_user = u.id
        WHERE a.id_ue IS NOT NULL AND a.id_user IS NOT NULL
        GROUP BY a.id_ue, a.id_user, ue.code_module, ue.intitule_module, ue.semestre, ue.filiere,
                 u.nom, u.prenom
        ORDER BY u.nom, u.prenom, ue.code_module";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Compter le nombre total d'affectations regroupées
$count_sql = "SELECT COUNT(DISTINCT CONCAT(a.id_ue, '-', a.id_user)) as total_grouped 
              FROM affectations a 
              WHERE a.id_ue IS NOT NULL AND a.id_user IS NOT NULL";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_grouped = $count_result->fetch_assoc()['total_grouped'];
$count_stmt->close();

// Compter le nombre total d'affectations individuelles
$total_sql = "SELECT COUNT(*) as total FROM affectations";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_affectations = $total_result->fetch_assoc()['total'];
$total_stmt->close();

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Modules Assurés</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="/e-service/css/style.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f4f7fc;
    }

    .main-title {
      color: #2c3e50;
      text-align: left;
      margin-bottom: 25px;
      margin-left: 2.5%;
      font-weight: 700;
    }

    .debug-info {
      background-color: #e8f4f8;
      border: 1px solid #bee5eb;
      border-radius: 5px;
      padding: 10px;
      margin: 20px 2.5%;
      font-size: 14px;
      color: #0c5460;
    }

    .table-style {
      width: 95%;
      margin: auto;
      border-collapse: collapse;
      text-align: left;
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .table-style thead th {
      background-color: rgb(25, 60, 255);
      color: rgb(255, 255, 255);
      padding: 16px 20px;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #e3e6f0;
    }

    .table-style tbody td {
      padding: 16px 20px;
      color: #5a6a7e;
      border-bottom: 1px solid #eef2f7;
      vertical-align: middle;
    }

    .table-style tbody tr:last-child td {
      border-bottom: none;
    }

    .table-style tbody tr:hover {
      background-color: #f8f9fc;
    }

    .intervention-pill {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 500;
      background-color: #e7f3ff;
      color: #007bff;
    }

    .intervention-multiple {
      background-color: #e8f5e8;
      color: #28a745;
    }

    .no-data {
      text-align: center; 
      padding: 40px;
      color: #6c757d;
      font-style: italic;
    }

    .enseignant-name {
      font-weight: 600;
      color: #2c3e50;
    }

    .module-code {
      font-weight: 500;
      color: #495057;
    }
  </style>
</head>

<body>
  <div class="layer"></div>
  <!-- ! Body -->
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">
    <!-- ! Sidebar -->
    <?php include "sidebar.php" ?>
    <div class="main-wrapper">
      <!-- ! Main nav -->
      <?php include "navbar.php" ?>
      <!-- ! Main -->
      <main class="main users" id="skip-target">
        <div class="container">
          <h2 class="main-title">Liste des affectations par enseignant</h2>

          <table class="table-style">
            <thead>
              <tr>
                <th>Enseignant</th>
                <th>Code Module</th>
                <th>Intitulé</th>
                <th>Semestre</th>
                <th>Filière</th>
                <th>Types d'Intervention</th>
                <th>Volume Horaire Total</th>
                <th>Nb. Interventions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                  // Utiliser la somme calculée des volumes horaires des affectations
                  $vh_total = $row['volume_horaire_total'] ?? 0;
                  
                  // Vérifier si l'enseignant a plusieurs types d'interventions
                  $types_array = explode('/', $row['types_intervention'] ?? '');
                  $is_multiple = count($types_array) > 1;
                ?>
                  <tr>
                    <td class="enseignant-name">
                      <?php 
                      $enseignant = '';
                      if (!empty($row['nom_enseignant']) && !empty($row['prenom_enseignant'])) {
                          $enseignant = htmlspecialchars($row['nom_enseignant'] . ' ' . $row['prenom_enseignant']);
                      } else {
                          $enseignant = 'Non assigné';
                      }
                      echo $enseignant;
                      ?>
                    </td>
                    <td class="module-code"><?= htmlspecialchars($row['code_module'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['intitule_module'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['semestre'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['filiere'] ?? 'N/A') ?></td>
                    <td>
                      <?php if (!empty($row['types_intervention'])): ?>
                        <span class="intervention-pill <?= $is_multiple ? 'intervention-multiple' : '' ?>">
                          <?= htmlspecialchars($row['types_intervention']) ?>
                        </span>
                      <?php else: ?>
                        <span class="intervention-pill">Non spécifié</span>
                      <?php endif; ?>
                    </td>
                    <td><strong><?= $vh_total ?> heures</strong></td>
                    <td>
                      <span class="intervention-pill">
                        <?= $row['nb_affectations'] ?> intervention<?= $row['nb_affectations'] > 1 ? 's' : '' ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="no-data">
                    Aucune affectation trouvée dans la base de données.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>

  <!-- Chart library -->
  <script src="/e-service/plugins/chart.min.js"></script>
  <!-- Icons library -->
  <script src="/e-service/plugins/feather.min.js"></script>
  <!-- Custom scripts -->
  <script src="/e-service/js/script.js"></script>

  <?php
  $stmt->close();
  $conn->close();
  ?>
</body>
</html>