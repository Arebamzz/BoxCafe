<?php
session_start();
require_once 'admin/db_connect.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {

        /* ===========================================================
         * 1) ADMIN LOGIN (try first)
         * ===========================================================
         */
        $adminOk = false;
        $admin   = null;

        try {
            $stmt = $pdo->prepare("
                SELECT admin_id, username, role, password
                FROM admins
                WHERE username = :u
                LIMIT 1
            ");
            $stmt->execute([':u' => $identity]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $admin = null; 
        }

        if ($admin) {
            $stored = $admin['password'];
            // bcrypt / password_hash
            if (password_verify($password, $stored)) {
                $adminOk = true;
            }
            // fallback sha256 (if used previously)
            elseif (hash('sha256', $password) === $stored) {
                $adminOk = true;
            }
        }

        // Optional: fallback hardcoded admin account
        if (!$adminOk && strcasecmp($identity, 'Admin') === 0 && $password === 'Admin123') {
            $adminOk = true;
            $admin = [
                'admin_id' => 0,
                'username' => 'Admin',
                'role'     => 'superadmin',
            ];
        }

        if ($adminOk) {
            $_SESSION['admin_id']   = $admin['admin_id'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            $_SESSION['username']   = $admin['username'];
            header('Location: admin/dashboard.php');
            exit;
        }

        /* ===========================================================
         * 2) USER LOGIN (fallback)
         * ===========================================================
         */
        $maybePhone = preg_replace('/\D+/', '', $identity); // normalize digits

        $stmt = $pdo->prepare("
            SELECT user_id, username, email, phone, password
            FROM users
            WHERE username = :id1
               OR email    = :id2
               OR phone    = :phone
            LIMIT 1
        ");

        $stmt->execute([
            ':id1'   => $identity,
            ':id2'   => $identity,
            ':phone' => $maybePhone,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['user_id'];   // match your DB PK
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');              // user landing page
            exit;
        }

        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background:#f5f5f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen; }
    form { max-width:450px;margin:3rem auto;padding:1.5rem;background:#fff;
           border-radius:12px;box-shadow:0 50px 20px rgba(0,0,0,.06); }
    h2 { margin-top:0;text-align:center; }
    label { display:block;font-weight:600;margin-top:12px; }
    input { width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;margin-top:6px; }
    button { margin-top:16px;padding:10px 16px;border:0;border-radius:8px;
             background:#111;color:#fff;cursor:pointer;width:100%; }
    .error { color:#b00020;margin:.5rem 0;text-align:center; }
    .hint { color:#666;font-size:0.9rem;text-align:center;margin-top:6px; }
  </style>
</head>
<body>
  <form method="post" novalidate>
    <h2>Login</h2>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <label>Username / Email / Phone</label>
    <input name="username" placeholder="Admin or afiq_23 or you@example.com or 01X-XXXX XXXX" required>

    <label>Password</label>
    <input type="password" name="password" placeholder="Your password" required>

    <button type="submit">Sign In</button>
    <div class="nav-left"> <a href="user_register.php"> New to BOX? <span style="color: blue;">Sign Up!!!</span> </a> </div>
  </form>
</body>
</html>