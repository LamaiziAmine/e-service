<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cordonnateur') {
    header("Location: ../login.php");
    exit; 
}
$coordonateur_id = $_SESSION['user_id'];

$currentPage = basename($_SERVER['PHP_SELF']); 
$etudiants_par_groupe = 20;  

// Nombre d'étudiants par filière
$filières = [
    "GI1" => 45,
    "GI2" => 42, 
    "GI3" => 38
]; 

$groupes = [];  

if ($_SERVER["REQUEST_METHOD"] == "POST") {     
    $filiere = $_POST["filiere"];     
    $type_groupe = $_POST["type_groupe"];      
    
    // Récupérer le nombre d'étudiants pour cette filière
    $etudiants_filiere = $filières[$filiere];
    
    // Calcul du nombre de groupes     
    $nombre_groupes = ceil($etudiants_filiere / $etudiants_par_groupe);     
    $groupes[$filiere][$type_groupe] = [
        'nombre_groupes' => $nombre_groupes,
        'etudiants_total' => $etudiants_filiere
    ]; 
} 
?> 

<!DOCTYPE html> 
<html lang="fr"> 
<head>   
    <meta charset="UTF-8">   
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Définir les Groupes TD/TP</title>   
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
 
        .container1 {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            margin: 40px auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .container1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header-section h2 {
            color: #2c3e50;
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 600;
            position: relative;
        }
        
        .header-section .subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
            margin-bottom: 0;
        }
        
        .stats-bar {
            display: flex;
            justify-content: space-around;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .form-container {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 1.1em;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }
        
        select {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1em;
            background: white;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        select:hover {
            border-color: #667eea;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .result-container {
            animation: slideInUp 0.5s ease-out;
        }
        
        .result {
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            border: none;
            border-left: 5px solid #28a745;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;
            position: relative;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.1);
        }
        
        .result-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2em;
            color: #28a745;
            opacity: 0.3;
        }
        
        .result-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #155724;
            margin-bottom: 10px;
        }
        
        .result-details {
            color: #155724;
            font-size: 1em;
            line-height: 1.6;
        }
        
        .highlight {
            background: linear-gradient(120deg, rgba(102, 126, 234, 0.2) 0%, transparent 100%);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .container1 {
                margin: 20px auto;
                padding: 25px;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-section h2 {
                font-size: 1.8em;
            }
        }
    </style> 
</head> 
<body>   
    <div class="page-flex">     
        <?php include "sidebar.php"; ?>     
        <div class="main-wrapper">       
            <?php include "navbar.php"; ?>       
            <main class="container1">
                <div class="header-section">
                    <h2><i class="fas fa-users-cog"></i> Gestion des Groupes</h2>
                    <p class="subtitle">Configuration des groupes TD et TP par filière</p>
                </div>
                
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-number"><?= array_sum($filières) ?></span>
                        <div class="stat-label">Étudiants Total</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $etudiants_par_groupe ?></span>
                        <div class="stat-label">Par Groupe</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($filières) ?></span>
                        <div class="stat-label">Filières</div>
                    </div>
                </div>
                
                <div class="form-container">
                    <form method="POST">           
                        <div class="form-group">
                            <label for="filiere"><i class="fas fa-graduation-cap"></i> Filière d'étude</label>
                            <div class="input-group">
                                <i class="fas fa-book input-icon"></i>
                                <select name="filiere" id="filiere" required>             
                                    <option value="" disabled selected>Sélectionnez une filière</option>             
                                    <?php foreach ($filières as $code => $nb_etudiants): ?>               
                                        <option value="<?= $code ?>" <?= (isset($_POST['filiere']) && $_POST['filiere'] == $code) ? 'selected' : '' ?>>
                                            <?= $code ?> (<?= $nb_etudiants ?> étudiants)
                                        </option>             
                                    <?php endforeach; ?>           
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="type_groupe"><i class="fas fa-layer-group"></i> Type de groupe</label>
                            <div class="input-group">
                                <i class="fas fa-users input-icon"></i>
                                <select name="type_groupe" id="type_groupe" required>             
                                    <option value="TD" <?= (isset($_POST['type_groupe']) && $_POST['type_groupe'] == 'TD') ? 'selected' : '' ?>>Travaux Dirigés (TD)</option>
                                    <option value="TP" <?= (isset($_POST['type_groupe']) && $_POST['type_groupe'] == 'TP') ? 'selected' : '' ?>>Travaux Pratiques (TP)</option>           
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-calculator"></i> Calculer les Groupes
                        </button>         
                    </form>
                </div>
                
                <?php if (!empty($groupes)): ?>
                    <div class="result-container">
                        <?php foreach ($groupes as $filiere => $types): ?>             
                            <?php foreach ($types as $type => $data): ?>               
                                <div class="result">
                                    <i class="fas fa-check-circle result-icon"></i>
                                    <div class="result-title">
                                        <i class="fas fa-info-circle"></i> Résultat du calcul
                                    </div>
                                    <div class="result-details">
                                        Pour la filière <span class="highlight"><?= $filiere ?></span> et le type <span class="highlight"><?= $type ?></span>,<br>
                                        il faut <span class="highlight"><?= $data['nombre_groupes'] ?> groupes</span><br>
                                        <small style="opacity: 0.8;">
                                            (<?= $data['etudiants_total'] ?> étudiants de <?= $filiere ?> répartis à raison de <?= $etudiants_par_groupe ?> étudiants par groupe)
                                        </small>
                                    </div>               
                                </div>             
                            <?php endforeach; ?>           
                        <?php endforeach; ?>
                    </div>         
                <?php endif; ?>       
            </main>     
        </div>   
    </div> 
     <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</body> 
</html>