<?php
require_once __DIR__ . '/includes/database.php';
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

require __DIR__ . '/includes/mail.config.php';
// DB
$conn = box_database();
if ($conn->connect_error) {
  die("DB error: " . $conn->connect_error);
}

function box_send_mail($to, $subject, $html) {
  // 1) function send_mail()
  if (function_exists('send_mail')) {
    return @send_mail($to, $subject, $html);
  }

  // 2) PHPMailer object $mail
  if (isset($GLOBALS['mail']) && is_object($GLOBALS['mail'])) {
    try {
      $m = $GLOBALS['mail'];
      $m->clearAllRecipients();
      $m->Subject = $subject;
      $m->Body    = $html;
      $m->isHTML(true);
      $m->addAddress($to);
      return $m->send();
    } catch (Throwable $e) {
      return false;
    }
  }

  // 3) fallback mail()
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8\r\n";
  return @mail($to, $subject, $html, $headers);
}

// Prefill dari DB (kalau user login)
$prefillName  = '';
$prefillEmail = '';
$prefillPhone = '';

if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $u = $conn->prepare("SELECT full_name, email, phone FROM users WHERE user_id = ? LIMIT 1");
  $u->bind_param("i", $uid);
  $u->execute();
  $u->bind_result($dbName, $dbEmail, $dbPhone);
  if ($u->fetch()) {
    $prefillName  = $dbName  ?? '';
    $prefillEmail = $dbEmail ?? '';
    $prefillPhone = $dbPhone ?? '';
  }
  $u->close();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $phone     = trim($_POST['phone'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $event_type= trim($_POST['event_type'] ?? '');
  $event_date= trim($_POST['event_date'] ?? '');
  $location  = trim($_POST['location'] ?? '');
  $budget    = trim($_POST['budget'] ?? '');
  $pax       = trim($_POST['pax'] ?? '');

  // Basic validation
  if ($full_name === '' || $phone === '' || $email === '' || $event_type === '' || $event_date === '' || $location === '') {
    $error = "Please fill in all required fields (*).";
  } else {
    
    $budgetVal = ($budget === '') ? null : (float)$budget;
    $paxVal    = ($pax === '') ? null : (int)$pax;

    $stmt = $conn->prepare("
      INSERT INTO event_bookings (full_name, phone, email, event_type, event_date, location, budget, pax)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->close();

    if ($budgetVal === null && $paxVal === null) {
      $stmt = $conn->prepare("
        INSERT INTO event_bookings (full_name, phone, email, event_type, event_date, location, budget, pax)
        VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)
      ");
      $stmt->bind_param("ssssss", $full_name, $phone, $email, $event_type, $event_date, $location);
    } elseif ($budgetVal === null) {
      $stmt = $conn->prepare("
        INSERT INTO event_bookings (full_name, phone, email, event_type, event_date, location, budget, pax)
        VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
      ");
      $stmt->bind_param("ssssssi", $full_name, $phone, $email, $event_type, $event_date, $location, $paxVal);
    } elseif ($paxVal === null) {
      $stmt = $conn->prepare("
        INSERT INTO event_bookings (full_name, phone, email, event_type, event_date, location, budget, pax)
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
      ");
      $stmt->bind_param("ssssssd", $full_name, $phone, $email, $event_type, $event_date, $location, $budgetVal);
    } else {
      $stmt = $conn->prepare("
        INSERT INTO event_bookings (full_name, phone, email, event_type, event_date, location, budget, pax)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssssssdi", $full_name, $phone, $email, $event_type, $event_date, $location, $budgetVal, $paxVal);
    }

    if ($stmt->execute()) {
      $success = true;
      $newId = $stmt->insert_id;

      // ===== Email notification to BOXKL =====
      // Tukar ikut email bisnes kau (atau letak constant BOXKL_NOTIFY_EMAIL dalam mail.config.php)
      $notifyTo = defined('BOXKL_NOTIFY_EMAIL') ? BOXKL_NOTIFY_EMAIL : 'boxkl2022@gmail.com';

      $subject = "New Event Booking (#{$newId}) - {$event_type}";
      $html = "
        <div style='font-family: -apple-system, Segoe UI, Roboto, Arial; line-height:1.5'>
          <h2 style='margin:0 0 10px'>New Event Booking Received</h2>
          <p style='margin:0 0 14px;color:#444'>A customer submitted an event booking via BOXKL website.</p>

          <table cellpadding='8' cellspacing='0' style='border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;overflow:hidden'>
            <tr><td style='background:#f9fafb'><b>Booking ID</b></td><td>#{$newId}</td></tr>
            <tr><td style='background:#f9fafb'><b>Full Name</b></td><td>".htmlspecialchars($full_name)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Phone</b></td><td>".htmlspecialchars($phone)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Email</b></td><td>".htmlspecialchars($email)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Event Type</b></td><td>".htmlspecialchars($event_type)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Event Date</b></td><td>".htmlspecialchars($event_date)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Location</b></td><td>".htmlspecialchars($location)."</td></tr>
            <tr><td style='background:#f9fafb'><b>Budget</b></td><td>".($budget === '' ? '-' : 'RM '.htmlspecialchars($budget))."</td></tr>
            <tr><td style='background:#f9fafb'><b>Estimated Pax</b></td><td>".($pax === '' ? '-' : htmlspecialchars($pax))."</td></tr>
          </table>

          <p style='margin:14px 0 0;color:#6b7280;font-size:12px'>Sent from BOXKL system • ".date('d M Y, h:i A')."</p>
        </div>
      ";
      box_send_mail($notifyTo, $subject, $html);

    } else {
      $error = "DB insert failed: " . $stmt->error;
    }

    $stmt->close();
  }
}

include("header.php");
?>
<link rel="stylesheet" href="style.css">

<style>
  /* Apple-ish page */
  .ev-page{
    min-height: calc(100vh - 140px);
    background: radial-gradient(1200px 500px at 50% -120px, rgba(0,0,0,.08), transparent 60%),
                linear-gradient(#f5f5f7, #f5f5f7);
    padding: 36px 16px 70px;
  }
  .ev-wrap{ max-width: 980px; margin: 0 auto; }
  .ev-head{ text-align:center; margin-bottom: 18px; }
  .ev-head h1{ margin:0; font-size: 34px; letter-spacing:-.02em; }
  .ev-head p{ margin:8px 0 0; color:#6b7280; font-size: 14px; }

  .ev-card{
    background: rgba(255,255,255,.75);
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 40px;
    box-shadow: 0 18px 60px rgba(0,0,0,.08);
    padding: 50px;
    backdrop-filter: blur(10px);
  }

  .ev-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px 50px;
  }
  .ev-field label{
    display:block;
    font-size: 13px;
    color:#111827;
    font-weight: 600;
    margin: 4px 0 8px;
  }
  .ev-field label .req{ color:#ef4444; margin-left:4px; }
  .ev-input{
    width: 100%;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.12);
    background: rgba(255,255,255,.9);
    outline: none;
    transition: box-shadow .15s ease, border-color .15s ease;
    font-size: 14px;
  }
  .ev-input:focus{
    border-color: rgba(0,0,0,.25);
    box-shadow: 0 0 0 4px rgba(0,0,0,.08);
  }

  .ev-actions{
    display:flex;
    justify-content:flex-end;
    gap: 12px;
    margin-top: 18px;
    flex-wrap: wrap;
  }
  .btn-apple{
    border: 0;
    border-radius: 999px;
    padding: 12px 18px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .12s ease, box-shadow .12s ease;
  }
  .btn-apple:active{ transform: scale(.98); }
  .btn-primary{
    background:#111827;
    color:#fff;
    box-shadow: 0 12px 26px rgba(0,0,0,.20);
  }
  .btn-ghost{
    background: transparent;
    color:#111827;
    border: 1px solid rgba(0,0,0,.18);
  }

  .ev-alert{
    margin: 0 auto 14px;
    max-width: 980px;
    border-radius: 16px;
    padding: 12px 14px;
    font-size: 14px;
  }
  .ev-error{ background:#fee2e2; border:1px solid #fecaca; color:#7f1d1d; }
  .ev-ok{ background:#dcfce7; border:1px solid #bbf7d0; color:#14532d; }

  @media (max-width: 760px){
    .ev-grid{ grid-template-columns: 1fr; }
    .ev-head h1{ font-size: 28px; }
    .ev-actions{ justify-content: stretch; }
    .btn-apple{ width:100%; }
  }
</style>

<div class="ev-page">
  <div class="ev-wrap">

    <div class="ev-head">
      <h1>Book an Event</h1>
      <p>Fill in the details below. BOXKL will contact you after review.</p>
    </div>

    <?php if ($error): ?>
      <div class="ev-alert ev-error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="ev-alert ev-ok">✅ Booking submitted successfully. We’ll contact you soon.</div>
    <?php endif; ?>

    <div class="ev-card">
      <form method="post" autocomplete="on" novalidate>
        <div class="ev-grid">
          <div class="ev-field">
            <label>Full Name<span class="req">*</span></label>
            <input class="ev-input" name="full_name" placeholder="Your full name"
                   value="<?= htmlspecialchars($_POST['full_name'] ?? $prefillName) ?>" required>
          </div>

          <div class="ev-field">
            <label>Phone Number<span class="req">*</span></label>
            <input class="ev-input" name="phone" placeholder="01X-XXXXXXX"
                   value="<?= htmlspecialchars($_POST['phone'] ?? $prefillPhone) ?>" required>
          </div>

          <div class="ev-field">
            <label>Email<span class="req">*</span></label>
            <input class="ev-input" type="email" name="email" placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? $prefillEmail) ?>" required>
          </div>

          <div class="ev-field">
            <label>Type of Event<span class="req">*</span></label>
            <select class="ev-input" name="event_type" required>
              <?php
                $curType = $_POST['event_type'] ?? '';
                $types = ['Wedding','Birthday','Corporate','Graduation','Family Day','Other'];
              ?>
              <option value="">-- Select Event Type --</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= ($curType===$t?'selected':'') ?>>
                  <?= htmlspecialchars($t) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ev-field">
            <label>Event Date<span class="req">*</span></label>
            <input class="ev-input" type="date" name="event_date"
                   value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required>
          </div>

          <div class="ev-field">
            <label>Location<span class="req">*</span></label>
            <input class="ev-input" name="location" placeholder="e.g. Mid Valley / Setapak / etc"
                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
          </div>

          <div class="ev-field">
            <label>Budget (MYR)</label>
            <input class="ev-input" name="budget" inputmode="decimal" placeholder="e.g. 1500"
                   value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>">
          </div>

          <div class="ev-field">
            <label>Estimated Pax</label>
            <input class="ev-input" name="pax" inputmode="numeric" placeholder="e.g. 80"
                   value="<?= htmlspecialchars($_POST['pax'] ?? '') ?>">
          </div>
        </div>

        <div class="ev-actions">
          <a class="btn-apple btn-ghost" href="index.php" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">
            Back to Home
          </a>
          <button class="btn-apple btn-primary" type="submit">Submit Booking</button>
        </div>
      </form>
    </div>

  </div>
</div>

<?php
$conn->close();
include("footer.php");
?>
