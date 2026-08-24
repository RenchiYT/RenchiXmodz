<?php
require_once 'config.php';

if (!isset($_GET['code'])) {
    die('No authorization code received');
}

// Exchange code for access token
$ch = curl_init('https://discord.com/api/oauth2/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => DISCORD_REDIRECT_URI,
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_RETURNTRANSFER => true,
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($response['access_token'])) {
    die('Failed to get access token');
}

// Get user info from Discord
$ch = curl_init('https://discord.com/api/users/@me');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $response['access_token']],
    CURLOPT_RETURNTRANSFER => true,
]);
$user = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($user['id'])) {
    die('Failed to get user info');
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE discord_id = ?");
$stmt->execute([$user['id']]);
$existing = $stmt->fetch();

if ($existing) {
    $_SESSION['user_id'] = $existing['id'];
    $_SESSION['username'] = $existing['username'];
} else {
    $stmt = $pdo->prepare("INSERT INTO users (discord_id, username, email, auth_method) VALUES (?, ?, ?, 'discord')");
    $stmt->execute([
        $user['id'],
        $user['username'] . '#' . $user['discriminator'],
        $user['email'] ?? ''
    ]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $user['username'];
}

redirect('dashboard.php');
