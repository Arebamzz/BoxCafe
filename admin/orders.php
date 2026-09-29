<?php
require 'headerAdmin.php';
require_once 'db_connect.php';                 // for root-level pages (e.g. cart.php)


// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: orders.php");
    exit;
}

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - BoxCafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 2rem;
        }
        h2 {
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        select {
            padding: 0.5rem;
        }
        button {
            padding: 0.4rem 1rem;
            background: #111;
            color: white;
            border: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<h2>Order Management</h2>

<table>
    <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Action</th>
    </tr>
    <?php foreach ($orders as $order): ?>
    <tr>
        <td><?= $order['order_id'] ?></td>
        <td><?= htmlspecialchars($order['customer_name']) ?></td>
        <td>RM<?= number_format($order['total_price'], 2) ?></td>
        <td>
            <form method="post" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <select name="status">
                    <?php
                    $statuses = ['Pending', 'Preparing', 'Completed', 'Cancelled'];
                    foreach ($statuses as $status) {
                        $selected = $status == $order['status'] ? 'selected' : '';
                        echo "<option value=\"$status\" $selected>$status</option>";
                    }
                    ?>
                </select>
                <button type="submit">Update</button>
            </form>
        </td>
        <td><?= $order['created_at'] ?></td>
        <td><a href="#">(view items)</a></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
<?php require 'footerAdmin.php'; ?>