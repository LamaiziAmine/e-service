<?php
include 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et validation des champs
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $department_id = filter_var($_POST['department_id'], FILTER_VALIDATE_INT);
    $role = 'professeur';

    // Vérification des champs requis
    if ($email && $password_raw && $nom && $prenom && $department_id !== false) {
        $stmt = $connection->prepare("INSERT INTO users (email, password, role, department_id, nom, prenom)
                                      VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $email, $password, $role, $department_id, $nom, $prenom);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Professeur ajouté avec succès.</p>";
        } else {
            $message = "<p style='color: red;'>Erreur : " . htmlspecialchars($stmt->error) . "</p>";
        }

        $stmt->close();
    } else {
        $message = "<p style='color: red;'>Veuillez remplir tous les champs correctement.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Professeur</title>
</head>
<body>
    <h2>Ajouter un professeur</h2>

    <?= $message ?>

    <form method="post">
        <input name="email" placeholder="Email" required type="email"><br>
        <input name="password" placeholder="Mot de passe" required type="password"><br>
        <input name="nom" placeholder="Nom" required><br>
        <input name="prenom" placeholder="Prénom" required><br>
        <input name="department_id" placeholder="ID du département" required type="number"><br>
        <button type="submit">Ajouter professeur</button>
    </form>
</body>
</html>
