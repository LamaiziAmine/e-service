<?php
session_start();
include '../config.php';

// Vérifier connexion utilisateur
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$chef_id = $_SESSION['user_id'];

// On récupère le département du chef, au cas où besoin (inutile ici, tu peux enlever si tu veux)
//$stmt = $connection->prepare("SELECT department_id FROM users WHERE id = ?");
//$stmt->bind_param("i", $chef_id);
//$stmt->execute();
//$stmt->bind_result($department_id);
//$stmt->fetch();
//$stmt->close();

// Récupérer données POST
$id_user = $_POST['prof_id'] ?? null;
$ue_ids = $_POST['ue_ids'] ?? [];
$annee_univ = $_POST['annee_univ'] ?? '';

if (!$id_user || empty($ue_ids) || !$annee_univ) {
    header("Location: affecter.php?error=Veuillez sélectionner un professeur, au moins une unité, et une année universitaire.");
    exit;
}

// Vérifier format année universitaire YYYY-YYYY
if (!preg_match('/^\d{4}-\d{4}$/', $annee_univ)) {
    header("Location: affecter.php?error=Format d'année universitaire invalide.");
    exit;
}

$insert_values = [];
$insert_params = [];
$insert_types = "";

$connection->begin_transaction();

try {
    foreach ($ue_ids as $ue_id) {
        $types = $_POST["type_$ue_id"] ?? [];
        if (empty($types)) {
            continue;
        }

        foreach ($types as $type_enseignement) {
            $insert_values[] = "(?, ?, ?, ?)";
            // Bind types: id_user (int), id_ue (int), type_enseignement (string), annee_univ (string)
            $insert_types .= "iiss";

            $insert_params[] = (int)$id_user;
            $insert_params[] = (int)$ue_id;
            $insert_params[] = $type_enseignement;
            $insert_params[] = $annee_univ;
        }
    }

    if (empty($insert_values)) {
        throw new Exception("Aucune unité avec type d'enseignement sélectionnée.");
    }

    $sql = "INSERT INTO affectations (id_user, id_ue, type_enseignement, annee_univ) VALUES " . implode(", ", $insert_values);
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erreur préparation requête: " . $connection->error);
    }

    // Préparer bind_param (par référence)
    $bind_names = [];
    $bind_names[] = &$insert_types;
    for ($i = 0; $i < count($insert_params); $i++) {
        $bind_names[] = &$insert_params[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    $stmt->execute();
    $stmt->close();

    $connection->commit();

    header("Location: affecter.php?success=Affectations enregistrées avec succès pour l'année $annee_univ.");
    exit;
} catch (Exception $e) {
    $connection->rollback();
    header("Location: affecter.php?error=" . urlencode($e->getMessage()));
    exit;
}
