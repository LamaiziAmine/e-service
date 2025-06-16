<?php
error_reporting(E_ALL); // Pour le développement
ini_set('display_errors', 1); // Pour le développement

session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Gérer l'ajout d'un module (AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'ajouter_module') {
  // Récupérer les données du formulaire
  $annee_univ = $_POST['annee_univ'];
  $code_module = $_POST['code_module'];
  $intitule = $_POST['intitule'];
  $semestre = $_POST['semestre'];
  $filiere = $_POST['filiere']; // Cette variable est déjà récupérée, c'est bien.
  $cours = $_POST['cours'];
  $td = $_POST['td'];
  $tp = $_POST['tp'];
  $autre = $_POST['autre'];
  $evaluation = $_POST['evaluation'];
  $responsable = $_POST['responsable'];

  // Vérifier si le nombre de modules pour ce semestre ET CETTE FILIERE n'excède pas 7
  // MODIFICATION ICI: Ajout de la condition sur la filière
  $sql_check = "SELECT COUNT(*) as count FROM unités_ensignement WHERE semestre = ? AND filiere = ?";
  $stmt_check = $conn->prepare($sql_check);
  // MODIFICATION ICI: Ajout de $filiere au bind_param et modification du type de paramètre "s" -> "ss"
  $stmt_check->bind_param("ss", $semestre, $filiere);
  $stmt_check->execute();
  $result_check = $stmt_check->get_result();
  $row_check = $result_check->fetch_assoc();
  $stmt_check->close(); // Bonne pratique de fermer le statement ici

  if ($row_check['count'] >= 7) {
    // Renvoyer une réponse d'erreur au format JSON
    header('Content-Type: application/json');
    // MODIFICATION OPTIONNELLE: Message d'erreur plus précis
    echo json_encode(['status' => 'error', 'message' => 'Le nombre maximum de 7 modules par semestre pour la filière "' . htmlspecialchars($filiere) . '" a été atteint.']);
    exit();
  }

  // Insérer les données dans la base de données
  $sql = "INSERT INTO unités_ensignement (annee_univ, code_module, intitule_module, semestre, filiere, V_h_cours, V_h_TD, V_h_TP, V_h_Autre, V_h_Evaluation, responsable)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conn->prepare($sql);
  // Assurez-vous que les types ici correspondent à votre base de données (s pour string, i pour integer)
  // Si V_h_... sont des entiers, les 'i' sont corrects.
  $stmt->bind_param("sssssiiiiis", $annee_univ, $code_module, $intitule, $semestre, $filiere, $cours, $td, $tp, $autre, $evaluation, $responsable);

  $result = $stmt->execute();

  if ($result) {
    $id = $conn->insert_id;
    if ($id > 0) {
      $sql_select = "SELECT * FROM unités_ensignement WHERE id = ?"; // Assurez-vous que le nom de la PK est 'id'
      $stmt_select = $conn->prepare($sql_select);
      $stmt_select->bind_param("i", $id);
      $stmt_select->execute();
      $result_select = $stmt_select->get_result();

      if ($result_select && $new_row = $result_select->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Module ajouté avec succès!', 'data' => $new_row]);
      } else {
        header('Content-Type: application/json');
        $error_message = 'Module ajouté (ID: ' . $id . ') mais erreur lors de la récupération des données.';
        if (!$result_select && isset($stmt_select)) { // Vérifier si stmt_select a été initialisé
          $error_message .= ' Erreur stmt_select: ' . $stmt_select->error;
        } elseif (!$new_row) {
          $error_message .= ' Aucune ligne trouvée pour l\'ID récupéré.';
        }
        echo json_encode(['status' => 'error', 'message' => $error_message, 'inserted_id' => $id]);
      }
      if (isset($stmt_select))
        $stmt_select->close();
    } else {
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'error',
        'message' => 'Module ajouté, mais impossible de récupérer l\'ID inséré. Vérifiez la configuration AUTO_INCREMENT de la table.',
        'sql_error' => $stmt->error
      ]);
    }
  } else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du module: ' . $stmt->error, 'sql_errno' => $stmt->errno]);
  }
  $stmt->close();
  exit(); // Important
}

