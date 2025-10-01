<?php
// api/integrations/meta/meta_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../../integrations/config.php';
require_once '../../integrations/encryption.php';
require_once '../../integrations/token_manager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// Helper function for Meta Graph API requests
function makeMetaApiRequest($endpoint, $access_token, $method = 'GET', $params = []) {
    $base_url = 'https://graph.facebook.com/v18.0';
    $api_url = $base_url . $endpoint;
    
    $ch = curl_init();
    
    if ($method === 'GET') {
        $params['access_token'] = $access_token;
        $api_url .= '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $api_url);
    } else {
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $params['access_token'] = $access_token;
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
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
    // Get a valid access token using the token manager
    $access_token = get_valid_access_token($pdo, $_SESSION['user_id'], 'meta');
    
    if ($action === 'dashboard_data') {
        // Get user profile information
        $user_info = makeMetaApiRequest('/me', $access_token, 'GET', ['fields' => 'id,name,email,picture']);
        if (isset($user_info['error'])) throw new Exception('Failed to fetch user profile.', 400);
        
        // Get user's pages
        $pages = makeMetaApiRequest('/me/accounts', $access_token, 'GET', ['fields' => 'id,name,access_token']);
        if (isset($pages['error'])) throw new Exception('Failed to fetch pages.', 400);
        
        // Get recent posts from user's feed
        $posts = makeMetaApiRequest('/me/feed', $access_token, 'GET', ['limit' => 5, 'fields' => 'id,message,created_time,permalink_url']);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'name' => $user_info['name'],
                'email' => $user_info['email'] ?? 'N/A',
                'id' => $user_info['id'],
                'picture' => $user_info['picture']['data']['url'] ?? null,
            ],
            'pages' => $pages['data'] ?? [],
            'recent_posts' => $posts['data'] ?? []
        ]);
    }
    elseif ($action === 'get_posts') {
        $limit = $_GET['limit'] ?? 10;
        $posts = makeMetaApiRequest('/me/feed', $access_token, 'GET', [
            'limit' => $limit,
            'fields' => 'id,message,story,created_time,permalink_url,likes.summary(true),comments.summary(true)'
        ]);
        
        if (isset($posts['error'])) throw new Exception('Failed to fetch posts.', 400);
        
        echo json_encode(['success' => true, 'data' => $posts]);
    }
    elseif ($action === 'create_post') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid request method", 405);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';
        
        if (empty($message)) throw new Exception("Message is required for posting.", 400);
        
        $result = makeMetaApiRequest('/me/feed', $access_token, 'POST', ['message' => $message]);
        
        if (isset($result['error'])) throw new Exception('Failed to create post.', 400);
        
        echo json_encode(['success' => true, 'data' => $result]);
    }
    elseif ($action === 'get_page_posts') {
        $page_id = $_GET['page_id'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        
        if (empty($page_id)) throw new Exception("Page ID is required.", 400);
        
        $posts = makeMetaApiRequest("/{$page_id}/feed", $access_token, 'GET', [
            'limit' => $limit,
            'fields' => 'id,message,story,created_time,permalink_url,likes.summary(true),comments.summary(true)'
        ]);
        
        if (isset($posts['error'])) throw new Exception('Failed to fetch page posts.', 400);
        
        echo json_encode(['success' => true, 'data' => $posts]);
    }
    elseif ($action === 'get_pages') {
        $pages = makeMetaApiRequest('/me/accounts', $access_token, 'GET', [
            'fields' => 'id,name,access_token,picture,fan_count'
        ]);
        
        if (isset($pages['error'])) throw new Exception('Failed to fetch pages.', 400);
        
        echo json_encode(['success' => true, 'data' => $pages]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
