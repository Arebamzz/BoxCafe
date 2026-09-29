<?php
// admin/users.php
session_start();
require_once 'db_connect.php';
require_once 'headerAdmin.php'; // keep your admin guard inside here if you have one

// --- simple CSRF token (since this is admin) ---
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

$msg = '';
$err = '';

// ---------------------- Helpers ----------------------
function normalize_phone($raw) {
  // Keep digits only; allow leading +60 if present in raw
  $digits = preg_replace('/\D+/', '', $raw);
  if ($digits === '') return '';
  // If starts with 60 and length >= 10, make +60..., else leave as digits
  if (strpos($raw, '+') === 0 || strpos($digits, '60') === 0) {
    return '+'.$digits; // E.164-ish
  }
  return $digits; // local digits (01..., 03...)
}

function uniq_violation($pdo, $user_id, $username, $email, $phone) {
  // Check uniqueness against other users
  $sql = "SELECT user_id, username, email, phone
          FROM users
          WHERE (username = :u OR email = :e OR phone = :p) AND user_id <> :id
          LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([':u'=>$username, ':e'=>$email, ':p'=>$phone, ':id'=>$user_id]);
  return $st->fetch(PDO::FETCH_ASSOC);
}

// ---------------------- DELETE ----------------------
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if (!isset($_GET['csrf']) || $_GET['csrf'] !== $CSRF) {
    $err = 'Invalid CSRF token.';
  } else {
    // Optional: also delete their orders/event bookings if you intend cascade
    // Make sure you have foreign keys or handle manually before deleting user.
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) $msg = "User #$id deleted.";
    else $err = "User #$id not found or could not be deleted.";
  }
}

