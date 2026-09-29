<?php 
require 'headerAdmin.php';
require_once 'db_connect.php';

// Handle new stock item insertion
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] === 'add') {
    $stmt = $pdo->prepare("INSERT INTO stock_items (item_name, quantity, unit) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['item_name'], $_POST['quantity'], $_POST['unit']]);
    header("Location: stock.php");
    exit;
}

// Handle stock update
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] === 'update') {
    $stmt = $pdo->prepare("UPDATE stock_items SET quantity = ?, last_updated = NOW() WHERE stock_id = ?");
    $stmt->execute([$_POST['new_quantity'], $_POST['stock_id']]);
    header("Location: stock.php");
    exit;
}

// Handle stock deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM stock_items WHERE stock_id = ?");
    $stmt->execute([$_POST['stock_id']]);
    header("Location: stock.php");
    exit;
}

// Fetch all stock items
$stmt = $pdo->query("SELECT * FROM stock_items ORDER BY item_name ASC");
$stock_items = $stmt->fetchAll();
?>

<h2>Stock Management</h2>

<!-- Add New Stock Item Form -->
<h3>Add New Stock Item</h3>
<form method="post" style="margin-bottom: 20px;" onsubmit="return confirm('Are you sure you want to add this item?');">
    <input type="hidden" name="action" value="add">
    <input type="text" name="item_name" placeholder="Item Name" required>
    <input type="number" name="quantity" placeholder="Quantity" min="0" required>
    <input type="text" name="unit" placeholder="Unit (e.g., pack, box)" required>
    <button type="submit">Add Item</button>
</form>

<!-- Display Stock Items -->
<?php if (count($stock_items) > 0): ?>
    <table border="1" width="100%" cellpadding="10" cellspacing="0" style="background:white; border-collapse: collapse;">
        <tr style="background:#eee;">
            <th>Stock ID</th>
            <th>Item Name</th>
            <th>Unit</th>
            <th>Current Quantity</th>
            <th>New Quantity</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($stock_items as $item): ?>
            <tr style="<?= $item['quantity'] <= 5 ? 'background: #ffcccc;' : '' ?>">
                <td><?= $item['stock_id'] ?></td>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= $item['unit'] ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Update this stock quantity?');">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="stock_id" value="<?= $item['stock_id'] ?>">
                        <input type="number" name="new_quantity" value="<?= $item['quantity'] ?>" min="0" required>
                        <button type="submit" style="background:green; color:white;">Update</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('⚠ Delete this stock item permanently?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="stock_id" value="<?= $item['stock_id'] ?>">
                        <button type="submit" style="background:red; color:white;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No stock items found. Use the form above to add new items.</p>
<?php endif; ?>

<?php require 'footerAdmin.php'; ?>
