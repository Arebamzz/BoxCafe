<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - BoxCafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
        }

        .admin-navbar {
            background: #111;
            padding: 1rem 2rem;
            display: flex;
            flex-direction: column;
            color: white;
        }

        .admin-navbar .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-navbar .nav-links {
            margin-top: 1rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .admin-navbar a {
            color: white;
            text-decoration: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .admin-navbar a:hover {
            background: #333;
        }

        .admin-navbar .logout {
            background: red;
            padding: 0.4rem 0.8rem;
        }

        .container {
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="admin-navbar">
    <div class="top-row">
        <div>Welcome, <?= $_SESSION['username'] ?> (<?= $_SESSION['admin_role'] ?>)</div>
        <a href="logout.php" class="logout">Logout</a>
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Products</a>
        <a href="event_history.php">Event History</a>
         <a href="admin_events.php">Upcoming Event</a>
        <a href="merchAdmin.php">Merchandise</a>
        <a href="stock.php">Stock</a>
        <a href="reports.php">Reports</a>
    </div>
</div>

<div class="container">
