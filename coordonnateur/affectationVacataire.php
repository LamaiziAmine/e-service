<!DOCTYPE html>
<html lang="en">

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

  <style>
    .main-title {
      font-size: 24px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 20px;
    }

    .table thead th {
      background-color: #f8f9fa;
      color: #2c3e50;
      text-align: center;
    }

    .table td,
    .table th {
      vertical-align: middle;
      text-align: center;
    }

    .non-affecte {
      color: #dc3545;
      font-weight: 600;
    }

    .affecte {
      color: #6c757d;
      font-style: italic;
    }

    .btn-outline-primary {
      border-radius: 20px;
      padding: 4px 12px;
    }

    .modal-content {
      border-radius: 16px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    }

    .modal-title {
      font-weight: 600;
      color: #007bff;
    }

    .form-label {
      font-weight: 500;
      color: #333;
    }

    .form-check-label {
      cursor: pointer;
    }

    .form-select {
      border-radius: 10px;
    }

    .modal-footer .btn-primary {
      border-radius: 10px;
      padding: 6px 16px;
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
          <h2 class="main-title">Modules non totalement affectés</h2>

          <div class="card">
            <div class="card-body">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>ID Module</th>
                    <th>Nom</th>
                    <th>Cours</th>
                    <th>TD</th>
                    <th>TP</th>
                    <th>Actions</th>
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
                  ?>

                <tbody id="table-modules">
                  <?php
                  if ($modules && $modules->num_rows > 0) {
                    while ($m = $modules->fetch_assoc()) {
                      $id_ue = $m['id'];
                      $code = $m['code_module'];
                      echo "<tr><td>{$code}</td><td>{$m['intitule_module']}</td>";

                      $non_affectes = [];

                      foreach ($types as $t) {
                        $res = $conn->query("SELECT * FROM affectations a JOIN types_intervention ti ON a.id_type = ti.id WHERE a.id_ue = '$id_ue' AND ti.type = '$t'");
                        if ($res->num_rows > 0) {
                          echo "<td class='affecte'>{$t}</td>";
                        } else {
                          echo "<td class='non-affecte'>{$t}</td>";
                          $non_affectes[] = $t;
                        }
                      }

                      $types_js = json_encode($non_affectes);
                      echo "<td><button class='btn btn-sm btn-outline-primary' onclick='openModal(\"$id_ue\", $types_js)'>Affecter</button></td>";
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

              <label class="form-label">Vacataire:</label>
              <select name="cin_vacataire" class="form-select mb-3">
                <?php
                $vacs = $conn->query("SELECT nom, prenom FROM vacataire");
                while ($v = $vacs->fetch_assoc()) {
                  echo "<option value='{$v['cin']}'>{$v['nom']} {$v['prenom']} </option>";
                }
                ?>
              </select>

              <label class="form-label">Types à affecter:</label>
              <div id="modal_types_checkboxes" class="ms-2">
                <!-- Injecté dynamiquement via JS -->
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Affecter</button>
            </div>
          </form>
        </div>
      </div>

      <style>
        .non-affecte {
          color: #212529;
          font-weight: 600;
        }

        .affecte {
          color: #6c757d;
          font-style: italic;
        }
      </style>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <script>
        function openModal(code, availableTypes) {
          document.getElementById('modal_module_code').value = code;
          const container = document.getElementById('modal_types_checkboxes');
          container.innerHTML = '';
          ['Cours', 'TD', 'TP'].forEach(function (type) {
            if (availableTypes.includes(type)) {
              container.innerHTML += `
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="types[]" value="${type}">
            <label class="form-check-label">${type}</label>
          </div>`;
            }
          });
          new bootstrap.Modal(document.getElementById('modalAffectation')).show();
        }
      </script>

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