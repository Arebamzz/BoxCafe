<?php
$orderRef = $_POST['order_ref'] ?? $_GET['order_id'] ?? ('BOX-'.date('YmdHis'));
$total    = $_POST['amount']    ?? $_GET['amount']    ?? 0;
?>


<div class="method" style="max-width:560px;border:1px solid #eee;padding:16px;border-radius:10px">
  <img src="/assets/DuitNow.jpg" alt="DuitNow QR" class="qr">

<a href="/assets/DuitNow.jpg" download="DuitNow-QR-<?= htmlspecialchars($order_id) ?>.jpg" class="btn">
  Save QR
</a>

<form action="/duitnow_upload.php" method="post" enctype="multipart/form-data" style="display:grid; gap:10px">
  <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
  <input type="hidden" name="amount" value="<?= htmlspecialchars(number_format($total ?? 0, 2, '.', '')) ?>">

  <!-- optional fields (keep if you showed them) -->
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
</form>

<div class="hint">
  Amount: <strong>RM <?= htmlspecialchars(number_format($total ?? 0, 2)) ?></strong><br>
  Order ID: <code><?= htmlspecialchars($order_id) ?></code>
</div>
