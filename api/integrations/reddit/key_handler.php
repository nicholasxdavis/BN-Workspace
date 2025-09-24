<?php
// api/integrations/reddit/key_handler.php
session_start();
require_once '../../auth/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'OPEN_ROUTER' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result && !empty($result['setting_value'])) {
        echo json_encode(['success' => true, 'apiKey' => $result['setting_value']]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OPEN_ROUTER API key not found in database settings.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error while fetching API key: ' . $e->getMessage()]);
    exit;
}
?>
