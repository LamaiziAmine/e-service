<?php
session_start();

$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Erreur de connexion: " . $conn->connect_error);
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// Fonction pour supprimer un professeur
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $sql = "DELETE FROM users WHERE id = ? AND role = 'professeur'";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    $_SESSION['message'] = "Professeur supprimé avec succès!";
    $_SESSION['msg_type'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  } else {
    $_SESSION['message'] = "Erreur lors de la suppression: " . $conn->error;
    $_SESSION['msg_type'] = "error";
  }
  $stmt->close();
}

// Récupération des données pour édition
$nom = $prenom = $email = $password = $department_id = "";
$edit_state = false;
$update_id = 0;

if (isset($_GET['edit'])) {
  $edit_state = true;
  $update_id = intval($_GET['edit']);
  $sql = "SELECT * FROM users WHERE id = ? AND role = 'professeur'";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $update_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $nom = $row['nom'];
    $prenom = $row['prenom'];
    $email = $row['email'];
    $department_id = $row['department_id'];
    // Le mot de passe ne sera pas affiché pour des raisons de sécurité
  }
  $stmt->close();
}

// Récupération des départements pour le select
$departments = [];
$dept_query = "SELECT id, nom FROM departement ORDER BY nom";
$dept_result = $conn->query($dept_query);
if ($dept_result) {
  while ($dept = $dept_result->fetch_assoc()) {
    $departments[] = $dept;
  }
}

