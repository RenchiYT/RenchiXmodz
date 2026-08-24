<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');

$error = '';
$success = '';
$active_session = null;

// Handle UID binding and session creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['freefire_uid'])) {
    $uid = trim($_POST['freefire_uid']);
    $server_id = intval($_POST['server_id'] ?? 0);

    if (empty($uid)) {
        $error = 'Please enter your Free Fire UID';
    } elseif (!is_numeric($uid) || strlen($uid) < 8) {
        $error = 'Invalid Free Fire UID';
    } elseif ($server_id <= 0) {
        $error = 'Please select a proxy server';
    } else {
        // Check server capacity
        $stmt = $pdo->prepare("SELECT current_users, max_users FROM proxy_servers WHERE id = ? AND status = 'online'");
        $stmt->execute([$server_id]);
        $server = $stmt->fetch();

        if (!$server) {
            $error = 'Server not available';
        } elseif ($server['current_users'] >= $server['max_users']) {
            $error = 'Server is full';
        } else {
            // Create session
            $token = generateToken(32);
            $expires = date('Y-m-d H:i:s', strtotime('+' . SESSION_EXPIRY_HOURS . ' hours'));

            $stmt = $pdo->prepare("INSERT INTO proxy_sessions (user_id, freefire_uid, proxy_token, assigned_server_id, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $uid, $token, $server_id, $expires]);

            // Update server count
            $stmt = $pdo->prepare("UPDATE proxy_servers SET current_users = current_users + 1 WHERE id = ?");
            $stmt->execute([$server_id]);

            $success = 'Session created successfully! Your proxy token is below.';
            $_SESSION['last_token'] = $token;
            $_SESSION['last_uid'] = $uid;
        }
    }
}

// Revoke session
if (isset($_GET['revoke'])) {
    $stmt = $pdo->prepare("UPDATE proxy_sessions SET status = 'revoked' WHERE id = ? AND user_id = ?");
    $stmt->execute([intval($_GET['revoke']), $_SESSION['user_id']]);
    $success = 'Session revoked';
}

