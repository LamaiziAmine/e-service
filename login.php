<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'chefdepart/config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email && $password) {
        $stmt = $connection->prepare("SELECT id, password, role, department_id, nom, prenom FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Trim role to avoid trailing spaces
                $role = trim($user['role']);
                
                // Debug - affiche le rôle (à retirer après test)
                // echo "Role récupéré : '" . $role . "'";
                // exit;

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $role;
                $_SESSION['user_department'] = $user['department_id'];
                $_SESSION['user_name'] = $user['nom'] . ' ' . $user['prenom'];

                switch ($role) {
                    case 'chef de departement':
                        header('Location: /e-service/chefdepart/dashboard_chef.php');
                        exit;
                    case 'cordonnateur':
                        header('Location: home.php');
                        exit;
                    case 'professeur':
                        header('Location: /e-service/professeur/UEspage.php');
                        exit;
                    case 'vacataire':
                        header('Location: dashboard_vacataire.php');
                        exit;
                    case 'admin':
                        header('Location: /e-service/admin/profcompte.php');
                        exit;
                    default:
                        $error = "Rôle utilisateur inconnu.";
                }
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Email non trouvé.";
        }

        $stmt->close();
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Connexion - Plateforme eServices</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">

  <div class="bg-white rounded-lg shadow-lg flex w-[900px] overflow-hidden">
    <!-- Left Side with Image -->
    <div class="w-1/2 relative">
      <img src="background-image.png" alt="Campus" class="w-full h-full object-cover" />
      <div class="absolute inset-0 bg-white bg-opacity-60 flex items-center justify-center">
        <img src="logo.png" alt="e-Services Logo" class="h-32" />
      </div>
    </div>

    <!-- Right Side with Form -->
    <div class="w-1/2 p-10 flex flex-col justify-center">
      <h2 class="text-2xl font-semibold text-center mb-6">Plateforme eServices</h2>

      <?php if ($error): ?>
        <div class="mb-4 text-red-600 text-center font-medium"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="" class="space-y-4">
        <input type="email" name="email" placeholder="Entrer votre email" required
               class="w-full px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" />

        <input type="password" name="password" placeholder="Mot de passe" required
               class="w-full px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" />

        <div class="flex items-center">
          <input type="checkbox" id="remember" name="remember" class="mr-2" />
          <label for="remember" class="text-sm text-gray-600">Se rappeler de moi</label>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-full hover:bg-blue-700 transition">
          Se connecter
        </button>
      </form>

      <p class="text-center text-xs text-gray-500 mt-6">Copyright © 2020 - Tous droits réservés</p>
    </div>
  </div>

</body>
</html>
