<?php
session_start();

$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = ''; // mot de passe si عندك

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Erreur de connexion: " . $conn->connect_error);
}

// Fonction pour supprimer un vacataire
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $sql = "DELETE FROM vacataire WHERE id = $id";
  if ($conn->query($sql) === TRUE) {
    $_SESSION['message'] = "Vacataire supprimé avec succès!";
    $_SESSION['msg_type'] = "success";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
  } else {
    $_SESSION['message'] = "Erreur lors de la suppression: " . $conn->error;
    $_SESSION['msg_type'] = "error";
  }
}

// Récupération des données pour édition
$nom = $prenom = $date_nais = $cin = $sexe = $num_tel = $email = $password = "";
$edit_state = false;
$update_id = 0;

if (isset($_GET['edit'])) {
  $edit_state = true;
  $update_id = $_GET['edit'];
  $result = $conn->query("SELECT * FROM vacataire WHERE id = $update_id");
  if ($result->num_rows == 1) {
    $row = $result->fetch_array();
    $nom = $row['nom'];
    $prenom = $row['prenom'];
    $date_nais = $row['date_naissance'];
    $cin = $row['cin'];
    $sexe = $row['sexe'];
    $num_tel = $row['num_tel'];
    $email = $row['email'];
    $password = $row['mot_de_passe'];
  }
}

