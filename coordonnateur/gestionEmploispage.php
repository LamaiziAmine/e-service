<?php
// Connexion à ta DB (votre code existant)
try {
  $db = new PDO('mysql:host=localhost;dbname=projet_web;charset=utf8', 'root', '');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Erreur de connexion : " . $e->getMessage();
  die();
}

// ===================================================================
// GESTIONNAIRE DE REQUÊTES AJAX (LOGIQUE DE SUPPRESSION INTÉGRÉE ICI)
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_timetable') {
  header('Content-Type: application/json');
  $filiere = $_POST['filiere'];

  // 1. Récupérer le chemin du PDF avant de le supprimer de la BDD
  $stmt_select = $db->prepare("SELECT emploi_pdf FROM promotion WHERE nom = :nom");
  $stmt_select->execute([':nom' => $filiere]);
  $result = $stmt_select->fetch(PDO::FETCH_ASSOC);

  if ($result && !empty($result['emploi_pdf'])) {
    $pdfPathFromDB = $result['emploi_pdf'];
    $serverFilePath = $_SERVER['DOCUMENT_ROOT'] . $pdfPathFromDB;

    // 2. Supprimer le fichier physique s'il existe
    if (file_exists($serverFilePath)) {
      unlink($serverFilePath);
    }
  }

  // 3. Mettre à NULL la colonne dans la base de données
  $stmt_update = $db->prepare("UPDATE promotion SET emploi_pdf = NULL WHERE nom = :nom");
  $stmt_update->execute([':nom' => $filiere]);

  if ($stmt_update->rowCount() > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Emploi du temps supprimé avec succès.']);
  } else {
    echo json_encode(['status' => 'warning', 'message' => "Aucun emploi du temps à supprimer pour la filière '$filiere'."]);
  }

  exit; // TRÈS IMPORTANT: Arrête l'exécution du script ici.
}
$filieres = $db->query("SELECT nom, emploi_pdf FROM promotion")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emplois du temps</title>
  <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
  <link rel="stylesheet" href="/e-service/css/style.min.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <style>
    table {
      width: 70%;
      margin: 30px auto;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      overflow: hidden;
    }

    th,
    td {
      padding: 18px;
      text-align: center;
      border-bottom: 1px solid #eee;
    }

    th {
      background-color: #007BFF;
      color: white;
    }

    form {
      display: inline-block;
    }

    input[type="file"] {
      display: none;
    }

    label {
      background-color: rgb(31, 177, 23);
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    label:hover {
      background-color: #218838;
    }

    .toast {
      position: fixed;
      top: 80px;
      /* Un peu plus bas pour ne pas être caché par la navbar */
      right: 20px;
      background: white;
      color: #333;
      border-left: 5px solid;
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 15px 20px;
      z-index: 9999;
      display: flex;
      align-items: center;
      opacity: 0;
      transform: translateX(100%);
      visibility: hidden;
      transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .toast.show {
      opacity: 1;
      transform: translateX(0);
      visibility: visible;
    }

    .toast.success {
      border-left-color: #28a745;
    }

    .toast.error {
      border-left-color: #dc3545;
    }

    .toast.warning {
      border-left-color: #ffc107;
    }

    .toast i {
      margin-right: 12px;
      font-size: 24px;
    }

    .toast.success i {
      color: #28a745;
    }

    .toast.error i {
      color: #dc3545;
    }

    .toast.warning i {
      color: #ffc107;
    }

    .btn-delete {
      background-color: #dc3545;
      /* Rouge danger */
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn-delete:hover {
      background-color: #c82333;
    }

    .pdf-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      /* Fond plus sombre pour le focus */
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1050;
      /* Doit être au-dessus des autres éléments */
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .pdf-modal-overlay.show {
      opacity: 1;
      visibility: visible;
    }

    .pdf-modal-content {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
      width: 80%;
      height: 90vh;
      /* Utilise 90% de la hauteur de l'écran */
      max-width: 1100px;
      display: flex;
      flex-direction: column;
      transform: scale(0.95);
      transition: transform 0.3s ease;
    }

    .pdf-modal-overlay.show .pdf-modal-content {
      transform: scale(1);
    }

    .pdf-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 25px;
      border-bottom: 1px solid #e9ecef;
      background-color: #f8f9fa;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }

    .pdf-modal-header h3 {
      margin: 0;
      font-size: 1.2rem;
      color: #343a40;
    }

    .pdf-modal-close-btn {
      background: transparent;
      border: none;
      font-size: 2rem;
      font-weight: bold;
      color: #6c757d;
      cursor: pointer;
      line-height: 1;
      padding: 0;
    }

    .pdf-modal-close-btn:hover {
      color: #343a40;
    }

    .pdf-modal-body {
      flex-grow: 1;
      /* Prend tout l'espace restant */
      padding: 5px;
      /* Petit espace autour de l'iframe */
    }

    #pdfFrame {
      width: 100%;
      height: 100%;
      border-bottom-left-radius: 12px;
      border-bottom-right-radius: 12px;
    }
  </style>
</head>

<body>
  <div class="layer"></div>
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">
    <?php include "sidebar.php" ?>
    <div class="main-wrapper">
      <?php include "navbar.php" ?><br>
      <div id="toast-notification" class="toast">
        <i class='bx bx-check-circle'></i>
        <span id="toast-message"></span>
      </div>
      <h2 style="margin-left: 20px;" class="main-title">Gestion des emplois du temps </h2>
      <table>
        <tr>
          <th>Filière</th>
          <th>Importer Emploi du Temps (PDF)</th>
          <th>Voir Emploi du Temps</th>
          <th>Actions</th> <!-- NOUVEL EN-TÊTE -->
        </tr>
        <?php foreach ($filieres as $f):
          // Créer un ID sûr pour les éléments HTML
          $filiere_id_safe = htmlspecialchars(str_replace(' ', '_', $f['nom']));
          ?>
          <tr id="row-<?= $filiere_id_safe ?>">
            <td><?= htmlspecialchars($f['nom']) ?></td>
            <td>
              <form onsubmit="return false;">
                <input type="hidden" name="filiere" value="<?= htmlspecialchars($f['nom']) ?>">
                <input type="file" name="emploi_pdf" id="file_<?= $filiere_id_safe ?>" accept="application/pdf"
                  onchange="uploadPDF(this, '<?= htmlspecialchars($f['nom']) ?>')">
                <label for="file_<?= $filiere_id_safe ?>">Importer</label>
              </form>
            </td>
            <!-- On garde l'ID sur cette cellule pour la mise à jour -->
            <td id="pdf-cell-<?= $filiere_id_safe ?>">
              <?php if (!empty($f['emploi_pdf'])): ?>
                <button
                  onclick="showPDF('<?= htmlspecialchars($f['emploi_pdf']) ?>', '<?= htmlspecialchars($f['nom']) ?>')">📄
                  Voir PDF</button> <?php else: ?>
                <span style="color:gray;">Pas encore ajouté</span>
              <?php endif; ?>
            </td>
            <!-- NOUVELLE CELLULE POUR LES ACTIONS -->
            <td id="action-cell-<?= $filiere_id_safe ?>">
              <?php if (!empty($f['emploi_pdf'])): ?>
                <button class="btn-delete" onclick="deleteTimetable('<?= htmlspecialchars($f['nom']) ?>')">
                  Supprimer
                </button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>

      <div id="notification-container" class="notification-container"></div>

      <div id="pdf-modal-overlay" class="pdf-modal-overlay">
        <div class="pdf-modal-content">
          <div class="pdf-modal-header">
            <h3 id="pdf-modal-title">Emploi du temps</h3>
            <button id="pdf-modal-close" class="pdf-modal-close-btn">×</button>
          </div>
          <div class="pdf-modal-body">
            <iframe id="pdfFrame" src="" frameborder="0"></iframe>
          </div>
        </div>
      </div>

      <div id="uploadMessage" style="text-align: center; margin-top: 20px;"></div>

    </div>
  </div>

  <script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>

  <div id="notification-modal-overlay" class="modal-overlay">
    <div id="notification-modal-content" class="modal-content">
      <div id="modal-icon-container">
      </div>
      <span id="modal-message-text"></span>
    </div>
  </div>

  <script>
    function showPDF(pdfUrl) {
      const viewer = document.getElementById('pdfViewer');
      const frame = document.getElementById('pdfFrame');
      frame.src = pdfUrl;
      viewer.style.display = 'block';
      window.scrollTo({
        top: viewer.offsetTop - 50,
        behavior: 'smooth'
      });
    }

    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast-notification');
      const toastMessage = document.getElementById('toast-message');
      const icon = toast.querySelector('i');

      toastMessage.textContent = message;
      toast.className = 'toast show ' + type;

      switch (type) {
        case 'success':
          icon.className = 'bx bx-check-circle';
          break;
        case 'error':
          icon.className = 'bx bx-x-circle';
          break;
        case 'warning':
          icon.className = 'bx bx-error-alt';
          break;
        default:
          icon.className = 'bx bx-info-circle';
          break;
      }
      setTimeout(() => {
        toast.className = 'toast ' + type;
      }, 3000);
    }

    function uploadPDF(input, filiere) {
      const file = input.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('emploi_pdf', file);
      formData.append('filiere', filiere);

      fetch('upload_emploi.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          if (!response.ok) throw new Error('Erreur réseau');
          return response.json();
        })
        .then(data => {
          showToast(data.message, data.status);
          if (data.status === 'success' && data.pdf_url) {
            const filiereId = filiere.replace(/ /g, '_');
            const pdfCell = document.getElementById(`pdf-cell-${filiereId}`);

            if (pdfCell) {
              const newButton = document.createElement('button');
              newButton.innerHTML = '📄 Voir PDF';
              // Mettez à jour l'appel ici aussi
              newButton.onclick = () => showPDF(data.pdf_url, filiere);

              pdfCell.innerHTML = '';
              pdfCell.appendChild(newButton);
            }
          }
        })
        .catch(error => {
          console.error('Erreur:', error);
          showToast("Une erreur de communication est survenue.", 'error');
        });

      input.value = '';
    }

    // Initialisation de Feather Icons (si vous l'utilisez toujours ailleurs sur la page)
    feather.replace();

    function deleteTimetable(filiere) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer l'emploi du temps pour la filière "${filiere}" ? Cette action est irréversible.`)) {
        return;
      }

      const formData = new FormData();
      formData.append('action', 'delete_timetable');
      formData.append('filiere', filiere);

      fetch('', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          showToast(data.message, data.status);

          if (data.status === 'success') {
            const filiereId = filiere.replace(/ /g, '_');
            const pdfCell = document.getElementById(`pdf-cell-${filiereId}`);
            const actionCell = document.getElementById(`action-cell-${filiereId}`);

            if (pdfCell) {
              pdfCell.innerHTML = `<span style="color:gray;">Pas encore ajouté</span>`;
            }
            if (actionCell) {
              actionCell.innerHTML = '';
            }
          }
        })
        .catch(error => {
          console.error('Erreur lors de la suppression:', error);
          showToast('Une erreur de communication est survenue.', 'error');
        });
    }

    const pdfModalOverlay = document.getElementById('pdf-modal-overlay');
    const pdfFrame = document.getElementById('pdfFrame');
    const pdfModalCloseBtn = document.getElementById('pdf-modal-close');
    const pdfModalTitle = document.getElementById('pdf-modal-title');

    /**
     * Ouvre la modale et affiche le PDF pour une filière donnée.
     * @param {string} pdfUrl L'URL du fichier PDF.
     * @param {string} filiereName Le nom de la filière (optionnel, pour le titre).
     */
    function showPDF(pdfUrl, filiereName = 'Emploi du temps') {
      // Met à jour la source de l'iframe et le titre
      pdfFrame.src = pdfUrl;
      pdfModalTitle.textContent = `Emploi du temps - ${filiereName}`;

      // Affiche la modale
      pdfModalOverlay.classList.add('show');

      // Empêche le défilement de la page en arrière-plan
      document.body.style.overflow = 'hidden';
    }

    /**
     * Ferme la modale PDF.
     */
    function closePDFModal() {
      pdfModalOverlay.classList.remove('show');

      // Réactive le défilement de la page
      document.body.style.overflow = 'auto';

      // Vide l'iframe pour stopper le chargement du PDF (important pour la performance)
      pdfFrame.src = '';
    }

    // --- ÉCOUTEURS D'ÉVÉNEMENTS POUR LA FERMETURE ---
    // 1. Fermer en cliquant sur le bouton (X)
    pdfModalCloseBtn.addEventListener('click', closePDFModal);

    // 2. Fermer en cliquant sur le fond gris (l'overlay)
    pdfModalOverlay.addEventListener('click', function (event) {
      if (event.target === pdfModalOverlay) {
        closePDFModal();
      }
    });

    // 3. Fermer en appuyant sur la touche "Échap"
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && pdfModalOverlay.classList.contains('show')) {
        closePDFModal();
      }
    });
  </script>
</body>

</html>