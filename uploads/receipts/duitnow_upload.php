<?php
require_once __DIR__ . '/../../includes/database.php';
session_start();
$conn = box_database();
if ($conn->connect_error) { http_response_code(500); exit("DB connect error"); }

// accept either order_id or order_ref from form
$orderRef = trim($_POST['order_id'] ?? '');
if ($orderRef === '') $orderRef = trim($_POST['order_ref'] ?? '');
$amount  = (float)($_POST['amount'] ?? 0);
$refNo   = trim($_POST['ref_no'] ?? '');
$paidAt  = trim($_POST['paid_at'] ?? '');
$note    = trim($_POST['note'] ?? '');

if ($orderRef === '') { http_response_code(400); exit("Missing order ref."); }
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); exit("Receipt upload failed.");
}

// ✅ delivery fields (from duitnow.php hidden inputs)
$orderType = (($_POST['order_type'] ?? 'PICKUP') === 'DELIVERY') ? 'DELIVERY' : 'PICKUP';
$deliveryFee = (float)($_POST['delivery_fee'] ?? 0);
$deliveryZone = trim($_POST['delivery_zone'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$deliveryNote = trim($_POST['delivery_note'] ?? '');
$customerPhone = trim($_POST['customer_phone'] ?? '');

// validate file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['receipt']['tmp_name']);
finfo_close($finfo);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
if (!isset($allowed[$mime])) { http_response_code(400); exit("Only JPG, PNG, or PDF allowed."); }
if ($_FILES['receipt']['size'] > 3 * 1024 * 1024) { http_response_code(400); exit("File too large (max 3MB)."); }

// ensure folder
$root = __DIR__;
$dir  = $root . '/uploads/receipts';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

// save file
$ext = $allowed[$mime];
$cleanId = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $orderRef);
$basename = 'rcpt_' . $cleanId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$dest     = $dir . '/' . $basename;
if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $dest)) {
  http_response_code(500); exit("Failed to save file.");
}
$publicPath = '/BoxCafe/uploads/receipts/' . $basename;

