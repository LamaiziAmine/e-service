<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "projet_web";

// Connexion à la base de données
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ====================================================================
// VÉRIFICATION DE L'IDENTITÉ DU PROFESSEUR - À ADAPTER
// ====================================================================
// Remplacez 'prof_id' par la variable de session qui contient l'ID unique du professeur.
//if (!isset($_SESSION['prof_id'])) {
// Si vous n'avez pas de système de connexion, vous pouvez définir un ID fixe pour tester :
// $_SESSION['prof_id'] = 1; 
// Mais en production, la ligne die() est nécessaire pour la sécurité.
//  die("Accès refusé. Vous devez être connecté en tant que professeur.");
//}
//$professeur_id = $_SESSION['prof_id'];
// ====================================================================

$_SESSION['prof_id'] = 1;

// On vérifie maintenant que la session existe (bonne pratique)
if (!isset($_SESSION['prof_id'])) {
    die("Accès refusé. Vous devez être connecté en tant que professeur.");
}

// On assigne la valeur à la variable utilisée dans les requêtes
$professeur_id = $_SESSION['prof_id'];



// --- GESTIONNAIRE DE REQUÊTES AJAX POUR ENREGISTRER LES SOUHAITS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enregistrer_souhaits') {
    header('Content-Type: application/json');

    $module_ids = isset($_POST['modules']) ? $_POST['modules'] : [];

    // Utiliser une transaction pour assurer l'intégrité des données
    $conn->begin_transaction();
    try {
        // 1. Supprimer tous les anciens souhaits de ce professeur
        $stmt_delete = $conn->prepare("DELETE FROM choix_ues WHERE id_professeur = ?");
        $stmt_delete->bind_param("i", $professeur_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 2. Insérer les nouveaux souhaits s'il y en a
        if (!empty($module_ids)) {
            $stmt_insert = $conn->prepare("INSERT INTO choix_ues (id_professeur, id_module) VALUES (?, ?)");
            foreach ($module_ids as $module_id) {
                // Assurez-vous que les IDs sont bien des entiers
                $safe_module_id = (int) $module_id;
                $stmt_insert->bind_param("ii", $professeur_id, $safe_module_id);
                $stmt_insert->execute();
            }
            $stmt_insert->close();
        }

        // Valider la transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Vos souhaits ont été enregistrés avec succès.']);
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'enregistrement.']);
    }
    exit; // Arrêter le script pour ne pas afficher le HTML
}


