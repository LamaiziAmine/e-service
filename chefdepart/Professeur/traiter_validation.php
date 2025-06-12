<?php
session_start();
include '../config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['action'] ?? null;

if (!$id || !in_array($action, ['valider', 'refuser'], true)) {
    $_SESSION['message'] = "Paramètres invalides.";
    header("Location: gestion_affectations.php");
    exit;
}

$valeur = ($action === 'valider') ? 1 : -1;

// Vérifier si l'affectation existe et récupérer sa valeur actuelle
$stmt_check = $connection->prepare("SELECT status FROM affectations WHERE id = ?");
if (!$stmt_check) {
    $_SESSION['message'] = "Erreur de préparation de la requête.";
    header("Location: gestion_affectations.php");
    exit;
}
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$stmt_check->bind_result($status_actuel);
if (!$stmt_check->fetch()) {
    $_SESSION['message'] = "Affectation introuvable.";
    $stmt_check->close();
    header("Location: gestion_affectations.php");
    exit;
}
$stmt_check->close();

if ((int)$status_actuel === $valeur) {
    $_SESSION['message'] = "Cette affectation est déjà " . ($valeur === 1 ? "validée" : "refusée") . ".";
    header("Location: gestion_affectations.php");
    exit;
}

// Mise à jour du statut
$stmt = $connection->prepare("UPDATE affectations SET status = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("ii", $valeur, $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = ($action === 'valider')
            ? "✅ Affectation validée avec succès."
            : "❌ Affectation refusée avec succès.";
    } else {
        $_SESSION['message'] = "Erreur lors de l'exécution de la mise à jour.";
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Erreur de préparation de la requête.";
}

header("Location: gestion_affectations.php");
exit;
