<?php
require_once __DIR__ . '/includes/database.php';
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  die("Cart empty.");
}

$name    = trim($_POST['customer_name'] ?? '');
$phone   = trim($_POST['customer_phone'] ?? '');
$addr    = trim($_POST['delivery_address'] ?? '');
$note    = trim($_POST['delivery_note'] ?? '');
$zone    = $_POST['delivery_zone'] ?? 'A';

if ($name==='' || $phone==='' || $addr==='') {
  die("Missing required fields.");
}

// dummy delivery fee
$fee = 5.00;
if ($zone === 'B') $fee = 8.00;
if ($zone === 'C') $fee = 12.00;

// subtotal from cart
$subtotal = 0.0;
foreach ($_SESSION['cart'] as $it) {
  $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
  $price = (float)($it['price'] ?? 0);
  $subtotal += ($qty * $price);
}

$total = $subtotal + $fee;

// DB
$conn = box_database();
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

// insert into orders (ikut column kau)
$stmt = $conn->prepare("
  INSERT INTO orders
    (customer_name, customer_phone, total_price, status, order_type, delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status)
  VALUES
    (?, ?, ?, 'PENDING', 'DELIVERY', ?, ?, ?, ?, 'PREPARING')
");
$stmt->bind_param("ssdssdds", $name, $phone, $total, $addr, $note, $fee, $zone);
$stmt->execute();
$orderId = $stmt->insert_id;
$stmt->close();

// insert order items (ikut column kau)
$itemStmt = $conn->prepare("
  INSERT INTO order_items (order_id, product_id, quantity, price, item_name)
  VALUES (?, ?, ?, ?, ?)
");

foreach ($_SESSION['cart'] as $it) {
  $productId = (int)($it['product_id'] ?? $it['menu_id'] ?? 0);
  $qty       = (int)($it['quantity'] ?? $it['qty'] ?? 1);
  $price     = (float)($it['price'] ?? 0);
  $itemName  = $it['item_name'] ?? $it['name'] ?? 'Item';

  $itemStmt->bind_param("iiids", $orderId, $productId, $qty, $price, $itemName);
  $itemStmt->execute();
}
$itemStmt->close();

$conn->close();

// clear cart
unset($_SESSION['cart']);

// go track
header("Location: delivery_track.php?order_id=" . $orderId);
exit;
