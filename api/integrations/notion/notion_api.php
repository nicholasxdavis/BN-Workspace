<?php
// api/integrations/notion/notion_api.php
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

// --- Helper function to make authenticated requests to the Notion API ---
function makeNotionApiRequest($endpoint, $access_token, $method = 'POST', $body = []) {
    $api_url = 'https://api.notion.com/v1' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        return ['error' => true, 'code' => $http_code, 'message' => json_decode($response, true)];
    }

    return json_decode($response, true);
}


// --- Main Logic ---
$action = $_GET['action'] ?? '';

try {
    // --- Fetch the user's encrypted access token from the database ---
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'notion'");
    $stmt->execute([$_SESSION['user_id']]);
    $integration = $stmt->fetch();

    if (!$integration) {
        throw new Exception("Notion integration not found for this user.", 404);
    }

    $access_token = decrypt_token($integration['access_token']);

    // --- Action: Search for pages ---
    if ($action === 'search_pages') {
        $query = $_GET['query'] ?? '';
        
        $search_body = [
            'query' => $query,
            'sort' => [
                'direction' => 'descending',
                'timestamp' => 'last_edited_time'
            ]
        ];
        
        $pages = makeNotionApiRequest('/search', $access_token, 'POST', $search_body);
        if (isset($pages['error'])) throw new Exception('Failed to search Notion pages.', $pages['code']);

        echo json_encode(['success' => true, 'pages' => $pages['results']]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>