<?php
session_start();

// 1. CONNEXION
$host = 'localhost'; $db = 'projet_web'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erreur de connexion : " . $conn->connect_error); }

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 2. INITIALISATION
$error_message = "";
$success_message = "";

if(isset($_GET['add_success'])) { $success_message = "Responsabilité ajoutée avec succès."; }
if(isset($_GET['edit_success'])) { $success_message = "Responsabilité modifiée avec succès."; }
if(isset($_GET['delete_success'])) { $success_message = "Responsabilité supprimée avec succès."; }

// 3. LOGIQUE CRUD
// Handle Ajouter
if (isset($_POST['action']) && $_POST['action'] == 'Ajouter') {
    $id_prof = intval($_POST['id_professeur']);
    $nom_respo = $_POST['nom_responsabilite'];
    $annee = trim($_POST['annee_universitaire']);
    $id_departement = !empty($_POST['id_departement']) ? intval($_POST['id_departement']) : null;
    $id_filiere = !empty($_POST['id_filiere']) ? intval($_POST['id_filiere']) : null;

    if ($id_prof > 0 && $nom_respo !== '' && $annee !== '') {
        $stmt = $conn->prepare("INSERT INTO responsabilites (id_professeur, nom_responsabilite, annee_universitaire, id_departement, id_filiere) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_prof, $nom_respo, $annee, $id_departement, $id_filiere);
        if ($stmt->execute()) {
            header("Location: responsabilities.php?add_success=1");
            exit();
        } else { $error_message = "Erreur lors de l'ajout : " . $stmt->error; }
    } else { $error_message = "Veuillez remplir tous les champs obligatoires."; }
}

// Handle Modifier
if (isset($_POST['action']) && $_POST['action'] == 'Modifier') {
    $id = intval($_POST['id_edit']);
    $id_prof = intval($_POST['id_professeur']);
    $nom_respo = $_POST['nom_responsabilite'];
    $annee = trim($_POST['annee_universitaire']);
    $id_departement = !empty($_POST['id_departement']) ? intval($_POST['id_departement']) : null;
    $id_filiere = !empty($_POST['id_filiere']) ? intval($_POST['id_filiere']) : null;

    if ($id > 0 && $id_prof > 0 && $nom_respo !== '' && $annee !== '') {
        $stmt = $conn->prepare("UPDATE responsabilites SET id_professeur=?, nom_responsabilite=?, annee_universitaire=?, id_departement=?, id_filiere=? WHERE id=?");
        $stmt->bind_param("issssi", $id_prof, $nom_respo, $annee, $id_departement, $id_filiere, $id);
        if ($stmt->execute()) {
            header("Location: responsabilities.php?edit_success=1");
            exit();
        } else { $error_message = "Erreur lors de la modification : " . $stmt->error; }
    } else { $error_message = "Veuillez remplir tous les champs obligatoires pour modifier."; }
}

// Handle Supprimer
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM responsabilites WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: responsabilities.php?delete_success=1");
            exit();
        } else { $error_message = "Erreur lors de la suppression : " . $stmt->error; }
    }
}

