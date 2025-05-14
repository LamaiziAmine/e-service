<?php
session_start();

$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Erreur de connexion: " . $conn->connect_error);
}

$av = $_POST['annee_univ'];
$cm = $_POST['code_module'];
$intit = $_POST['intitule'];
$semestre = $_POST['semestre'];
$filiere = $_POST['filiere'];
$cours = $_POST['cours'];
$td = $_POST['td'];
$tp = $_POST['tp'];
$autre = $_POST['autre'];
$eva = $_POST['evaluation'];
$resp = $_POST['responsable'];

$sql = "INSERT INTO unités_ensignement (annee_univ, code_module, intitule_module, semestre, filiere, V_h_cours, V_h_TD, V_h_TP, V_h_Autre, V_h_Evaluation, responsable)
        VALUES ('$av', '$cm', '$intit', '$semestre', '$filiere', '$cours', '$td', '$tp', '$autre', '$eva', '$resp' )";

if ($conn->query($sql) === TRUE) {
  $_SESSION['message'] = "Descriptif ajouté avec succès.";
  $_SESSION['msg_type'] = "success";
} else {
  $_SESSION['message'] = "Erreur: " . $conn->error;
  $_SESSION['msg_type'] = "error";
}
$conn->close();

// رجع المستخدم لنفس الصفحة
header("Location: descriptifpage.php"); 
exit();

?>
