<?php
// api/integrations/remove_integration.php
session_start();
require_once '../auth/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$provider = $data['provider'] ?? '';

if (empty($provider)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provider not specified.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM user_integrations WHERE user_id = ? AND provider = ?");
    $stmt->execute([$_SESSION['user_id'], $provider]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Integration removed.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Integration not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
