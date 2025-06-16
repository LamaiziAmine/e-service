<?php
session_start();

// --- Sécurité et Vérifications Initiales ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'chef de departement') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['choix_id']) || !isset($_GET['action'])) {
    $_SESSION['message'] = "Erreur : Paramètres de traitement manquants.";
    header('Location: gestion_affectations.php');
    exit;
}

$choix_id = (int)$_GET['choix_id'];
$action = $_GET['action'];
$department_id = $_SESSION['user_department'] ?? die("Erreur : département non défini.");

if (!in_array($action, ['valider', 'refuser'])) {
    $_SESSION['message'] = "Erreur : Action non valide.";
    header('Location: gestion_affectations.php');
    exit;
}

// --- Connexion à la base de données ---
$host = 'localhost'; $db = 'projet_web'; $user = 'root'; $pass = '';
$connection = new mysqli($host, $user, $pass, $db);
if ($connection->connect_error) { die("Erreur de connexion : " . $connection->connect_error); }

// --- Vérifier que le choix existe et appartient au bon département ---
$sql_check = "
    SELECT c.id, c.id_professeur, c.id_module, u.nom, u.prenom, ue.code_module, ue.department_id
    FROM choix_ues c
    JOIN users u ON c.id_professeur = u.id
    JOIN unités_ensignement ue ON c.id_module = ue.id
    WHERE c.id = ?";
$check_stmt = $connection->prepare($sql_check);
$check_stmt->bind_param("i", $choix_id);
$check_stmt->execute();
$choix_data = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$choix_data) {
    $_SESSION['message'] = "Erreur : Ce choix est introuvable ou a déjà été traité.";
    header('Location: gestion_affectations.php');
    exit;
}

if ($choix_data['department_id'] != $department_id) {
    $_SESSION['message'] = "Accès refusé : Ce choix ne concerne pas votre département.";
    header('Location: gestion_affectations.php');
    exit;
}

// --- Traitement ---
$connection->begin_transaction();

try {
    $prof_name = htmlspecialchars($choix_data['nom'] . ' ' . $choix_data['prenom']);
    $module_code = htmlspecialchars($choix_data['code_module']);

    if ($action === 'valider') {
        // --- ACTION: VALIDER ---
        // 1. Insérer l'affectation validée
        
        $sql_insert = "INSERT INTO affectations (id_user, id_ue, id_type) VALUES (?, ?, '1')";
        $insert_stmt = $connection->prepare($sql_insert);
        $insert_stmt->bind_param("ii", $choix_data['id_professeur'], $choix_data['id_module']);
        $insert_stmt->execute();
        $insert_stmt->close();

        // 2. Supprimer le choix de la table des choix
        $sql_delete = "DELETE FROM choix_ues WHERE id = ?";
        $delete_stmt = $connection->prepare($sql_delete);
        $delete_stmt->bind_param("i", $choix_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $_SESSION['message'] = "Affectation validée pour {$prof_name} sur le module {$module_code}.";

    } else { // action === 'refuser'
        // --- ACTION: REFUSER ---
        // 1. Supprimer simplement le choix de la table
        $sql_delete = "DELETE FROM choix_ues WHERE id = ?";
        $delete_stmt = $connection->prepare($sql_delete);
        $delete_stmt->bind_param("i", $choix_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        $_SESSION['message'] = "Le choix de {$prof_name} pour le module {$module_code} a été refusé et supprimé.";
    }

    // Si tout s'est bien passé
    $connection->commit();

} catch (Exception $e) {
    $connection->rollback();
    $_SESSION['message'] = "Erreur Transactionnelle : " . $e->getMessage();
}

$connection->close();
header('Location: gestion_affectations.php');
exit;
?>