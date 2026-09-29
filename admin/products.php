<?php
require 'headerAdmin.php';
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Delete menu item if requested
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: products.php");
    exit;
}

// Helper: calculate promo price (if active + within date)
function getPromoPrice($row) {
    $price = floatval($row['price']);
    $active = isset($row['promo_active']) ? intval($row['promo_active']) : 0;

    if ($active !== 1) return null;

    $type = isset($row['discount_type']) ? $row['discount_type'] : null;
    $value = isset($row['discount_value']) ? floatval($row['discount_value']) : 0;

    if (!$type || $value <= 0) return null;

    $today = date('Y-m-d');
    $start = !empty($row['promo_start']) ? $row['promo_start'] : null;
    $end   = !empty($row['promo_end']) ? $row['promo_end'] : null;

    if ($start && $today < $start) return null;
    if ($end && $today > $end) return null;

    if ($type === 'percent') {
        $newPrice = $price - ($price * ($value / 100));
    } else { // fixed
        $newPrice = $price - $value;
    }

    if ($newPrice < 0) $newPrice = 0;
    return round($newPrice, 2);
}

// Fetch all menu items
$items = $pdo->query("SELECT * FROM menu_items ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Management - BoxCafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 2rem;
        }
        h2 {
            margin-bottom: 1.5rem;
        }
        a.button {
            padding: 0.6rem 1rem;
            background: #111;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: inline-block;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        img {
            max-width: 60px;
            border-radius: 6px;
        }
        .action-buttons a {
            margin-right: 0.5rem;
            color: #007BFF;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            background: #111;
            color: #fff;
        }
        .old {
            text-decoration: line-through;
            color: #888;
            margin-right: 6px;
        }
        .new {
            font-weight: bold;
        }
        .muted {
            color: #666;
            font-size: 13px;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<h2>Manage Menu Items</h2>
<a class="button" href="add_product.php">+ Add New Menu Item</a>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price (RM)</th>
        <th>Promotion</th>
        <th>Description</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($items as $item): ?>
    <?php $promoPrice = getPromoPrice($item); ?>
    <tr>
        <td><?= $item['id'] ?></td>
        <td><?= htmlspecialchars($item['name']) ?></td>

        <td>
            <?php if ($promoPrice !== null): ?>
                <span class="old">RM<?= number_format($item['price'], 2) ?></span>
                <span class="new">RM<?= number_format($promoPrice, 2) ?></span>
            <?php else: ?>
                RM<?= number_format($item['price'], 2) ?>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($promoPrice !== null): ?>
                <span class="badge">ACTIVE</span>
                <div class="muted">
                    Type: <?= htmlspecialchars($item['discount_type']) ?><br>
                    Value: <?= number_format($item['discount_value'], 2) ?><br>
                    Start: <?= !empty($item['promo_start']) ? htmlspecialchars($item['promo_start']) : '-' ?><br>
                    End: <?= !empty($item['promo_end']) ? htmlspecialchars($item['promo_end']) : '-' ?>
                </div>
            <?php else: ?>
                <span class="muted">No active promo</span>
            <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($item['description']) ?></td>
        <td><img src="../<?= $item['image_path'] ?>" alt="Image"></td>
        <td class="action-buttons">
            <a href="edit_product.php?id=<?= $item['id'] ?>">Edit</a>
            <a href="products.php?delete=<?= $item['id'] ?>" onclick="return confirm('Delete this item?');">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

<?php require 'footerAdmin.php'; ?>