// 4. RÉCUPÉRATION DES DONNÉES
$departements = $conn->query("SELECT id, nom FROM departement ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$filieres = $conn->query("SELECT id, nom FROM filiere ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$professeurs = $conn->query("SELECT id, nom, prenom FROM users WHERE role IN ('professeur', 'chef de departement') ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$responsabilites = $conn->query("SELECT r.id, r.nom_responsabilite, r.annee_universitaire, u.id AS id_professeur, u.nom, u.prenom, r.id_departement, d.nom AS departement_nom, r.id_filiere, f.nom AS filiere_nom FROM responsabilites r JOIN users u ON r.id_professeur = u.id LEFT JOIN departement d ON r.id_departement = d.id LEFT JOIN filiere f ON r.id_filiere = f.id ORDER BY r.annee_universitaire DESC, u.nom ASC")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Responsabilités</title>
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        .main-content {
            padding: 30px;
            min-height: 100vh;
        }

        .container-respo {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        h2.main-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #64748b;
            font-size: 1.1rem;
            margin-top: 10px;
            font-weight: 400;
        }

        /* Messages d'alerte */
        .alert, .success {
            padding: 18px 24px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-weight: 500;
            border: none;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInDown 0.5s ease-out;
        }

        .alert {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }

        .success {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
            box-shadow: 0 8px 25px rgba(81, 207, 102, 0.3);
        }

        /* Formulaire */
        .form-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            color: #475569;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #374151;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-control:hover {
            border-color: #d1d5db;
        }

        /* Boutons */
        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(81, 207, 102, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(81, 207, 102, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(108, 117, 125, 0.4);
        }

        /* Tableau */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .table-respo {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table-respo th,
        .table-respo td {
            padding: 18px 24px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-respo th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-respo tbody tr {
            transition: all 0.3s ease;
        }

        .table-respo tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: scale(1.01);
        }

        .table-respo td {
            color: #374151;
            font-weight: 500;
        }

        /* Badges pour les responsabilités */
        .responsibility-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .responsibility-badge.chefdedepartement {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .responsibility-badge.coordonnateur {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .responsibility-badge.autre {
            background: linear-gradient(135deg, #a8e6cf, #88d8a3);
            color: #2d5a47;
        }

        /* Boutons d'action */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
            font-weight: 500;
        }

        /* --- THIS IS THE CHANGE --- */
        .btn-edit {
            background: linear-gradient(135deg, #667eea, #764ba2); /* Changed to blue gradient */
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3); /* Changed to blue shadow */
        }
        /* --- END OF CHANGE --- */


        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
            color: white;
        }

        /* États des champs cachés */
        .hidden-field {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .visible-field {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Message vide */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e1;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #475569;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .container-respo {
                padding: 25px;
            }
            
            h2.main-title {
                font-size: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                justify-content: stretch;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    <div class="page-flex">
        
        <?php include "sidebar.php"; ?>
        
        <div class="main-wrapper">
            <?php include "navbar.php"; ?>
            
            <main class="main-content" id="skip-target">
                <div class="container-respo">
                    <div class="page-header">
                        <h2 class="main-title">
                            <i class="fas fa-users-cog"></i>
                            Gestion des Responsabilités
                        </h2>
                        <p class="subtitle">Affectation et gestion des responsabilités académiques</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="success">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <h3 class="form-title">
                            <i class="fas fa-plus-circle"></i>
                            <span id="form-title-text">Affecter une nouvelle responsabilité</span>
                        </h3>
                        
                        <form method="post" action="responsabilities.php" id="respoForm">
                            <input type="hidden" name="id_edit" id="id_edit">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="id_professeur">
                                        <i class="fas fa-user-tie"></i>
                                        Professeur
                                    </label>
                                    <select name="id_professeur" id="id_professeur" class="form-control" required>
                                        <option value="">-- Sélectionner un professeur --</option>
                                        <?php foreach ($professeurs as $prof): ?>
                                            <option value="<?= $prof['id'] ?>">
                                                <?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="nom_responsabilite">
                                        <i class="fas fa-briefcase"></i>
                                        Type de responsabilité
                                    </label>
                                    <select name="nom_responsabilite" id="nom_responsabilite" class="form-control" required>
                                        <option value="">-- Choisir une responsabilité --</option>
                                        <option value="chef de departement">Chef de Département</option>
                                        <option value="coordonnateur">Coordonnateur de Filière</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="annee_universitaire">
                                        <i class="fas fa-calendar-alt"></i>
                                        Année universitaire
                                    </label>
                                    <input type="text" name="annee_universitaire" id="annee_universitaire" 
                                           class="form-control" placeholder="Ex: 2024/2025" required pattern="\d{4}\/\d{4}">
                                </div>

                                <div class="form-group" id="departement_field" style="display: none;">
                                    <label for="id_departement">
                                        <i class="fas fa-building"></i>
                                        Département
                                    </label>
                                    <select name="id_departement" id="id_departement" class="form-control">
                                        <option value="">-- Sélectionner un département --</option>
                                        <?php foreach ($departements as $dep): ?>
                                            <option value="<?= $dep['id'] ?>">
                                                <?= htmlspecialchars($dep['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group" id="filiere_field" style="display: none;">
                                    <label for="id_filiere">
                                        <i class="fas fa-graduation-cap"></i>
                                        Filière
                                    </label>
                                    <select name="id_filiere" id="id_filiere" class="form-control">
                                        <option value="">-- Sélectionner une filière --</option>
                                        <?php foreach ($filieres as $fil): ?>
                                            <option value="<?= $fil['id'] ?>">
                                                <?= htmlspecialchars($fil['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" name="action" value="Ajouter" class="btn btn-primary" id="btnAjouter">
                                    <i class="fas fa-plus"></i> Ajouter
                                </button>
                                <button type="submit" name="action" value="Modifier" class="btn btn-primary" id="btnModifier" style="display: none;">
                                    <i class="fas fa-check"></i> Modifier
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnAnnuler">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-list"></i>
                            <h3>Liste des responsabilités</h3>
                        </div>
                        
                        <?php if (!empty($responsabilites)): ?>
                            <table class="table-respo">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user"></i> Professeur</th>
                                        <th><i class="fas fa-briefcase"></i> Responsabilité</th>
                                        <th><i class="fas fa-calendar"></i> Année</th>
                                        <th><i class="fas fa-info-circle"></i> Détail</th>
                                        <th><i class="fas fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($responsabilites as $res): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($res['nom'] . ' ' . $res['prenom']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="responsibility-badge <?= str_replace(' ', '', $res['nom_responsabilite']) ?>">
                                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $res['nom_responsabilite']))) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($res['annee_universitaire']) ?></td>
                                            <td><?= htmlspecialchars($res['departement_nom'] ?? $res['filiere_nom'] ?? 'N/A') ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-edit btn-sm" type="button" 
                                                        onclick='editResponsabilite(<?= json_encode($res, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                    Modifier
                                                </button>
                                                <a href="responsabilities.php?supprimer=<?= $res['id'] ?>" 
                                                   class="btn btn-delete btn-sm" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette responsabilité ? Cette action est irréversible.')">
                                                    <i class="fas fa-trash"></i>
                                                    Supprimer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>Aucune responsabilité trouvée</h3>
                                <p>Commencez par affecter une responsabilité à un professeur.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('respoForm');
            const formTitle = document.getElementById('form-title-text');
            const idEditInput = document.getElementById('id_edit');
            const btnAjouter = document.getElementById('btnAjouter');
            const btnModifier = document.getElementById('btnModifier');
            const nomRespoSelect = document.getElementById('nom_responsabilite');
            const btnAnnuler = document.getElementById('btnAnnuler');

            function resetFormToAddState() {
                form.reset(); 
                idEditInput.value = ''; 
                btnAjouter.style.display = 'inline-flex';
                btnModifier.style.display = 'none';
                formTitle.textContent = 'Affecter une nouvelle responsabilité';
                document.getElementById('id_departement').value = '';
                document.getElementById('id_filiere').value = '';
                toggleFields();
            }

            function toggleFields() {
                const nomRespo = nomRespoSelect.value;
                const deptField = document.getElementById('departement_field');
                const filField = document.getElementById('filiere_field');
                
                deptField.style.display = 'none';
                filField.style.display = 'none';
                
                if (nomRespo === 'chef de departement') {
                    deptField.style.display = 'block';
                } else if (nomRespo === 'coordonnateur') {
                    filField.style.display = 'block';
                }
            }

            window.editResponsabilite = function(data) {
                idEditInput.value = data.id;
                document.getElementById('id_professeur').value = data.id_professeur;
                nomRespoSelect.value = data.nom_responsabilite;
                document.getElementById('annee_universitaire').value = data.annee_universitaire;
                
                toggleFields(); 
                
                document.getElementById('id_departement').value = data.id_departement || '';
                document.getElementById('id_filiere').value = data.id_filiere || '';
                
                btnAjouter.style.display = 'none';
                btnModifier.style.display = 'inline-flex';
                formTitle.textContent = 'Modifier la responsabilité';
                
                document.querySelector('.form-container').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }

            btnAnnuler.addEventListener('click', resetFormToAddState);
            nomRespoSelect.addEventListener('change', toggleFields);
            
            window.onload = function() {
                resetFormToAddState();
                
                const alerts = document.querySelectorAll('.alert, .success');
                alerts.forEach(function(alert) {
                    setTimeout(function() {
                        if (alert) {
                            alert.style.transition = 'opacity 0.3s, transform 0.3s';
                            alert.style.opacity = '0';
                            alert.style.transform = 'translateY(-20px)';
                            setTimeout(() => alert.remove(), 300);
                        }
                    }, 5000);
                });
            };
        });
    </script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</body>
</html>

