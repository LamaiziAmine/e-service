<?php
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
            font-weight: 400;
        }

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
            height: 300px;
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

        .header-section {
            margin-bottom: 3rem;
            padding-top: 3rem;
            text-align: center;
        }

        .main-title {
            color: white;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.3rem;
            font-weight: 400;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.9rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            padding: 3rem 2rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color), var(--warning-color));
        }

        .card-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .card-title i {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-subtitle {
            color: var(--gray-600);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .table-container {
            overflow-x: auto;
            background: transparent;
            padding: 0;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 1rem;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, var(--gray-50), white);
            color: var(--gray-700);
            padding: 2rem 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.85rem;
            border-bottom: 3px solid var(--primary-color);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modern-table thead th:first-child {
            border-top-left-radius: 0;
        }

        .modern-table thead th:last-child {
            border-top-right-radius: 0;
        }

        .modern-table tbody td {
            padding: 2rem 1.5rem;
            color: var(--gray-700);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .modern-table tbody tr {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .modern-table tbody tr::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: transparent;
            transition: all 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(255, 255, 255, 0.8));
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .modern-table tbody tr:hover::before {
            background: linear-gradient(135deg, var(--primary-color), var(--success-color));
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .badge:hover::before {
            left: 100%;
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success-color), var(--success-dark));
            color: white;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.75rem;
            border: none;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-right: 0.75rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), var(--success-dark));
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), var(--danger-dark));
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.3);
        }

        .file-upload {
            position: relative;
            display: inline-block;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(243, 244, 246, 0.8));
            color: var(--gray-700);
            border: 2px dashed var(--gray-300);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .file-upload-label:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.25);
        }

        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 2rem;
            opacity: 0.3;
            background: linear-gradient(135deg, var(--gray-400), var(--gray-500));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--gray-600);
            font-weight: 700;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: var(--gray-500);
        }

        .toast {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            color: var(--gray-800);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 1.5rem 2rem;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            transform: translateX(100%) scale(0.8);
            visibility: hidden;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 6px solid;
            max-width: 400px;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0) scale(1);
            visibility: visible;
        }

        .toast.success {
            border-left-color: var(--success-color);
        }

        .toast.error {
            border-left-color: var(--danger-color);
        }

        .toast.warning {
            border-left-color: var(--warning-color);
        }

        .toast i {
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .toast.success i {
            color: var(--success-color);
        }

        .toast.error i {
            color: var(--danger-color);
        }

        .toast.warning i {
            color: var(--warning-color);
        }

        .toast-message {
            font-weight: 600;
            line-height: 1.5;
            font-size: 1rem;
        }

        .actions-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .no-file-text {
            color: var(--gray-400);
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            padding: 1rem 1.5rem;
            background: rgba(156, 163, 175, 0.1);
            border-radius: 12px;
        }

        .module-title {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .main-title {
                font-size: 2.5rem;
            }

            .subtitle {
                font-size: 1.1rem;
            }

            .card-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .card-title {
                font-size: 1.5rem;
            }

            .modern-table thead th,
            .modern-table tbody td {
                padding: 1.5rem 1rem;
            }

            .btn {
                padding: 0.8rem 1.2rem;
                font-size: 0.9rem;
            }

            .toast {
                top: 1rem;
                right: 1rem;
                left: 1rem;
                max-width: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .header-section {
            animation: slideInUp 0.8s ease-out;
        }

        .stat-card {
            animation: fadeInScale 0.6s ease-out;
            animation-fill-mode: both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .card {
            animation: slideInUp 0.8s ease-out 0.3s both;
        }

        .modern-table tbody tr {
            animation: slideInUp 0.5s ease-out;
            animation-fill-mode: both;
        }

        .modern-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .modern-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .modern-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .modern-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .modern-table tbody tr:nth-child(5) { animation-delay: 0.5s; }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            
            <div class="page-container">
                <div class="content-wrapper">
                    <div class="header-section">
                        <h1 class="main-title">Gestion des Notes</h1>
                        <p class="subtitle">Session Normale - Importation et gestion des fichiers PDF</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-book-open'></i>
                            </div>
                            <div class="stat-value"><?= $result->num_rows ?></div>
                            <div class="stat-label">Modules Assignés</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-file-blank'></i>
                            </div>
                            <div class="stat-value">
                                <?php 
                                $files_count = 0;
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()) {
                                    if (!empty($row['fichier_notes_normal'])) $files_count++;
                                }
                                echo $files_count;
                                $result->data_seek(0);
                                ?>
                            </div>
                            <div class="stat-label">Fichiers Uploadés</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-check-circle'></i>
                            </div>
                            <div class="stat-value">
                                <?= $result->num_rows > 0 ? round(($files_count / $result->num_rows) * 100) : 0 ?>%
                            </div>
                            <div class="stat-label">Progression</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class='bx bx-folder-open'></i>
                                Modules Assignés
                            </h2>
                            <p class="card-subtitle">Gérez vos fichiers PDF de notes pour chaque module</p>
                        </div>
                        
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th><i class='bx bx-code-alt'></i> Code Module</th>
                                        <th><i class='bx bx-book-open'></i> Intitulé</th>
                                        <th><i class='bx bx-calendar'></i> Semestre</th>
                                        <th><i class='bx bx-category'></i> Filière</th>
                                        <th><i class='bx bx-upload'></i> Import PDF</th>
                                        <th><i class='bx bx-cog'></i> Actions</th>
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
                                                        Semestre <?= htmlspecialchars($row['semestre']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['filiere']) ?></strong>
                                                </td>
                                                <td>
                                                    <form onsubmit="return false;" class="file-upload">
                                                        <input type="file" id="file-<?= $row['id'] ?>" accept="application/pdf" onchange="uploadNotes(this, <?= $row['id'] ?>, 'normal')">
                                                        <label for="file-<?= $row['id'] ?>" class="file-upload-label">
                                                            <i class='bx bx-cloud-upload'></i>
                                                            Sélectionner PDF
                                                        </label>
                                                    </form>
                                                </td>
                                                <td id="actions-cell-<?= $row['id'] ?>">
                                                    <div class="actions-cell">
                                                        <?php if (!empty($row['fichier_notes_normal'])): ?>
                                                            <a href="<?= htmlspecialchars($row['fichier_notes_normal']) ?>" target="_blank" class="btn btn-success">
                                                                <i class='bx bx-show'></i>
                                                                Visualiser
                                                            </a>
                                                            <button class="btn btn-danger" onclick="deleteNotes(<?= $row['id'] ?>, 'normal')">
                                                                <i class='bx bx-trash'></i>
                                                                Supprimer
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="no-file-text">
                                                                <i class='bx bx-info-circle'></i>
                                                                Aucun fichier uploadé
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
                    </div>
                </div>
            </div>

            <div id="toast-notification" class="toast">
                <i class='bx bx-check-circle'></i>
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
            
            // Update icon based on type
            if (type === 'success') {
                icon.className = 'bx bx-check-circle';
            } else if (type === 'error') {
                icon.className = 'bx bx-x-circle';
            } else if (type === 'warning') {
                icon.className = 'bx bx-error-alt';
            }
            
            // Hide toast after 5 seconds
            setTimeout(() => {
                toast.className = `toast ${type}`;
            }, 5000);
        }

        function uploadNotes(input, moduleId, sessionType) {
            const file = input.files[0];
            if (!file) return;
            
            // Validate file type
            if (file.type !== 'application/pdf') {
                showToast('Veuillez sélectionner un fichier PDF valide.', 'error');
                input.value = '';
                return;
            }

            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                showToast('Le fichier est trop volumineux. Taille maximale: 10MB', 'error');
                input.value = '';
                return;
            }
            
            // Show loading state with modern animation
            const label = input.nextElementSibling;
            const originalContent = label.innerHTML;
            label.innerHTML = '<div class="loading-spinner"></div> Téléchargement...';
            label.style.pointerEvents = 'none';
            label.style.opacity = '0.7';
            
            const formData = new FormData();
            formData.append('notes_pdf', file);
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            fetch('backend/upload_note.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(text || 'Erreur de téléchargement');
                    });
                }
                return response.json();
            })
            .then(data => {
                showToast(data.message, data.status);
                if (data.status === 'success') {
                    // Update actions cell with modern styling
                    const actionsCell = document.getElementById(`actions-cell-${moduleId}`);
                    actionsCell.innerHTML = `
                        <div class="actions-cell">
                            <a href="${data.pdf_url}" target="_blank" class="btn btn-success">
                                <i class='bx bx-show'></i>
                                Visualiser
                            </a>
                            <button class="btn btn-danger" onclick="deleteNotes(${moduleId}, '${sessionType}')">
                                <i class='bx bx-trash'></i>
                                Supprimer
                            </button>
                        </div>
                    `;
                    
                    // Update stats if visible
                    updateStats();
                    
                    // Add success animation to row
                    const row = document.getElementById(`module-row-${moduleId}`);
                    row.style.animation = 'none';
                    row.offsetHeight; // Trigger reflow
                    row.style.animation = 'fadeInScale 0.5s ease-out';
                }
            })
            .catch(error => {
                console.error('Upload Error:', error);
                showToast('Erreur lors du téléchargement: ' + error.message, 'error');
            })
            .finally(() => {
                // Reset label with smooth transition
                setTimeout(() => {
                    label.innerHTML = originalContent;
                    label.style.pointerEvents = 'auto';
                    label.style.opacity = '1';
                    input.value = '';
                }, 300);
            });
        }

        function deleteNotes(moduleId, sessionType) {
            // Modern confirmation with custom styling
            const confirmed = confirm("🗑️ Êtes-vous sûr de vouloir supprimer définitivement ce fichier ?\n\nCette action est irréversible.");
            if (!confirmed) {
                return;
            }
            
            // Show loading state on delete button
            const deleteBtn = event.target.closest('.btn-danger');
            const originalContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<div class="loading-spinner"></div> Suppression...';
            deleteBtn.style.pointerEvents = 'none';
            deleteBtn.style.opacity = '0.7';
            
            const formData = new FormData();
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            fetch('backend/delete_note.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.status);
                if (data.status === 'success') {
                    // Update actions cell with smooth transition
                    const actionsCell = document.getElementById(`actions-cell-${moduleId}`);
                    actionsCell.style.opacity = '0.5';
                    actionsCell.style.transform = 'scale(0.95)';
                    
                    setTimeout(() => {
                        actionsCell.innerHTML = `
                            <span class="no-file-text">
                                <i class='bx bx-info-circle'></i>
                                Aucun fichier uploadé
                            </span>
                        `;
                        actionsCell.style.opacity = '1';
                        actionsCell.style.transform = 'scale(1)';
                    }, 200);
                    
                    // Update stats
                    updateStats();
                }
            })
            .catch(error => {
                console.error('Delete Error:', error);
                showToast('Une erreur de communication est survenue.', 'error');
            })
            .finally(() => {
                // Reset button state
                setTimeout(() => {
                    if (deleteBtn) {
                        deleteBtn.innerHTML = originalContent;
                        deleteBtn.style.pointerEvents = 'auto';
                        deleteBtn.style.opacity = '1';
                    }
                }, 300);
            });
        }

        function updateStats() {
            // Update statistics in real-time
            const totalModules = document.querySelectorAll('#module-row-').length;
            const uploadedFiles = document.querySelectorAll('.btn-success').length;
            const progress = totalModules > 0 ? Math.round((uploadedFiles / totalModules) * 100) : 0;
            
            // Animate stat updates
            const statValues = document.querySelectorAll('.stat-value');
            if (statValues.length >= 3) {
                animateValue(statValues[1], parseInt(statValues[1].textContent), uploadedFiles, 500);
                animateValue(statValues[2], parseInt(statValues[2].textContent.replace('%', '')), progress, 500, '%');
            }
        }

        function animateValue(element, start, end, duration, suffix = '') {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current) + suffix;
            }, 16);
        }

        // Add smooth scrolling for skip link
        document.querySelector('.skip-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('#skip-target')?.scrollIntoView({
                behavior: 'smooth'
            });
        });

        // Add drag and drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(input => {
                const label = input.nextElementSibling;
                
                // Drag and drop events
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    label.addEventListener(eventName, preventDefaults, false);
                });
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    label.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    label.addEventListener(eventName, unhighlight, false);
                });
                
                label.addEventListener('drop', handleDrop, false);
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                function highlight(e) {
                    label.style.background = 'linear-gradient(135deg, var(--primary-light), var(--primary-color))';
                    label.style.color = 'white';
                    label.style.borderColor = 'var(--primary-color)';
                    label.style.transform = 'scale(1.05)';
                }
                
                function unhighlight(e) {
                    label.style.background = '';
                    label.style.color = '';
                    label.style.borderColor = '';
                    label.style.transform = '';
                }
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length > 0) {
                        input.files = files;
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                }
            });
        });

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close any open toasts
                const toast = document.getElementById('toast-notification');
                if (toast.classList.contains('show')) {
                    toast.classList.remove('show');
                }
            }
        });

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        // Observe table rows for animation
        document.querySelectorAll('.modern-table tbody tr').forEach(row => {
            observer.observe(row);
        });
    </script>

    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</body>
</html>