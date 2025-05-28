// ======== upload_emploi.php ========
<?php
header('Content-Type: application/json');

try {
  $db = new PDO('mysql:host=localhost;dbname=projet_web;charset=utf8', 'root', '');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion à la base de données.']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['emploi_pdf']) && isset($_POST['filiere'])) {
  $filiere = $_POST['filiere'];
  $uploadDir = '../emplois_pdf/';
  $uploadFile = $uploadDir . $filiere . ".pdf";

  if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  if (move_uploaded_file($_FILES['emploi_pdf']['tmp_name'], $uploadFile)) {
    $stmt = $db->prepare("UPDATE promotion SET emploi_pdf = :path WHERE nom = :nom");
    $stmt->execute([
      ':path' => $uploadFile,
      ':nom' => $filiere
    ]);

    if ($stmt->rowCount() > 0) {
      echo json_encode(['status' => 'success', 'message' => "✅ PDF importé avec succès pour la filière $filiere."]);
    } else {
      echo json_encode(['status' => 'warning', 'message' => "⚠️ PDF copié, mais aucune mise à jour pour '$filiere'."]);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => "❌ Erreur lors de l'importation du fichier."]);
  }
} else {
  echo json_encode(['status' => 'error', 'message' => "Requête invalide."]);
}
?>
