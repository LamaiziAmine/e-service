<?php
$currentPage = basename($_SERVER['PHP_SELF']);
// This file is now ONLY for displaying the page. All logic is in the backend.
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vacataire') {
    header("Location: ../login.php");
    exit; 
}

$vacataire_id = $_SESSION['user_id'];
$_SESSION['role'] = 'vacataire';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";
$conn = new mysqli($host, $user, $pass, $dbname);
$vacataire_id = $_SESSION['user_id'];

$sql = "SELECT ue.id, ue.code_module, ue.intitule_module, ue.semestre, ue.filiere, ue.fichier_notes_normal
        FROM affectations a 
        JOIN unités_ensignement ue ON a.id_ue = ue.id 
        WHERE a.id_user = ?
        GROUP BY ue.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vacataire_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes - Session Normale</title>
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --success-color: #10b981;
            --success-dark: #059669;
            --danger-color: #ef4444;
            --danger-dark: #dc2626;
            --warning-color: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        /* --- RESET & GLOBAL --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* --- STRUCTURE DE LA PAGE --- */
        .page-container {
            min-height: 100vh;
            background: var(--gray-50);
            position: relative;
            overflow: hidden;
        }

        .page-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 250px; /* MODIFIÉ: hauteur réduite */
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            z-index: 0;
        }

        .content-wrapper {
            position: relative;
            z-index: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* --- EN-TÊTE --- */
        .header-section {
            margin-bottom: 2rem;
            padding-top: 2rem;
            text-align: center;
        }

        .main-title {
            color: white;
            font-size: 2.5rem; /* MODIFIÉ: taille réduite */
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem; /* MODIFIÉ: taille réduite */
            font-weight: 400;
            margin-bottom: 2rem;
        }
        
        /* --- STATISTIQUES --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px; /* MODIFIÉ: rayon réduit */
            padding: 1.5rem; /* MODIFIÉ: espacement réduit */
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 50px; height: 50px; /* MODIFIÉ */
            border-radius: 14px; /* MODIFIÉ */
            font-size: 1.5rem; /* MODIFIÉ */
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            color: white;
        }

        .stat-value {
            font-size: 2rem; /* MODIFIÉ */
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.8rem; /* MODIFIÉ */
        }
        
        /* --- CARTE PRINCIPALE --- */
        .card {
            background: white;
            border-radius: 20px; /* MODIFIÉ */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.07);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem 2rem; /* MODIFIÉ: espacement fortement réduit */
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 1.5rem; /* MODIFIÉ */
            font-weight: 700;
            color: var(--gray-800);
            display: flex; align-items: center; gap: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .card-title i {
            font-size: 1.6rem; /* MODIFIÉ */
            color: var(--primary-color);
        }

        .card-subtitle {
            color: var(--gray-500);
            font-size: 1rem; /* MODIFIÉ */
            font-weight: 400;
        }
        
        /* --- TABLEAU --- */
        .table-container {
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem; /* MODIFIÉ */
        }

        .modern-table thead th {
            background: var(--gray-50);
            color: var(--gray-600);
            padding: 1rem 1.5rem; /* MODIFIÉ: espacement réduit */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.8rem;
            border-bottom: 2px solid var(--gray-200);
            text-align: left;
        }

        .modern-table tbody td {
            padding: 1rem 1.5rem; /* MODIFIÉ: espacement réduit */
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
            transition: background-color 0.2s ease;
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background-color: var(--gray-50);
        }

        .module-title {
            font-weight: 600;
            color: var(--gray-800);
        }
        
        /* --- COMPOSANTS (BOUTONS, BADGES) --- */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem; /* MODIFIÉ: plus petit */
            border-radius: 20px;
            font-size: 0.75rem; /* MODIFIÉ */
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-primary { background-color: var(--primary-light); color: var(--primary-dark); }
        .badge-success { background-color: #d1fae5; color: #065f46; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem; /* MODIFIÉ: plus petit */
            border: none;
            border-radius: 10px; /* MODIFIÉ */
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-danger { background-color: var(--danger-color); color: white; }

        .file-upload input[type="file"] { display: none; }

        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem; /* MODIFIÉ: taille similaire aux boutons */
            background-color: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .file-upload-label:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-dark);
        }

        .no-file-text {
            color: var(--gray-500);
            font-style: italic;
            font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        
        .actions-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        
        /* --- ÉTAT VIDE --- */
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--gray-500); }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state h3 { font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--gray-700); }
        
        /* --- NOTIFICATIONS --- */
        .toast {
            position: fixed;
            top: 1.5rem; right: 1.5rem;
            background: white;
            color: var(--gray-800);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 1rem 1.5rem;
            z-index: 9999;
            display: flex; align-items: center; gap: 1rem;
            opacity: 0; transform: translateX(100%);
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 5px solid;
            max-width: 380px;
        }
        .toast.show { opacity: 1; transform: translateX(0); visibility: visible; }
        .toast.success { border-left-color: var(--success-color); }
        .toast.error { border-left-color: var(--danger-color); }
        .toast i { font-size: 1.5rem; }
        .toast.success i { color: var(--success-color); }
        .toast.error i { color: var(--danger-color); }
        .toast-message { font-weight: 500; line-height: 1.4; font-size: 0.95rem; }

        /* --- ANIMATIONS & UTILITAIRES --- */
        .loading-spinner {
            display: inline-block; width: 16px; height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%; border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .content-wrapper { padding: 1rem; }
            .main-title { font-size: 2rem; }
            .subtitle { font-size: 1rem; }
            .card-header, .modern-table thead th, .modern-table tbody td { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    
    <div class="page-flex">
        <?php include "sidebar_vacataire.php"; ?>
        
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php"; ?>
            
            <main class="page-container">
                <div class="content-wrapper">
                    <header class="header-section">
                        <h1 class="main-title">Gestion des Notes</h1>
                        <p class="subtitle">Session Normale - Importation et gestion des fichiers PDF</p>
                    </header>

                    <!-- Stats Grid -->
                    <section class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class='bx bx-book-open'></i></div>
                            <div class="stat-value"><?= $result->num_rows ?></div>
                            <div class="stat-label">Modules Assignés</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class='bx bx-file-blank'></i></div>
                            <div class="stat-value">
                                <?php 
                                $files_count = 0;
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if (!empty($row['fichier_notes_normal'])) $files_count++;
                                    }
                                    $result->data_seek(0);
                                }
                                echo $files_count;
                                ?>
                            </div>
                            <div class="stat-label">Fichiers Uploadés</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class='bx bx-check-circle'></i></div>
                            <div class="stat-value">
                                <?= $result->num_rows > 0 ? round(($files_count / $result->num_rows) * 100) : 0 ?>%
                            </div>
                            <div class="stat-label">Progression</div>
                        </div>
                    </section>

                    <section class="card" id="skip-target">
                        <header class="card-header">
                            <h2 class="card-title">
                                <i class='bx bx-folder-open'></i>
                                Modules Assignés
                            </h2>
                            <p class="card-subtitle">Gérez vos fichiers PDF de notes pour chaque module</p>
                        </header>
                        
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Code Module</th>
                                        <th>Intitulé</th>
                                        <th>Semestre</th>
                                        <th>Filière</th>
                                        <th>Import PDF</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr id="module-row-<?= $row['id'] ?>">
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($row['code_module']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="module-title"><?= htmlspecialchars($row['intitule_module']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">
                                                        <?= htmlspecialchars($row['semestre']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['filiere']) ?></strong>
                                                </td>
                                                <td>
                                                    <form onsubmit="return false;" class="file-upload">
                                                        <input type="file" id="file-<?= $row['id'] ?>" accept="application/pdf" onchange="uploadNotes(this, <?= $row['id'] ?>, 'normal')">
                                                        <label for="file-<?= $row['id'] ?>" class="file-upload-label">
                                                            <i class='bx bx-upload'></i>
                                                            Choisir...
                                                        </label>
                                                    </form>
                                                </td>
                                                <td id="actions-cell-<?= $row['id'] ?>">
                                                    <div class="actions-cell">
                                                        <?php if (!empty($row['fichier_notes_normal'])): ?>
                                                            <a href="<?= htmlspecialchars($row['fichier_notes_normal']) ?>" target="_blank" class="btn btn-success">
                                                                <i class='bx bx-show'></i>
                                                                Voir
                                                            </a>
                                                            <button class="btn btn-danger" onclick="deleteNotes(<?= $row['id'] ?>, 'normal')">
                                                                <i class='bx bx-trash'></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="no-file-text">
                                                                <i class='bx bx-info-circle'></i>
                                                                Aucun fichier
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class='bx bx-folder-open'></i>
                                                    <h3>Aucun module assigné</h3>
                                                    <p>Vous n'avez actuellement aucun module affecté pour cette session.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>

            <div id="toast-notification" class="toast">
                <i class='bx'></i>
                <div class="toast-message" id="toast-message"></div>
            </div>
        </div>
    </div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById("toast-notification");
            const toastMessage = document.getElementById("toast-message");
            const icon = toast.querySelector("i");
            
            toastMessage.textContent = message;
            toast.className = `toast show ${type}`;
            
            if (type === 'success') icon.className = 'bx bx-check-circle';
            else if (type === 'error') icon.className = 'bx bx-x-circle';
            
            setTimeout(() => { toast.classList.remove('show'); }, 4000);
        }

        function uploadNotes(input, moduleId, sessionType) {
            const file = input.files[0];
            if (!file) return;

            if (file.type !== 'application/pdf') {
                showToast('Veuillez sélectionner un fichier PDF.', 'error');
                return;
            }
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                showToast('Le fichier est trop volumineux (max 10MB).', 'error');
                return;
            }
            
            const label = input.nextElementSibling;
            const originalContent = label.innerHTML;
            label.innerHTML = '<div class="loading-spinner"></div>';
            label.style.pointerEvents = 'none';
            
            const formData = new FormData();
            formData.append('notes_pdf', file);
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            fetch('backend/upload_note.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.status);
                if (data.status === 'success') {
                    const actionsCell = document.getElementById(`actions-cell-${moduleId}`);
                    actionsCell.innerHTML = `
                        <div class="actions-cell">
                            <a href="${data.pdf_url}" target="_blank" class="btn btn-success">
                                <i class='bx bx-show'></i> Voir
                            </a>
                            <button class="btn btn-danger" onclick="deleteNotes(${moduleId}, '${sessionType}')">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    `;
                    updateStats();
                }
            })
            .catch(error => {
                showToast('Erreur de communication.', 'error');
                console.error('Upload Error:', error);
            })
            .finally(() => {
                label.innerHTML = originalContent;
                label.style.pointerEvents = 'auto';
                input.value = '';
            });
        }

        function deleteNotes(moduleId, sessionType) {
            if (!confirm("Êtes-vous sûr de vouloir supprimer ce fichier ? Cette action est irréversible.")) {
                return;
            }
            
            const deleteBtn = event.target.closest('.btn-danger');
            const originalContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<div class="loading-spinner"></div>';
            deleteBtn.disabled = true;

            const formData = new FormData();
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            fetch('backend/delete_note.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.status);
                if (data.status === 'success') {
                    const actionsCell = document.getElementById(`actions-cell-${moduleId}`);
                    actionsCell.innerHTML = `
                        <span class="no-file-text">
                            <i class='bx bx-info-circle'></i> Aucun fichier
                        </span>
                    `;
                    updateStats();
                }
            })
            .catch(error => {
                showToast('Erreur de communication.', 'error');
                console.error('Delete Error:', error);
                deleteBtn.innerHTML = originalContent;
                deleteBtn.disabled = false;
            });
        }
        
        function updateStats() {
            // This function would refetch stats or update them on the client side
            // For simplicity, we just reload the stats part or the page
            // A more advanced implementation would use fetch to get updated JSON data.
            // Let's do a simple client-side update for now.
             const statValues = document.querySelectorAll('.stat-value');
             const totalModules = document.querySelectorAll('tbody tr[id^="module-row-"]').length;
             const uploadedFiles = document.querySelectorAll('tbody .btn-success').length;
             const progress = totalModules > 0 ? Math.round((uploadedFiles / totalModules) * 100) : 0;
             
             if(statValues.length >= 3) {
                 statValues[0].textContent = totalModules;
                 statValues[1].textContent = uploadedFiles;
                 statValues[2].textContent = progress + '%';
             }
        }
    </script>

    <script src="/e-service/plugins/chart.min.js"></script>
  <script src="/e-service/plugins/feather.min.js"></script>
  <script src="/e-service/js/script.js"></script>
</body>
</html>