// Traitement du formulaire d'ajout/modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['ajout'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

    // Vérifier si l'email existe déjà
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
      $_SESSION['message'] = "Cet email est déjà utilisé!";
      $_SESSION['msg_type'] = "error";
    } else {
      // Hacher le mot de passe
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      $sql = "INSERT INTO users (email, password, role, department_id, nom, prenom) 
              VALUES (?, ?, 'professeur', ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssiss", $email, $hashed_password, $department_id, $nom, $prenom);

      if ($stmt->execute()) {
        $_SESSION['message'] = "Professeur ajouté avec succès!";
        $_SESSION['msg_type'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
      } else {
        $_SESSION['message'] = "Erreur: " . $conn->error;
        $_SESSION['msg_type'] = "error";
      }
      $stmt->close();
    }
    $stmt_check->close();
  }

  if (isset($_POST['update'])) {
    $id = intval($_POST['update_id']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

    // Vérifier si l'email existe déjà pour un autre utilisateur
    $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("si", $email, $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
      $_SESSION['message'] = "Cet email est déjà utilisé par un autre utilisateur!";
      $_SESSION['msg_type'] = "error";
    } else {
      // Si un nouveau mot de passe est fourni, le hacher
      if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET nom=?, prenom=?, email=?, password=?, department_id=? WHERE id=? AND role='professeur'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisi", $nom, $prenom, $email, $hashed_password, $department_id, $id);
      } else {
        // Ne pas modifier le mot de passe s'il n'est pas fourni
        $sql = "UPDATE users SET nom=?, prenom=?, email=?, department_id=? WHERE id=? AND role='professeur'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisi", $nom, $prenom, $email, $department_id, $id);
      }

      if ($stmt->execute()) {
        $_SESSION['message'] = "Professeur mis à jour avec succès!";
        $_SESSION['msg_type'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
      } else {
        $_SESSION['message'] = "Erreur lors de la mise à jour: " . $conn->error;
        $_SESSION['msg_type'] = "error";
      }
      $stmt->close();
    }
    $stmt_check->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des Professeurs</title>
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

    .professeur-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, sans-serif;
      overflow: hidden;
    }

    .professeur-table thead {
      background-color: #2780FD;
      color: white;
    }

    .professeur-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 0.5px;
    }

    .professeur-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eaeaea;
      vertical-align: middle;
    }

    .professeur-table tbody tr:hover {
      background-color: #f5f8ff;
    }

    .professeur-table tbody tr:last-child td {
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
      background-color: rgb(9, 39, 232);
      color: white;
    }

    .edit-btn {
      background-color: rgb(234, 194, 18);
      color: white;
    }

    .delete-btn {
      background-color: rgb(230, 24, 24);
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
  </style>
</head>

<body>
  <div class="layer"></div>
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">

    <!-- ! Intégration de la nouvelle Sidebar -->
    <?php include "sidebar.php"; ?>

    <div class="main-wrapper">

      <!-- ! Intégration de la nouvelle Navbar -->
      <?php include "navbar.php"; ?>

      <main class="main-content">
        <!-- ! Main -->
        <h1 style="margin-left: 20px;" class="main-title">Gestion des comptes Professeur:</h1>
        <p style="margin-left: 15px; color:#2780FD;">Cliquer sur "Ajouter Professeur" pour créer un compte Professeur
        </p>
        <br>

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

        <!-- Bouton Ajouter -->
        <button onclick="openDialog()" class="btn_ajout">+ Ajouter Professeur</button><br><br><br>
        <hr class="ligne">

        <!-- Zone de recherche -->
        <div class="search-container">
          <div class="search-wrapper">
            <i data-feather="search" aria-hidden="true"></i>
            <input type="text" id="searchInput" style="border: 1px solid black;" placeholder="Chercher un professeur..."
              required>
          </div>
        </div>

        <!-- Tableau des professeurs -->
        <div class="table-container">
          <table class="professeur-table" id="professeurTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Département</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Nombre d'éléments par page
              $items_per_page = 6;

              // Page actuelle
              $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
              $start_from = ($page - 1) * $items_per_page;

              // Requête pour compter le nombre total de professeurs
              $count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'professeur'";
              $count_result = $conn->query($count_query);
              $count_row = $count_result->fetch_assoc();
              $total_professeurs = $count_row['total'];

              // Calcul du nombre total de pages
              $total_pages = ceil($total_professeurs / $items_per_page);

              // Requête pour récupérer les professeurs de la page actuelle
              $query = "SELECT u.*, d.nom as dept_nom 
                        FROM users u 
                        LEFT JOIN departement d ON u.department_id = d.id 
                        WHERE u.role = 'professeur' 
                        ORDER BY u.id DESC 
                        LIMIT $start_from, $items_per_page";
              $result = $conn->query($query);

              if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>" . $row['id'] . "</td>";
                  echo "<td>" . htmlspecialchars($row['nom']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['prenom']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                  echo "<td>" . ($row['dept_nom'] ? htmlspecialchars($row['dept_nom']) : 'Non assigné') . "</td>";
                  echo "<td>
                          <button class='action-btn view-btn' onclick='viewProfesseur(" . $row['id'] . ")'>
                            <i class='fas fa-eye'></i> Voir
                          </button>
                          <button class='action-btn edit-btn' onclick='editProfesseur(" . $row['id'] . ")'>
                            <i class='fas fa-edit'></i> Modifier
                          </button>
                          <button class='action-btn delete-btn' onclick='deleteProfesseur(" . $row['id'] . ")'>
                            <i class='fas fa-trash'></i> Supprimer
                          </button>
                        </td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='6' style='text-align:center;'>Aucun professeur trouvé</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li>
                <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        <?php endif; ?>
      </main>

      <!-- Dialog pour créer/modifier un professeur -->
      <dialog id="accountDialog">
        <button type="button" onclick="closeDialog()"
          style="position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 30px; font-weight: bold; cursor: pointer; color: red; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">&times;
        </button>
        <form method="POST" action="">
          <h2 style="text-align:center;"><?= $edit_state ? 'Modifier' : 'Créer' ?> un compte Professeur</h2>

          <?php if ($edit_state): ?>
            <input type="hidden" name="update_id" value="<?= $update_id ?>">
          <?php endif; ?>

          <label>Nom:<span class="required">*</span></label>
          <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required>

          <label>Prénom:<span class="required">*</span></label>
          <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>

          <label>Email:<span class="required">*</span></label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

          <label>Département:</label>
          <select name="department_id">
            <option value="">-- Aucun département --</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= $dept['id'] ?>" <?= $department_id == $dept['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($dept['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Mot de passe:<span class="required">*</span></label>
          <input type="text" name="password" id="generatedPassword" value="" <?= $edit_state ? '' : 'readonly' ?>>
          <?php if ($edit_state): ?>
            <small style="color: #777; margin-top: 2px;">Laissez vide pour conserver le mot de passe actuel</small>
          <?php else: ?>
            <small style="color: #777; margin-top: 2px;">Le mot de passe est généré automatiquement</small>
          <?php endif; ?>

          <div class="btn-group">
            <?php if ($edit_state): ?>
              <button type="submit" class="btn-create" name="update">Mettre à jour</button>
            <?php else: ?>
              <button type="submit" class="btn-create" name="ajout">Créer</button>
            <?php endif; ?>
          </div>
        </form>
      </dialog>

      <!-- Dialog pour voir les détails d'un professeur -->
      <dialog id="viewDetailsDialog">
        <div class="details-header">
          <h2 class="details-title">Détails du Professeur</h2>
          <button type="button" onclick="document.getElementById('viewDetailsDialog').close()"
            style="background: transparent; border: none; font-size: 24px; font-weight: bold; cursor: pointer; color: red;">&times;
          </button>
        </div>
        <div class="details-content" id="professeurDetails">
          <!-- Contenu chargé dynamiquement -->
        </div>
        <button class="close-details" onclick="document.getElementById('viewDetailsDialog').close()">Fermer</button>
      </dialog>

      <script>

        document.addEventListener('DOMContentLoaded', function () {
          // Find the sidebar menu by its ID
          const sidebarMenu = document.getElementById('admin-sidebar-menu');

          if (sidebarMenu) {
            // Get all the links inside the menu
            const menuLinks = sidebarMenu.querySelectorAll('a');

            // Add a click listener to each link
            menuLinks.forEach(function (link) {
              link.addEventListener('click', function () {
                // 1. Remove 'active' class from all links first
                menuLinks.forEach(function (innerLink) {
                  innerLink.classList.remove('active');
                });

                // 2. Add 'active' class to the link that was just clicked
                this.classList.add('active');
              });
            });
          }
        });
        // Fonctions de base pour le dialogue
        function openDialog() {
          document.getElementById('accountDialog').showModal();
          <?php if (!$edit_state): ?>
            generatePassword();
          <?php endif; ?>
        }

        function closeDialog() {
          document.getElementById('accountDialog').close();
        }

        function generatePassword() {
          const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#&!";
          let pass = "";
          for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
          }
          document.getElementById("generatedPassword").value = pass;
        }

        // Fonction pour éditer un professeur
        function editProfesseur(id) {
          window.location.href = `?edit=${id}`;
        }

        // Fonction pour supprimer un professeur avec confirmation
        function deleteProfesseur(id) {
          if (confirm("Êtes-vous sûr de vouloir supprimer ce professeur ?")) {
            window.location.href = `/e-service/admin/profcompte.php?delete=${id}`;
          }
        }

        // Fonction pour voir les détails d'un professeur
        function viewProfesseur(id) {
          // Simuler les données (vous devrez créer get_professeur_details.php)
          // Pour l'instant, on fait une requête simple
          fetch(`get_professeur_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
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
                  <span class="detail-label">Email:</span>
                  <div class="detail-value">${data.email}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Rôle:</span>
                  <div class="detail-value">${data.role}</div>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Département:</span>
                  <div class="detail-value">${data.department_name || 'Non assigné'}</div>
                </div>
              `;

              document.getElementById('professeurDetails').innerHTML = detailsHTML;
              document.getElementById('viewDetailsDialog').showModal();
            })
            .catch(error => {
              console.error('Erreur:', error);
              alert('Erreur lors de la récupération des détails du professeur');
            });
        }

        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('keyup', function () {
          const searchTerm = this.value.toLowerCase();
          const table = document.getElementById('professeurTable');
          const rows = table.getElementsByTagName('tr');

          for (let i = 1; i < rows.length; i++) {
            let found = false;
            const cells = rows[i].getElementsByTagName('td');

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
        <?php if ($edit_state): ?>
          window.onload = function () {
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
        }, 500);
      }
    }, 4000); 
  </script>
  <script src="/e-service/plugins/chart.min.js"></script>
  <!-- Icons library -->
  <script src="/e-service/plugins/feather.min.js"></script>
  <!-- Custom scripts -->
  <script src="/e-service/js/script.js"></script>
</body>

</html>