// --- RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE DE LA PAGE ---
// On utilise un LEFT JOIN pour savoir si le module a été sélectionné par le prof actuel
// J'ai supposé que la clé primaire de 'unités_ensignement' est 'id'. À adapter si besoin.
$sql = "SELECT u.*, s.id_module IS NOT NULL AS est_selectionne
        FROM unités_ensignement u
        LEFT JOIN choix_ues s ON u.id = s.id_module AND s.id_professeur = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professeur_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souhaits d'enseignement</title>
    <link rel="shortcut icon" href="/e-service/img/svg/logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="/e-service/css/style.min.css">

    <style>
        .table-style {
            width: 95%;
            margin: auto;
            border-collapse: collapse;
            font-family: 'Arial', sans-serif;
            color: #333;
            text-align: left;
            border: 2px solid #1e3a8a;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 100px;
            /* Espace pour la barre de résumé */
        }

        .table-style th,
        .table-style td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .table-style thead th {
            background-color: rgb(7, 8, 83);
            color: white;
            font-size: 0.9rem;
        }

        .table-style tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-style tr:hover {
            background-color: rgba(0, 42, 255, 0.1);
        }

        h2 {
            margin-bottom: 10px;
            color: #333;
        }

        input[type="checkbox"] {
            transform: scale(1.4);
            cursor: pointer;
        }

        /* Barre de résumé flottante */
        #summary-bar {
            position: fixed;
            bottom: 0;
            left: 250px;
            /* Ajustez à la largeur de votre sidebar */
            right: 0;
            background-color: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e0e0e0;
            z-index: 1000;
            transition: background-color 0.3s;
        }

        #charge-info {
            font-size: 1.1rem;
            color: #333;
        }

        #charge-info strong {
            color: #0056b3;
            font-size: 1.4rem;
            padding: 0 5px;
        }

        #charge-warning {
            margin-left: 15px;
            color: #c82333;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.3s;
        }

        #summary-bar.warning #charge-warning {
            opacity: 1;
        }

        #enregistrer-souhaits {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        #enregistrer-souhaits:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        #enregistrer-souhaits:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="layer"></div>
    <div class="page-flex">
        <?php include "sidebar_prof.php" ?>
        <div class="main-wrapper">
            <?php include "../coordonnateur/navbar.php" ?><br>
            <h2 style="margin-left: 20px;" class="main-title">Sélection des souhaits d'enseignement</h2>
            <p style="margin-left: 20px; margin-bottom: 25px; max-width: 90%; color: rgb(12, 90, 246);">Cochez les
                modules que vous souhaitez
                enseigner cette année. Votre charge horaire totale est calculée automatiquement en bas de l'écran.</p>

            <table class="table-style">
                <thead>
                    <tr>
                        <th style="text-align: center;"><input type="checkbox" id="select-all"
                                title="Tout sélectionner/désélectionner"></th>
                        <th>Intitulé du Module</th>
                        <th>Semestre</th>
                        <th>Filière</th>
                        <th>Volume Horaire Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Le calcul du VH Total reste, car on en a besoin pour le data-attribute
                            $vh_total = $row['V_h_cours'] + $row['V_h_TD'] + $row['V_h_TP'] + $row['V_h_Autre'] + $row['V_h_Evaluation'];
                            $est_coche = $row['est_selectionne'] ? 'checked' : '';

                            echo "<tr>";
                            // 1. Case à cocher (inchangée)
                            echo "<td style='text-align: center;'><input type='checkbox' class='module-checkbox' value='" . $row['id'] . "' data-heures='" . $vh_total . "' " . $est_coche . "></td>";
                            // 2. Intitulé (inchangé)
                            echo "<td><strong>" . htmlspecialchars($row['intitule_module']) . "</strong><br><small style='color:#555;'>" . htmlspecialchars($row['code_module']) . "</small></td>";
                            // 3. Semestre (inchangé)
                            echo "<td>" . htmlspecialchars($row['semestre']) . "</td>";
                            // 4. Filière (inchangé)
                            echo "<td>" . htmlspecialchars($row['filiere']) . "</td>";
                            // 5. Volume Horaire Total (maintenant la seule colonne de volume)
                            echo "<td class='vh-total-cell'>" . $vh_total . "h</td>";
                            echo "</tr>";
                        }
                    } else {
                        // On ajuste le colspan pour correspondre au nouveau nombre de colonnes (6)
                        echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>Aucun module n'est disponible pour le moment.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- BARRE DE RÉSUMÉ ET DE SAUVEGARDE -->
            <div id="summary-bar">
                <div id="charge-info">
                    Charge horaire totale : <strong id="total-heures">0</strong>h
                    <small id="charge-warning">(Minimum requis : 72h)</small>
                </div>
                <button id="enregistrer-souhaits" disabled>Enregistrer mes souhaits</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.module-checkbox');
            const selectAllCheckbox = document.getElementById('select-all');
            const totalHeuresEl = document.getElementById('total-heures');
            const summaryBar = document.getElementById('summary-bar');
            const enregistrerBtn = document.getElementById('enregistrer-souhaits');
            const MIN_HOURS = 72; // Définissez ici la charge horaire minimale

            let aChangeEteFait = false;

            function calculerTotalHeures() {
                let total = 0;
                document.querySelectorAll('.module-checkbox:checked').forEach(checkbox => {
                    total += parseInt(checkbox.dataset.heures, 10);
                });

                totalHeuresEl.textContent = total;

                if (total > 0 && total < MIN_HOURS) {
                    summaryBar.classList.add('warning');
                } else {
                    summaryBar.classList.remove('warning');
                }

                // Activer le bouton seulement si un changement a été fait
                enregistrerBtn.disabled = !aChangeEteFait;
            }

            function marquerChangement() {
                aChangeEteFait = true;
                calculerTotalHeures();
            }

            // Calcul initial au chargement de la page
            calculerTotalHeures();

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', marquerChangement);
            });

            selectAllCheckbox.addEventListener('change', function () {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                marquerChangement();
            });

            enregistrerBtn.addEventListener('click', function () {
                const totalActuel = parseInt(totalHeuresEl.textContent, 10);

                if (totalActuel > 0 && totalActuel < MIN_HOURS) {
                    if (!confirm(`Attention : Votre charge horaire (${totalActuel}h) est inférieure au minimum requis de ${MIN_HOURS}h. Voulez-vous quand même enregistrer vos souhaits ?`)) {
                        return;
                    }
                }

                enregistrerBtn.disabled = true;
                enregistrerBtn.textContent = 'Enregistrement...';

                const selectedModules = Array.from(document.querySelectorAll('.module-checkbox:checked')).map(cb => cb.value);
                const formData = new FormData();
                formData.append('action', 'enregistrer_souhaits');
                selectedModules.forEach(id => formData.append('modules[]', id));

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message); // Remplacez par votre système de notification "toast" si vous le souhaitez
                        if (data.status === 'success') {
                            aChangeEteFait = false;
                            enregistrerBtn.textContent = 'Enregistrer mes souhaits';
                        } else {
                            enregistrerBtn.disabled = false;
                            enregistrerBtn.textContent = 'Enregistrer mes souhaits';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur de communication est survenue. Veuillez réessayer.');
                        enregistrerBtn.disabled = false;
                        enregistrerBtn.textContent = 'Enregistrer mes souhaits';
                    });
            });
        });
    </script>

    <!-- Libraries de votre projet -->
    <script src="/e-service/plugins/chart.min.js"></script>
    <script src="/e-service/plugins/feather.min.js"></script>
    <script src="/e-service/js/script.js"></script>

</body>

</html>
<?php
$conn->close();
?>