// insert audit row
$paidAtDB = $paidAt ? date('Y-m-d H:i:s', strtotime($paidAt)) : null;
$stmt = $conn->prepare("INSERT INTO receipt_uploads (order_ref, amount_myr, ref_no, paid_at, note, receipt_path) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("sdssss", $orderRef, $amount, $refNo, $paidAtDB, $note, $publicPath);
if (!$stmt->execute()) { http_response_code(500); exit("DB insert error (receipt_uploads)"); }
$stmt->close();

/**
 * ✅ Orders table:
 * order_id is INT AUTO_INCREMENT.
 * So if orderRef is numeric -> update that order_id.
 * If orderRef is NOT numeric -> create a new order row (minimal) and store ref in receipt_path + status.
 */

// If delivery selected, basic validation
if ($orderType === 'DELIVERY') {
  if ($customerPhone === '' || $deliveryAddress === '') {
    http_response_code(400);
    exit("Delivery requires phone + address.");
  }
}

$isNumericId = ctype_digit($orderRef); // "123" only

if ($isNumericId) {
  // update existing order by order_id
  $oid = (int)$orderRef;

  $stmtU = $conn->prepare("
    UPDATE orders
    SET status = 'pending_review',
        receipt_path = ?,
        total_price = ?,
        order_type = ?,
        delivery_address = ?,
        delivery_note = ?,
        delivery_fee = ?,
        delivery_zone = ?,
        delivery_status = ?
    WHERE order_id = ?
  ");
  $dAddr = ($orderType === 'DELIVERY') ? $deliveryAddress : '';
  $dNote = ($orderType === 'DELIVERY') ? $deliveryNote : '';
  $dZone = ($orderType === 'DELIVERY') ? ($deliveryZone ?: 'A') : '';
  $dStat = ($orderType === 'DELIVERY') ? 'PREPARING' : 'PREPARING';

  $stmtU->bind_param(
    "sdsssds si",
    $publicPath,
    $amount,
    $orderType,
    $dAddr,
    $dNote,
    $deliveryFee,
    $dZone,
    $dStat,
    $oid
  );
  $stmtU->execute();
  $updated = $stmtU->affected_rows;
  $stmtU->close();

  // if not found, create
  if ($updated === 0) {
    $stmtI = $conn->prepare("
      INSERT INTO orders
        (customer_name, customer_phone, total_price, status, created_at, receipt_path,
         order_type, delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status)
      VALUES
        ('', ?, ?, 'pending_review', NOW(), ?, ?, ?, ?, ?, ?, ?)
    ");
    $dAddr = ($orderType === 'DELIVERY') ? $deliveryAddress : '';
    $dNote = ($orderType === 'DELIVERY') ? $deliveryNote : '';
    $dZone = ($orderType === 'DELIVERY') ? ($deliveryZone ?: 'A') : '';
    $dStat = ($orderType === 'DELIVERY') ? 'PREPARING' : 'PREPARING';

    $stmtI->bind_param("sdsssssdss",
      $customerPhone, $amount, $publicPath,
      $orderType, $dAddr, $dNote, $deliveryFee, $dZone, $dStat
    );
    $stmtI->execute();
    $stmtI->close();
  }

} else {
  // orderRef is like BOX-20251217... -> create a new order row
  $customerName = 'BOX Customer';

  // optional: if logged in, fill name
  if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $u = $conn->prepare("SELECT name, phone FROM users WHERE id = ? LIMIT 1");
    $u->bind_param("i", $uid);
    $u->execute();
    $u->bind_result($dbName, $dbPhone);
    if ($u->fetch()) {
      if (!empty($dbName)) $customerName = $dbName;
      if ($customerPhone === '' && !empty($dbPhone)) $customerPhone = $dbPhone;
    }
    $u->close();
  }

  // create new order, status pending_review
  $dAddr = ($orderType === 'DELIVERY') ? $deliveryAddress : '';
  $dNote = ($orderType === 'DELIVERY') ? $deliveryNote : '';
  $dZone = ($orderType === 'DELIVERY') ? ($deliveryZone ?: 'A') : '';
  $dStat = ($orderType === 'DELIVERY') ? 'PREPARING' : 'PREPARING';

  $stmtI = $conn->prepare("
    INSERT INTO orders
      (customer_name, customer_phone, total_price, status, created_at, receipt_path,
       order_type, delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status)
    VALUES
      (?, ?, ?, 'pending_review', NOW(), ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmtI->bind_param("ssdsss ssdss",
    $customerName, $customerPhone, $amount,
    $publicPath,
    $orderType, $dAddr, $dNote, $deliveryFee, $dZone, $dStat
  );
  $stmtI->execute();
  $newOrderId = $stmtI->insert_id;
  $stmtI->close();

  // ✅ also create a payments row (optional but nice for tracking)
  // We'll store orderRef in ref
  $payRef = 'DN-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
  $stmtP = $conn->prepare("
    INSERT INTO payments (method, ref, session_id, amount_rm, currency, customer_email, status)
    VALUES ('EWALLET', ?, ?, ?, 'MYR', NULL, 'PENDING')
  ");
  $fakeSession = 'DUITNOW-' . $newOrderId . '-' . $orderRef;
  $stmtP->bind_param("ssd", $payRef, $fakeSession, $amount);
  $stmtP->execute();
  $stmtP->close();
}

$conn->close();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Receipt Submitted</title>
<style>
  body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 40px; }
  a.btn { display:inline-block; padding:10px 14px; border-radius:8px; background:#111; color:#fff; text-decoration:none; }
</style>
</head>
<body>
  <h1>✅ Receipt Submitted</h1>
  <p>Order Ref: <strong><?= htmlspecialchars($orderRef) ?></strong></p>
  <p>We’ve received your receipt. We’ll verify it soon.</p>
  <p><a href="/BoxCafe/paymentSuccess.php" class="btn">Back</a></p>
</body>
</html>
