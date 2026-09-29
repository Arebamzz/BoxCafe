<?php
require 'headerAdmin.php';
require_once 'db_connect.php';                 // for root-level pages (e.g. cart.php)


// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ==========================
// EXISTING METRICS (KEEP)
// ==========================
$totalProducts = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();


// ==========================
// NEW: SALES METRICS (PAID)
// Source: payments table
// ==========================
function getPaidSales($pdo, $interval) {
    // $interval example: "1 DAY", "7 DAY", "1 MONTH"
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
          AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
    ");
    $stmt->execute();
    return (float)($stmt->fetchColumn() ?? 0);
}

function getPaidSalesToday($pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
          AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    return (float)($stmt->fetchColumn() ?? 0);
}

function getPaidSalesAllTime($pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
    ");
    $stmt->execute();
    return (float)($stmt->fetchColumn() ?? 0);
}

$paidToday   = getPaidSalesToday($pdo);
$paidWeekly  = getPaidSales($pdo, '7 DAY');
$paidMonthly = getPaidSales($pdo, '1 MONTH');
$paidAllTime = getPaidSalesAllTime($pdo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - BoxCafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #111;
            color: #fff;
            padding: 1.2rem;
            text-align: center;
        }
        .container {
            padding: 2rem;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }
        .card h3 {
            margin: 0 0 0.4rem;
            font-size: 16px;
            color: #333;
        }
        .card p {
            margin: 0;
            font-size: 26px;
            font-weight: bold;
            color: #111;
        }
        .sub {
            margin-top: 6px;
            font-size: 13px;
            color: #666;
            font-weight: normal;
        }
        .row-actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn:hover { opacity: .92; }
        .logout {
            display: inline-block;
            margin-top: 2rem;
            color: #fff;
            background: #d9534f;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
        }
        .logout:hover { background: #c9302c; }
        .money { color: #198754; }
    </style>
</head>
<body>

<div class="header">
    <h2>Admin Dashboard</h2>
</div>

<div class="container">
    <h3>Overview</h3>

    <div class="cards">
        <!-- EXISTING CARDS (KEEP) -->
        <div class="card">
            <h3>Total Users</h3>
            <p><?= $totalUsers ?></p>
        </div>

        <div class="card">
            <h3>Total Products</h3>
            <p><?= $totalProducts ?></p>
        </div>

        <div class="card">
            <h3>Total Orders</h3>
            <p><?= $totalOrders ?></p>
        </div>

        <!-- NEW SALES CARDS -->
        <div class="card">
            <h3>Total Sales (All Time)</h3>
            <p class="money">RM <?= number_format($paidAllTime, 2) ?></p>
            <div class="sub">Based on payments.status = PAID</div>
        </div>

        <div class="card">
            <h3>Today's Sales</h3>
            <p class="money">RM <?= number_format($paidToday, 2) ?></p>
            <div class="sub">Paid today</div>
        </div>

        <div class="card">
            <h3>This Week's Sales</h3>
            <p class="money">RM <?= number_format($paidWeekly, 2) ?></p>
            <div class="sub">Last 7 days</div>
        </div>

        <div class="card">
            <h3>This Month's Sales</h3>
            <p class="money">RM <?= number_format($paidMonthly, 2) ?></p>
            <div class="sub">Last 1 month</div>
        </div>
    </div>

    <div class="row-actions">
        <a class="btn" href="reports.php">View Sales Reports</a>
        <a class="btn" href="orders.php">Manage Orders</a>
        <a class="btn" href="products.php">Manage Products</a>
    </div>

    <a href="logout.php" class="logout">Logout</a>
</div>

</body>
</html>
