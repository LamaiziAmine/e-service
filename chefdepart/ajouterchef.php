<?php
include 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et sécurisation des champs
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $department_id = filter_var($_POST['department_id'], FILTER_VALIDATE_INT);
    $role = 'chef de departement';

    // Vérification des champs obligatoires
    if ($email && $password_raw && $nom && $prenom && $department_id) {
        $stmt = $connection->prepare("INSERT INTO users (email, password, role, department_id, nom, prenom)
                                      VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $email, $password, $role, $department_id, $nom, $prenom);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Chef de département ajouté avec succès.</p>";
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
    <title>Ajouter Chef de Département</title>
</head>
<body>
    <h2>Ajouter un chef de département</h2>

    <?= $message ?>

    <form method="post">
        <input name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <input name="nom" placeholder="Nom" required><br>
        <input name="prenom" placeholder="Prénom" required><br>
        <input type="number" name="department_id" placeholder="ID du département" required><br>
        <button type="submit">Ajouter chef de département</button>
    </form>
</body>
</html>
