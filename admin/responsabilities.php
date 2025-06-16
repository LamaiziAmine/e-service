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

// 3. LOGIQUE CRUD (avec les nouveaux noms de boutons)

// **CHANGEMENT ICI** : On vérifie maintenant 'action' qui est le 'name' des boutons submit
// Handle Ajouter
if (isset($_POST['action']) && $_POST['action'] == 'Ajouter') {
    $id_prof = intval($_POST['id_professeur']);
    $nom_respo = $_POST['nom_responsabilite'];
    $annee = trim($_POST['annee_universitaire']);
    $id_departement = !empty($_POST['id_departement']) ? intval($_POST['id_departement']) : null;
    $id_filiere = !empty($_POST['id_filiere']) ? intval($_POST['id_filiere']) : null;

    if ($id_prof > 0 && $nom_respo !== '' && $annee !== '') {
        $stmt = $conn->prepare("INSERT INTO responsabilites (id_professeur, nom_responsabilite, annee_universitaire, id_departement, id_filiere) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $id_prof, $nom_respo, $annee, $id_departement, $id_filiere);
        if ($stmt->execute()) {
            header("Location: responsabilities.php?add_success=1");
            exit();
        } else { $error_message = "Erreur lors de l'ajout : " . $stmt->error; }
    } else { $error_message = "Veuillez remplir tous les champs obligatoires."; }
}

// **CHANGEMENT ICI** : On vérifie maintenant 'action' qui est le 'name' des boutons submit
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
        $stmt->bind_param("issiii", $id_prof, $nom_respo, $annee, $id_departement, $id_filiere, $id);
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
    <style>
        .main-content { padding: 20px; }
        .container-respo { padding: 25px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        h2.main-title { color: #2c3e50; font-weight: 700; margin-bottom: 25px; }
        .alert, .success { padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid; font-weight: 500;}
        .alert { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .form-respo { margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px; }
        .form-respo select, .form-respo input { padding: 10px; border-radius: 5px; border: 1px solid #ced4da; font-size: 1rem; flex-grow: 1; }
        .btn { padding: 10px 18px; border: 1px solid transparent; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; transition: background-color 0.2s; font-size: 1rem; }
        .btn-ajout { background-color: #28a745; border-color: #28a745; }
        .btn-modif { background-color: #007bff; border-color: #007bff; }
        .btn-annuler { background-color: #6c757d; border-color: #6c757d; }
        .table-respo { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table-respo th, .table-respo td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table-respo th { background-color: #343a40; color: white; text-transform: uppercase; font-size: 0.85rem; }
        .table-respo tr:hover { background-color: #f1f1f1; }
        .action-buttons button, .action-buttons a { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; margin: 0 3px; border-radius: 5px; text-decoration: none; color: white; border: none; cursor: pointer; font-size: 0.8rem; }
        .action-buttons .btn-edit { background-color: #ffc107; color: #212529; }
        .action-buttons .btn-delete { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    <div class="page-flex">
        
        <?php include "sidebar.php"; ?>
        
        <div class="main-wrapper">
            <?php include "navbar.php"; ?>
            
            <main class="main users" id="skip-target">
                <div class="container-respo">
                    <h2 class="main-title">Affectation des Responsabilités</h2>

                    <?php if ($error_message): ?><div class="alert"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                    <?php if ($success_message): ?><div class="success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>

                    <form method="post" action="responsabilities.php" id="respoForm" class="form-respo">
                        <input type="hidden" name="id_edit" id="id_edit">
                        
                        <!-- Les listes déroulantes ne changent pas -->
                        <select name="id_professeur" id="id_professeur" required>
                            <option value="">-- Choisir un professeur --</option>
                            <?php foreach ($professeurs as $prof): ?><option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nom'] . ' ' . $prof['prenom']) ?></option><?php endforeach; ?>
                        </select>
                        <select name="nom_responsabilite" id="nom_responsabilite" required>
                            <option value="">-- Choisir une responsabilité --</option>
                            <option value="chef de departement">Chef de Département</option>
                            <option value="coordonnateur">Coordonnateur de Filière</option>
                            <option value="autre">Autre</option>
                        </select>
                        <input type="text" name="annee_universitaire" id="annee_universitaire" placeholder="Année (ex: 2024/2025)" required>
                        <select name="id_departement" id="departement_field" style="display:none;"><?php foreach ($departements as $dep): ?><option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option><?php endforeach; ?></select>
                        <select name="id_filiere" id="filiere_field" style="display:none;"><?php foreach ($filieres as $fil): ?><option value="<?= $fil['id'] ?>"><?= htmlspecialchars($fil['nom']) ?></option><?php endforeach; ?></select>

                        <!-- ** FIX: Remplacement des <button> par des <input type="submit"> ** -->
                        <input type="submit" name="action" value="Ajouter" class="btn btn-ajout" id="btnAjouter">
                        <input type="submit" name="action" value="Modifier" class="btn btn-modif" id="btnModifier" style="display:none;">
                        <button type="button" class="btn btn-annuler" id="btnAnnuler">Annuler</button>
                    </form>

                    <table class="table-respo">
                        <thead>
                            <tr>
                                <th>Professeur</th>
                                <th>Responsabilité</th>
                                <th>Année</th>
                                <th>Détail (Département/Filière)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($responsabilites)): ?>
                                <?php foreach ($responsabilites as $res): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($res['nom'] . ' ' . $res['prenom']) ?></td>
                                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $res['nom_responsabilite']))) ?></td>
                                        <td><?= htmlspecialchars($res['annee_universitaire']) ?></td>
                                        <td><?= htmlspecialchars($res['departement_nom'] ?? $res['filiere_nom'] ?? 'N/A') ?></td>
                                        <td class="action-buttons">
                                            <button class="btn-edit" type="button" onclick='editResponsabilite(<?= json_encode($res, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Modifier</button>
                                            <a href="responsabilities.php?supprimer=<?= $res['id'] ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 20px;">Aucune responsabilité n'a encore été affectée.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find the sidebar menu by its ID
    const sidebarMenu = document.getElementById('admin-sidebar-menu');

    if (sidebarMenu) {
        // Get all the links inside the menu
        const menuLinks = sidebarMenu.querySelectorAll('a');

        // Add a click listener to each link
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // 1. Remove 'active' class from all links first
                menuLinks.forEach(function(innerLink) {
                    innerLink.classList.remove('active');
                });

                // 2. Add 'active' class to the link that was just clicked
                this.classList.add('active');
            });
        });
    }
});

function resetFormToAddState() {
    const form = document.getElementById('respoForm');
    form.reset(); 
    document.getElementById('id_edit').value = ''; 
    document.getElementById('btnAjouter').style.display = 'inline-block';
    document.getElementById('btnModifier').style.display = 'none';
    toggleFields(); 
}

function toggleFields() {
    const nomRespo = document.getElementById('nom_responsabilite').value;
    document.getElementById('departement_field').style.display = nomRespo === 'chef de departement' ? 'inline-block' : 'none';
    document.getElementById('filiere_field').style.display = nomRespo === 'coordonnateur' ? 'inline-block' : 'none';
}

function editResponsabilite(data) {
    document.getElementById('id_edit').value = data.id;
    document.getElementById('id_professeur').value = data.id_professeur;
    document.getElementById('nom_responsabilite').value = data.nom_responsabilite;
    document.getElementById('annee_universitaire').value = data.annee_universitaire;
    toggleFields(); 
    document.getElementById('departement_field').value = data.id_departement || '';
    document.getElementById('filiere_field').value = data.id_filiere || '';
    document.getElementById('btnAjouter').style.display = 'none';
    document.getElementById('btnModifier').style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('btnAnnuler').addEventListener('click', resetFormToAddState);
document.getElementById('nom_responsabilite').addEventListener('change', toggleFields);
window.onload = resetFormToAddState;
</script>
<script src="/e-service/plugins/feather.min.js"></script>
<script src="/e-service/js/script.js"></script>
</body>
</html>