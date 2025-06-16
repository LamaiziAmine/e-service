
  <!-- ! Sidebar -->
<aside class="sidebar">
    <div class="sidebar-start">
        <div class="sidebar-head">
            <a href="/" class="logo-wrapper" title="Home">
                <span class="sr-only">Home</span>
                <span class="icon logo" aria-hidden="true"></span>
                <div class="logo-text">
                    <span class="logo-title">E-service</span>
                    <span class="logo-subtitle">ENSAH</span>
                </div>

            </a>
            <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
                <span class="sr-only">Toggle menu</span>
                <span class="icon menu-toggle" aria-hidden="true"></span>
            </button>
        </div>
        <div class="sidebar-body">
            <ul class="sidebar-body-menu">
                <li>
                    <a class="<?= ($currentPage == 'home_vacataire.php') ? 'active' : '' ?>" href="/e-service/vacataire/home_vacataire.php"><span class="icon home" aria-hidden="true"></span>Accuil</a>
                </li>
                <li>
                  <a href="/e-service/vacataire/consultation_page.php" class="<?= ($currentPage == 'consultation_page.php') ? 'active' : '' ?>"> <span class="icon paper" aria-hidden="true"></span>Consulter UEs assurés</a>
                </li>
                <li>
                    <a href="/e-service/vacataire/session_normale_page.php" class="<?= ($currentPage == 'session_normale_page.php') ? 'active' : '' ?>"> <span class="icon folder" aria-hidden="true"></span>Session normale </a>
                </li>
                <li>
                  <a href="/e-service/vacataire/session_rattrapage_page.php" class="<?= ($currentPage == 'session_rattrapage_page.php') ? 'active' : '' ?>"> <span class="icon paper" aria-hidden="true"></span>Session rattrapage</a>
                </li>
                        
            </ul>
        </div>
    </div>
    
</aside>
    <!-- ! Main nav -->