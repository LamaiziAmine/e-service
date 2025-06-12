<?php
// This file is now ONLY for displaying the page. All logic is in the backend.
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professeur') {
    header("Location: ../login.php");
    exit; 
}

$professeur_id = $_SESSION['user_id'];
$_SESSION['role'] = 'professeur';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";
$conn = new mysqli($host, $user, $pass, $dbname);
$professeur_id = $_SESSION['user_id'];

$sql = "SELECT ue.id, ue.code_module, ue.intitule_module, ue.semestre, ue.filiere, ue.fichier_notes_normal
        FROM affectations a 
        JOIN unités_ensignement ue ON a.id_ue = ue.id 
        WHERE a.id_user = ?
        GROUP BY ue.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes (Session Normale)</title>
    <link rel="stylesheet" href="/e-service/css/style.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Your CSS styles here */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fc
        }

        .main-title {
            color: #2c3e50;
            text-align: left;
            margin-bottom: 25px;
            margin-left: 2.5%;
            font-weight: 700
        }

        .table-style {
            width: 95%;
            margin: auto;
            border-collapse: collapse;
            text-align: left;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
            overflow: hidden
        }

        .table-style thead th {
            background-color: #f8f9fa;
            color: #34495e;
            padding: 16px 20px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 2px solid #e3e6f0
        }

        .table-style tbody td {
            padding: 16px 20px;
            color: #5a6a7e;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle
        }

        .table-style tbody tr:last-child td {
            border-bottom: none
        }

        .btn-action {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color .3s;
            font-size: 14px;
            margin-right: 5px
        }

        .btn-action:hover {
            background-color: #0056b3
        }

        .btn-delete {
            background-color: #dc3545
        }

        .btn-delete:hover {
            background-color: #c82333
        }

        .btn-view {
            background-color: #28a745
        }

        .btn-view:hover {
            background-color:rgb(117, 211, 133)
        }

        label.btn-action {
            display: inline-block
        }

        input[type=file] {
            display: none
        }

        .toast {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #fff;
            color: #333;
            border-left: 5px solid;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            padding: 15px 20px;
            z-index: 9999;
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(100%);
            visibility: hidden;
            transition: all .4s cubic-bezier(.68, -.55, .265, 1.55)
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
            visibility: visible
        }

        .toast.success {
            border-left-color: #28a745
        }

        .toast.error {
            border-left-color: #dc3545
        }

        .toast.warning {
            border-left-color: #ffc107
        }

        .toast i {
            margin-right: 12px;
            font-size: 24px
        }

        .toast.success i {
            color: #28a745
        }

        .toast.error i {
            color: #dc3545
        }

        .toast.warning i {
            color: #ffc107
        }
    </style>
</head>

<body>
    <div class="layer"></div>
    <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
    <div class="page-flex">
        <?php include "sidebar_prof.php"; ?>
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php"; ?><br>
            <div id="toast-notification" class="toast"><i class='bx bx-check-circle'></i><span id="toast-message"></span></div>
            <main class="main users" id="skip-target">
                <div class="container">
                    <h2 class="main-title">Gestion des Notes - Session Normale</h2>
                    <!-- Your HTML table structure remains the same -->
                    <table class="table-style">
                        <thead>
                            <tr>
                                <th>Code Module</th>
                                <th>Intitulé</th>
                                <th>Semestre</th>
                                <th>Filière</th>
                                <th>Importer Notes (PDF)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr id="module-row-<?= $row['id'] ?>">
                                        <td><?= htmlspecialchars($row['code_module']) ?></td>
                                        <td><?= htmlspecialchars($row['intitule_module']) ?></td>
                                        <td><?= htmlspecialchars($row['semestre']) ?></td>
                                        <td><?= htmlspecialchars($row['filiere']) ?></td>
                                        <td>
                                            <form onsubmit="return false;">
                                                <input type="file" id="file-<?= $row['id'] ?>" accept="application/pdf" onchange="uploadNotes(this, <?= $row['id'] ?>, 'normal')">
                                                <label for="file-<?= $row['id'] ?>" class="btn-action">Importer</label>
                                            </form>
                                        </td>
                                        <td id="actions-cell-<?= $row['id'] ?>">
                                            <?php if (!empty($row['fichier_notes_normal'])): ?>
                                                <a href="<?= htmlspecialchars($row['fichier_notes_normal']) ?>" target="_blank" class="btn-action btn-view">Voir</a>
                                                <button class="btn-action btn-delete" onclick="deleteNotes(<?= $row['id'] ?>, 'normal')">Supprimer</button>
                                            <?php else: ?>
                                                <span style="color:gray;">Pas de fichier</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style='text-align:center;'>Aucun module ne vous est affecté.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <script>
        function showToast(message, type = 'success') {
            /* No changes needed */
            const toast = document.getElementById("toast-notification"),
                toastMessage = document.getElementById("toast-message"),
                icon = toast.querySelector("i");
            toastMessage.textContent = message, toast.className = "toast show " + type, icon.className = "success" === type ? "bx bx-check-circle" : "error" === type ? "bx bx-x-circle" : "bx bx-error-alt", setTimeout(() => {
                toast.className = "toast " + type
            }, 3e3)
        }

        function uploadNotes(input, moduleId, sessionType) {
            const file = input.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('notes_pdf', file);
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            // *** FIX: Point to the correct backend file ***
            fetch('backend/upload_note.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(text)
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    showToast(data.message, data.status);
                    if (data.status === 'success') {
                        const actionsCell = document.getElementById(`actions-cell-${moduleId}`);
                        actionsCell.innerHTML = '<a href="' + data.pdf_url + '" target="_blank" class="btn-action btn-view">Voir</a> <button class="btn-action btn-delete" onclick="deleteNotes(' + moduleId + ', \'' + sessionType + '\')">Supprimer</button>';
                    }
                })
                .catch(error => {
                    console.error('Upload Error:', error);
                    showToast("Erreur: " + error.message, 'error');
                });
            input.value = '';
        }

        function deleteNotes(moduleId, sessionType) {
            if (!confirm("Êtes-vous sûr de vouloir supprimer ce fichier?")) {
                return;
            }
            const formData = new FormData();
            formData.append('module_id', moduleId);
            formData.append('session_type', sessionType);

            // *** FIX: Point to the correct backend file ***
            fetch('backend/delete_note.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.status);
                    if (data.status === 'success') {
                        document.getElementById(`actions-cell-${moduleId}`).innerHTML = '<span style="color:gray;">Pas de fichier</span>';
                    }
                })
                .catch(error => {
                    console.error('Delete Error:', error);
                    showToast('Une erreur de communication est survenue.', 'error');
                });
        }
    </script>
</body>
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>
</html>