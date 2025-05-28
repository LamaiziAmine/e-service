<?php
// Connexion à ta DB
try {
  $db = new PDO('mysql:host=localhost;dbname=projet_web;charset=utf8', 'root', '');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Erreur de connexion : " . $e->getMessage();
  die();
}

// Récupérer les filières
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
  </style>
</head>

<body>
  <div class="layer"></div>
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">
    <?php include "sidebar.php" ?>
    <div class="main-wrapper">
      <?php include "navbar.php" ?><br>
      <h2 style="margin-left: 20px;" class="main-title">Gestion des emplois du temps </h2>
      <table>
        <tr>
          <th>Filière</th>
          <th>Importer Emploi du Temps (PDF)</th>
          <th>Voir Emploi du Temps</th>
        </tr>
        <?php foreach ($filieres as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['nom']) ?></td>
            <td>
              <form onsubmit="return false;">
                <input type="hidden" name="filiere" value="<?= $f['nom'] ?>">
                <input type="file" name="emploi_pdf" id="file_<?= $f['nom'] ?>" accept="application/pdf"
                  onchange="uploadPDF(this, '<?= $f['nom'] ?>')">
                <label for="file_<?= $f['nom'] ?>">Importer</label>
              </form>
            </td>
            <td>
              <?php if (!empty($f['emploi_pdf'])): ?>
                <button onclick="showPDF('<?= htmlspecialchars($f['emploi_pdf']) ?>')">📄 Voir PDF</button>
              <?php else: ?>
                <span style="color:gray;">Pas encore ajouté</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>

      <div id="pdfViewer" style="display:none; margin-top: 30px; text-align: center;">
        <h3>📄 Emploi du temps sélectionné :</h3>
        <iframe id="pdfFrame" src="" width="80%" height="600px"
          style="border: 1px solid #ccc; border-radius: 10px;"></iframe>
      </div>

      <div id="uploadMessage" style="text-align: center; margin-top: 20px;"></div>

    </div>
  </div>

  <script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>
  <script>
    function showPDF(pdfUrl) {
      const viewer = document.getElementById('pdfViewer');
      const frame = document.getElementById('pdfFrame');
      frame.src = pdfUrl;
      viewer.style.display = 'block';
      window.scrollTo({ top: viewer.offsetTop - 50, behavior: 'smooth' });
    }

    function uploadPDF(input, filiere) {
      const file = input.files[0];
      const formData = new FormData();
      formData.append('emploi_pdf', file);
      formData.append('filiere', filiere);

      fetch('upload_emploi.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          const msg = document.getElementById('uploadMessage');
          msg.innerHTML = `<p style="color:${data.status === 'success' ? 'green' : (data.status === 'warning' ? 'orange' : 'red')}; font-weight:bold;">${data.message}</p>`;
          if (data.status === 'success') {
            setTimeout(() => location.reload(), 1500);
          }
        })
        .catch(error => {
          alert('Erreur lors de l'envoi : ' + error);
        });
    }
  </script>
</body>
</html>
