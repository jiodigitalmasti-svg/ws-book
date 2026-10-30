<?php
require_once __DIR__ . '/db.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email/username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facebook - Log In or Sign Up</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-header">
      <div class="auth-logo">facebook</div>
      <div class="auth-sub">Connect with friends and the world around you on Facebook.</div>
    </div>

    <div class="auth-card">
      <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label for="email">Email or Username</label>
          <input type="text" id="email" name="email" class="form-control" placeholder="Email address or username" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? 'alex@example.com') ?>">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Password (default: password123)" required value="password123">
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; font-size: 16px; margin-top: 8px;">Log In</button>
      </form>

      <div style="margin-top: 14px; text-align: center; font-size: 13px; color: var(--fb-subtext);">
        Demo Accounts: <strong>alex@example.com</strong> or <strong>sarah@example.com</strong> (Pass: <code>password123</code>)
      </div>

      <div class="auth-divider"></div>

      <div style="text-align: center;">
        <a href="register.php" class="btn-secondary" style="display: inline-block; text-decoration: none; padding: 10px 20px; font-size: 15px; background: #42b72a; color: #fff;">Create New Account</a>
      </div>
    </div>
  </div>
</body>
</html>
