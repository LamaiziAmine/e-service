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
//

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
    /* =========================
   STYLES MODERNES POUR GESTION PROFESSEURS
   ========================= */

/* Dialog Modal */
dialog {
  width: min(90vw, 600px);
  max-height: 90vh;
  margin: auto;
  border: none;
  border-radius: 16px;
  padding: 0;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  overflow: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

dialog::backdrop {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
}

/* Header du Dialog */
.dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px;
  position: relative;
}

.dialog-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  text-align: center;
}

/* Bouton de fermeture */
.close-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  font-size: 24px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

/* Formulaire */
form {
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  letter-spacing: 0.025em;
}

.required {
  color: #ef4444;
  margin-left: 4px;
}

/* Inputs et Select */
input, select {
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  background: white;
  font-family: inherit;
}

input:focus, select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
}

input:hover, select:hover {
  border-color: #d1d5db;
}

/* Password field */
.password-wrapper {
  position: relative;
}

.password-info {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.password-info::before {
  content: "ℹ️";
  font-size: 0.875rem;
}

/* Boutons */
.btn-group {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.btn-create, .btn_ajout {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.btn-create:hover, .btn_ajout:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn_ajout {
  float: right;
  margin: 20px 40px 20px 0;
}

.btn_ajout::before {
  content: "➕";
  font-size: 0.875rem;
}

/* Ligne de séparation */
.ligne {
  border: none;
  height: 1px;
  background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
  width: 94%;
  margin: 32px auto;
}

/* Container principal */
.main-title {
  margin-left: 40px;
  color: #1f2937;
  font-weight: 700;
  font-size: 2rem;
  margin-bottom: 8px;
}

.subtitle {
  margin-left: 40px;
  color: #667eea;
  font-weight: 500;
  margin-bottom: 32px;
}

/* Conteneur de recherche */
.search-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 94%;
  margin: 0 auto 24px;
}

.search-wrapper {
  position: relative;
  width: 320px;
}

.search-wrapper i {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  z-index: 1;
}

#searchInput {
  width: 100%;
  padding: 12px 16px 12px 48px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  background: white;
}

#searchInput:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#searchInput::placeholder {
  color: #9ca3af;
}

/* Container du tableau */
.table-container {
  width: 94%;
  margin: 0 auto;
  overflow: hidden;
  border-radius: 16px;
  background: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: 1px solid #f3f4f6;
}

/* Tableau */
.professeur-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Inter', sans-serif;
}

.professeur-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.professeur-table th {
  padding: 20px 24px;
  text-align: left;
  font-weight: 600;
  color: white;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  position: relative;
}

.professeur-table th::after {
  content: '';
  position: absolute;
  right: 0;
  top: 25%;
  height: 50%;
  width: 1px;
  background: rgba(255, 255, 255, 0.2);
}

.professeur-table th:last-child::after {
  display: none;
}

.professeur-table td {
  padding: 20px 24px;
  border-bottom: 1px solid #f3f4f6;
  color: #374151;
  font-size: 0.875rem;
  vertical-align: middle;
}

.professeur-table tbody tr {
  transition: all 0.3s ease;
}

.professeur-table tbody tr:hover {
  background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%);
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.professeur-table tbody tr:last-child td {
  border-bottom: none;
}

/* Boutons d'action */
.action-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  margin-right: 8px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.view-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
}

.edit-btn {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.delete-btn {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.view-btn:hover {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.edit-btn:hover {
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.delete-btn:hover {
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* Alertes */
.alert {
  width: 94%;
  margin: 20px auto;
  padding: 16px 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  font-weight: 500;
  font-size: 0.875rem;
  border-left: 4px solid;
  backdrop-filter: blur(10px);
}

.alert-success {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
  color: #065f46;
  border-left-color: #10b981;
}

.alert-error {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
  color: #7f1d1d;
  border-left-color: #ef4444;
}

.alert i {
  margin-right: 12px;
  font-size: 1.25rem;
}
.professeur-table th:last-child,
.professeur-table td:last-child {
  width: 280px; /* Set a fixed width for the actions column */
  min-width: 280px;
  white-space: nowrap; /* Prevent text wrapping */
}

/* Animation de sortie pour les alertes */
@keyframes slideOut {
  0% {
    transform: translateX(0) scale(1);
    opacity: 1;
  }
  100% {
    transform: translateX(100%) scale(0.8);
    opacity: 0;
  }
}

.alert.slide-out {
  animation: slideOut 0.5s ease-in forwards;
}

/* Dialog de détails */
#viewDetailsDialog {
  width: min(90vw, 700px);
  padding: 0;
  border-radius: 16px;
  overflow: hidden;
}

.details-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.details-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.details-content {
  padding: 32px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.detail-item {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.detail-label {
  font-weight: 600;
  color: #475569;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
  display: block;
}

.detail-value {
  color: #1e293b;
  font-size: 0.875rem;
  font-weight: 500;
}

.close-details {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 24px;
  cursor: pointer;
  margin: 0 32px 32px;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  align-self: center;
}

.close-details:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 32px 0;
  list-style: none;
  padding: 0;
  gap: 8px;
}

.pagination li {
  margin: 0;
}

.pagination a {
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  color: #374151;
  text-decoration: none;
  transition: all 0.3s ease;
  font-weight: 500;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
}

.pagination a.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.pagination a:hover:not(.active) {
  background: #f3f4f6;
  border-color: #d1d5db;
  transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
  dialog {
    width: 95vw;
    margin: 20px auto;
  }
  
  .search-container {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .search-wrapper {
    width: 100%;
  }
  
  .table-container {
    width: 98%;
    overflow-x: auto;
  }
  
  .professeur-table th,
  .professeur-table td {
    padding: 12px 16px;
    font-size: 0.8rem;
  }
  
  .action-btn {
    padding: 6px 12px;
    font-size: 0.7rem;
    margin-right: 4px;
  }
  
  .details-content {
    grid-template-columns: 1fr;
    padding: 20px;
  }
  
  .main-title {
    margin-left: 20px;
    font-size: 1.5rem;
  }
  
  .subtitle {
    margin-left: 20px;
  }
  
  .btn_ajout {
    margin: 20px 20px 20px 0;
  }
}

/* Animations d'entrée */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.table-container {
  animation: fadeInUp 0.6s ease-out;
}

.alert {
  animation: fadeInUp 0.4s ease-out;
}

/* States pour les inputs */
.form-group.error input,
.form-group.error select {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-group.success input,
.form-group.success select {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Loading states */
.btn-create:disabled,
.btn_ajout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-create:disabled:hover,
.btn_ajout:disabled:hover {
  transform: none;
  box-shadow: none;
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
        <p style="margin-left: 15px; color:#2780FD;">Cliquer sur "Ajouter Professeur" pour créer un compte Professeur</p>
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
            <input type="text" id="searchInput" style="border: 1px solid black;" placeholder="Chercher un professeur..." required>
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

      document.addEventListener('DOMContentLoaded', function() {
          // Find the sidebar menu by its ID
          const sidebarMenu = document.getElementById('admin-sidebar-menu');

          if (sidebarMenu) {
              // Get all the links inside the menu
              const menuLinks = sidebarMenu.querySelectorAll('a');

              // Add a click listener to each link
              menuLinks.forEach(function(link) {
                  link.addEventListener('click', function() {
                      // 1. Remove 'active' class from all links first
                      menuLinks.forEach(function(innerLink) {
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

  <script src="/e-service/js/script.js"></script>
</body>

</html>