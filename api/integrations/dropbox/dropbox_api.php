<?php
// api/integrations/dropbox/dropbox_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../../integrations/config.php';
require_once '../../integrations/encryption.php';
require_once '../../integrations/token_manager.php'; // Added token manager

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// Advanced Helper function for Dropbox API
function makeDropboxApiRequest($endpoint, $access_token, $body = null, $is_content_upload = false, $is_content_download = false) {
    $base_url = $is_content_upload || $is_content_download ? 'https://content.dropboxapi.com/2' : 'https://api.dropboxapi.com/2';
    $api_url = $base_url . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $headers = ['Authorization: Bearer ' . $access_token];
    
    if ($is_content_upload) {
        $api_args = json_encode($body);
        $headers[] = 'Dropbox-API-Arg: ' . $api_args;
        $headers[] = 'Content-Type: application/octet-stream';
        // The actual file content will be set later from php://input
    } else {
        $headers[] = 'Content-Type: application/json';
        $post_data = $body ? json_encode($body) : 'null';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
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
    // --- Get a valid access token using the token manager ---
    $access_token = get_valid_access_token($pdo, $_SESSION['user_id'], 'dropbox');

    if ($action === 'dashboard_data') {
        $account_info = makeDropboxApiRequest('/users/get_current_account', $access_token);
        if (isset($account_info['error'])) throw new Exception('Failed to fetch user account.', $account_info['code']);
        
        $space_usage = makeDropboxApiRequest('/users/get_space_usage', $access_token);
        if (isset($space_usage['error'])) throw new Exception('Failed to fetch space usage.', $space_usage['code']);

        // Fetch recent files - this is an example using search.
        $recent_files_body = [
            'query' => '*', // Query for everything
            'options' => [
                'max_results' => 5,
                'order_by' => 'last_modified_time',
            ],
        ];
        // Note: Search can be slow to index. A more robust solution might involve tracking recent activity.
        // For this dashboard, we'll list the root folder as "recent".
        $recent_files = makeDropboxApiRequest('/files/list_folder', $access_token, ['path' => '']);


        echo json_encode([
            'success' => true, 
            'stats' => [
                'name' => $account_info['name']['display_name'],
                'email' => $account_info['email'],
                'used' => $space_usage['used'],
                'allocated' => $space_usage['allocation']['allocated'],
            ],
            'recent_files' => $recent_files['entries'] ?? []
        ]);
    }
    elseif ($action === 'list_files') {
        $path = $_GET['path'] ?? '';
        $files = makeDropboxApiRequest('/files/list_folder', $access_token, ['path' => $path]);
        if (isset($files['error'])) throw new Exception('Failed to list files.', $files['code']);
        echo json_encode(['success' => true, 'data' => $files]);
    }
    elseif ($action === 'upload_file') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid request method", 405);
        
        $path = $_GET['path'] ?? '/';
        $filename = $_GET['filename'] ?? 'uploaded_file';
        $full_path = rtrim($path, '/') . '/' . $filename;

        $upload_args = [
            'path' => $full_path,
            'mode' => 'add',
            'autorename' => true,
            'mute' => false
        ];

        // The makeDropboxApiRequest function is not suitable for direct file upload from php://input this way.
        // We need a direct cURL call here.
        $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Dropbox-API-Arg: ' . json_encode($upload_args),
            'Content-Type: application/octet-stream'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Stream the file directly from the request body to Dropbox
        $request_body = fopen('php://input', 'r');
        curl_setopt($ch, CURLOPT_INFILE, $request_body);
        curl_setopt($ch, CURLOPT_INFILESIZE, (int)$_SERVER['CONTENT_LENGTH']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 400) {
            throw new Exception("Upload failed: " . $response, $http_code);
        }
        
        echo $response;
    }
    elseif ($action === 'get_temporary_link') {
        $path = $_GET['path'] ?? '';
        if (empty($path)) throw new Exception("File path is required.", 400);
        
        $link_data = makeDropboxApiRequest('/files/get_temporary_link', $access_token, ['path' => $path]);
        if (isset($link_data['error'])) throw new Exception('Failed to get temporary link.', $link_data['code']);

        echo json_encode(['success' => true, 'link' => $link_data['link']]);
    }
    elseif ($action === 'delete_item') {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid request method", 405);
         $input = json_decode(file_get_contents('php://input'), true);
         $path = $input['path'] ?? '';
         if (empty($path)) throw new Exception("Path is required for deletion.", 400);

         $result = makeDropboxApiRequest('/files/delete_v2', $access_token, ['path' => $path]);
         if (isset($result['error'])) throw new Exception('Failed to delete item.', $result['code']);

         echo json_encode(['success' => true, 'data' => $result]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
