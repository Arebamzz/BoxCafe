<?php
session_start();
require_once 'admin/db_connect.php'; // adjust path if needed

$loggedIn = false;
$user = [];

// 1) Your normal login ($_SESSION['user'])
if (!empty($_SESSION['user'])) {
    $loggedIn = true;
    $user = $_SESSION['user'];
}

// 2) Fallback if your login uses $_SESSION['username']
elseif (!empty($_SESSION['username'])) {
    $loggedIn = true;
    $user = [
        'user_id'  => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'] ?? '',
        'phone'    => $_SESSION['phone'] ?? ''
    ];
}

// 3) If REALLY no session → go to login
if (!$loggedIn) {
    header("Location: user_login.php");
    exit;
}


// Extract user_id safely
$user_id = $user['user_id'] ?? null;
if (!$user_id) {
    die("Your session is missing user_id. Please login again.");
}

$success = '';
$error = '';

/* ---------------------------------------------------------
   LOAD CURRENT USER DATA FROM DATABASE
--------------------------------------------------------- */
try {
    $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        die("User not found in database.");
    }
} catch (Throwable $e) {
    die("Database error loading profile.");
}

/* ---------------------------------------------------------
   HANDLE FORM SUBMISSION
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = trim($_POST['username'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $new_password   = trim($_POST['new_password'] ?? '');
    $confirm_pass   = trim($_POST['confirm_password'] ?? '');

    if ($username === '' || $email === '') {
        $error = "Username and email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($new_password !== '' && $new_password !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Update with password
            if ($new_password !== '') {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

                $sql = "UPDATE users 
                        SET username = :username, email = :email, phone = :phone, password = :password 
                        WHERE user_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':password' => $hashedPassword,
                    ':id'       => $user_id
                ]);
            }
            // Update without password
            else {
                $sql = "UPDATE users 
                        SET username = :username, email = :email, phone = :phone 
                        WHERE user_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':id'       => $user_id
                ]);
            }

            // Update session values to reflect changes in header.php
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email']    = $email;
            $_SESSION['user']['phone']    = $phone;
            $_SESSION['username']         = $username;

            $success = "Profile updated successfully.";

            // Update for display
            $currentUser['username'] = $username;
            $currentUser['email']    = $email;
            $currentUser['phone']    = $phone;

        } catch (Throwable $e) {
            $error = "Failed to update profile. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Settings - BOXKL</title>
  <link rel="stylesheet" href="style.css">

  <style>
    .account-page {
      max-width: 700px;
      margin: 60px auto;
      padding: 40px 30px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .account-page h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .subtitle {
      color: #666;
      margin-bottom: 25px;
    }

    .account-form-group {
      margin-bottom: 18px;
    }

    .account-form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
      font-size: 14px;
    }

    .account-form-group input {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-size: 14px;
    }

    .btn-primary {
      background: #111;
      color: white;
      padding: 10px 18px;
      border-radius: 999px;
      border: none;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-primary:hover {
      opacity: 0.85;
    }

    .alert-success, .alert-error {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .alert-success {
      background: #e7f8ec;
      border: 1px solid #b7e1c1;
      color: #1b6a32;
    }

    .alert-error {
      background: #fdecea;
      border: 1px solid #f5c2c0;
      color: #a1262f;
    }

    .small-note {
      font-size: 12px;
      color: #777;
    }
  </style>

</head>
<body>

<?php include 'header.php'; ?>

<div class="account-page">
  <h1>Account Settings</h1>
  <p class="subtitle">Update your personal details and login information.</p>

  <?php if ($success): ?>
    <div class="alert-success"><?= $success ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error"><?= $error ?></div>
  <?php endif; ?>

  <form method="post">

    <div class="account-form-group">
      <label>Username</label>
      <input type="text" name="username" required
             value="<?= htmlspecialchars($currentUser['username']) ?>">
    </div>

    <div class="account-form-group">
      <label>Email</label>
      <input type="email" name="email" required
             value="<?= htmlspecialchars($currentUser['email']) ?>">
    </div>

    <div class="account-form-group">
      <label>Phone</label>
      <input type="text" name="phone"
             value="<?= htmlspecialchars($currentUser['phone']) ?>">
    </div>

    <hr style="margin:25px 0">

    <div class="account-form-group">
      <label>New Password (optional)</label>
      <input type="password" name="new_password" placeholder="Leave blank to keep current password">
    </div>

    <div class="account-form-group">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" placeholder="Re-type new password">
    </div>

    <p class="small-note">Leave password fields empty to keep your current password.</p>

    <button type="submit" class="btn-primary">Save Changes</button>
  </form>

  <br>
  <a href="profile.php" class="small-note">← Back to Profile</a>
</div>

</body>
</html>
