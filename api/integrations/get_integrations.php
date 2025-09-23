<?php
// api/integrations/get_integrations.php
session_start();
require_once '../auth/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT provider FROM user_integrations WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'integrations' => $integrations]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
