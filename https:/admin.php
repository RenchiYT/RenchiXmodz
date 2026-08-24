<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');

// Simple admin check - first user or hardcoded admin
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
// For production, add an 'is_admin' column. For now, anyone can view.

// Update server user counts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_server'])) {
    $server_id = intval($_POST['server_id']);
    $name = $_POST['name'];
    $ip = $_POST['ip'];
    $port = intval($_POST['port']);
    $location = $_POST['location'];
    $country_code = $_POST['country_code'];
    $max_users = intval($_POST['max_users']);
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE proxy_servers SET name=?, ip=?, port=?, location=?, country_code=?, max_users=?, status=? WHERE id=?");
    $stmt->execute([$name, $ip, $port, $location, $country_code, $max_users, $status, $server_id]);
    $success = 'Server updated';
}

// Add server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_server'])) {
    $stmt = $pdo->prepare("INSERT INTO proxy_servers (name, ip, port, location, country_code, max_users, status) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['ip'], intval($_POST['port']), $_POST['location'], $_POST['country_code'], intval($_POST['max_users']), 'online']);
    $success = 'Server added';
}

$servers = $pdo->query("SELECT * FROM proxy_servers ORDER BY location")->fetchAll();
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_sessions = $pdo->query("SELECT COUNT(*) FROM proxy_sessions WHERE status='active' AND expires_at > NOW()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= SITE_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#0f0c29; color:#fff; padding:40px; }
        .container { max-width:1200px; margin:0 auto; }
        h1 { color:#f5576c; margin-bottom:20px; }
        .stats { display:flex; gap:20px; margin-bottom:30px; }
        .stat-card { background:rgba(255,255,255,0.08); border-radius:12px; padding:25px; flex:1; text-align:center; }
        .stat-card h3 { color:#aaa; font-size:0.9em; text-transform:uppercase; }
        .stat-card .num { font-size:2em; font-weight:bold; margin-top:10px; }
        table { width:100%; border-collapse:collapse; margin:20px 0; background:rgba(255,255,255,0.05); border-radius:12px; overflow:hidden; }
        th, td { padding:12px 15px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        th { background:rgba(255,255,255,0.1); color:#aaa; text-transform:uppercase; font-size:0.85em; }
        input, select { padding:8px 12px; border:1px solid rgba(255,255,255,0.2); border-radius:6px; background:rgba(0,0,0,0.3); color:white; width:100%; }
        .btn { padding:8px 16px; border:none; border-radius:6px; cursor:pointer; }
        .btn-primary { background:linear-gradient(45deg,#f093fb,#f5576c); color:white; }
        .btn-success { background:#2ecc71; color:white; }
        .card { background:rgba(255,255,255,0.08); border-radius:16px; padding:25px; margin:20px 0; }
        .alert-success { background:rgba(46,204,113,0.2); border:1px solid #2ecc71; border-radius:8px; padding:12px; color:#2ecc71; margin-bottom:15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ Admin Panel</h1>
        <a href="dashboard.php" style="color:#f5576c;">← Back to Dashboard</a>

        <?php if (isset($success)): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card"><h3>Users</h3><div class="num"><?= $user_count ?></div></div>
            <div class="stat-card"><h3>Active Sessions</h3><div class="num"><?= $active_sessions ?></div></div>
            <div class="stat-card"><h3>Servers</h3><div class="num"><?= count($servers) ?></div></div>
        </div>

        <div class="card">
            <h2 style="margin-bottom:15px;">Proxy Servers</h2>
            <table>
                <tr><th>ID</th><th>Name</th><th>IP</th><th>Port</th><th>Location</th><th>Load</th><th>Status</th><th>Action</th></tr>
                <?php foreach ($servers as $s): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="server_id" value="<?= $s['id'] ?>">
                        <td><?= $s['id'] ?></td>
                        <td><input type="text" name="name" value="<?= htmlspecialchars($s['name']) ?>"></td>
                        <td><input type="text" name="ip" value="<?= $s['ip'] ?>"></td>
                        <td><input type="number" name="port" value="<?= $s['port'] ?>" style="width:80px"></td>
                        <td><input type="text" name="location" value="<?= htmlspecialchars($s['location']) ?>"></td>
                        <td><?= $s['current_users'] ?>/<input type="number" name="max_users" value="<?= $s['max_users'] ?>" style="width:60px"></td>
                        <td>
                            <select name="status">
                                <option value="online" <?= $s['status']=='online'?'selected':'' ?>>Online</option>
                                <option value="offline" <?= $s['status']=='offline'?'selected':'' ?>>Offline</option>
                                <option value="maintenance" <?= $s['status']=='maintenance'?'selected':'' ?>>Maintenance</option>
                            </select>
                        </td>
                        <td><button type="submit" name="update_server" class="btn btn-primary">Update</button></td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h2 style="margin-bottom:15px;">Add New Server</h2>
            <form method="POST" style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="ip" placeholder="IP Address" required>
                <input type="number" name="port" placeholder="Port" value="1080" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="country_code" placeholder="Country Code (US, EU, SG)" required>
                <input type="number" name="max_users" placeholder="Max Users" value="100" required>
                <button type="submit" name="add_server" class="btn btn-success" style="grid-column:span 6;">Add Server</button>
            </form>
        </div>
    </div>
</body>
</html>
