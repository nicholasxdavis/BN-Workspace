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
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ];
    
    if ($method === 'POST' || $method === 'PATCH') {
         if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
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

// --- Main Logic ---
$action = $_GET['action'] ?? '';

try {
    // Fetch the user's encrypted access token from the database
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'notion'");
    $stmt->execute([$_SESSION['user_id']]);
    $integration = $stmt->fetch();

    if (!$integration) {
        throw new Exception("Notion integration not found for this user.", 404);
    }

    $access_token = decrypt_token($integration['access_token']);

    // --- Action: Fetch all data for the main dashboard ---
    if ($action === 'dashboard_data') {
        // ... (existing dashboard_data logic remains the same)
        // 1. Get User Info
        $user_info = makeNotionApiRequest('/users/me', $access_token, 'GET');
        if (isset($user_info['error'])) throw new Exception('Failed to fetch user data from Notion.', $user_info['code']);

        // 2. Search for all accessible pages and databases to get stats
        $search_body = ['page_size' => 100];
        $search_results = makeNotionApiRequest('/search', $access_token, 'POST', $search_body);
        if (isset($search_results['error'])) throw new Exception('Failed to search Notion content.', $search_results['code']);
        
        $page_count = 0;
        $db_count = 0;
        foreach($search_results['results'] as $item) {
            if ($item['object'] === 'page') $page_count++;
            if ($item['object'] === 'database') $db_count++;
        }

        // 3. Get 5 recently edited pages
        $recent_pages_body = [
            'sort' => ['direction' => 'descending', 'timestamp' => 'last_edited_time'],
            'filter' => ['property' => 'object', 'value' => 'page'],
            'page_size' => 5
        ];
        $recent_pages_results = makeNotionApiRequest('/search', $access_token, 'POST', $recent_pages_body);
        if (isset($recent_pages_results['error'])) throw new Exception('Failed to get recent pages.', $recent_pages_results['code']);

        $recent_pages = [];
        foreach($recent_pages_results['results'] as $page) {
             $title_property = $page['properties']['title'] ?? ($page['properties']['Name'] ?? null);
             $title = 'Untitled';
             if ($title_property && $title_property['type'] === 'title' && !empty($title_property['title'])) {
                 $title = $title_property['title'][0]['plain_text'];
             }

            $recent_pages[] = [
                'id' => $page['id'],
                'title' => $title,
                'url' => $page['url'],
                'last_edited' => (new DateTime($page['last_edited_time']))->format('M j, Y g:i A')
            ];
        }

        $stats = [
            'name' => $user_info['name'] ?? 'Notion User',
            'page_count' => $page_count,
            'db_count' => $db_count
        ];

        echo json_encode(['success' => true, 'stats' => $stats, 'recent_pages' => $recent_pages]);
    }
    // --- Action: List all databases the integration has access to ---
    elseif ($action === 'list_databases') {
        // ... (existing list_databases logic remains the same)
         $db_search_body = [
            'filter' => ['property' => 'object', 'value' => 'database'],
            'page_size' => 100
        ];
        $db_results = makeNotionApiRequest('/search', $access_token, 'POST', $db_search_body);
        if (isset($db_results['error'])) throw new Exception('Failed to list databases.', $db_results['code']);

        $databases = [];
        foreach($db_results['results'] as $db) {
            $title = 'Untitled Database';
            if (isset($db['title']) && !empty($db['title'])) {
                $title = $db['title'][0]['plain_text'];
            }
             $databases[] = [
                'id' => $db['id'],
                'title' => $title,
            ];
        }
        echo json_encode(['success' => true, 'databases' => $databases]);
    }
    // --- Action: Create a new page in a database ---
    elseif ($action === 'create_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... (existing create_page logic remains the same)
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['database_id']) || empty($input['title'])) {
            throw new Exception("Database ID and Title are required.", 400);
        }

        // Determine the title property name by fetching database schema
        $db_info = makeNotionApiRequest('/databases/' . $input['database_id'], $access_token, 'GET');
        $title_prop_name = 'title'; // default
        if (isset($db_info['properties'])) {
            foreach ($db_info['properties'] as $key => $prop) {
                if ($prop['type'] === 'title') {
                    $title_prop_name = $key;
                    break;
                }
            }
        }

        $page_body = [
            'parent' => ['database_id' => $input['database_id']],
            'properties' => [
                $title_prop_name => [
                    'title' => [['text' => ['content' => $input['title']]]]
                ]
            ],
            'children' => [
                [
                    'object' => 'block',
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [['type' => 'text', 'text' => ['content' => $input['content'] ?? '']]]
                    ]
                ]
            ]
        ];

        $result = makeNotionApiRequest('/pages', $access_token, 'POST', $page_body);
         if (isset($result['error'])) {
             throw new Exception('Notion API error: ' . ($result['message']['message'] ?? 'Unknown error'), $result['code']);
        }

        echo json_encode(['success' => true, 'data' => $result]);
    }
    // --- Action: Get content of a specific database ---
    elseif ($action === 'get_database_content') {
        $database_id = $_GET['database_id'] ?? '';
        if (empty($database_id)) throw new Exception("Database ID is required.", 400);

        $db_content = makeNotionApiRequest("/databases/{$database_id}/query", $access_token, 'POST');
        if (isset($db_content['error'])) throw new Exception("Failed to get database content.", $db_content['code']);

        $pages = [];
         foreach($db_content['results'] as $page) {
             $title = 'Untitled';
             // Find the title property dynamically
             foreach($page['properties'] as $prop) {
                 if ($prop['type'] === 'title' && !empty($prop['title'])) {
                     $title = $prop['title'][0]['plain_text'];
                     break;
                 }
             }

            $pages[] = [
                'id' => $page['id'],
                'title' => $title,
                'url' => $page['url'],
                'last_edited' => (new DateTime($page['last_edited_time']))->format('M j, Y')
            ];
        }
        echo json_encode(['success' => true, 'pages' => $pages]);
    }
    // --- Action: Get all text content from a page for summarization ---
    elseif ($action === 'get_page_content') {
        $page_id = $_GET['page_id'] ?? '';
        if (empty($page_id)) throw new Exception("Page ID is required.", 400);

        $blocks = makeNotionApiRequest("/blocks/{$page_id}/children?page_size=100", $access_token, 'GET');
        if (isset($blocks['error'])) throw new Exception("Failed to get page content.", $blocks['code']);

        $full_text = '';
        foreach($blocks['results'] as $block) {
            $type = $block['type'];
            if (isset($block[$type]['rich_text']) && !empty($block[$type]['rich_text'])) {
                foreach($block[$type]['rich_text'] as $text_part) {
                    $full_text .= $text_part['plain_text'];
                }
                $full_text .= "\n"; // Add newline after each block
            }
        }
        echo json_encode(['success' => true, 'content' => $full_text]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>

