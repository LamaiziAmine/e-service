<?php
$host = "localhost";        // أو 127.0.0.1
$user = "root";             // اسم المستخدم، غالباً يكون root في localhost
$password = "";             // كلمة السر، غالباً فارغة في localhost
$database = "projet_web"; // ← عوضها باسم قاعدة البيانات ديالك

$conn = new mysqli($host, $user, $password, $database);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

$id_vacataire = $_POST['id_vacataire'];
$module = $_POST['code_module'];
$types = $_POST['types'];

foreach ($types as $type) {
    $getType = $conn->query("SELECT id FROM types_intervention WHERE type = '$type'");
    $idType = $getType->fetch_assoc()['id'];

    $check = $conn->query("SELECT * FROM affectations WHERE id_ue='$module' AND id_type=$idType");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO affectations (id_user, id_ue, id_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_vacataire, $module, $idType);
        $stmt->execute();
    }
}

header("Location: affectationVacataire.php"); // rafraîchir la page
?>