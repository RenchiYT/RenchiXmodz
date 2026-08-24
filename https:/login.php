<?php
require_once 'config.php';
if (isLoggedIn()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <div class="header">
            <h1>Login</h1>
            <p>Welcome back</p>
        </div>
        <?php if ($error): ?>
            <div style="background: rgba(231,76,60,0.2); border: 1px solid #e74c3c; border-radius: 8px; padding: 12px; color: #e74c3c; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="card">
            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <br><br>
                <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
            </form>
            <p style="text-align:center; margin-top:15px; color:#aaa;">
                No account? <a href="register.php" style="color:#f5576c;">Register</a>
            </p>
            <p style="text-align:center; margin-top:10px;">
                <a href="discord_login.php" class="btn btn-discord" style="width:100%; text-align:center;">Login with Discord</a>
            </p>
        </div>
    </div>
</body>
</html>
