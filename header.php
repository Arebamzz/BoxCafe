<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BOXKL</title>
  <link rel="stylesheet" href="style.css">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    .topbar {
      background-color: #f8f8f8;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .nav-left,
    .nav-right {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .topbar a {
      text-decoration: none;
      padding: 6px 12px;
      border-radius: 4px;
      color: #333;
      font-weight: 500;
    }

    .topbar a:hover {
      background-color: #e0e0e0;
    }

    .logout {
      background-color: black;
      color: white !important;
      padding: 6px 12px;
      border-radius: 5px;
      font-weight: bold;
      text-decoration: none;
    }

    .logout:hover {
      opacity: 0.9;
    }

    .user-name {
      font-weight: 600;
      /* not clickable anymore, so no cursor:pointer */
    }

    /* Small minimal icon buttons (Apple-style) */
    .icon-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #f2f2f2;
      text-decoration: none;
      color: #000;
    }

    .icon-btn i {
      font-size: 14px;
    }

    .icon-btn:hover {
      background: #e6e6e6;
    }
  </style>
</head>

<body>

<center>
  <img src="images/boxlogo1.png" alt="logo" width="100">
</center>

<header class="topbar">
  <div class="nav-left">
    <a href="index.php">Home</a>
    <a href="menu.php">Menu</a>
    <!-- Cart removed here; now cart is an icon on the right -->
    <a href="merchandise.php">Merchandise</a>
  </div>

  <div class="nav-right">
    <?php
      // Decide which session value to use for the name
      $displayName = null;

      if (!empty($_SESSION['user']['username'])) {
          $displayName = $_SESSION['user']['username'];
      } elseif (!empty($_SESSION['username'])) {
          $displayName = $_SESSION['username'];
      }
    ?>

    <?php if ($displayName): ?>
      <!-- LOGGED IN: username text + cart icon + profile icon + logout -->
      <span class="user-name">
        Hi, <?= htmlspecialchars($displayName) ?>
      </span>

      <!-- Cart icon (Apple bag style) -->
      <a href="cart.php" class="icon-btn" title="Cart">
        <i class="fa-solid fa-bag-shopping"></i>
      </a>

      <!-- Profile icon -->
      <a href="profile.php" class="icon-btn" title="Profile">
        <i class="fa-solid fa-user"></i>
      </a>

      <a href="user_logout.php" class="logout">Logout</a>

    <?php else: ?>
      <!-- NOT LOGGED IN: cart icon + login + sign up -->
      <a href="cart.php" class="icon-btn" title="Cart">
        <i class="fa-solid fa-bag-shopping"></i>
      </a>

      <a href="user_login.php" class="logout">Login</a>
      <a href="user_register.php" class="logout">Sign Up</a>
    <?php endif; ?>
  </div>
</header>
