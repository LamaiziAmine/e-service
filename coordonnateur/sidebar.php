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
                    <a class="<?= ($currentPage == 'home.php') ? 'active' : '' ?>" href="/e-service/home.php"><span
                            class="icon home" aria-hidden="true"></span>Accuil</a>
                </li>
                <li>
                    <a href="/e-service/coordonnateur/descriptifpage.php"
                        class="<?= ($currentPage == 'descriptifpage.php') ? 'active' : '' ?>"><span
                            class="icon document" aria-hidden="true"></span>Créer un descriptif </a>
                </li>
                <li>
                    <a href="/e-service/coordonnateur/UEspage.php"
                        class="<?= ($currentPage == 'UEspage.php') ? 'active' : '' ?>"> <span class="icon folder"
                            aria-hidden="true"></span>Us d'enseignement </a>
                </li>
                <li>
                    <a href="/e-service/coordonnateur/groupTD&TPpage.php"
                        class="<?= ($currentPage == 'groupTD&TPpage.php') ? 'active' : '' ?>"> <span class="icon user-3"
                            aria-hidden="true"></span>Groupe TD & TP</a>
                    <span class="msg-counter">1</span>
                </li>
                <li>
                    <a href="/e-service/coordonnateur/affectationUEspage.php"
                        class="<?= ($currentPage == 'affectationUEspage.php') ? 'active' : '' ?>"> <span
                            class="icon paper" aria-hidden="true"></span>Affectation des UEs</a>
                </li>
                <li>
                    <a class="show-cat-btn <?= $isGestionVacatairesActive ? 'active' : '' ?>" href="##">
                        <span class="icon category" aria-hidden="true"></span>Gestion vacataires
                        <span class="category__btn transparent-btn" title="Ouvrir liste">
                            <span class="sr-only">Ouvrir liste</span>
                            <span class="icon arrow-down" aria-hidden="true"></span>
                        </span>
                    </a>
                    <ul class="cat-sub-menu">
                        <li>
                            <a href="/e-service/coordonnateur/creationCompteVAcataire.php"
                                class="<?= ($currentPage == 'creationCompteVAcataire.php') ? 'active' : '' ?>">créer
                                compte</a>
                        </li>
                        <li>
                            <a href="/e-service/coordonnateur/affectationVacataire.php"
                                class="<?= ($currentPage == 'affectationVacataire.php') ? 'active' : '' ?>">affecter</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="/e-service/coordonnateur/gestionEmploispage.php"
                        class="<?= ($currentPage == 'gestionEmploispage.php') ? 'active' : '' ?>"> <span
                            class="icon paper" aria-hidden="true"></span>Emplois du temps</a>
                </li>
            </ul>
            <span class="system-menu__title">Historique</span>
            <ul class="sidebar-body-menu">
                <li>
                    <a href="appearance.html"><span class="icon edit" aria-hidden="true"></span>descriptif</a>
                </li>
                <li>
                    <a href="##"><span class="icon user-3" aria-hidden="true"></span>Affectation</a>
                </li>
            </ul>
        </div>
    </div>

</aside>
<!-- ! Main nav -->