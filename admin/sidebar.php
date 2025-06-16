<?php
// Get the name of the current file (e.g., "gestion_professeurs.php")
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- ! Sidebar -->
<aside class="sidebar">
    <div class="sidebar-start">
        <div class="sidebar-head">
            <a href="/" class="logo-wrapper" title="Home">
                <span class="sr-only">Home</span>
                <span class="icon logo" aria-hidden="true"></span>
                <div class="logo-text">
                    <span class="logo-title">Admin</span>
                    <span class="logo-subtitle">ENSAH</span>
                </div>
            </a>
            <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
                <span class="sr-only">Toggle menu</span>
                <span class="icon menu-toggle" aria-hidden="true"></span>
            </button>
        </div>
        <div class="sidebar-body">
            <!-- Give the menu a unique ID for our JavaScript to find it -->
            <ul class="sidebar-body-menu" id="admin-sidebar-menu">
               
                <li>
                    <a href="/e-service/admin/profcompte.php" class="<?= ($currentPage == 'profcompte.php') ? 'active' : '' ?>">
                        <span class="icon user-3" aria-hidden="true"></span>Gestion des Comptes
                    </a>
                </li>
                <li>
                    <!-- Updated the condition to handle both possible filenames -->
                    <a href="/e-service/admin/responsabilities.php" class="<?= ($currentPage == 'responsabilities.php') ? 'active' : '' ?>">
                        <span class="icon folder" aria-hidden="true"></span>Affecter Responsabilités
                    </a>
                </li>
            
                
            </ul>
            
        </div>
    </div>
</aside>
