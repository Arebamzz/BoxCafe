<?php
session_start();

// Allow login via both session formats
$loggedIn = false;
$username = "Guest";

if (!empty($_SESSION['user']['username'])) {
    $loggedIn = true;
    $username = $_SESSION['user']['username'];
} elseif (!empty($_SESSION['username'])) {
    $loggedIn = true;
    $username = $_SESSION['username'];
}

// Redirect if no login
if (!$loggedIn) {
    header('Location: user_login.php?redirect=profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile - BOXKL</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .profile-page {
      max-width: 700px;
      margin: 60px auto;
      padding: 40px 20px;
      text-align: left;
    }

    .profile-page h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }

    .profile-page .subtitle {
      color: #666;
      margin-bottom: 40px;
    }

    .profile-section-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .profile-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .profile-links li {
      margin-bottom: 10px;
    }

    .profile-links a {
      text-decoration: none;
      color: #111;
      font-size: 16px;
    }

    .profile-links a:hover {
      text-decoration: underline;
    }

    .profile-empty-text {
      margin-bottom: 30px;
      font-size: 16px;
      color: #555;
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="profile-page">
  
  <p class="subtitle">Welcome back, <?= htmlspecialchars($username); ?>!</p>

  <p class="profile-empty-text">
    You don’t have any active orders yet. When you place an order, it will appear here.
  </p>

  <h2 class="profile-section-title">My Profile</h2>
  <ul class="profile-links">
    <li><a href="orders.php">Orders</a></li>
    <li><a href="cart.php">Your Saves</a></li>
    <li><a href="account.php">Account</a></li>
    <li><a href="user_logout.php">Sign out</a></li>
  </ul>
</div>

</body>
</html>