// ---------------------- UPDATE (POST) ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
  if (!isset($_POST['csrf']) || $_POST['csrf'] !== $CSRF) {
    $err = 'Invalid CSRF token.';
  } else {
    $user_id   = (int)($_POST['user_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone_raw = trim($_POST['phone'] ?? '');

    // Basic validation
    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
      $err = "Username must be 3–20 chars (letters, numbers, underscore).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = "Invalid email.";
    } else {
      $phone = normalize_phone($phone_raw);

      // Check uniqueness
      $dupe = uniq_violation($pdo, $user_id, $username, $email, $phone);
      if ($dupe) {
        if (strcasecmp($dupe['username'], $username) === 0)       $err = "Username already used by another account.";
        elseif (strcasecmp($dupe['email'], $email) === 0)         $err = "Email already used by another account.";
        elseif ($phone && $dupe['phone'] === $phone)              $err = "Phone already used by another account.";
      }

      if (!$err) {
        $stmt = $pdo->prepare("
          UPDATE users
          SET full_name = :n, username = :u, email = :e, phone = :p
          WHERE user_id = :id
        ");
        $stmt->execute([
          ':n'=>$full_name, ':u'=>$username, ':e'=>$email, ':p'=>$phone, ':id'=>$user_id
        ]);
        $msg = "User #$user_id updated.";
      }
    }
  }
}

// ---------------------- FETCH: edit target (if any) ----------------------
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare("SELECT user_id, username, full_name, email, phone, created_at FROM users WHERE user_id = ?");
  $st->execute([$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
  if (!$editRow) $err = "User #$editId not found.";
}

// ---------------------- SEARCH + LIST ----------------------
$q = trim($_GET['q'] ?? '');
$params = [];
$where  = '';

if ($q !== '') {
  // IMPORTANT FIX: don't reuse same named placeholder multiple times (HY093)
  $where = "WHERE (u.username LIKE :q1 OR u.full_name LIKE :q2 OR u.email LIKE :q3 OR u.phone LIKE :q4)";
  $like  = "%$q%";
  $params = [
    ':q1' => $like,
    ':q2' => $like,
    ':q3' => $like,
    ':q4' => $like,
  ];
}

$sql = "
  SELECT u.user_id, u.username, u.full_name, u.email, u.phone, u.created_at
  FROM users u
  $where
  ORDER BY u.created_at DESC
  LIMIT 500
";

$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total users for dashboard link (optional quick stat here)
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>

<div style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin:8px 0 16px">
  <h2 style="margin:0">Users</h2>
  <div style="opacity:.7">Total: <?= number_format($totalUsers) ?></div>
</div>

<?php if ($msg): ?>
  <div style="background:#e6ffed;color:#05521b;border:1px solid #9ae6b4;padding:10px;border-radius:8px;margin:8px 0"><?=$msg?></div>
<?php endif; ?>
<?php if ($err): ?>
  <div style="background:#ffeaea;color:#7a0b0b;border:1px solid #feb2b2;padding:10px;border-radius:8px;margin:8px 0"><?=$err?></div>
<?php endif; ?>

<!-- Search -->
<form method="get" style="margin:12px 0 16px;display:flex;gap:8px;align-items:center">
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search username / name / email / phone"
         style="flex:1;padding:10px;border:1px solid #ddd;border-radius:8px">
  <button class="btn" style="padding:10px 16px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer">
    Search
  </button>
</form>

<!-- Edit form (appears when ?edit=ID) -->
<?php if ($editRow): ?>
  <form method="post" style="margin:16px 0;padding:16px;background:#fff;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.06);">
    <h3 style="margin-top:0">Edit User #<?= (int)$editRow['user_id'] ?></h3>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
    <input type="hidden" name="user_id" value="<?= (int)$editRow['user_id'] ?>">

    <label style="display:block;margin-top:10px;font-weight:600">Full Name</label>
    <input name="full_name" value="<?= htmlspecialchars($editRow['full_name'] ?? '') ?>"
           style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">

    <label style="display:block;margin-top:10px;font-weight:600">Username</label>
    <input name="username" value="<?= htmlspecialchars($editRow['username']) ?>"
           required pattern="[A-Za-z0-9_]{3,20}"
           style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">

    <label style="display:block;margin-top:10px;font-weight:600">Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($editRow['email']) ?>"
           required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">

    <label style="display:block;margin-top:10px;font-weight:600">Phone</label>
    <input name="phone" value="<?= htmlspecialchars($editRow['phone']) ?>" placeholder="+60..., 01..., 03..."
           style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">

    <div style="margin-top:12px;display:flex;gap:8px">
      <button name="save_user" value="1" class="btn"
              style="padding:10px 16px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer">
        Save
      </button>
      <a href="users.php" style="padding:10px 16px;border:1px solid #ddd;border-radius:8px;text-decoration:none">
        Cancel
      </a>
    </div>
  </form>
<?php endif; ?>

<!-- List -->
<table style="width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden">
  <thead>
    <tr style="background:#e5e5e7">
      <th style="padding:10px;text-align:left">User #</th>
      <th style="padding:10px;text-align:left">Name</th>
      <th style="padding:10px;text-align:left">Username</th>
      <th style="padding:10px;text-align:left">Email</th>
      <th style="padding:10px;text-align:left">Phone</th>
      <th style="padding:10px;text-align:left">Joined</th>
      <th style="padding:10px;text-align:left">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$users): ?>
      <tr><td colspan="7" style="padding:14px">No users found.</td></tr>
    <?php else: ?>
      <?php foreach ($users as $u): ?>
        <tr style="border-bottom:1px solid #ddd">
          <td style="padding:10px;"><?= (int)$u['user_id'] ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($u['full_name'] ?: '-') ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($u['username']) ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($u['email']) ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($u['created_at']) ?></td>
          <td style="padding:10px;">
            <a class="btn" href="users.php?edit=<?= (int)$u['user_id'] ?>"
               style="display:inline-block;padding:6px 10px;border-radius:6px;background:#0d6efd;color:#fff;text-decoration:none">Edit</a>

            <a class="btn" href="users.php?delete=<?= (int)$u['user_id'] ?>&csrf=<?= urlencode($CSRF) ?>"
               onclick="return confirm('Delete user #<?= (int)$u['user_id'] ?>? This cannot be undone.');"
               style="display:inline-block;padding:6px 10px;border-radius:6px;background:#dc3545;color:#fff;text-decoration:none">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php require_once 'footerAdmin.php'; ?>
