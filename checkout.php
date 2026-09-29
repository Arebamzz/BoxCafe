<?php
require_once __DIR__ . '/includes/database.php';
// checkout.php — creates a Stripe Checkout Session from cart + saves order (supports delivery)
session_start();
require __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

header('Content-Type: application/json');

// Stripe key is loaded from shared configuration.
$secretKey = box_config('stripe_secret_key');

function feeFromZone($zone) {
  // dummy rule sama macam paymentOptions.php
  if ($zone === 'B') return 8.00;
  if ($zone === 'C') return 12.00;
  return 5.00; // default A
}

try {
  if (empty($_SESSION['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Cart is empty']);
    exit;
  }

  // Read delivery fields from POST (sent by paymentOptions.php)
  $orderRef  = $_POST['order_ref'] ?? ('BOX-' . date('YmdHis') . '-' . rand(100,999));
  $orderType = ($_POST['order_type'] ?? 'PICKUP') === 'DELIVERY' ? 'DELIVERY' : 'PICKUP';

  $deliveryZone    = trim($_POST['delivery_zone'] ?? '');
  $deliveryAddress = trim($_POST['delivery_address'] ?? '');
  $deliveryNote    = trim($_POST['delivery_note'] ?? '');
  $customerPhone   = trim($_POST['customer_phone'] ?? '');

  $deliveryFee    = 0.00;
  $deliveryStatus = 'PREPARING';

  if ($orderType === 'DELIVERY') {
    if ($deliveryAddress === '' || $customerPhone === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Delivery needs phone + address']);
      exit;
    }
    // enforce fee based on zone (server-side)
    $deliveryFee = feeFromZone($deliveryZone ?: 'A');
  }

  // Connect DB
  $conn = box_database();
  if ($conn->connect_error) throw new Exception('DB connection failed: ' . $conn->connect_error);

  // Get customer_name + phone (fallback from users table if logged in)
  $customerName = 'BOX Customer';

  if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    $u = $conn->prepare("SELECT name, phone FROM users WHERE user_id = ? LIMIT 1");
    if (!$u) throw new Exception("Prepare users failed: " . $conn->error);

    $u->bind_param("i", $uid);
    $u->execute();
    $u->store_result();
    $u->bind_result($dbName, $dbPhone);

    if ($u->fetch()) {
      if (!empty($dbName)) $customerName = $dbName;
      if ($customerPhone === '' && !empty($dbPhone)) $customerPhone = $dbPhone;
    }

    $u->close();
  }

  if ($customerPhone === '') $customerPhone = 'N/A';

  // Build Stripe line items + subtotal (sen)
  $lineItems = [];
  $subtotalSen = 0;

  foreach ($_SESSION['cart'] as $it) {
    $type = $it['type'] ?? 'menu';
    $id   = (int)($it['product_id'] ?? 0);
    $qty  = max(1, (int)($it['quantity'] ?? 1));
    if ($id <= 0) continue;

    $stmt = ($type === 'merchandise')
      ? $conn->prepare("SELECT name, price FROM merchandise WHERE id = ?")
      : $conn->prepare("SELECT name, price FROM menu_items WHERE id = ?");

    if (!$stmt) throw new Exception("Prepare item failed: " . $conn->error);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result(); // IMPORTANT
    $stmt->bind_result($name, $priceRM);

    if ($stmt->fetch()) {
      $unit = (int) round(((float)$priceRM) * 100); // RM -> sen

      $lineItems[] = [
        'price_data' => [
          'currency' => 'myr',
          'product_data' => ['name' => $name],
          'unit_amount' => $unit,
        ],
        'quantity' => $qty,
      ];
      $subtotalSen += $unit * $qty;
    }
    $stmt->close();
  }

  if (!$lineItems) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid items']);
    exit;
  }

  // Ensure deliveryFeeSen always exists (pickup => 0)
  $deliveryFeeSen = 0;

  // ✅ Add delivery fee as a Stripe line item (if delivery)
  if ($orderType === 'DELIVERY') {
    $deliveryFeeSen = (int) round($deliveryFee * 100);
    if ($deliveryFeeSen > 0) {
      $lineItems[] = [
        'price_data' => [
          'currency' => 'myr',
          'product_data' => ['name' => 'Delivery Fee (' . ($deliveryZone ?: 'A') . ')'],
          'unit_amount' => $deliveryFeeSen,
        ],
        'quantity' => 1,
      ];
    }
  }

  $totalSen = $subtotalSen + $deliveryFeeSen;

  Stripe::setApiKey($secretKey);

  $payRef = 'CARD-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

  // ✅ Create order in DB (use DB default status to avoid enum mismatch)
  $totalRM = (float) number_format($totalSen / 100, 2, '.', '');

  // For pickup, keep delivery fields empty
  $addrVal = ($orderType === 'DELIVERY') ? $deliveryAddress : '';
  $noteVal = ($orderType === 'DELIVERY') ? $deliveryNote : '';
  $zoneVal = ($orderType === 'DELIVERY') ? ($deliveryZone ?: 'A') : '';
  $feeVal  = ($orderType === 'DELIVERY') ? (float)$deliveryFee : 0.00;
  $dStat   = ($orderType === 'DELIVERY') ? $deliveryStatus : 'PREPARING';

  // NOTE: removed `status` column here -> let DB default handle it
  $insOrder = $conn->prepare("
    INSERT INTO orders
      (customer_name, customer_phone, total_price, order_type,
       delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  if (!$insOrder) throw new Exception("Prepare insert order failed: " . $conn->error);

  // Types: s s d s s s d s s  (9 params)
  $insOrder->bind_param(
    "ssdsssdss",
    $customerName,
    $customerPhone,
    $totalRM,
    $orderType,
    $addrVal,
    $noteVal,
    $feeVal,
    $zoneVal,
    $dStat
  );

  $insOrder->execute();
  if ($insOrder->error) throw new Exception("Insert order failed: " . $insOrder->error);

  $orderId = $insOrder->insert_id;
  $insOrder->close();

  // ✅ Insert order_items
  $insItem = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, quantity, price, item_name)
    VALUES (?, ?, ?, ?, ?)
  ");
  if (!$insItem) throw new Exception("Prepare insert items failed: " . $conn->error);

  foreach ($_SESSION['cart'] as $it) {
    $type = $it['type'] ?? 'menu';
    $id   = (int)($it['product_id'] ?? 0);
    $qty  = max(1, (int)($it['quantity'] ?? 1));
    if ($id <= 0) continue;

    $stmt = ($type === 'merchandise')
      ? $conn->prepare("SELECT name, price FROM merchandise WHERE id = ?")
      : $conn->prepare("SELECT name, price FROM menu_items WHERE id = ?");

    if (!$stmt) throw new Exception("Prepare item (2) failed: " . $conn->error);

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result(); // IMPORTANT
    $stmt->bind_result($nm, $pr);

    if ($stmt->fetch()) {
      $prFloat = (float)$pr;
      $insItem->bind_param("iiids", $orderId, $id, $qty, $prFloat, $nm);
      $insItem->execute();
      if ($insItem->error) throw new Exception("Insert item failed: " . $insItem->error);
    }

    $stmt->close();
  }

  $insItem->close();

  // ✅ Create Stripe session (store order_id inside metadata)
  $session = Session::create([
    'mode' => 'payment',
    'payment_method_types' => ['card'],
    'line_items' => $lineItems,
    'success_url' => 'http://localhost/BoxCafe/paymentSuccess.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => 'http://localhost/BoxCafe/paymentCancelled.php',
    'client_reference_id' => $_SESSION['user_id'] ?? null,
    'metadata' => [
      'ref' => $payRef,
      'order_id' => (string)$orderId,
      'order_type' => $orderType
    ],
  ]);

  // ✅ Save payments row as PENDING
  $stmt = $conn->prepare("
    INSERT INTO payments
      (method, ref, session_id, amount_rm, currency, customer_email, status)
    VALUES
      ('CARD', ?, ?, ?, 'MYR', NULL, 'PENDING')
  ");
  if (!$stmt) throw new Exception("Prepare payments failed: " . $conn->error);

  $stmt->bind_param('ssd', $payRef, $session->id, $totalRM);
  $stmt->execute();
  if ($stmt->error) throw new Exception("Insert payment failed: " . $stmt->error);
  $stmt->close();

  $conn->close();

  echo json_encode(['url' => $session->url, 'order_id' => $orderId]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
