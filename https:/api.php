<?php
// api.php - API endpoint for proxy APK to verify tokens
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'verify':
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Token required']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT ps.*, ps2.ip, ps2.port, ps2.location 
            FROM proxy_sessions ps 
            LEFT JOIN proxy_servers ps2 ON ps.assigned_server_id = ps2.id 
            WHERE ps.proxy_token = ? AND ps.status = 'active' AND ps.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if ($session) {
            echo json_encode([
                'status' => 'success',
                'uid' => $session['freefire_uid'],
                'server_ip' => $session['ip'],
                'server_port' => $session['port'],
                'location' => $session['location'],
                'expires_at' => $session['expires_at']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
