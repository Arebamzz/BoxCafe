<?php
require_once __DIR__ . '/includes/database.php';
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

$conn = box_database();
if ($conn->connect_error) { die("DB error"); }

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) { die("Invalid order."); }

// Fetch order
$stmt = $conn->prepare("
  SELECT order_id, customer_name, customer_phone, total_price, created_at,
         order_type, delivery_address, delivery_note, delivery_fee, delivery_zone, delivery_status
  FROM orders
  WHERE order_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$order) { die("Order not found."); }
if (($order['order_type'] ?? '') !== 'DELIVERY') { die("This order is not a delivery order."); }

// Normalize status
$status = strtoupper(trim($order['delivery_status'] ?? 'PREPARING'));
if ($status === 'ON THE WAY') $status = 'ON_THE_WAY';

function statusLabel($s) {
  $s = strtoupper(trim($s));
  if ($s === 'ON_THE_WAY') return 'On The Way';
  if ($s === 'DELIVERED')  return 'Delivered';
  return 'Preparing';
}

$label = statusLabel($status);

// Progress %
$progress = 35;
if ($status === 'ON_THE_WAY') $progress = 70;
if ($status === 'DELIVERED')  $progress = 100;

$donePreparing = in_array($status, ['PREPARING','ON_THE_WAY','DELIVERED']);
$doneOTW       = in_array($status, ['ON_THE_WAY','DELIVERED']);
$doneDelivered = in_array($status, ['DELIVERED']);

include("header.php");
?>
<link rel="stylesheet" href="styleCart.css">

