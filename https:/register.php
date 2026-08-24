<?php
require_once 'config.php';

if (isLoggedIn()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, auth_method) VALUES (?, ?, ?, 'custom')");
            $stmt->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <div class="header">
            <h1>Register</h1>
            <p>Create your account to get started</p>
        </div>
        <?php if ($error): ?>
            <div style="background: rgba(231,76,60,0.2); border: 1px solid #e74c3c; border-radius: 8px; padding: 12px; color: #e74c3c; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="card">
            <form method="POST">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
                <br><br>
                <button type="submit" class="btn btn-primary" style="width:100%">Create Account</button>
            </form>
            <p style="text-align:center; margin-top:15px; color:#aaa;">
                Already have an account? <a href="login.php" style="color:#f5576c;">Login</a>
            </p>
            <p style="text-align:center; margin-top:10px; color:#aaa;">
                <a href="discord_login.php" class="btn btn-discord" style="width:100%; text-align:center;">Login with Discord</a>
            </p>
        </div>
    </div>
</body>
</html>