// Get user's sessions
$stmt = $pdo->prepare("SELECT ps.*, ps2.name as server_name, ps2.ip, ps2.port, ps2.location, ps2.country_code 
    FROM proxy_sessions ps 
    LEFT JOIN proxy_servers ps2 ON ps.assigned_server_id = ps2.id 
    WHERE ps.user_id = ? 
    ORDER BY ps.created_at DESC 
    LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$sessions = $stmt->fetchAll();

// Get active session for display
$stmt = $pdo->prepare("SELECT * FROM proxy_sessions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$active_session = $stmt->fetch();

// Get available servers
$servers = $pdo->query("SELECT * FROM proxy_servers WHERE status = 'online' ORDER BY location")->fetchAll();

// Get country flag emoji (simple mapping)
function getFlag($code) {
    $flags = ['US'=>'🇺🇸','EU'=>'🇪🇺','SG'=>'🇸🇬','IN'=>'🇮🇳','BR'=>'🇧🇷','JP'=>'🇯🇵','KR'=>'🇰🇷','HK'=>'🇭🇰','GB'=>'🇬🇧','DE'=>'🇩🇪','FR'=>'🇫🇷'];
    return $flags[strtoupper($code)] ?? '🌍';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            min-height: 100vh;
        }
        .container { max-width: 1000px; margin:0 auto; padding:30px 20px; }
        .header { display:flex; justify-content:space-between; align-items:center; padding:20px 0; }
        .header h1 { font-size:1.8em; background:linear-gradient(45deg, #f093fb, #f5576c); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .user-info { color:#aaa; }
        .user-info a { color:#f5576c; text-decoration:none; margin-left:15px; }
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            margin: 20px 0;
        }
        .card h2 { margin-bottom:20px; font-size:1.3em; }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary { background:linear-gradient(45deg, #f093fb, #f5576c); color:white; }
        .btn-primary:hover { opacity:0.9; }
        .btn-danger { background:#e74c3c; color:white; }
        .btn-danger:hover { background:#c0392b; }
        .btn-success { background:#2ecc71; color:white; }
        .btn-success:hover { background:#27ae60; }

        input[type="text"], select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 1em;
            margin: 8px 0;
        }
        select option { background:#302b63; color:white; }
        input:focus, select:focus { outline:none; border-color:#f5576c; }
        label { display:block; margin-top:12px; color:#ccc; font-size:0.9em; }

        .server-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .server-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
            transition: all 0.3s;
        }
        .server-card:hover { border-color:#f5576c; background:rgba(255,255,255,0.1); }
        .server-card.selected { border-color:#f5576c; background:rgba(245,87,108,0.1); }
        .server-card .flag { font-size:2.5em; margin-bottom:8px; }
        .server-card .name { font-weight:bold; }
        .server-card .load { font-size:0.85em; color:#aaa; margin-top:5px; }
        .server-card .status-dot {
            display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px;
        }
        .status-dot.online { background:#2ecc71; }
        .status-dot.full { background:#e67e22; }

        .token-box {
            background: rgba(0,0,0,0.3);
            border: 2px dashed #f5576c;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 1.1em;
        }
        .alert-error {
            background: rgba(231,76,60,0.2);
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 12px;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .alert-success {
            background: rgba(46,204,113,0.2);
            border: 1px solid #2ecc71;
            border-radius: 8px;
            padding: 12px;
            color: #2ecc71;
            margin-bottom: 15px;
        }

        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { padding:12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        th { color:#aaa; font-size:0.85em; text-transform:uppercase; }
        .badge {
            display:inline-block;
            padding:4px 10px;
            border-radius:20px;
            font-size:0.8em;
        }
        .badge-active { background:rgba(46,204,113,0.2); color:#2ecc71; }
        .badge-expired { background:rgba(149,165,166,0.2); color:#95a5a6; }
        .badge-revoked { background:rgba(231,76,60,0.2); color:#e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= SITE_NAME ?></h1>
            <div class="user-info">
                👋 <?= htmlspecialchars($_SESSION['username']) ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Active Session Card -->
        <?php if ($active_session): ?>
        <div class="card">
            <h2>✅ Active Session</h2>
            <div class="token-box">
                Proxy Token: <?= htmlspecialchars($active_session['proxy_token']) ?>
            </div>
            <table>
                <tr><td><strong>Free Fire UID</strong></td><td><?= htmlspecialchars($active_session['freefire_uid']) ?></td></tr>
                <tr><td><strong>Expires</strong></td><td><?= htmlspecialchars($active_session['expires_at']) ?></td></tr>
                <tr><td><strong>Status</strong></td><td><span class="badge badge-active">Active</span></td></tr>
            </table>
            <br>
            <a href="?revoke=<?= $active_session['id'] ?>" class="btn btn-danger" onclick="return confirm('Revoke this session?')">Revoke Session</a>
        </div>
        <?php endif; ?>

        <!-- Create Session Card -->
        <div class="card">
            <h2>🔗 Create New Proxy Session</h2>
            <form method="POST">
                <label>Free Fire UID (Account ID)</label>
                <input type="text" name="freefire_uid" placeholder="Enter your FF UID" value="<?= htmlspecialchars($_SESSION['last_uid'] ?? '') ?>" required>

                <label>Select Proxy Server</label>
                <div class="server-grid" id="serverGrid">
                    <?php foreach ($servers as $srv): 
                        $full = $srv['current_users'] >= $srv['max_users'];
                    ?>
                    <div class="server-card <?= $full ? '' : '' ?>" data-id="<?= $srv['id'] ?>" onclick="selectServer(this)">
                        <div class="flag"><?= getFlag($srv['country_code']) ?></div>
                        <div class="name"><?= htmlspecialchars($srv['name']) ?></div>
                        <div class="load">
                            <span class="status-dot <?= $full ? 'full' : 'online' ?>"></span>
                            <?= $srv['current_users'] ?>/<?= $srv['max_users'] ?> users
                        </div>
                        <small style="color:#777;"><?= htmlspecialchars($srv['location']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="server_id" id="selectedServer" value="">

                <br>
                <button type="submit" class="btn btn-primary" style="width:100%">Generate Proxy Session</button>
            </form>
        </div>

        <!-- Session History -->
        <div class="card">
            <h2>📜 Session History</h2>
            <?php if (count($sessions) > 0): ?>
            <table>
                <tr>
                    <th>UID</th>
                    <th>Server
