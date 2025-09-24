<?php
// api/integrations/reddit/key_handler.php
session_start();
header('Content-Type: application/json');

// Check if the user is authenticated in the workspace.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// Fetch the API key from the server's environment variables.
$apiKey = getenv('OPEN_ROUTER');

if ($apiKey) {
    // If the key is found, send it to the dashboard.
    echo json_encode(['success' => true, 'apiKey' => $apiKey]);
} else {
    // If the environment variable is not set or empty, return an error.
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'OPEN_ROUTER environment variable not found on the server.']);
}
?>

