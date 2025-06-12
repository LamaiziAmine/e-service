<?php
// navbar_admin.php
?>
<nav class="main-nav--bg">
  <div class="container main-nav">
    <div class="main-nav-start">
      <!-- Le champ de recherche peut rester si vous en avez besoin -->
    </div>
    <div class="main-nav-end">
      <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
        <span class="sr-only">Toggle menu</span>
        <span class="icon menu-toggle--gray" aria-hidden="true"></span>
      </button>

      <!-- ... (les autres boutons comme le thème et les notifications peuvent rester) ... -->

      <div class="nav-user-wrapper">
        <button class="nav-user-btn dropdown-btn" title="My profile" type="button">
          <span class="sr-only">Mon profil</span>
          <span class="nav-user-img">
            <picture>
              <source srcset="/e-service/img/avatar/avatar-illustrated-02.webp" type="image/webp" />
              <img src="/e-service/img/avatar/avatar-illustrated-02.png" alt="Admin" />
            </picture>
          </span>
        </button>
        <ul class="users-item-dropdown nav-user-dropdown dropdown">
          <li><a href="#"><i data-feather="user" aria-hidden="true"></i><span>Profil</span></a></li>
          <!-- Assurez-vous d'avoir un script de déconnexion -->
          <li><a class="danger" href="../logout.php"><i data-feather="log-out" aria-hidden="true"></i><span>Se déconnecter</span></a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<script>
  // Cette initialisation doit être faite une seule fois par page, idéalement à la fin.
  // Je la laisse ici, mais nous l'inclurons à la fin du corps de la page principale.
  if (typeof feather !== 'undefined') {
    feather.replace();
  }
</script>