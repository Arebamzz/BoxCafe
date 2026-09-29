<?php
require_once __DIR__ . '/includes/database.php';
session_start();

// Direct DB connection (fix for missing db_connect.php)
$conn = box_database();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper: calculate promo price (if active + within date)
function promoPrice($row) {
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
    } else {
        $newPrice = $price - $value;
    }

    if ($newPrice < 0) $newPrice = 0;
    return round($newPrice, 2);
}

// Get menu item by ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = "SELECT * FROM menu_items WHERE id = $id";
$result = mysqli_query($conn, $query);
$item = mysqli_fetch_assoc($result);

if (!$item) {
    echo "<p>Item not found.</p>";
    exit;
}

$promo = promoPrice($item);
$displayPrice = ($promo !== null) ? $promo : floatval($item['price']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customize Order</title>
  <link rel="stylesheet" href="styleMenuCustomize.css">
</head>
<body>

<div class="customize-container">
  <div class="customize-card">
    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-img">
    <h2><?= htmlspecialchars($item['name']) ?></h2>

    <p class="price">
      <?php if ($promo !== null): ?>
        <span class="old-price">RM <?= number_format($item['price'], 2) ?></span>
        <span class="new-price">RM <?= number_format($promo, 2) ?></span>
        <span class="promo-tag">PROMO</span>
      <?php else: ?>
        RM <?= number_format($item['price'], 2) ?>
      <?php endif; ?>
    </p>

    <form method="post" action="add_to_cart.php">
      <input type="hidden" name="id" value="<?= $item['id'] ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($item['name']) ?>">
      <input type="hidden" name="price" value="<?= $displayPrice ?>">

      <label for="spice">Spice Level:</label>
      <select name="spice_level" id="spice" required>
        <option value="Less Spicy">Less Spicy</option>
        <option value="Normal">Normal</option>
        <option value="Extra Spicy">Extra Spicy</option>
      </select>

      <label for="remarks">Remarks:</label>
      <textarea name="remarks" id="remarks" rows="3" placeholder="Any special instructions? (e.g., no onions)"></textarea>

      <label for="quantity">Quantity:</label>
      <input type="number" name="quantity" id="quantity" value="1" min="1" required>

      <button type="submit" class="add-btn">Add to Cart</button>
    </form>

    <a href="menu.php" class="back-link">← Back to Menu</a>
  </div>
</div>

</body>
</html>