// Traitement du formulaire d'ajout/modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['ajout'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_nais = $_POST['dob'];
    $cin = $_POST['cine'];
    $sexe = $_POST['sexe'];
    $num_tel = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "INSERT INTO vacataire(nom, prenom, date_naissance, cin, sexe, num_tel, email, mot_de_passe)
            VALUES ('$nom', '$prenom', '$date_nais', '$cin', '$sexe', '$num_tel', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
      $_SESSION['message'] = "Vacataire ajouté avec succès!";
      $_SESSION['msg_type'] = "success";
      header("Location: ".$_SERVER['PHP_SELF']);
      exit();
    } else {
      $_SESSION['message'] = "Erreur: " . $conn->error;
      $_SESSION['msg_type'] = "error";
    }
  }

  if (isset($_POST['update'])) {
    $id = $_POST['update_id'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_nais = $_POST['dob'];
    $cin = $_POST['cine'];
    $sexe = $_POST['sexe'];
    $num_tel = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "UPDATE vacataire SET nom='$nom', prenom='$prenom', date_naissance='$date_nais', 
            cin='$cin', sexe='$sexe', num_tel='$num_tel', email='$email', mot_de_passe='$password' 
            WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
      $_SESSION['message'] = "Vacataire mis à jour avec succès!";
      $_SESSION['msg_type'] = "success";
      header("Location: ".$_SERVER['PHP_SELF']);
      exit();
    } else {
      $_SESSION['message'] = "Erreur lors de la mise à jour: " . $conn->error;
      $_SESSION['msg_type'] = "error";
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Créer compte</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="/e-service/css/style.min.css">
  <!-- Font Awesome pour les icônes -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    dialog {
      width: 60%;
      margin-left: 30%;
      height: 85%;
      border: none;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
      font-family: Arial, sans-serif;
    }

    dialog::backdrop {
      background: rgba(0, 0, 0, 0.5);
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-top: 10px;
      font-weight: bold;
    }

    input,
    select {
      padding: 8px;
      border-radius: 5px;
      border: 1px solid #ccc;
      margin-top: 5px;
    }

    .btn-group {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    .btn_ajout {
      background-color: rgb(4, 0, 255);
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      float: right;
      margin: 10px;
      margin-right: 40px;
    }

    .btn-create {
      background-color: rgb(4, 0, 255);
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      width: 150px;
      margin-left: 76%;
    }

    .ligne {
      border: none;
      height: 2px;
      background-color: gray;
      width: 94%;
      /* Largeur de la ligne */
      margin: 20px auto;
    }

    .required {
      color: red;
      margin-left: 3px;
    }

    /* Style du tableau */
    .table-container {
      width: 94%;
      margin: 0 auto;
      overflow-x: auto;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      background-color: white;
    }

    .vacataire-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, sans-serif;
      overflow: hidden;
    }

    .vacataire-table thead {
      background-color: #2780FD;
      color: white;
    }

    .vacataire-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.5px;
    }

    .vacataire-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eaeaea;
      vertical-align: middle;
    }

    .vacataire-table tbody tr:hover {
      background-color: #f5f8ff;
    }

    .vacataire-table tbody tr:last-child td {
      border-bottom: none;
    }

    /* Style pour les boutons d'action */
    .action-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 5px;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }

    .view-btn {
      background-color: #3498db;
      color: white;
    }

    .edit-btn {
      background-color: #f39c12;
      color: white;
    }

    .delete-btn {
      background-color: #e74c3c;
      color: white;
    }

    .action-btn:hover {
      opacity: 0.8;
      transform: translateY(-2px);
    }

    .action-btn i {
      margin-right: 4px;
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

    /* Style pour la boîte de détails */
    #viewDetailsDialog {
      width: 50%;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .details-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 2px solid #eaeaea;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }

    .details-title {
      font-size: 22px;
      font-weight: 600;
      color: #2780FD;
    }

    .details-content {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .detail-item {
      margin-bottom: 15px;
    }

    .detail-label {
      font-weight: 600;
      color: #555;
      margin-bottom: 5px;
      display: block;
    }

    .detail-value {
      color: #333;
      padding: 8px 10px;
      background-color: #f5f8ff;
      border-radius: 5px;
      min-height: 36px;
      display: flex;
      align-items: center;
    }

    .close-details {
      background-color: #2780FD;
      color: white;
      border: none;
      border-radius: 5px;
      padding: 10px 15px;
      cursor: pointer;
      margin-top: 20px;
      font-weight: 500;
      width: 150px;
      align-self: center;
    }
    
    /* Style pour pagination */
    .pagination {
      display: flex;
      justify-content: center;
      margin: 20px 0;
      list-style: none;
      padding: 0;
    }
    
    .pagination li {
      margin: 0 5px;
    }
    
    .pagination a {
      padding: 8px 12px;
      border-radius: 4px;
      border: 1px solid #ccc;
      color: #333;
      text-decoration: none;
      transition: all 0.3s;
    }
    
    .pagination a.active {
      background-color: #2780FD;
      color: white;
      border-color: #2780FD;
    }
    
    .pagination a:hover:not(.active) {
      background-color: #f5f5f5;
    }
    
    /* Style pour la recherche */
    .search-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 94%;
      margin: 0 auto 20px;
    }
    
    .search-box {
      display: flex;
      align-items: center;
      width: 320px;
      border: 1px solid #ccc;
      border-radius: 27px;
      padding: 5px 15px;
      background-color: white;
    }
    
    .search-box input {
      border: none;
      outline: none;
      padding: 8px;
      width: 100%;
      background-color: white;
    }
    
    .search-box i {
      margin-right: 5px;
    }

    @keyframes slideOut {
  0% { transform: translateY(0); opacity: 1; }
  100% { transform: translateY(-20px); opacity: 0; }
}

.alert.slide-out {
  animation: slideOut 0.5s forwards;
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
      <?php include "navbar.php" ?> <br>
      <!-- ! Main -->
      <h1 style="margin-left: 20px;" class="main-title">Gestion des comptes Vacataire:</h1>
      <p style="margin-left: 15px; color:#2780FD;">Cliquer sur "Ajouter Vacataire" pour créer un compte Vacataire</p>
      <br>
      
      <!-- Message d'alerte -->
      <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?=$_SESSION['msg_type']?>">
          <i class="fas fa-<?=$_SESSION['msg_type'] == 'success' ? 'check-circle' : 'exclamation-circle'?>"></i>
          <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
          ?>
        </div>
      <?php endif ?>
      
      <!-- Bouton Ajouter -->
      <button onclick="openDialog()" class="btn_ajout">+ Ajouter Vacataire</button><br><br><br>
      <hr class="ligne">
      
      <!-- Zone de recherche -->
      <div class="search-container">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Rechercher un vacataire...">
        </div>
      </div>
      
      <!-- Tableau des vacataires -->
      <div class="table-container">
        <table class="vacataire-table" id="vacataireTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>CIN</th>
              <th>Email</th>
              <th>Téléphone</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Nombre d'éléments par page
              $items_per_page = 6;
              
              // Page actuelle
              $page = isset($_GET['page']) ? $_GET['page'] : 1;
              $start_from = ($page - 1) * $items_per_page;
              
              // Requête pour compter le nombre total de vacataires
              $count_query = "SELECT COUNT(*) as total FROM vacataire";
              $count_result = $conn->query($count_query);
              $count_row = $count_result->fetch_assoc();
              $total_vacataires = $count_row['total'];
              
              // Calcul du nombre total de pages
              $total_pages = ceil($total_vacataires / $items_per_page);
              
              // Requête pour récupérer les vacataires de la page actuelle
              $query = "SELECT * FROM vacataire ORDER BY id DESC LIMIT $start_from, $items_per_page";
              $result = $conn->query($query);
              
              if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>" . $row['id'] . "</td>";
                  echo "<td>" . $row['nom'] . "</td>";
                  echo "<td>" . $row['prenom'] . "</td>";
                  echo "<td>" . $row['cin'] . "</td>";
                  echo "<td>" . $row['email'] . "</td>";
                  echo "<td>" . $row['num_tel'] . "</td>";
                  echo "<td>
                        <button class='action-btn view-btn' onclick='viewVacataire(" . $row['id'] . ")'>
                          <i class='fas fa-eye'></i> Voir
                        </button>
                        <button class='action-btn edit-btn' onclick='editVacataire(" . $row['id'] . ")'>
                          <i class='fas fa-edit'></i> Modifier
                        </button>
                        <button class='action-btn delete-btn' onclick='deleteVacataire(" . $row['id'] . ")'>
                          <i class='fas fa-trash'></i> Supprimer
                        </button>
                      </td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='7' style='text-align:center;'>Aucun vacataire trouvé</td></tr>";
              }
            ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <ul class="pagination">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
          <li>
            <a href="?page=<?=$i?>" class="<?=($i == $page) ? 'active' : ''?>">
              <?=$i?>
            </a>
          </li>
        <?php endfor; ?>
      </ul>

      <!-- Dialog pour créer/modifier un vacataire -->
      <dialog id="accountDialog">
        <button type="button" onclick="closeDialog()"
          style="position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 30px; font-weight: bold; cursor: pointer; color: #555; color: red; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">&times;
        </button>
        <form method="POST" action="">
          <h2 style="text-align:center;"><?=$edit_state ? 'Modifier' : 'Créer'?> un compte</h2>
          
          <?php if($edit_state): ?>
            <input type="hidden" name="update_id" value="<?=$update_id?>">
          <?php endif; ?>

          <label>Nom:<span class="required">*</span></label>
          <input type="text" name="nom" value="<?=$nom?>" required>

          <label>Prénom:<span class="required">*</span></label>
          <input type="text" name="prenom" value="<?=$prenom?>" required>

          <label>Date de naissance:<span class="required">*</span></label>
          <input type="date" name="dob" value="<?=$date_nais?>" required>

          <label>CIN:<span class="required">*</span></label>
          <input type="text" name="cine" value="<?=$cin?>" required>

          <label>Sexe:<span class="required">*</span></label>
          <select name="sexe" required>
            <option value="">-- Choisir --</option>
            <option value="Homme" <?=$sexe == 'Homme' ? 'selected' : ''?>>Homme</option>
            <option value="Femme" <?=$sexe == 'Femme' ? 'selected' : ''?>>Femme</option>
          </select>

          <label>Numéro de téléphone:<span class="required">*</span></label>
          <input type="tel" name="phone" value="<?=$num_tel?>" required>

          <label>Email:<span class="required">*</span></label>
          <input type="email" name="email" value="<?=$email?>" required>

          <label>Mot de passe:<span class="required">*</span></label>
          <input type="text" name="password" id="generatedPassword" value="<?=$password?>" <?=$edit_state ? '' : 'readonly'?>>
          <?php if(!$edit_state): ?>
            <small style="color: #777; margin-top: 2px;">Le mot de passe est généré automatiquement</small>
          <?php endif; ?>

          <div class="btn-group">
            <?php if($edit_state): ?>
              <button type="submit" class="btn-create" name="update">Mettre à jour</button>
            <?php else: ?>
              <button type="submit" class="btn-create" name="ajout">Créer</button>
            <?php endif; ?>
          </div>
        </form>
      </dialog>
      
      <!-- Dialog pour voir les détails d'un vacataire -->
      <dialog id="viewDetailsDialog">
        <div class="details-header">
          <h2 class="details-title">Détails du Vacataire</h2>
          <button type="button" onclick="document.getElementById('viewDetailsDialog').close()"
            style="background: transparent; border: none; font-size: 24px; font-weight: bold; cursor: pointer; color: red;">&times;
          </button>
        </div>
        <div class="details-content" id="vacataireDetails">
          <!-- Contenu chargé dynamiquement -->
        </div>
        <button class="close-details" onclick="document.getElementById('viewDetailsDialog').close()">Fermer</button>
      </dialog>

      <script>
        // Fonctions de base pour le dialogue
        function openDialog() {
          document.getElementById('accountDialog').showModal();
          generatePassword();
        }

        function closeDialog() {
          document.getElementById('accountDialog').close();
        }

        function generatePassword() {
          const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#&!";
          let pass = "";
          for (let i = 0; i < 10; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
          }
          document.getElementById("generatedPassword").value = pass;
        }
        
        // Fonction pour éditer un vacataire
        function editVacataire(id) {
          window.location.href = `?edit=${id}`;
        }
        
        // Fonction pour supprimer un vacataire avec confirmation
        function deleteVacataire(id) {
          if (confirm("Êtes-vous sûr de vouloir supprimer ce vacataire ?")) {
            window.location.href = `?delete=${id}`;
          }
        }
        
        // Fonction pour voir les détails d'un vacataire
        function viewVacataire(id) {
          // Requête AJAX pour récupérer les détails
          fetch(`get_vacataire_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
              // Construction du HTML avec les détails
              let detailsHTML = `
                <div class="detail-item">
                  <span class="detail-label">ID:</span>
                  <div class="detail-value">${data.id}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Nom:</span>
                  <div class="detail-value">${data.nom}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Prénom:</span>
                  <div class="detail-value">${data.prenom}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Date de naissance:</span>
                  <div class="detail-value">${data.date_naissance}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">CIN:</span>
                  <div class="detail-value">${data.cin}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Sexe:</span>
                  <div class="detail-value">${data.sexe}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Téléphone:</span>
                  <div class="detail-value">${data.num_tel}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Email:</span>
                  <div class="detail-value">${data.email}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Mot de passe:</span>
                  <div class="detail-value">${data.mot_de_passe}</div>
                </div>
              `;
              
              // Injection du HTML dans le dialogue
              document.getElementById('vacataireDetails').innerHTML = detailsHTML;
              
              // Ouverture du dialogue
              document.getElementById('viewDetailsDialog').showModal();
            })
            .catch(error => {
              console.error('Erreur:', error);
              alert('Erreur lors de la récupération des détails du vacataire');
            });
        }
        
        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('keyup', function() {
          const searchTerm = this.value.toLowerCase();
          const table = document.getElementById('vacataireTable');
          const rows = table.getElementsByTagName('tr');
          
          // Pour chaque ligne sauf l'en-tête
          for (let i = 1; i < rows.length; i++) {
            let found = false;
            const cells = rows[i].getElementsByTagName('td');
            
            // Parcourir toutes les cellules sauf celle des actions
            for (let j = 0; j < cells.length - 1; j++) {
              const cellText = cells[j].textContent.toLowerCase();
              
              if (cellText.includes(searchTerm)) {
                found = true;
                break;
              }
            }
            
            rows[i].style.display = found ? '' : 'none';
          }
        });
        
        // Ouvrir le dialogue si on est en mode édition
        <?php if($edit_state): ?>
          window.onload = function() {
            document.getElementById('accountDialog').showModal();
          }
        <?php endif; ?>
      </script>
    </div>
  </div>
  
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
      }, 500); // مدة animation = 0.5s
    }
  }, 4000); // تبقى 5 ثواني قبل ما تبدا animation
</script>


  <script src="/e-service/js/script.js"></script>
</body>

</html>