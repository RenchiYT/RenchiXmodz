<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'proxy_panel');
define('DB_USER', 'root');
define('DB_PASS', 'your_database_password');

define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'MyProxy');

// Discord OAuth - get these from https://discord.com/developers/applications
define('DISCORD_CLIENT_ID', 'your_discord_client_id');
define('DISCORD_CLIENT_SECRET', 'your_discord_client_secret');
define('DISCORD_REDIRECT_URI', SITE_URL . '/discord_callback.php');

// Session expiry in hours
define('SESSION_EXPIRY_HOURS', 24);

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function getClientIP() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>