<style>
/* page wrapper */
.track-page{
  max-width: 980px;
  margin: 28px auto;
  padding: 0 14px;
}
.track-card{
  background:#fff;
  border:1px solid #eef2f7;
  border-radius:20px;
  box-shadow:0 18px 40px rgba(0,0,0,.06);
  overflow:hidden;
}
.track-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  padding:22px 22px 14px;
  border-bottom:1px solid #f1f5f9;
}
.track-title h2{ margin:0; font-size:22px; }
.track-title .sub{ color:#6b7280; font-size:13px; margin-top:4px; }

/* ===== Grab-like badge ===== */
.badge-grab{
  display:inline-flex; align-items:center; gap:10px;
  padding:10px 14px; border-radius:999px;
  background:#f8fafc; border:1px solid #e5e7eb;
  font-weight:700; font-size:13px;
}
.badge-grab .dot{
  width:10px; height:10px; border-radius:50%;
  background:#22c55e; box-shadow:0 0 0 4px rgba(34,197,94,.15);
}
.badge-grab .brand{ font-weight:800; letter-spacing:.2px; }
.badge-grab .status{ font-weight:700; color:#111; }

/* ===== Info layout (Deliver+Phone+Fee | Note | Total) ===== */
.body{
  padding:18px 22px 22px;
}
.info-grid{
  display:grid;
  grid-template-columns: 1.6fr .9fr 1.1fr;
  gap:12px;
}
.info-card{
  background:#fff;
  border:1px solid #eef2f7;
  border-radius:14px;
  padding:14px 16px;
}
.info-card .label{ font-size:12px; color:#6b7280; margin-bottom:6px; }
.info-card .value{ font-weight:800; color:#111; }
.info-wide{ grid-column: 1 / -1; }
.total-row{
  margin-top:12px;
  display:flex;
  justify-content:flex-start;
}
.total-box{
  width:260px;
}

/* ===== Grab-like progress ===== */
.grab-progress{
  margin-top:16px;
  padding:16px;
  background:#fff;
  border:1px solid #eef2f7;
  border-radius:16px;
}
.grab-topline{
  display:flex; align-items:center; justify-content:space-between;
  gap:12px;
}
.grab-eta{ font-size:22px; font-weight:900; }
.grab-sub{ color:#6b7280; font-size:13px; margin-top:2px; }

.track-line{
  height:10px;
  border-radius:999px;
  background:#e5e7eb;
  overflow:hidden;
  margin:14px 0 10px;
}
.track-line .fill{
  height:100%;
  width:0%;
  background:#22c55e;
  border-radius:999px;
  transition:width .25s ease;
}

.track-steps{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-top:8px;
}
.step{
  width:33.333%;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:6px;
  text-align:center;
  font-size:12px;
  color:#6b7280;
}
.step .icon{
  width:34px;height:34px;border-radius:999px;
  display:grid;place-items:center;
  border:2px solid #e5e7eb;
  background:#fff;
}
.step.active{ color:#111; font-weight:800; }
.step.active .icon{ border-color:#22c55e; }
.step.active .icon {
  animation: popIn .35s ease-out;
  box-shadow: 0 0 0 6px rgba(34,197,94,.15);
}
@keyframes popIn {
  0%   { transform: scale(.7); opacity: .3; }
  70%  { transform: scale(1.08); }
  100% { transform: scale(1); opacity: 1; }
}
.step small{ color:#6b7280; font-weight:600; }

/* Buttons */
.actions{
  display:flex;
  gap:10px;
  justify-content:center;
  margin-top:18px;
}
@media (max-width: 720px){
  .info-grid{ grid-template-columns:1fr; }
  .total-box{ width:100%; }
  .track-head{ flex-direction:column; gap:12px; }
  .badge-grab{ align-self:flex-start; }
}
.track-line {
  position: relative;
  height: 10px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
}

.track-line .fill {
  height: 100%;
  background: linear-gradient(
    110deg,
    #22c55e 25%,
    #4ade80 37%,
    #22c55e 63%
  );
  background-size: 200% 100%;
  border-radius: 999px;
  animation: shimmer 1.6s linear infinite;
}

@keyframes shimmer {
  from { background-position: 200% 0; }
  to   { background-position: -200% 0; }
}


</style>

<div class="track-page">
  <div class="track-card">

    <div class="track-head">
      <div class="track-title">
        <h2>Delivery Tracking</h2>
        <div class="sub">
          Order #<?= (int)$order['order_id'] ?> • <?= htmlspecialchars(date('d M Y, h:i A', strtotime($order['created_at']))) ?>
        </div>
      </div>

      <div class="badge-grab">
        <span class="dot"></span>
        <span class="brand">Grab</span>
        <span>•</span>
        <span class="status"><?= htmlspecialchars($label) ?></span>
      </div>
    </div>

    <div class="body">

      <!-- Info Boxes -->
      <div class="info-grid">
        <div class="info-card">
          <div class="label">Deliver to</div>
          <div class="value"><?= htmlspecialchars($order['delivery_address'] ?? '-') ?></div>
        </div>

        <div class="info-card">
          <div class="label">Phone</div>
          <div class="value"><?= htmlspecialchars($order['customer_phone'] ?? '-') ?></div>
        </div>

        <div class="info-card">
          <div class="label">Delivery Fee</div>
          <div class="value">
            RM<?= number_format((float)($order['delivery_fee'] ?? 0), 2) ?>
            (Zone <?= htmlspecialchars($order['delivery_zone'] ?? '-') ?>)
          </div>
        </div>

        <div class="info-card info-wide">
          <div class="label">Note</div>
          <div class="value"><?= htmlspecialchars($order['delivery_note'] ?: '-') ?></div>
        </div>
      </div>

      <div class="total-row">
        <div class="info-card total-box">
          <div class="label">Total</div>
          <div class="value">RM<?= number_format((float)($order['total_price'] ?? 0), 2) ?></div>
        </div>
      </div>

      <!-- Progress -->
      <div class="grab-progress">
        <div class="grab-topline">
          <div>
            <div class="grab-eta"><?= htmlspecialchars($label) ?></div>
            <div class="grab-sub">
              <?php if ($status === 'PREPARING'): ?>
                Your order's in the kitchen.
              <?php elseif ($status === 'ON_THE_WAY'): ?>
                Rider is heading to your location.
              <?php else: ?>
                Order delivered. Enjoy!
              <?php endif; ?>
            </div>
          </div>
          <div style="font-size:12px;color:#6b7280;">Contact Support</div>
        </div>

       <div class="track-line">
  <div class="fill" style="--progress: <?= $progress ?>%; width: <?= $progress ?>%"></div>
</div>


        <div class="track-steps">
          <div class="step <?= $donePreparing ? 'done' : '' ?> <?= $status==='PREPARING' ? 'active':'' ?>">
            <div class="icon">🍳</div>
            <div>Preparing</div>
            <small>In the kitchen</small>
          </div>

          <div class="step <?= $doneOTW ? 'done' : '' ?> <?= $status==='ON_THE_WAY' ? 'active':'' ?>">
            <div class="icon">🛵</div>
            <div>On The Way</div>
            <small>Rider moving</small>
          </div>

          <div class="step <?= $doneDelivered ? 'done' : '' ?> <?= $status==='DELIVERED' ? 'active':'' ?>">
            <div class="icon">🏠</div>
            <div>Delivered</div>
            <small>Enjoy!</small>
          </div>
        </div>
      </div>

      <!-- Buttons -->
      <div class="actions">
        <a class="btn btn-clear" href="index.php">Back to Home</a>
        <a class="btn" href="cart.php">Order Again</a>
      </div>

    </div>
  </div>
</div>

<?php include("footer.php"); ?>
