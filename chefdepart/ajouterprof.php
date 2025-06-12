<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $specialite = $_POST['specialite'];
    $department_id = $_POST['department_id'];
    
    $role = 'professeur';

    $stmt = $connection->prepare("INSERT INTO users (email, password, role, department_id, nom, prenom, specialite)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $email, $password, $role, $department_id, $nom, $prenom, $specialite);
    
    if ($stmt->execute()) {
        echo "Professeur ajouté avec succès.";
    } else {
        echo "Erreur : " . $stmt->error;
    }

    $stmt->close();
}
?>

<form method="post">
  <input name="email" placeholder="Email" required type="email"><br>
  <input name="password" placeholder="Mot de passe" required type="password"><br>
  <input name="nom" placeholder="Nom" required><br>
  <input name="prenom" placeholder="Prénom" required><br>
  <input name="specialite" placeholder="Spécialité"><br>
  <input name="department_id" placeholder="ID du département" required type="number"><br>
  <button type="submit">Ajouter professeur</button>
</form>
