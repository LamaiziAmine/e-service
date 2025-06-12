<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur la Plateforme E-service</title>
    
    <!-- Google Fonts pour une typographie moderne -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- Style général --- */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: #eef2f9; /* Un fond très clair et doux */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* --- Conteneur principal --- */
        .page-container {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Deux colonnes de taille égale */
            align-items: center;
            gap: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Pour que le border-radius s'applique à l'image */
            padding: 2rem;
        }

        /* --- Colonne de gauche (Texte) --- */
        .text-content {
            padding: 2rem;
            animation: slideInLeft 1s ease-out;
        }

        .text-content .logo {
            max-width: 80px;
            margin-bottom: 20px;
        }

        .text-content h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #1a253c;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .text-content h1 span {
            color: #0056b3; /* Couleur accentuée pour le nom de l'école */
        }
        
        .text-content p {
            font-size: 1.1rem;
            color: #5a6a7e;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* --- Bouton de connexion --- */
        .login-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px; /* Pour un look de "pilule" moderne */
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-button:hover {
            background-color: #0056b3;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
        }

        /* --- Colonne de droite (Image) --- */
        .image-content {
            text-align: center;
            animation: fadeIn 1.2s ease-out;
        }
        
        .image-content img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
        }
        
        /* --- Animations --- */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* --- Responsive Design pour les petits écrans --- */
        @media (max-width: 992px) {
            .page-container {
                grid-template-columns: 1fr; /* Une seule colonne sur mobile */
                text-align: center;
                padding: 2rem 1rem;
            }
            .image-content {
                order: -1; /* Place l'image en haut sur mobile */
                margin-bottom: 2rem;
            }
            .text-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="page-container">
        
        <!-- Section Texte -->
        <div class="text-content">
            <img src="https://upload.wikimedia.org/wikipedia/fr/4/40/Ensa-hoceima.png" alt="Logo ENSAH" class="logo">
            <h1>
                Plateforme E-service de l'<span>ENSAH</span>
            </h1>
            <p>
                Accédez à tous vos services pédagogiques en un seul endroit. Consultez vos modules, gérez les notes, et suivez l'ensemble de vos responsabilités académiques de manière simple et efficace.
            </p>
            
            <!-- Assurez-vous que le lien pointe vers votre page de connexion -->
            <a href="\e-service\login.php" class="login-button">Se connecter</a>
        </div>

        <!-- Section Image -->
        <div class="image-content">
            <!-- Vous pouvez utiliser n'importe quelle image d'illustration ici -->
            <img src="https://cdni.iconscout.com/illustration/premium/thumb/online-education-3749718-3141142.png" alt="Illustration de e-learning">
        </div>

    </div>

</body>
</html>