<?php 
$currentPage = basename($_SERVER['PHP_SELF']);
$pagesGestionVacataires = ['creationCompteVAcataire.php', 'affectationVacataire.php'];
$isGestionVacatairesActive = in_array($currentPage, $pagesGestionVacataires);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cordonnateur') {
    header("Location: ../login.php");
    exit; 
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Affecter vacataire</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/e-service/css/style.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* Variables CSS */
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
      --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      --hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }

    /* Header avec style moderne */
    .main-title {
      font-size: 2.2rem;
      font-weight: 700;
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 2rem;
      position: relative;
    }

    .main-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 60px;
      height: 4px;
      background: var(--primary-gradient);
      border-radius: 2px;
    }

    /* Card moderne */
    .card {
      border: none;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      transition: all 0.4s ease;
      background: white;
    }

    .card:hover {
      box-shadow: var(--hover-shadow);
      transform: translateY(-5px);
    }

    .card-body {
      padding: 0;
    }

    /* Table moderne */
    .table {
      margin: 0;
    }

    .table thead th {
      background: var(--primary-gradient);
      color: white;
      border: none;
      padding: 20px 15px;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      text-align: center;
      position: relative;
    }

    .table thead th::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: rgba(255, 255, 255, 0.3);
    }

    .table tbody tr {
      transition: all 0.3s ease;
      border-bottom: 1px solid #f0f0f0;
    }

    .table tbody tr:hover {
      background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
      transform: scale(1.01);
    }

    .table td, .table th {
      vertical-align: middle;
      text-align: center;
      padding: 18px 15px;
      border: none;
    }

    .table td:first-child {
      font-weight: 700;
      color: #667eea;
    }

    /* Status badges améliorés */
    .non-affecte {
      background: var(--danger-gradient);
      color: white;
      padding: 8px 16px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .affecte {
      background: var(--success-gradient);
      color: white;
      padding: 8px 16px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    }

    /* Bouton d'action stylé */
    .btn-outline-primary {
      background: var(--primary-gradient);
      border: none;
      color: white;
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-outline-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .btn-outline-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      color: white;
    }

    .btn-outline-primary:hover::before {
      left: 100%;
    }

    /* Modal moderne */
    .modal-content {
      border-radius: 25px;
      border: none;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .modal-header {
      background: var(--primary-gradient);
      color: white;
      border: none;
      padding: 25px 30px;
    }

    .modal-title {
      font-weight: 700;
      font-size: 1.3rem;
      margin: 0;
    }

    .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.8;
      transition: opacity 0.3s ease;
    }

    .btn-close:hover {
      opacity: 1;
    }

    .modal-body {
      padding: 30px;
      background: white;
    }

    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-select {
      border: 2px solid #e1e8ed;
      border-radius: 15px;
      padding: 12px 16px;
      font-weight: 500;
      transition: all 0.3s ease;
      background: white;
    }

    .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      outline: none;
    }

    .form-check {
      padding: 12px 16px;
      border-radius: 12px;
      transition: all 0.2s ease;
      margin-bottom: 8px;
      border: 1px solid transparent;
    }

    .form-check:hover {
      background: #f8f9ff;
      border-color: #e1e8ed;
    }

    .form-check-input {
      width: 20px;
      height: 20px;
      margin-right: 12px;
      border-radius: 6px;
      border: 2px solid #d1d5db;
    }

    .form-check-input:checked {
      background-color: #667eea;
      border-color: #667eea;
    }

    .form-check-label {
      font-weight: 500;
      color: #374151;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .modal-footer {
      background: #f8f9fa;
      border: none;
      padding: 20px 30px;
    }

    .modal-footer .btn-primary {
      background: var(--primary-gradient);
      border: none;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
    }

    .modal-footer .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    /* État vide */
    .text-center {
      padding: 40px 20px;
      color: #6b7280;
    }

    .text-center::before {
      content: '📚';
      font-size: 3rem;
      display: block;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    /* Animations */
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card {
      animation: slideInUp 0.6s ease-out;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-title {
        font-size: 1.8rem;
      }
      
      .table td, .table th {
        padding: 12px 8px;
        font-size: 0.9rem;
      }
      
      .btn-outline-primary {
        padding: 8px 16px;
        font-size: 0.85rem;
      }
      
      .modal-body {
        padding: 20px;
      }
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
        <div class="container">
          <h2 class="main-title">
            <i class="bi bi-person-gear me-3"></i>
            Modules non totalement affectés
          </h2>

          <div class="card">
            <div class="card-body">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th><i class="bi bi-hash me-2"></i>ID Module</th>
                    <th><i class="bi bi-book me-2"></i>Nom</th>
                    <th><i class="bi bi-mortarboard me-2"></i>Cours</th>
                    <th><i class="bi bi-people me-2"></i>TD</th>
                    <th><i class="bi bi-laptop me-2"></i>TP</th>
                    <th><i class="bi bi-gear me-2"></i>Actions</th>
                  </tr>
                </thead>
                <tbody id="table-modules">
                  <?php

                  $host = "localhost";
                  $user = "root";
                  $password = "";
                  $database = "projet_web";

                  $conn = new mysqli($host, $user, $password, $database);

                  // Vérification de la connexion
                  if ($conn->connect_error) {
                    die("Erreur de connexion: " . $conn->connect_error);
                  }

                  $modules = $conn->query("SELECT id, code_module, intitule_module FROM unités_ensignement");
                  $types = ['Cours', 'TD', 'TP'];

                  if ($modules && $modules->num_rows > 0) {
                    while ($m = $modules->fetch_assoc()) {
                      $id_ue = $m['id'];
                      $code = $m['code_module'];
                      echo "<tr><td>{$code}</td><td>{$m['intitule_module']}</td>";

                      $non_affectes = [];

                      foreach ($types as $t) {
                        $res = $conn->query("SELECT * FROM affectations a JOIN types_intervention ti ON a.id_type = ti.id WHERE a.id_ue = '$id_ue' AND ti.type = '$t'");
                        if ($res->num_rows > 0) {
                          echo "<td><span class='affecte'><i class='bi bi-check-circle-fill'></i>{$t}</span></td>";
                        } else {
                          echo "<td><span class='non-affecte'><i class='bi bi-x-circle-fill'></i>{$t}</span></td>";
                          $non_affectes[] = $t;
                        }
                      }

                      $types_js = json_encode($non_affectes);
                      echo "<td><button class='btn btn-sm btn-outline-primary' onclick='openModal(\"$id_ue\", $types_js)'><i class='bi bi-person-plus me-2'></i>Affecter</button></td>";
                      echo "</tr>";
                    }
                  } else {
                    echo "<tr><td colspan='6' class='text-center'>Aucune unité d'enseignement trouvée.</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>

      <!-- Modal pour affectation -->
      <div class="modal fade" id="modalAffectation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form method="POST" action="affecter.php" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-person-check me-2"></i>Affecter un vacataire
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="code_module" id="modal_module_code">

              <label class="form-label">
                <i class="bi bi-person-circle"></i>
                Vacataire:
              </label>
              <select name="id_vacataire" class="form-select mb-3">
                <?php
                $vacs = $conn->query("SELECT id, nom, prenom FROM users WHERE role = 'vacataire'");
                if ($vacs && $vacs->num_rows > 0) {
                  while ($v = $vacs->fetch_assoc()) {
                    echo "<option value='{$v['id']}'>{$v['nom']} {$v['prenom']}</option>";
                  }
                } else {
                  echo "<option value=''>Aucun vacataire disponible</option>";
                }
                ?>
              </select>

              <label class="form-label">
                <i class="bi bi-list-check"></i>
                Types à affecter:
              </label>
              <div id="modal_types_checkboxes" class="ms-2">
                <!-- Injecté dynamiquement via JS -->
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Affecter
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>

  <!-- Scripts - Dans l'ordre exact de votre version originale -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openModal(code, availableTypes) {
      document.getElementById('modal_module_code').value = code;
      const container = document.getElementById('modal_types_checkboxes');
      container.innerHTML = '';
      
      const typeIcons = {
        'Cours': 'bi-mortarboard',
        'TD': 'bi-people', 
        'TP': 'bi-laptop'
      };
      
      ['Cours', 'TD', 'TP'].forEach(function (type) {
        if (availableTypes.includes(type)) {
          container.innerHTML += `
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="types[]" value="${type}" id="type_${type}">
          <label class="form-check-label" for="type_${type}">
            <i class="${typeIcons[type]} me-2"></i>${type}
          </label>
        </div>`;
        }
      });
      new bootstrap.Modal(document.getElementById('modalAffectation')).show();
    }
  </script>

  <!-- Vos scripts originaux -->
  <script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>
</body>

</html>