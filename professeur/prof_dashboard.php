<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-service | ENSAH</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="../img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="../css/style.min.css">
</head>

<body>
  <div class="layer"></div>
  <!-- ! Body -->
  <a class="skip-link sr-only" href="#skip-target">Skip to content</a>
  <div class="page-flex">
    <!-- ! Sidebar -->
    <?php include "sidebar_prof.php" ?>
    <div class="main-wrapper">
      <!-- ! Main nav -->
      <?php include "../coordonnateur/navbar.php" ?>
      <!-- ! Main -->
      <main class="main users chart-page" id="skip-target">
        <div class="container">
          <h2 class="main-title">Dashboard</h2>
          <div class="row">
            <div class="col-lg-9">
              <div class="chart">
                <canvas id="myChart" aria-label="Site statistics" role="img"></canvas>
              </div>
              <div class="users-table table-wrapper">
                <table class="posts-table">
                  <thead>
                    <tr class="users-table-info">
                      <th>
                        <label class="users-table__checkbox ms-20">
                          <input type="checkbox" class="check-all">Thumbnail
                        </label>
                      </th>
                      <th>Title</th>
                      <th>Author</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/01.webp" type="image/webp"><img
                                src="../img/categories/01.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        Starting your traveling blog with Vasco
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-04.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-04.png" alt="User Name">
                          </picture>
                          Jenny Wilson
                        </div>
                      </td>
                      <td><span class="badge-pending">Pending</span></td>
                      <td>17.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/02.webp" type="image/webp"><img
                                src="../img/categories/02.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        Start a blog to reach your creative peak
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-03.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-03.png" alt="User Name">
                          </picture>
                          Annette Black
                        </div>
                      </td>
                      <td><span class="badge-pending">Pending</span></td>
                      <td>23.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/03.webp" type="image/webp"><img
                                src="../img/categories/03.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        Helping a local business reinvent itself
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-02.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-02.png" alt="User Name">
                          </picture>
                          Kathryn Murphy
                        </div>
                      </td>
                      <td><span class="badge-active">Active</span></td>
                      <td>17.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/04.webp" type="image/webp"><img
                                src="../img/categories/04.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        Caring is the new marketing
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-05.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-05.png" alt="User Name">
                          </picture>
                          Guy Hawkins
                        </div>
                      </td>
                      <td><span class="badge-active">Active</span></td>
                      <td>17.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/01.webp" type="image/webp"><img
                                src="../img/categories/01.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        How to build a loyal community online and offline
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-03.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-03.png" alt="User Name">
                          </picture>
                          Robert Fox
                        </div>
                      </td>
                      <td><span class="badge-active">Active</span></td>
                      <td>17.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <label class="users-table__checkbox">
                          <input type="checkbox" class="check">
                          <div class="categories-table-img">
                            <picture>
                              <source srcset="../img/categories/03.webp" type="image/webp"><img
                                src="../img/categories/03.jpg" alt="category">
                            </picture>
                          </div>
                        </label>
                      </td>
                      <td>
                        How to build a loyal community online and offline
                      </td>
                      <td>
                        <div class="pages-table-img">
                          <picture>
                            <source srcset="../img/avatar/avatar-face-03.webp" type="image/webp"><img
                              src="../img/avatar/avatar-face-03.png" alt="User Name">
                          </picture>
                          Robert Fox
                        </div>
                      </td>
                      <td><span class="badge-active">Active</span></td>
                      <td>17.04.2021</td>
                      <td>
                        <span class="p-relative">
                          <button class="dropdown-btn transparent-btn" type="button" title="More info">
                            <div class="sr-only">More info</div>
                            <i data-feather="more-horizontal" aria-hidden="true"></i>
                          </button>
                          <ul class="users-item-dropdown dropdown">
                            <li><a href="##">Edit</a></li>
                            <li><a href="##">Quick edit</a></li>
                            <li><a href="##">Trash</a></li>
                          </ul>
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-lg-3">
              <article class="customers-wrapper">
                <canvas id="customersChart" aria-label="Customers statistics" role="img"></canvas>
              </article>
            </div>
          </div>
        </div>
      </main>
      <!-- ! Footer -->
      <footer class="footer">
        <div class="container footer--flex">
          <div class="footer-start">
            <p>2025 © ENSAH E-services - <a href="e-service-dashboard.com" target="_blank"
                rel="noopener noreferrer">e-services.com</a></p>
          </div>
          <ul class="footer-end">
            <li><a href="##">About</a></li>
            <li><a href="##">Support</a></li>
            <li><a href="##">Puchase</a></li>
          </ul>
        </div>
      </footer>
    </div>
  </div>
  <!-- Chart library -->
  <script src="../plugins/chart.min.js"></script>
  <!-- Icons library -->
  <script src="../plugins/feather.min.js"></script>
  <!-- Custom scripts -->
  <script src="../js/script.js"></script>
</body>

</html>