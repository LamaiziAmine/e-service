<?php
// /admin/navbar_admin.php
// Ce fichier contient la barre de navigation unifiée pour toute la section admin.
?>
<nav class="main-nav--bg">
  <div class="container main-nav">
    <div class="main-nav-start">
      <div class="search-wrapper">
        <i data-feather="search" aria-hidden="true"></i>
        <input type="text" placeholder="Chercher ..." required>
      </div>
    </div>
    <div class="main-nav-end">
      <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
        <span class="sr-only">Toggle menu</span>
        <span class="icon menu-toggle--gray" aria-hidden="true"></span>
      </button>

      <div class="lang-switcher-wrapper">
        <button class="lang-switcher transparent-btn" type="button">
          FR
          <i data-feather="chevron-down" aria-hidden="true"></i>
        </button>
        <ul class="lang-menu dropdown">
          <li><a href="#">Anglais</a></li>
          <li><a href="#">Français</a></li>
        </ul>
      </div>

      <button class="theme-switcher gray-circle-btn" type="button" title="Switch theme">
        <span class="sr-only">Switch theme</span>
        <i class="sun-icon" data-feather="sun" aria-hidden="true"></i>
        <i class="moon-icon" data-feather="moon" aria-hidden="true"></i>
      </button>

      <div class="notification-wrapper">
        <button class="gray-circle-btn dropdown-btn" title="Notifications" type="button">
          <span class="sr-only">To messages</span>
          <span class="icon notification active" aria-hidden="true"></span>
        </button>
        <ul class="users-item-dropdown notification-dropdown dropdown">
          <li>
            <a href="#">
              <div class="notification-dropdown-icon info">
                <i data-feather="check"></i>
              </div>
              <div class="notification-dropdown-text">
                <span class="notification-dropdown__title">Mise à jour système</span>
                <span class="notification-dropdown__subtitle">Le système a été mis à jour avec succès.</span>
              </div>
            </a>
          </li>
          <li>
            <a href="#">
              <div class="notification-dropdown-icon danger">
                <i data-feather="info" aria-hidden="true"></i>
              </div>
              <div class="notification-dropdown-text">
                <span class="notification-dropdown__title">Cache plein !</span>
                <span class="notification-dropdown__subtitle">Veuillez vider le cache pour améliorer les performances.</span>
              </div>
            </a>
          </li>
          <li>
            <a class="link-to-page" href="#">Voir toutes les notifications</a>
          </li>
        </ul>
      </div>

      <div class="nav-user-wrapper">
        <button class="nav-user-btn dropdown-btn" title="My profile" type="button">
          <span class="sr-only">Mon profil</span>
          <span class="nav-user-img">
            <!-- Utilisation d'un chemin absolu pour la fiabilité -->
            <picture>
                <source srcset="/e-service/img/avatar/avatar-illustrated-02.webp" type="image/webp">
                <img src="/e-service/img/avatar/avatar-illustrated-02.png" alt="Admin">
            </picture>
          </span>
        </button>
        <ul class="users-item-dropdown nav-user-dropdown dropdown">
          <li><a href="#"><i data-feather="user" aria-hidden="true"></i><span>Profil</span></a></li>
          <li><a href="#"><i data-feather="settings" aria-hidden="true"></i><span>Paramètres</span></a></li>
          <li>
            <!-- Utilisation d'un chemin absolu pour la déconnexion -->
            <a class="danger" href="/e-service/logout.php">
              <i data-feather="log-out" aria-hidden="true"></i>
              <span>Se déconnecter</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>