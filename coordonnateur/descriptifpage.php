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
  <title>Ajouter descriptif</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="/e-service/css/style.min.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

    .auth-wrapper {
      display: flex;
      align-items: center;
      flex-direction: column;
      min-height: 100vh;
    }

    .auth-box {
      background-color: #fff;
      border-radius: 30px;
      box-shadow: 0 5px 15px rgba(255, 255, 255, 0.65);
      position: relative;
      overflow: hidden;
      width: 908px;
      max-width: 100%;
      min-height: 800px;
    }

    .auth-box p {
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0.3px;
      margin: 20px 0;
    }

    .auth-box span {
      font-size: 12px;
    }

    .auth-box a {
      color: #333;
      font-size: 13px;
      text-decoration: none;
      margin: 15px 0 10px;
    }

    .auth-box button {
      background-color: rgb(41, 13, 255);
      color: #fff;
      font-size: 12px;
      padding: 10px 45px;
      border: 2px solid transparent;
      border-radius: 8px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-top: 10px;
      cursor: pointer;
    }

    .auth-box button.auth-hidden-btn {
      background-color: transparent;
      border-color: #fff;
    }

    .form-group {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .form-group label {
      width: 150px;
      font-weight: bold;
    }

    .form-group input,
    .form-group select {
      flex: 1;
      padding: 5px;
    }

    fieldset {
      margin-top: 10px;
      padding: 10px;
      border-left: none;
      border-right: none;
    }

    .auth-box form {
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 0 40px;
      height: 100%;
    }

    .auth-form-container {
      position: absolute;
      top: 0;
      height: 100%;
      transition: all 0.6s ease-in-out;
    }

    .auth-saisie {
      left: 0;
      width: 50%;
      opacity: 0;
      z-index: 1;
    }

    .auth-box.active .auth-saisie {
      transform: translateX(100%);
      opacity: 1;
      z-index: 5;
      animation: auth-move 0.6s;
    }

    .auth-importation {
      left: 0;
      width: 50%;
      z-index: 2;
    }

    .auth-box.active .auth-importation {
      transform: translateX(100%);
    }

    @keyframes auth-move {

      0%,
      49.99% {
        opacity: 0;
        z-index: 1;
      }

      50%,
      100% {
        opacity: 1;
        z-index: 5;
      }
    }

    .auth-toggle-container {
      position: absolute;
      top: 0;
      left: 50%;
      width: 50%;
      height: 100%;
      overflow: hidden;
      transition: all 0.6s ease-in-out;
      border-radius: 20px;
      z-index: 1000;
    }

    .auth-box.active .auth-toggle-container {
      transform: translateX(-100%);
      border-radius: 20px;
    }

    .auth-toggle {
      background-color: rgba(4, 8, 255, 0.95);
      height: 100%;
      color: #fff;
      position: relative;
      left: -100%;
      width: 200%;
      transform: translateX(0);
      transition: all 0.6s ease-in-out;
    }

    .auth-box.active .auth-toggle {
      transform: translateX(50%);
    }

    .auth-toggle-panel {
      position: absolute;
      width: 50%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 0 30px;
      text-align: center;
      top: 0;
      transform: translateX(0);
      transition: all 0.6s ease-in-out;
    }

    .auth-toggle-right {
      right: 0;
      transform: translateX(0);
    }

    .auth-box.active .auth-toggle-right {
      transform: translateX(200%);
    }

    .auth-toggle-left {
      transform: translateX(-200%);
    }

    .auth-box.active .auth-toggle-left {
      transform: translateX(0);
    }


    #ajouterBtn:disabled {
      background-color: #ccc;
      color: #666;
      cursor: not-allowed;
    }

    #ajouterBtn {
      background-color: rgb(42, 235, 21);
      /* bleu */
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

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
      <?php include "navbar.php" ?>
      <!-- ! Main -->
      <main class="main users chart-page" id="skip-target">
        <h1 style="margin-left: 20px;" class="main-title">Création d’un descriptif:</h1>
        <div class="auth-wrapper">
          <div class="auth-box" id="auth-box">
            <!-- Formulaire de descriptif -->
            <div class="auth-form-container auth-saisie">
              <form>
                <h1>Importer un descriptif</h1><br>
                <span>importer le fichier qui contient le descriptif</span><br>
                <input type="file" accept=".csv, .xlsx" />
                <button disabled>Importer</button>
              </form>
            </div>
            <div class="auth-form-container auth-importation">
              <form id="moduleForm" method="POST" action="enregistrer.php">
                <h1>Saisir le descriptif </h1><br>
                <span>Saisissez les informations nécessaires</span><br>
                <div class="form-group">
                  <label>Année Universitaire :</label>
                  <select name="annee_univ">
                    <option value="">-----Choisir l'année-----</option>
                    <option>2024/2025</option>
                    <option>2025/2026</option>
                    <option>2026/2027</option>
                  </select><br>
                </div>
                <div class="form-group">
                  <label>Code Module :</label>
                  <input type="text" name="code_module"><br>
                </div>
                <div class="form-group">
                  <label>Intitulé :</label>
                  <input type="text" name="intitule"><br>
                </div>

                <div class="form-group">
                  <label>Semestre :</label>
                  <select name="semestre">
                    <option value="">----Choisir la semestre----</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                    <option value="S4">S4</option>
                    <option value="S5">S5</option>
                  </select><br>
                </div>

                <div class="form-group">
                  <label>Filière :</label>
                  <input type="text" name="filiere"><br>
                </div>

                <fieldset>
                  <legend>Volume horaire: </legend>
                  <div class="form-group">
                    <label>Cours :</label>
                    <input type="number" name="cours" min="0"><br>
                  </div>
                  <div class="form-group">
                    <label>TD :</label>
                    <input type="number" name="td" min="0"><br>
                  </div>
                  <div class="form-group">
                    <label>TP :</label>
                    <input type="number" name="tp" min="0"><br>
                  </div>
                  <div class="form-group">
                    <label>Autre :</label>
                    <input type="number" name="autre" min="0"><br>
                  </div>
                  <div class="form-group">
                    <label>Évaluation :</label>
                    <input type="number" name="evaluation" min="0"><br>
                  </div>
                </fieldset><br>
                <div class="form-group">
                  <label>Responsable du module :</label>
                  <input type="text" name="responsable"><br>
                </div>
                <div style="display: flex; gap: 35px;">
                  <button type="submit" id="ajouterBtn" disabled>Ajouter</button>
                  <button type="submit">Enregistrer</button>
                </div>
              </form>
            </div>
            <div class="auth-toggle-container">
              <div class="auth-toggle">
                <div class="auth-toggle-panel auth-toggle-left">
                  <h1 style="color: white;">Saisir un descriptif</h1>
                  <p>Commencez à remplir les informations de votre filière</p>
                  <button class="auth-hidden-btn" id="auth-importation">Saisir</button>
                </div>
                <div class="auth-toggle-panel auth-toggle-right">
                  <h1 style="color: white;">Importation de discriptif</h1>
                  <p>Importer le descriptif de votre filière</p>
                  <button class="auth-hidden-btn" id="auth-saisie">Importer</button>
                </div>
              </div>
            </div>
          </div>
        </div><br><br>
        <!-- Tableau لعرض البيانات -->
        <h2 style="margin-left: 20px;" class="main-title">Le Descriptif Actuel:</h2>

        <table class="table-style">
          <thead>
            <tr>
              <th rowspan="2">Année Universitaire</th>
              <th rowspan="2">Code Module</th>
              <th rowspan="2">Intitulé</th>
              <th rowspan="2">Semestre</th>
              <th rowspan="2">Filière</th>
              <th colspan="5" style="text-align:center;">Volume Horaire</th>
              <th rowspan="2">Responsable</th>
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
                echo "<td>" . $row['annee_univ'] . "</td>";
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

      </main>
    </div>
  </div>
  </div>
  <script>
    const form = document.getElementById('moduleForm');
    const ajouterBtn = document.getElementById('ajouterBtn');

    function checkFormFields() {
      const inputs = form.querySelectorAll('input, select');
      let allFilled = true;

      inputs.forEach(input => {
        if (input.type !== 'number' && input.value.trim() === '') {
          allFilled = false;
        }
      });

      ajouterBtn.disabled = !allFilled;
    }

    form.querySelectorAll('input, select').forEach(element => {
      element.addEventListener('input', checkFormFields);
      element.addEventListener('change', checkFormFields);
    });
  </script>
  <script>
    const authBox = document.getElementById('auth-box');
    const authImportationBtn = document.getElementById('auth-saisie');
    const authSaisieBtn = document.getElementById('auth-importation');
    authSaisieBtn.addEventListener('click', () => {
      authBox.classList.remove("active");
    });
    authImportationBtn.addEventListener('click', () => {
      authBox.classList.add("active");
    });


  </script>
  <!-- Chart library -->
  <script src="/e-service/plugins/chart.min.js"></script>
  <!-- Icons library -->
  <script src="/e-service/plugins/feather.min.js"></script>
  <!-- Custom scripts -->
  <script src="/e-service/js/script.js"></script>
</body>
<?php
if (isset($_SESSION['message'])):
  ?>
  <div
    style="padding: 15px; margin: 15px; border-radius: 5px; background-color: <?= $_SESSION['msg_type'] === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $_SESSION['msg_type'] === 'success' ? '#155724' : '#721c24' ?>;">
    <?= $_SESSION['message']; ?>
  </div>
  <?php
  unset($_SESSION['message']);
  unset($_SESSION['msg_type']);
endif;
?>



<?php
$conn->close();
?>

</html>