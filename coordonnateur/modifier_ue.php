<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupération de l'UE à modifier
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM unités_ensignement WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ue = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    die("ID de l'UE manquant !");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $code_module = $_POST['code_module'];
    $intitule = $_POST['intitule_module'];
    $filiere = $_POST['filiere'];
    $semestre = $_POST['semestre'];
    $V_h_cours = intval($_POST['V_h_cours']);
    $V_h_TD = intval($_POST['V_h_TD']);
    $V_h_TP = intval($_POST['V_h_TP']);
    $V_h_Autre = intval($_POST['V_h_Autre']);
    $V_h_Evaluation = intval($_POST['V_h_Evaluation']);

    $stmt = $conn->prepare("UPDATE unités_ensignement SET 
        code_module = ?, 
        intitule_module = ?, 
        filiere = ?, 
        semestre = ?, 
        V_h_cours = ?, 
        V_h_TD = ?, 
        V_h_TP = ?, 
        V_h_Autre = ?, 
        V_h_Evaluation = ? 
        WHERE id = ?");
    $stmt->bind_param("ssssiiiiii", $code_module, $intitule, $filiere, $semestre, 
                      $V_h_cours, $V_h_TD, $V_h_TP, $V_h_Autre, $V_h_Evaluation, $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "UE modifiée avec succès !";
        header("Location: gestion_ue.php");
        exit();
    } else {
        echo "Erreur : " . $stmt->error;
    }
}
?>