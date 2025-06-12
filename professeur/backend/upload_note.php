<?php
// FILE: /professeur/backend/upload_note.php

// Start output buffering immediately. This captures everything.
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session must be started to check for user permissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    ob_clean(); // Clean the buffer before sending the error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']);
    exit;
}

// --- All checks passed, now prepare for the real response ---

// Discard any stray output (like PHP notices, BOMs, or whitespace) that was captured.
ob_clean();
header('Content-Type: application/json');


// --- Start of your original, correct logic ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['notes_pdf'])) {
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide ou fichier manquant.']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=projet_web;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion à la base de données.']);
    exit;
}

$moduleId = filter_var($_POST['module_id'], FILTER_VALIDATE_INT);
$sessionType = $_POST['session_type'];

if (!$moduleId || !in_array($sessionType, ['normal', 'rattrapage'])) {
    echo json_encode(['status' => 'error', 'message' => 'Données de module ou de session invalides.']);
    exit;
}

$dbColumn = ($sessionType === 'normal') ? 'fichier_notes_normal' : 'fichier_notes_rattrapage';
$safeFileName = "module_{$moduleId}_{$sessionType}_" . time() . ".pdf";

$uploadDir = '../fichiers_notes/'; 
$serverFilePath = $uploadDir . $safeFileName;
$dbPath = "/e-service/professeur/fichiers_notes/" . $safeFileName;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Échec de la création du dossier de destination. Vérifiez les permissions.']);
        exit;
    }
}

if (move_uploaded_file($_FILES['notes_pdf']['tmp_name'], $serverFilePath)) {
    $stmt = $db->prepare("UPDATE unités_ensignement SET {$dbColumn} = :path WHERE id = :id");
    $stmt->execute([':path' => $dbPath, ':id' => $moduleId]);
    echo json_encode(['status' => 'success', 'message' => 'Fichier importé avec succès.', 'pdf_url' => $dbPath]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erreur critique lors de l\'enregistrement du fichier.']);
}

exit;