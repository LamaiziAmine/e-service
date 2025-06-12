<?php
// FILE: /professeur/backend/delete_note.php

// Start output buffering immediately.
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    ob_clean(); // Clean before sending error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']);
    exit;
}

// Discard any stray output.
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Requête invalide.']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=projet_web;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion BDD.']);
    exit;
}

$moduleId = filter_var($_POST['module_id'], FILTER_VALIDATE_INT);
$sessionType = $_POST['session_type'];

if (!$moduleId || !in_array($sessionType, ['normal', 'rattrapage'])) {
    echo json_encode(['status' => 'error', 'message' => 'Données invalides.']);
    exit;
}

$dbColumn = ($sessionType === 'normal') ? 'fichier_notes_normal' : 'fichier_notes_rattrapage';

$stmt_select = $db->prepare("SELECT {$dbColumn} FROM unités_ensignement WHERE id = :id");
$stmt_select->execute([':id' => $moduleId]);
$result = $stmt_select->fetch(PDO::FETCH_ASSOC);

if ($result && !empty($result[$dbColumn])) {
    $serverFilePath = $_SERVER['DOCUMENT_ROOT'] . $result[$dbColumn];
    if (file_exists($serverFilePath)) {
        unlink($serverFilePath);
    }
}

$stmt_update = $db->prepare("UPDATE unités_ensignement SET {$dbColumn} = NULL WHERE id = :id");
$stmt_update->execute([':id' => $moduleId]);
echo json_encode(['status' => 'success', 'message' => 'Fichier supprimé avec succès.']);

exit;