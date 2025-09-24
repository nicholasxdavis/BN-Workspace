<?php
// api/integrations/dropbox/dropbox_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../../integrations/config.php';
require_once '../../integrations/encryption.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// Helper function to make requests to Dropbox API
function makeDropboxApiRequest($endpoint, $access_token, $body = null) {
    $api_url = 'https://api.dropboxapi.com/2' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ];
    
    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } else {
        // Some endpoints require an empty JSON object if no other body is present
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        return ['error' => true, 'code' => $http_code, 'message' => json_decode($response, true)];
    }

    return json_decode($response, true);
}


// Main Logic
$action = $_GET['action'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'dropbox'");
    $stmt->execute([$_SESSION['user_id']]);
    $integration = $stmt->fetch();

    if (!$integration) {
        throw new Exception("Dropbox integration not found for this user.", 404);
    }
    $access_token = decrypt_token($integration['access_token']);

    if ($action === 'get_account_info') {
        $info = makeDropboxApiRequest('/users/get_current_account', $access_token);
        if (isset($info['error'])) throw new Exception('Failed to fetch user data from Dropbox.', $info['code']);
        echo json_encode(['success' => true, 'data' => $info]);
    } 
    elseif ($action === 'list_files') {
        $path = $_GET['path'] ?? ''; // Dropbox uses empty string for root
        $body = [
            'path' => $path,
            'recursive' => false,
            'include_media_info' => false,
            'include_deleted' => false,
            'include_has_explicit_shared_members' => false
        ];
        $files = makeDropboxApiRequest('/files/list_folder', $access_token, $body);
        if (isset($files['error'])) throw new Exception('Failed to list files.', $files['code']);
        echo json_encode(['success' => true, 'data' => $files]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
