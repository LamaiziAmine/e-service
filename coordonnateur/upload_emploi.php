<?php
// Activez ceci temporairement pour voir toutes les erreurs potentielles
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

  $safeFileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filiere) . '.pdf';

  // --- CORRECTION ---
  // On remonte d'un dossier (de /coordonnateur/ vers /e-service/) avant de cibler /emplois_du_temps/
  $uploadDir = dirname(__DIR__) . '/emplois_du_temps/';
  $serverFilePath = $uploadDir . $safeFileName;

  // Le chemin pour la base de données reste le même, car c'est une URL web absolue depuis la racine du site.
  $dbPath = '/e-service/emplois_du_temps/' . $safeFileName;

  // Créer le dossier s'il n'existe pas
  if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
      echo json_encode(['status' => 'error', 'message' => "❌ Erreur: Impossible de créer le dossier de destination."]);
      exit;
    }
  }

  if (!is_writable($uploadDir)) {
    echo json_encode(['status' => 'error', 'message' => "❌ Erreur : Le dossier de destination n'est pas accessible en écriture. Vérifiez les permissions de '$uploadDir'."]);
    exit;
  }

  // move_uploaded_file renvoie true en cas de succès, false en cas d'échec
  if (move_uploaded_file($_FILES['emploi_pdf']['tmp_name'], $serverFilePath)) {
    $stmt = $db->prepare("UPDATE promotion SET emploi_pdf = :path WHERE nom = :nom");
    $stmt->execute([
      ':path' => $dbPath,
      ':nom' => $filiere
    ]);

    if ($stmt->rowCount() > 0) {
      echo json_encode([
        'status' => 'success',
        'message' => "✅ PDF importé avec succès pour la filière $filiere.",
        'pdf_url' => $dbPath // On ajoute cette information cruciale
      ]);
    } else {
      echo json_encode(['status' => 'warning', 'message' => "⚠️ PDF enregistré, mais le nom de filière '$filiere' n'a pas été trouvé dans la BDD."]);
    }
  } else {
    // ... (fin du code inchangée)
    echo json_encode(['status' => 'error', 'message' => "❌ Erreur lors de l'importation du fichier."]);
  }
} else {
  echo json_encode(['status' => 'error', 'message' => "Requête invalide."]);
}
?>