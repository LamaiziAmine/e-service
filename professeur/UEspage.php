<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// 1. DÉMARRER LA SESSION
// Doit être la toute première chose pour accéder à $_SESSION.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// 2. VÉRIFICATION DE SÉCURITÉ ET DES RÔLES
// Si l'utilisateur n'est pas connecté OU si son rôle n'est pas 'professeur',
// on le redirige vers la page de connexion.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
  header("Location: ../login.php");
  exit;
}

// 3. RÉCUPÉRATION DES INFORMATIONS DE L'UTILISATEUR
// On stocke les informations de la session dans des variables claires.
$professeur_id = $_SESSION['user_id'];
$professeur_nom_complet = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Professeur';


// 4. CONNEXION À LA BASE DE DONNÉES
// Ce bloc établit la connexion pour le reste de la page.
$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);


$sql = "SELECT * FROM unités_ensignement";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> unités d'enseignement</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="/e-service/css/style.min.css">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.css" rel="stylesheet">

  <style>
     .table-container {
            margin: 20px auto;
            padding: 20px;
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table-style {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            text-align: left;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .table-style th, .table-style td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-style thead th {
            background: rgb(25, 60, 255);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-style tbody tr {
            transition: all 0.3s ease;
        }

        .table-style tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table-style tbody tr:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(30, 58, 138, 0.05));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .code-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .filiere-badge {
            border: 2px solid #3b82f6;
            color: black;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 1em;
            font-weight: 600;
        }

        .semestre-badge {
           border: 2px solid #3b82f6;
            color: black;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 1em;
            font-weight: 600;
        }

        .total-hours {
           border: 2px solid #3b82f6;
            color: black;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1em;
            text-align: center;
            min-width: 60px;
            display: inline-block;
        }
  </style>
</head>

<body>
  <div class="layer"></div>
  <!-- ! Body -->
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">
    <!-- ! Sidebar -->
    <?php include "sidebar_prof.php" ?>
    <div class="main-wrapper">
      <!-- ! Main nav -->
      <?php include "../coordonnateur/navbar.php" ?><br>
      <!-- ! Main -->
      <h2 style="margin-left: 20px;" class="main-title"> les unités d'enseignement:</h2>

      <!-- Tableau -->
      <div class="table-container">
        <table class="table-style">
          <thead>
            <tr>
              <th><i class="fas fa-code"></i> Code</th>
              <th><i class="fas fa-book"></i> Intitulé</th>
              <th><i class="fas fa-calendar"></i> Semestre</th>
              <th><i class="fas fa-university"></i> Filière</th>
              <th><i class="fas fa-clock"></i> Volume Horaire Total</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $total_hours = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                echo "<tr>";
                echo "<td><span class='code-badge'>" . htmlspecialchars($row['code_module']) . "</span></td>";
                echo "<td><strong>" . htmlspecialchars($row['intitule_module']) . "</strong></td>";
                echo "<td><span class='semestre-badge'>" . htmlspecialchars($row['semestre']) . "</span></td>";
                echo "<td><span class='filiere-badge'>" . htmlspecialchars($row['filiere']) . "</span></td>";
                echo "<td><span class='total-hours'>" . $total_hours . "h</span></td>";
              }
            } else {
              echo "<tr><td colspan='6' class='no-data'>
                                        <div>
                                            <i class='fas fa-inbox'></i><br>
                                            Aucune unité d'enseignement trouvée<br>
                                            <small>Commencez par ajouter votre première UE</small>
                                        </div>
                                      </td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- Chart library -->
  <script src="/e-service/plugins/chart.min.js"></script>
  <!-- Icons library -->
  <script src="/e-service/plugins/feather.min.js"></script>
  <!-- Custom scripts -->
  <script src="/e-service/js/script.js"></script>
</body>

</html>