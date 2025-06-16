<?php
$currentPage = basename($_SERVER['PHP_SELF']); 
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cordonnateur') {
    header("Location: ../login.php");
    exit; 
}

$coordonateur_id = $_SESSION['user_id'];

// Gestion de la requête AJAX pour récupérer les données d'une UE
if (isset($_GET['get_ue_data']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM unités_ensignement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $ue = $result->fetch_assoc();
        echo json_encode(['success' => true, 'ue' => $ue]);
    } else {
        echo json_encode(['success' => false, 'message' => 'UE non trouvée']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Gestion de la suppression
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_sql = "DELETE FROM unités_ensignement WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Module supprimé avec succès !";
    } else {
        $error_message = "Erreur lors de la suppression : " . $conn->error;
    }
    $stmt->close();
}

// Gestion de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = intval($_POST['id']);
    $code_module = $_POST['code_module'];
    $intitule_module = $_POST['intitule_module'];
    $semestre = $_POST['semestre'];
    $filiere = $_POST['filiere'];
    $v_h_cours = intval($_POST['v_h_cours']);
    $v_h_td = intval($_POST['v_h_td']);
    $v_h_tp = intval($_POST['v_h_tp']);
    $v_h_autre = intval($_POST['v_h_autre']);
    $v_h_evaluation = intval($_POST['v_h_evaluation']);
    
    $update_sql = "UPDATE unités_ensignement SET 
                   code_module = ?, 
                   intitule_module = ?, 
                   semestre = ?, 
                   filiere = ?, 
                   V_h_cours = ?, 
                   V_h_TD = ?, 
                   V_h_TP = ?, 
                   V_h_Autre = ?, 
                   V_h_Evaluation = ? 
                   WHERE id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssiiiiii", $code_module, $intitule_module, $semestre, $filiere, 
                      $v_h_cours, $v_h_td, $v_h_tp, $v_h_autre, $v_h_evaluation, $id);
    
    if ($stmt->execute()) {
        $success_message = "Module modifié avec succès !";
    } else {
        $error_message = "Erreur lors de la modification : " . $conn->error;
    }
    $stmt->close();
}

