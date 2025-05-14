<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM unités_ensignement";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion UEs</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="/e-service/css/style.min.css">

  <style>
  .table-style {
    width: 95%;
    margin: auto;
    border-collapse: collapse;
    font-family: 'Arial', sans-serif;
    color: #333;
    text-align: left;
    border: 2px solid #1e3a8a;
    border-radius: 12px;
    overflow: hidden; /* pour appliquer le border-radius */
  }

  .table-style th, .table-style td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
  }

  .table-style thead th {
    background-color: rgb(7, 8, 83);
    color: white;
  }

  .table-style tr:nth-child(even) {
    background-color: #fff;
  }

  .table-style tr:hover {
    background-color: rgba(0, 42, 255, 0.14);
  }

  h2 {
    margin-bottom: 20px;
    color: #333;
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
       <?php include "navbar.php"?><br>
       <!-- ! Main -->
        <h2 style="margin-left: 20px;" class="main-title"> les unités d'enseignement:</h2>

        <table class="table-style">
          <thead>
            <tr>
              <th rowspan="2">Code Module</th>
              <th rowspan="2">Intitulé</th>
              <th rowspan="2">Semestre</th>
              <th rowspan="2">Filière</th>
              <th colspan="5" style="text-align:center;">Volume Horaire</th>
              <th rowspan="2">Responsable</th>
              <th rowspan="2">Actions</th>
            </tr>
            <tr>
              <th>Cours</th>
              <th>TD</th>
              <th>TP</th>
              <th>Autre</th>
              <th>Évaluation</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['code_module'] . "</td>";
                echo "<td>" . $row['intitule_module'] . "</td>";
                echo "<td>" . $row['semestre'] . "</td>";
                echo "<td>" . $row['filiere'] . "</td>";
                echo "<td>" . $row['V_h_cours'] . "</td>";
                echo "<td>" . $row['V_h_TD'] . "</td>";
                echo "<td>" . $row['V_h_TP'] . "</td>";
                echo "<td>" . $row['V_h_Autre'] . "</td>";
                echo "<td>" . $row['V_h_Evaluation'] . "</td>";
                echo "<td>" . $row['responsable'] . "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='12'>Aucune donnée trouvée</td></tr>";
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