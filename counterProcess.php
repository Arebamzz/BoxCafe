<?php
require_once __DIR__ . '/includes/database.php';
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

if (empty($_SESSION['cart'])) {
  header("Location: cart.php"); exit;
}

$conn = box_database();
if ($conn->connect_error) { die("DB error: " . $conn->connect_error); }

// -----------------------
// 1) Get POST inputs
// -----------------------
$orderRef  = $_POST['order_ref'] ?? ('BOX-' . date('YmdHis') . '-' . rand(100,999));
$orderType = (($_POST['order_type'] ?? 'PICKUP') === 'DELIVERY') ? 'DELIVERY' : 'PICKUP';

$deliveryZone    = trim($_POST['delivery_zone'] ?? 'A');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$deliveryNote    = trim($_POST['delivery_note'] ?? '');
$customerPhone   = trim($_POST['customer_phone'] ?? '');

// -----------------------
// 2) Helper: fee by zone
// -----------------------
function feeFromZone($zone) {
  $zone = strtoupper(trim($zone));
  if ($zone === 'B') return 8.00;
  if ($zone === 'C') return 12.00;
  return 5.00; // A default
}

$deliveryFee    = 0.00;
$deliveryStatus = 'PREPARING';

if ($orderType === 'DELIVERY') {
  if ($deliveryAddress === '' || $customerPhone === '') {
    die("Delivery needs phone + address.");
  }
  $deliveryFee = feeFromZone($deliveryZone ?: 'A');
}

// -----------------------
// 3) Customer name/phone fallback (from users)
// Your users table columns: user_id, full_name, phone
// -----------------------
$customerName = 'BOX Customer';

if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $u = $conn->prepare("SELECT full_name, phone FROM users WHERE user_id = ? LIMIT 1");
  if ($u) {
    $u->bind_param("i", $uid);
    $u->execute();
    $u->bind_result($dbName, $dbPhone);
    if ($u->fetch()) {
      if (!empty($dbName)) $customerName = $dbName;
      if ($customerPhone === '' && !empty($dbPhone)) $customerPhone = $dbPhone;
    }
    $u->close();
  }
}

if ($customerPhone === '') $customerPhone = 'N/A';

// -----------------------
// 4) Recalculate items from DB (no trust session prices)
// -----------------------
$subtotal = 0.0;
$itemRows = [];

foreach ($_SESSION['cart'] as $it) {
  $id   = (int)($it['product_id'] ?? 0);
  $qty  = max(1, (int)($it['quantity'] ?? 1));
  $type = $it['type'] ?? 'menu';

  if ($id <= 0) continue;

  if ($type === 'merchandise') {
    $stmt = $conn->prepare("SELECT name, price FROM merchandise WHERE id = ? LIMIT 1");
  } else {
    $stmt = $conn->prepare("SELECT name, price FROM menu_items WHERE id = ? LIMIT 1");
  }

  if (!$stmt) continue;

  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($name, $priceRM);
  if ($stmt->fetch()) {
    $price = (float)$priceRM;
    $subtotal += $price * $qty;

    $itemRows[] = [
      'product_id' => $id,
      'quantity'   => $qty,
      'price'      => $price,
      'item_name'  => $name
    ];
  }
  $stmt->close();
}

if (empty($itemRows)) {
  $conn->close();
  die("No valid items in cart.");
}

// Totals
$total    = $subtotal + $deliveryFee;
$amtFloat = (float)$total;                         // for DB (double)
$amt      = number_format($amtFloat, 2, '.', '');  // for session/display

// Delivery insert values
$addrVal = ($orderType === 'DELIVERY') ? $deliveryAddress : '';
$noteVal = ($orderType === 'DELIVERY') ? $deliveryNote : '';
$zoneVal = ($orderType === 'DELIVERY') ? ($deliveryZone ?: 'A') : '';
$feeVal  = ($orderType === 'DELIVERY') ? (float)$deliveryFee : 0.00;
$dStat   = ($orderType === 'DELIVERY') ? $deliveryStatus : 'PREPARING';

// -----------------------
// 5) Insert orders
// -----------------------
$insOrder = $conn->prepare("
  INSERT INTO orders
    (customer_name, customer_phone, total_price, status, order_type,
     delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status)
  VALUES
    (?, ?, ?, 'UNPAID', ?, ?, ?, ?, ?, ?)
");
if (!$insOrder) { die("Prepare orders failed: " . $conn->error); }

$insOrder->bind_param(
  "ssdsssdss",
  $customerName, $customerPhone, $amtFloat,
  $orderType, $addrVal, $noteVal, $feeVal, $zoneVal, $dStat
);

if (!$insOrder->execute()) { die("Insert order failed: " . $insOrder->error); }

$orderId = $insOrder->insert_id;
$insOrder->close();

// -----------------------
// 6) Insert order_items
// -----------------------
$insItem = $conn->prepare("
  INSERT INTO order_items (order_id, product_id, quantity, price, item_name)
  VALUES (?, ?, ?, ?, ?)
");
if (!$insItem) { die("Prepare order_items failed: " . $conn->error); }

foreach ($itemRows as $row) {
  $pid  = (int)$row['product_id'];
  $qty  = (int)$row['quantity'];
  $prc  = (float)$row['price'];
  $name = (string)$row['item_name'];

  $insItem->bind_param("iiids", $orderId, $pid, $qty, $prc, $name);
  if (!$insItem->execute()) { die("Insert item failed: " . $insItem->error); }
}
$insItem->close();

// -----------------------
// 7) Insert payments (COUNTER placeholder)
// payments table: method ENUM('CARD','EWALLET','COUNTER') etc.
// -----------------------
$payRef = 'CTR-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
$fakeSession = 'COUNTER-' . $orderId;

$payStmt = $conn->prepare("
  INSERT INTO payments (method, ref, session_id, amount_rm, currency, customer_email, status)
  VALUES ('COUNTER', ?, ?, ?, 'MYR', NULL, 'PENDING')
");
if (!$payStmt) { die("Prepare payments failed: " . $conn->error); }

$payAmt = (float)$amtFloat;
$payStmt->bind_param("ssd", $payRef, $fakeSession, $payAmt);

if (!$payStmt->execute()) { die("Insert payment failed: " . $payStmt->error); }
$payStmt->close();

$conn->close();

// session display
$_SESSION['last_payment_method'] = 'Pay at Counter';
$_SESSION['last_payment_ref']    = $payRef;
$_SESSION['last_payment_total']  = $amt;

// clear cart
unset($_SESSION['cart']);

// -----------------------
// 8) Redirect
// DELIVERY -> tracking page
// PICKUP   -> payment success page
// -----------------------
if ($orderType === 'DELIVERY') {
  header("Location: delivery_track.php?order_id=" . $orderId);
  exit;
} else {
  header("Location: paymentSuccess.php?tid=" . urlencode($payRef));
  exit;
}
