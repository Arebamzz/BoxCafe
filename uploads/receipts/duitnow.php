<?php
// /payments/duitnow.php
session_start();

$order_id = $_POST['order_id'] ?? $_POST['order_ref'] ?? ('BOX-'.date('YmdHis'));
$amount   = $_POST['amount']   ?? '0.00';

// ✅ carry delivery fields
$order_type = $_POST['order_type'] ?? 'PICKUP';
$delivery_fee = $_POST['delivery_fee'] ?? '0.00';
$delivery_zone = $_POST['delivery_zone'] ?? '';
$delivery_address = $_POST['delivery_address'] ?? '';
$delivery_note = $_POST['delivery_note'] ?? '';
$customer_phone = $_POST['customer_phone'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>DuitNow QR Payment</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 24px; }
    .wrap { max-width: 720px; margin: auto; border:1px solid #eee; border-radius:14px; padding:16px; background:#fff; }
    .row { display:flex; gap:16px; flex-wrap:wrap; }
    .card { flex:1; min-width:260px; }
    img.qr { max-width:100%; border:1px solid #eee; border-radius:10px; padding:10px; background:#fff; }
    .btn { padding:10px 14px; border-radius:8px; border:1px solid #222; background:#f7f7f7; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-primary { background:#111; color:#fff; border-color:#111; }
    label small { color:#555; display:block; margin-bottom:6px; }
    .hint { color:#666; font-size:13px; margin-top:8px; }
    input[type="file"], input[type="text"], input[type="datetime-local"] {
      width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:10px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <h2>DuitNow QR (Manual)</h2>
    <p>Scan with your banking app, pay the exact amount, then upload your receipt.</p>

    <div class="row">
      <div class="card" style="text-align:center">
        <img src="/BoxCafe/assets/DuitNow.jpg" alt="DuitNow QR" class="qr">
        <div style="margin-top:10px">
          <a href="/BoxCafe/assets/DuitNow.jpg" download="DuitNow-QR-<?= htmlspecialchars($order_id) ?>.jpg" class="btn">Save QR</a>
        </div>
        <div class="hint">
          Amount: <strong>RM <?= htmlspecialchars(number_format((float)$amount, 2)) ?></strong><br>
          Order Ref: <code><?= htmlspecialchars($order_id) ?></code>
        </div>
      </div>

      <div class="card">
        <form action="/BoxCafe/duitnow_upload.php" method="post" enctype="multipart/form-data" style="display:grid; gap:10px">
          <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
          <input type="hidden" name="amount" value="<?= htmlspecialchars(number_format((float)$amount, 2, '.', '')) ?>">

          <!-- ✅ keep delivery -->
          <input type="hidden" name="order_type" value="<?= htmlspecialchars($order_type) ?>">
          <input type="hidden" name="delivery_fee" value="<?= htmlspecialchars($delivery_fee) ?>">
          <input type="hidden" name="delivery_zone" value="<?= htmlspecialchars($delivery_zone) ?>">
          <input type="hidden" name="delivery_address" value="<?= htmlspecialchars($delivery_address) ?>">
          <input type="hidden" name="delivery_note" value="<?= htmlspecialchars($delivery_note) ?>">
          <input type="hidden" name="customer_phone" value="<?= htmlspecialchars($customer_phone) ?>">

          <label><small>Bank Reference (optional)</small>
            <input type="text" name="ref_no" placeholder="e.g. MBANK-123456">
          </label>
          <label><small>Payment Date & Time (optional)</small>
            <input type="datetime-local" name="paid_at">
          </label>
          <label><small>Upload payment receipt (jpg/png/pdf, ≤ 3MB)</small>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
          </label>
          <label><small>Note (optional)</small>
            <input type="text" name="note" placeholder="Any note to admin…">
          </label>

          <button type="submit" class="btn btn-primary">Submit for verification</button>
          <div class="hint">We’ll verify your receipt and update your order status.</div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