$sql = "SELECT * FROM unités_ensignement ORDER BY filiere, semestre, code_module";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Unités d'Enseignement</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <!-- Custom styles -->
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.css" rel="stylesheet">

    <style>
       
        .main-container {
            padding: 20px;
            margin: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .page-title {
            color: #2c3e50;
            font-size: 2.2em;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table-style {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            text-align: left;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .table-style th, .table-style td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-style thead th {
            background: rgb(25, 60, 255);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-style tbody tr {
            transition: all 0.3s ease;
        }

        .table-style tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table-style tbody tr:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(30, 58, 138, 0.05));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .code-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .filiere-badge {
            border: 2px solid #3b82f6;
            color: black;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 1em;
            font-weight: 600;
        }

        .semestre-badge {
           border: 2px solid #3b82f6;
            color: black;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 1em;
            font-weight: 600;
        }

        .total-hours {
           border: 2px solid #3b82f6;
            color: black;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1em;
            text-align: center;
            min-width: 60px;
            display: inline-block;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 1.1em;
        }

        .no-data i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #d1d5db;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            flex: 1;
            min-width: 200px;
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Styles pour le modal de modification */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5em;
            font-weight: 600;
            margin: 0;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            transform: scale(1.1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .table-style {
                font-size: 0.85em;
            }
            
            .table-style th, .table-style td {
                padding: 10px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    <div class="page-flex">
        <!-- Sidebar -->
        <?php include "sidebar.php" ?>
        <div class="main-wrapper">
            <!-- Main nav -->
            <?php include "navbar.php"?>
            
            <div class="main-container">
                <!-- Messages d'alerte -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <!-- En-tête de page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-graduation-cap"></i>
                        Gestion des Unités d'Enseignement
                    </h1>
                    
                </div>

                <!-- Statistiques -->
                <div class="stats-bar">
                    <div class="stat-card">
                        <span class="stat-number"><?= $result->num_rows ?></span>
                        <div class="stat-label">Total des UEs</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">
                            <?php 
                            $filiere_count = $conn->query("SELECT COUNT(DISTINCT filiere) as count FROM unités_ensignement")->fetch_assoc()['count'];
                            echo $filiere_count;
                            ?>
                        </span>
                        <div class="stat-label">Filières</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">
                            <?php 
                            $total_hours = $conn->query("SELECT SUM(V_h_cours + V_h_TD + V_h_TP + V_h_Autre + V_h_Evaluation) as total FROM unités_ensignement")->fetch_assoc()['total'];
                            echo $total_hours ?? 0;
                            ?>
                        </span>
                        <div class="stat-label">Heures Totales</div>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="table-container">
                    <table class="table-style">
                        <thead>
                            <tr>
                                <th><i class="fas fa-code"></i> Code</th>
                                <th><i class="fas fa-book"></i> Intitulé</th>
                                <th><i class="fas fa-calendar"></i> Semestre</th>
                                <th><i class="fas fa-university"></i> Filière</th>
                                <th><i class="fas fa-clock"></i> Volume Horaire Total</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $total_hours = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                                    echo "<tr>";
                                    echo "<td><span class='code-badge'>" . htmlspecialchars($row['code_module']) . "</span></td>";
                                    echo "<td><strong>" . htmlspecialchars($row['intitule_module']) . "</strong></td>";
                                    echo "<td><span class='semestre-badge'>" . htmlspecialchars($row['semestre']) . "</span></td>";
                                    echo "<td><span class='filiere-badge'>" . htmlspecialchars($row['filiere']) . "</span></td>";
                                    echo "<td><span class='total-hours'>" . $total_hours . "h</span></td>";
                                    echo "<td>
                                            <div class='action-buttons'>
                                                <button class='btn-edit' onclick='openEditModal(" . $row['id'] . ")' title='Modifier'>
                                                    <i class='fas fa-edit'></i> Modifier
                                                </button>
                                                <button class='btn-delete' onclick='confirmDelete(" . $row['id'] . ", \"" . htmlspecialchars($row['code_module']) . "\")' title='Supprimer'>
                                                    <i class='fas fa-trash'></i> Supprimer
                                                </button>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='no-data'>
                                        <div>
                                            <i class='fas fa-inbox'></i><br>
                                            Aucune unité d'enseignement trouvée<br>
                                            <small>Commencez par ajouter votre première UE</small>
                                        </div>
                                      </td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Modifier l'Unité d'Enseignement
                </h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_code_module">
                                <i class="fas fa-code"></i> Code du Module *
                            </label>
                            <input type="text" id="edit_code_module" name="code_module" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_intitule_module">
                                <i class="fas fa-book"></i> Intitulé du Module *
                            </label>
                            <input type="text" id="edit_intitule_module" name="intitule_module" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_semestre">
                                <i class="fas fa-calendar"></i> Semestre *
                            </label>
                            <select id="edit_semestre" name="semestre" required>
                                <option value="">Sélectionner un semestre</option>
                                <option value="S1">Semestre 1</option>
                                <option value="S2">Semestre 2</option>
                                <option value="S3">Semestre 3</option>
                                <option value="S4">Semestre 4</option>
                                <option value="S5">Semestre 5</option>
                                <option value="S6">Semestre 6</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_filiere">
                                <i class="fas fa-university"></i> Filière *
                            </label>
                            <input type="text" id="edit_filiere" name="filiere" required>
                        </div>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px 0; color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                        <i class="fas fa-clock"></i> Volume Horaire
                        <span id="total_hours_display" style="float: right; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9em;">0h</span>
                    </h3>
                    
                    <div class="hours-grid">
                        <div class="form-group">
                            <label for="edit_v_h_cours">Cours (h)</label>
                            <input type="number" id="edit_v_h_cours" name="v_h_cours" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_v_h_td">TD (h)</label>
                            <input type="number" id="edit_v_h_td" name="v_h_td" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_v_h_tp">TP (h)</label>
                            <input type="number" id="edit_v_h_tp" name="v_h_tp" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_v_h_autre">Autre (h)</label>
                            <input type="number" id="edit_v_h_autre" name="v_h_autre" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_v_h_evaluation">Évaluation (h)</label>
                            <input type="number" id="edit_v_h_evaluation" name="v_h_evaluation" min="0" value="0">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.js"></script>
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>

    <script>
        function openEditModal(id) {
            // Faire une requête AJAX pour récupérer les données de l'UE
            fetch(`?get_ue_data=1&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remplir le formulaire avec les données existantes
                        document.getElementById('edit_id').value = data.ue.id;
                        document.getElementById('edit_code_module').value = data.ue.code_module;
                        document.getElementById('edit_intitule_module').value = data.ue.intitule_module;
                        document.getElementById('edit_semestre').value = data.ue.semestre;
                        document.getElementById('edit_filiere').value = data.ue.filiere;
                        document.getElementById('edit_v_h_cours').value = data.ue.V_h_cours || 0;
                        document.getElementById('edit_v_h_td').value = data.ue.V_h_TD || 0;
                        document.getElementById('edit_v_h_tp').value = data.ue.V_h_TP || 0;
                        document.getElementById('edit_v_h_autre').value = data.ue.V_h_Autre || 0;
                        document.getElementById('edit_v_h_evaluation').value = data.ue.V_h_Evaluation || 0;
                        
                        // Mettre à jour le total des heures
                        updateTotalHours();
                        
                        // Afficher le modal
                        document.getElementById('editModal').style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    } else {
                        Swal.fire({
                            title: 'Erreur',
                            text: 'Impossible de charger les données de l\'UE.',
                            icon: 'error',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Une erreur s\'est produite lors du chargement des données.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

         function confirmDelete(id, codeModule) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: `Vous allez supprimer le module "${codeModule}". Cette action est irréversible !`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=delete&id=${id}`;
                }
            });
        }

        // Fermer le modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Gérer la touche Escape pour fermer le modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });

        // Animation d'entrée pour les lignes du tableau
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });

        // Calculateur de volume horaire total en temps réel
        function updateTotalHours() {
            const cours = parseInt(document.getElementById('edit_v_h_cours').value) || 0;
            const td = parseInt(document.getElementById('edit_v_h_td').value) || 0;
            const tp = parseInt(document.getElementById('edit_v_h_tp').value) || 0;
            const autre = parseInt(document.getElementById('edit_v_h_autre').value) || 0;
            const evaluation = parseInt(document.getElementById('edit_v_h_evaluation').value) || 0;
            
            const total = cours + td + tp + autre + evaluation;
            
            // Afficher le total si un élément existe pour cela
            const totalElement = document.getElementById('total_hours_display');
            if (totalElement) {
                totalElement.textContent = total + 'h';
            }
        }

        // Ajouter les événements pour le calcul en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const hourInputs = ['edit_v_h_cours', 'edit_v_h_td', 'edit_v_h_tp', 'edit_v_h_autre', 'edit_v_h_evaluation'];
            hourInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', updateTotalHours);
                }
            });
        });

        // Validation du formulaire
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const codeModule = document.getElementById('edit_code_module').value.trim();
            const intituleModule = document.getElementById('edit_intitule_module').value.trim();
            const semestre = document.getElementById('edit_semestre').value;
            const filiere = document.getElementById('edit_filiere').value.trim();

            if (!codeModule || !intituleModule || !semestre || !filiere) {
                e.preventDefault();
                Swal.fire({
                    title: 'Erreur de validation',
                    text: 'Veuillez remplir tous les champs obligatoires.',
                    icon: 'error',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }

            // Confirmation avant modification
            e.preventDefault();
            Swal.fire({
                title: 'Confirmer la modification',
                text: `Voulez-vous vraiment modifier le module "${codeModule}" ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Oui, modifier',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Soumettre le formulaire
                    e.target.submit();
                }
            });
        });
    </script>
</body>
</html>