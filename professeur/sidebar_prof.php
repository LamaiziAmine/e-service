
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
                    <a class="<?= ($currentPage == 'prof_dashboard.php') ? 'active' : '' ?>" href="/e-service/professeur/prof_dashboard.php"><span class="icon home" aria-hidden="true"></span>Accuil</a>
                </li>
                <li>
                    <a href="/e-service/professeur/UEspage.php" class="<?= ($currentPage == 'UEspage.php') ? 'active' : '' ?>"> <span class="icon folder" aria-hidden="true"></span>Us d'enseignement </a>
                </li>
                <li>
                  <a href="/e-service/professeur/choix_page.php" class="<?= ($currentPage == 'choix_page.php') ? 'active' : '' ?>"> <span class="icon paper" aria-hidden="true"></span>Choix des UEs</a>
                </li>
                <li>
                  <a href="/e-service/professeur/consultation_page.php" class="<?= ($currentPage == 'consultation_page.php') ? 'active' : '' ?>"> <span class="icon paper" aria-hidden="true"></span>Consulter UEs</a>
                </li>
                <li>
                  <a class="show-cat-btn" href="##">
                      <span class="icon category" aria-hidden="true"></span>Uploder note
                      <span class="category__btn transparent-btn" title="Ouvrir liste">
                          <span class="sr-only">Ouvrir liste</span>
                          <span class="icon arrow-down" aria-hidden="true"></span>
                      </span>
                  </a>
                  <ul class="cat-sub-menu">
                      <li>
                          <a href="/e-service/professeur/session_normale_page.php" class="<?= ($currentPage == 'session_normale_page.php') ? 'active' : '' ?>">Session normale</a>
                      </li>
                      <li>
                          <a href="/e-service/professeur/session_rattrapage_page.php" class="<?= ($currentPage == 'session_rattrapage_page.php') ? 'active' : '' ?>">Session rattrapage</a>
                      </li>
                  </ul>
              </li>
              <li>
                <a href="/e-service/professeur/historique_page.php" class="<?= ($currentPage == 'historique_page.php') ? 'active' : '' ?>">
                    <span class="icon edit" aria-hidden="true"></span>
                    Historique
                </a>
            </li>
            </ul>
        </div>
    </div>
</aside>
    <!-- ! Main nav -->