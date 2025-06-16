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

// Vérifier si l'ID est fourni
if (isset($_GET['id'])) {
  $id = $_GET['id'];
  
  // Requête pour récupérer les détails du vacataire
  $sql = "SELECT * FROM vacataire WHERE id = $id";
  $result = $conn->query($sql);
  
  if ($result->num_rows > 0) {
    // Convertir le résultat en tableau associatif
    $vacataire = $result->fetch_assoc();
    
    // Renvoyer les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($vacataire);
  } else {
    // Vacataire non trouvé
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Vacataire non trouvé']);
  }
} else {
  // ID non fourni
  header('HTTP/1.1 400 Bad Request');
  echo json_encode(['error' => 'ID non fourni']);
}

$conn->close();
?>