// Récupérer les données pour le tableau
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
      overflow: hidden;
      /* pour appliquer le border-radius */
    }

    .table-style th,
    .table-style td {
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

    @keyframes slideOut {
      0% {
        transform: translateY(0);
        opacity: 1;
      }

      100% {
        transform: translateY(-20px);
        opacity: 0;
      }
    }

    .alert.slide-out {
      animation: slideOut 0.5s forwards;
    }

    /* Style pour les alertes */
    .alert {
      width: 94%;
      margin: 10px auto;
      padding: 12px;
      border-radius: 5px;
      display: flex;
      align-items: center;
      font-weight: 500;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert i {
      margin-right: 10px;
      font-size: 18px;
    }

    /* Notification toast */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: white;
      color: #333;
      border-left: 5px solid;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      padding: 15px 20px;
      z-index: 9999;
      display: flex;
      align-items: center;
      opacity: 0;
      transform: translateY(-50px);
      transition: all 0.5s ease;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .toast.success {
      border-left-color: #28a745;
    }

    .toast.error {
      border-left-color: #dc3545;
    }

    .toast i {
      margin-right: 10px;
      font-size: 24px;
    }

    .toast.success i {
      color: #28a745;
    }

    .toast.error i {
      color: #dc3545;
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
        <h1 style="margin-left: 20px;" class="main-title">Création d'un descriptif:</h1>
        <p style="margin-left: 15px; margin-bottom: 10px;">
          Veuillez d'abord <span style="font-weight:bold; color:#007bff;">« Ajouter »</span> l'ensemble des unités
          d'enseignement, puis cliquer sur
          <span style="font-weight:bold; color:#28a745;">« Enregistrer »</span> afin de soumetre le descriptif.
        </p><br>

        <!-- Toast de notification -->
        <div id="toast" class="toast">
          <i class='bx bx-check-circle'></i>
          <span id="toast-message"></span>
        </div>

        <!-- Message d'alerte -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <i class="fas fa-<?= $_SESSION['msg_type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?php
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
            ?>
          </div>
        <?php endif ?>
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
              <form id="moduleForm" method="POST" action=" ">
                <h1>Saisir le descriptif </h1><br>
                <span>Saisissez les informations nécessaires</span><br>
                <div class="form-group">
                  <label>Année Universitaire :</label>
                  <select name="annee_univ" required>
                    <option value="">-----Choisir l'année-----</option>
                    <option>2024/2025</option>
                    <option>2025/2026</option>
                    <option>2026/2027</option>
                  </select><br>
                </div>
                <div class="form-group">
                  <label>Code Module :</label>
                  <input type="text" name="code_module" required><br>
                </div>
                <div class="form-group">
                  <label>Intitulé :</label>
                  <input type="text" name="intitule" required><br>
                </div>

                <div class="form-group">
                  <label>Semestre :</label>
                  <select name="semestre" required>
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
                  <input type="text" name="filiere" required><br>
                </div>

                <fieldset>
                  <legend>Volume horaire: </legend>
                  <div class="form-group">
                    <label>Cours :</label>
                    <input type="number" name="cours" min="0" value="0" required><br>
                  </div>
                  <div class="form-group">
                    <label>TD :</label>
                    <input type="number" name="td" min="0" value="0" required><br>
                  </div>
                  <div class="form-group">
                    <label>TP :</label>
                    <input type="number" name="tp" min="0" value="0" required><br>
                  </div>
                  <div class="form-group">
                    <label>Autre :</label>
                    <input type="number" name="autre" min="0" value="0" required><br>
                  </div>
                  <div class="form-group">
                    <label>Évaluation :</label>
                    <input type="number" name="evaluation" min="0" value="0" required><br>
                  </div>
                </fieldset><br>
                <div style="display: flex; gap: 35px;">
                  <button type="button" id="ajouterBtn" disabled>Ajouter</button>
                  <button type="submit" id="enregistrerBtn">Enregistrer</button>
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

        <!-- Tableau pour afficher les données -->
        <h2 style="margin-left: 20px;" class="main-title">Le Descriptif Actuel:</h2>

        <table class="table-style" id="descriptifTable">
          <thead>
            <tr>
              <th rowspan="2">Année Universitaire</th>
              <th rowspan="2">Code Module</th>
              <th rowspan="2">Intitulé</th>
              <th rowspan="2">Semestre</th>
              <th rowspan="2">Filière</th>
              <th colspan="5" style="text-align:center;">Volume Horaire</th>
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
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('moduleForm');
      const ajouterBtn = document.getElementById('ajouterBtn');
      const enregistrerBtn = document.getElementById('enregistrerBtn');
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');

      // Vérifier si tous les champs sont remplis
      function checkFormFields() {
        const inputs = form.querySelectorAll('input[required], select[required]');
        let allFilled = true;

        inputs.forEach(input => {
          if (input.value.trim() === '') {
            allFilled = false;
          }
        });

        ajouterBtn.disabled = !allFilled;
      }

      // Activer/désactiver le bouton Ajouter en fonction des champs
      form.querySelectorAll('input, select').forEach(element => {
        element.addEventListener('input', checkFormFields);
        element.addEventListener('change', checkFormFields);
      });

      // Afficher un toast de notification
      function showToast(message, type) {
        toast.className = 'toast ' + type + ' show';
        toastMessage.textContent = message;

        // Modifier l'icône en fonction du type
        const icon = toast.querySelector('i');
        if (type === 'success') {
          icon.className = 'bx bx-check-circle';
        } else {
          icon.className = 'bx bx-error-circle';
        }

        // Cacher le toast après 3 secondes
        setTimeout(() => {
          toast.className = 'toast';
        }, 3000);
      }

      // Ajouter un module via AJAX
      ajouterBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // Créer un objet FormData avec les données du formulaire
        const formData = new FormData(form);
        formData.append('action', 'ajouter_module');

        // Envoyer la requête AJAX
        fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
          .then(response => {
            // Vérifier si la réponse est OK
            if (!response.ok) {
              throw new Error('Erreur réseau');
            }
            return response.json();
          })
          .then(data => {
            console.log('Réponse reçue:', data); // Pour déboguer

            if (data.status === 'success') {
              // Afficher un message de succès
              showToast(data.message, 'success');

              // Ajouter la nouvelle ligne au tableau
              const table = document.getElementById('descriptifTable');
              const tbody = table.querySelector('tbody');

              // Si le tableau est vide, supprimer le message "Aucune donnée trouvée"
              if (tbody.innerHTML.includes('Aucune donnée trouvée')) {
                tbody.innerHTML = '';
              }

              // Vérifier que data.data existe
              if (data.data) {
                // Créer une nouvelle ligne
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
            <td>${data.data.annee_univ}</td>
            <td>${data.data.code_module}</td>
            <td>${data.data.intitule_module}</td>
            <td>${data.data.semestre}</td>
            <td>${data.data.filiere}</td>
            <td>${data.data.V_h_cours}</td>
            <td>${data.data.V_h_TD}</td>
            <td>${data.data.V_h_TP}</td>
            <td>${data.data.V_h_Autre}</td>
            <td>${data.data.V_h_Evaluation}</td>
          `;

                tbody.appendChild(newRow);
              } else {
                console.error('Les données du module sont manquantes dans la réponse');
              }

              // Réinitialiser le formulaire
              form.reset();
              ajouterBtn.disabled = true;
            } else {
              // Afficher un message d'erreur
              showToast(data.message, 'error');
            }
          })
          .catch(error => {
            console.error('Erreur:', error);
            showToast('Une erreur est survenue lors de l\'ajout du module. Vérifiez la console pour plus de détails.', 'error');
          });
      });

      // Soumission du formulaire pour enregistrer
      form.addEventListener('submit', function (e) {
        // Ne pas empêcher la soumission par défaut,
        // car on veut que le formulaire soit soumis normalement
      });
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
  <script>
    setTimeout(() => {
      const alert = document.querySelector('.alert');
      if (alert) {
        alert.classList.add('slide-out');
        setTimeout(() => {
          alert.remove();
        }, 500);
      }
    }, 4000);
  </script>
  <script src="/e-service/js/script.js"></script>
</body>
<?php
$conn->close();
?>

</html>