<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'projet_web';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  echo json_encode(['error' => 'Erreur de connexion à la base de données']);
  exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo json_encode(['error' => 'ID invalide']);
  exit();
}

$id = intval($_GET['id']);

$sql = "SELECT u.*, d.nom as department_name 
        FROM users u 
        LEFT JOIN departements d ON u.department_id = d.id 
        WHERE u.id = ? AND u.role = 'professeur'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
  $row = $result->fetch_assoc();
  
  // Ne pas inclure le mot de passe dans la réponse pour des raisons de sécurité
  unset($row['password']);
  
  echo json_encode($row);
} else {
  echo json_encode(['error' => 'Professeur non trouvé']);
}

$stmt->close();
$conn->close();
?>