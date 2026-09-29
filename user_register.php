<?php
session_start();
require_once 'admin/db_connect.php'; // adjust if different

$errors = [];
$old    = ['username'=>'','full_name'=>'','email'=>'','phone'=>''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Trim inputs
    $username   = trim($_POST['username'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // Keep for sticky form
    $old = compact('username','full_name','email','phone');

    // ---------- Validation ----------
    // Username: 3-20 chars, letters, numbers, underscore only
    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3–20 characters (letters, numbers, underscore only).";
    }

    // Full name: basic length check
    if ($full_name === '' || mb_strlen($full_name) < 2) {
        $errors[] = "Please enter a valid full name.";
    }

    // Email: RFC-basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Phone: normalize to digits, allow +60 or 0-leading mobiles/landlines
    // Store a normalized version with only digits and optional leading +.
    $normalizedPhone = preg_replace('/\D+/', '', $phone);
    if ($normalizedPhone === '') {
        $errors[] = "Please enter a valid phone number.";
    } else {
        // Basic Malaysia-friendly check: 9–12 digits after normalization
        // (e.g., 01XXXXXXXX, 03XXXXXXXX, or 601XXXXXXXX)
        if (strlen($normalizedPhone) < 9 || strlen($normalizedPhone) > 12) {
            $errors[] = "Phone number looks invalid. Use local (01/03...) or +60 format.";
        }
    }

    // Password strength: min 8, must include upper, lower, digit, special
    $passOk = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,72}$/', $password);
    if (!$passOk) {
        $errors[] = "Password must be 8+ chars and include uppercase, lowercase, number, and special character.";
    }

    // Confirm password
    if ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    // ---------- Uniqueness checks ----------
    if (!$errors) {
        // Username or email or phone already taken?
        $stmt = $pdo->prepare("SELECT username,email,phone FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $email, $normalizedPhone]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if (strcasecmp($existing['username'], $username) === 0) {
                $errors[] = "Username already exists.";
            }
            if (strcasecmp($existing['email'], $email) === 0) {
                $errors[] = "Email already exists.";
            }
            if ($existing['phone'] === $normalizedPhone) {
                $errors[] = "Phone number already exists.";
            }
        }
    }

    // ---------- Create account ----------
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $full_name, $email, $normalizedPhone]);

        // Auto login
        $_SESSION['user_id']  = $pdo->lastInsertId();
        $_SESSION['username'] = $username;

        header("Location: merchandise.php"); // or wherever you want
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>User Register</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .error { color:#b00020; margin:.5rem 0; }
    .ok { color:#0a7f2e; }
    form { max-width: 480px; margin: 2rem auto; padding: 1.5rem; background:#fff; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,.06); }
    label { display:block; font-weight:600; margin-top:12px; }
    input { width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-top:6px; }
    button { margin-top:16px; padding:10px 16px; border:0; border-radius:8px; background:#111; color:#fff; cursor:pointer; }
    .hint { font-size:.85rem; color:#666; }
  </style>
</head>
<body>
  <form method="post" novalidate>
    <h2>Create your account</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <label>Username</label>
    <input name="username" required value="<?= htmlspecialchars($old['username']) ?>" placeholder="e.g. afiq_23">

    <label>Full Name</label>
    <input name="full_name" required value="<?= htmlspecialchars($old['full_name']) ?>" placeholder="e.g. Muhammad Nur Aiman">

    <label>Email</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($old['email']) ?>" placeholder="you@example.com">

    <label>Phone</label>
    <input name="phone" required value="<?= htmlspecialchars($old['phone']) ?>" placeholder="+60 1X-XXXX XXXX or 01X-XXXX XXXX">

    <label>Password</label>
    <input type="password" name="password" id="password" required placeholder="Strong password">
    <div class="hint">Must include uppercase, lowercase, number, and special character; min 8 chars.</div>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter password">
    <div id="matchNote" class="hint"></div>

    <button type="submit">Create Account</button>
  </form>

  <!-- Lightweight client-side feedback -->
  <script>
    const pwd = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    const note = document.getElementById('matchNote');

    function checkMatch() {
      if (!pwd.value || !cpw.value) { note.textContent = ''; return; }
      if (pwd.value === cpw.value) { note.textContent = 'Passwords match.'; note.className='hint ok'; }
      else { note.textContent = 'Passwords do not match.'; note.className='hint error'; }
    }
    pwd.addEventListener('input', checkMatch);
    cpw.addEventListener('input', checkMatch);
  </script>
</body>
</html>
