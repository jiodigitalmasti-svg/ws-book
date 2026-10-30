<?php
require_once __DIR__ . '/db.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $color = $_POST['avatar_color'] ?? '#1877F2';

    // Basic clean validation
    $username = preg_replace('/[^a-z0-9_]/', '', $username);

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            $error = "Username or email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $db->prepare("INSERT INTO users (full_name, username, email, password, avatar_color, bio) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$fullName, $username, $email, $hash, $color, "Hello! I just joined Facebook Clone."]);

            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;

            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up for Facebook</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-header">
      <div class="auth-logo">facebook</div>
      <div class="auth-sub">It's quick and easy.</div>
    </div>

    <div class="auth-card">
      <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php">
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="username">Username (letters, numbers, _)</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="e.g. johndoe" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
        </div>

        <div class="form-group">
          <label for="avatar_color">Avatar Color</label>
          <select name="avatar_color" id="avatar_color" class="form-control" style="background:#fff;">
            <option value="#1877F2">Facebook Blue</option>
            <option value="#e91e63">Pink Rose</option>
            <option value="#00a884">Teal Green</option>
            <option value="#8e24aa">Deep Purple</option>
            <option value="#f57c00">Warm Amber</option>
            <option value="#2b2d42">Midnight Slate</option>
          </select>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; font-size: 16px; margin-top: 8px; background: #00a884;">Sign Up</button>
      </form>

      <div class="auth-divider"></div>

      <div style="text-align: center;">
        <a href="login.php" style="color: var(--fb-blue); text-decoration: none; font-weight: 600; font-size: 15px;">Already have an account? Log In</a>
      </div>
    </div>
  </div>
</body>
</html>
