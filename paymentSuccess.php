<?php
require_once __DIR__ . '/includes/database.php';
// paymentSuccess.php — pretty receipt + DB write + email notification (Stripe card success)
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

/**
 * Toggle debug when testing on friend's laptop.
 * true  = show errors on screen
 * false = silent in UI
 */
$DEBUG = true; // tukar false bila dah ok

if ($DEBUG) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
}

/** ---------- Load mail config safely ---------- */
$cfg = __DIR__ . '/includes/mail.config.php';
if (!file_exists($cfg)) {
  // Important: this is the #1 cause of "undefined function sendOrderEmail"
  if ($DEBUG) die("Missing mail config file: " . htmlspecialchars($cfg));
  // If not debugging, just continue without email
} else {
  require_once $cfg;
}

/** ---------- Receipt display defaults ---------- */
$displayRef    = $_GET['tid'] ?? $_GET['session_id'] ?? ($_SESSION['last_payment_ref'] ?? 'N/A');
$displayMethod = $_SESSION['last_payment_method'] ?? (isset($_GET['session_id']) ? 'Card (Stripe/Test)' : 'Payment');

// If last_payment_total stored already formatted, just show it; else format
$displayTotal  = isset($_SESSION['last_payment_total'])
  ? number_format((float)$_SESSION['last_payment_total'], 2, '.', '')
  : null;

/** ---------- If Stripe checkout returns session_id, verify paid ---------- */
if (isset($_GET['session_id'])) {
  $autoload = __DIR__ . '/vendor/autoload.php';
  if (!file_exists($autoload)) {
    if ($DEBUG) die("Missing composer vendor/autoload.php. Run: composer install");
  } else {
    require $autoload;
  }

  $sid = $_GET['session_id'];

  try {
    \Stripe\Stripe::setApiKey(box_config('stripe_secret_key'));

    $session = \Stripe\Checkout\Session::retrieve($sid);

    if (($session->payment_status ?? '') === 'paid') {

      /** ====== 1) Pull data from Stripe ====== */
      $piId     = $session->payment_intent ?? null;
      $amountRM = ($session->amount_total ?? 0) / 100.0;
      $currency = strtoupper($session->currency ?? 'MYR');

      $ref      = $session->metadata->ref ?? $sid;

      $email        = $session->customer_details->email ?? null;
      $customerName = $session->customer_details->name  ?? 'BOX Customer';

      /** ====== 2) Fallback email/name from DB if missing ====== */
      if (!$email && isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];

        $uconn = box_database();
        if ($uconn->connect_error) {
          if ($DEBUG) die("DB connect failed (users lookup): " . $uconn->connect_error);
        } else {
          $ustmt = $uconn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
          if ($ustmt) {
            $ustmt->bind_param("i", $uid);
            $ustmt->execute();
            $ustmt->bind_result($dbName, $dbEmail);

            if ($ustmt->fetch()) {
              if (!empty($dbEmail)) $email = $dbEmail;
              if (!empty($dbName))  $customerName = $dbName;
            }
            $ustmt->close();
          }
          $uconn->close();
        }
      }

      /** Update receipt display */
      $displayRef    = $ref;
      $displayMethod = 'Card (Stripe)';
      $displayTotal  = number_format((float)$amountRM, 2, '.', '');

      /** ====== 3) Save payment to DB ====== */
      $conn = box_database();
      if ($conn->connect_error) {
        throw new Exception("DB connect failed: " . $conn->connect_error);
      }

      $conn->query("
        CREATE TABLE IF NOT EXISTS payments (
          id                INT AUTO_INCREMENT PRIMARY KEY,
          method            ENUM('CARD','EWALLET','COUNTER') NOT NULL DEFAULT 'CARD',
          ref               VARCHAR(50) NOT NULL UNIQUE,
          session_id        VARCHAR(255) NOT NULL,
          payment_intent_id VARCHAR(255) DEFAULT NULL,
          amount_rm         DECIMAL(10,2) NOT NULL,
          currency          CHAR(3) NOT NULL DEFAULT 'MYR',
          customer_email    VARCHAR(255) DEFAULT NULL,
          status            ENUM('PENDING','PAID','FAILED','CANCELLED') NOT NULL,
          created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
      ");

      /** Check if already paid (avoid email spam on refresh) */
      $alreadyPaid = false;
      $check = $conn->prepare("SELECT status FROM payments WHERE ref = ? LIMIT 1");
      if ($check) {
        $check->bind_param('s', $ref);
        $check->execute();
        $check->bind_result($existingStatus);
        if ($check->fetch() && $existingStatus === 'PAID') {
          $alreadyPaid = true;
        }
        $check->close();
      }

      /** Upsert PAID */
      $stmt = $conn->prepare("
        INSERT INTO payments (method, ref, session_id, payment_intent_id, amount_rm, currency, customer_email, status)
        VALUES ('CARD', ?, ?, ?, ?, ?, ?, 'PAID')
        ON DUPLICATE KEY UPDATE
          payment_intent_id = VALUES(payment_intent_id),
          amount_rm         = VALUES(amount_rm),
          currency          = VALUES(currency),
          customer_email    = VALUES(customer_email),
          status            = 'PAID'
      ");

      if ($stmt) {
        $amountFloat = (float)$amountRM;
        $stmt->bind_param('sssdss', $ref, $sid, $piId, $amountFloat, $currency, $email);
        $stmt->execute();
        $stmt->close();
      }

      $conn->close();

      /** ====== 5) Clear cart & set session display ====== */
      unset($_SESSION['cart']);
      $_SESSION['last_payment_ref']    = $ref;
      $_SESSION['last_payment_method'] = 'Card (Stripe)';
      $_SESSION['last_payment_total']  = number_format((float)$amountRM, 2, '.', '');

      /** ====== 6) Send email ONCE ONLY ====== */
      $canSendEmail = (!$alreadyPaid && !empty($email) && function_exists('sendOrderEmail'));

      if ($canSendEmail) {
        $orderData = [
          'order_id'   => $ref,
          'total'      => number_format((float)$amountRM, 2, '.', ''),
          'branch'     => $session->metadata->branch ?? 'BOX Cafe',
          'created_at' => date('Y-m-d H:i')
        ];

        // Send
        sendOrderEmail($email, $customerName, $orderData);
      } else {
        if ($DEBUG) {
          // helpful debug info
          // (comment out kalau tak nak)
          // echo "<!-- Email skipped: alreadyPaid=" . ($alreadyPaid ? "yes" : "no") .
          //      ", email=" . ($email ? "yes" : "no") .
          //      ", fn=" . (function_exists('sendOrderEmail') ? "yes" : "no") . " -->";
        }
      }
    }

  } catch (\Throwable $e) {
    if ($DEBUG) {
      die("paymentSuccess error: " . htmlspecialchars($e->getMessage()));
    }
    // else silent
  }
}

/** ---------- Render UI ---------- */
$paidOn = date('d M Y, h:i A');
include("header.php");
?>
<link rel="stylesheet" href="styleCart.css">

<style>
.success-card{max-width:720px;margin:24px auto;background:#fff;border-radius:16px;box-shadow:0 12px 32px rgba(0,0,0,.06);overflow:hidden}
.success-hero{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;text-align:center;padding:32px 24px}
.success-hero .icon{width:72px;height:72px;margin:0 auto 12px;background:#fff;border-radius:999px;display:grid;place-items:center;box-shadow:0 6px 20px rgba(0,0,0,.15)}
.success-hero .icon svg{width:42px;height:42px;color:#16a34a}
.success-hero h1{margin:8px 0 4px;font-size:28px}
.success-hero p{margin:0;opacity:.95}
.success-body{padding:22px}
.meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:14px 0 6px}
.meta{border:1px solid #eee;border-radius:12px;padding:12px 14px;background:#fafafa}
.meta span{display:block;font-size:12px;color:#666;margin-bottom:4px}
.meta strong{font-size:16px;color:#111}
.actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:14px}
.actions .btn,.actions .btn-clear{min-width:160px;text-align:center}
.note{text-align:center;color:#666;font-size:13px;margin-top:12px}
@media print{header,footer,.actions a,.actions button{display:none!important}.success-card{box-shadow:none;border:1px solid #ddd}}
</style>

<div class="container">
  <div class="success-card">
    <div class="success-hero">
      <div class="icon">
        <svg viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="11" stroke="currentColor" stroke-width="2" opacity=".15"/>
          <path d="M7 12.5l3.2 3.2L17.5 8.4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h1>Payment Success</h1>
      <p>Thank you! Your payment was processed.</p>
    </div>

    <div class="success-body">
      <div class="meta-grid">
        <div class="meta"><span>Reference</span><strong><?= htmlspecialchars($displayRef) ?></strong></div>
        <div class="meta"><span>Method</span><strong><?= htmlspecialchars($displayMethod) ?></strong></div>
        <?php if ($displayTotal !== null): ?>
          <div class="meta"><span>Paid Amount</span><strong>RM<?= htmlspecialchars($displayTotal) ?></strong></div>
        <?php endif; ?>
        <div class="meta"><span>Paid On</span><strong><?= htmlspecialchars($paidOn) ?></strong></div>
      </div>

      <div class="actions">
        <a href="menu.php" class="btn">Continue Shopping</a>
        <a href="cart.php" class="btn btn-clear">View Cart</a>
        <button class="btn" onclick="window.print()">Print Receipt</button>
      </div>

      <p class="note">You’ll receive updates by email/WhatsApp if applicable.</p>
    </div>
  </div>
</div>

<?php include("footer.php"); ?>

<?php
unset($_SESSION['last_payment_total'], $_SESSION['last_payment_ref'], $_SESSION['last_payment_method']);
