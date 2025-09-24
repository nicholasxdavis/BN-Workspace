<?php
// api/integrations/github/github_api.php
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

function makeGitHubApiRequest($endpoint, $access_token, $method = 'GET', $body = null) {
    $api_url = 'https://api.github.com' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'User-Agent: BN-Workspace/1.0',
        'Accept: application/vnd.github.v3+json'
    ]);

    if ($body && ($method === 'POST' || $method === 'PATCH')) {
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

$action = $_GET['action'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'github'");
    $stmt->execute([$_SESSION['user_id']]);
    $integration = $stmt->fetch();

    if (!$integration) throw new Exception("GitHub integration not found.", 404);
    
    $access_token = decrypt_token($integration['access_token']);

    if ($action === 'dashboard_data') {
        $user_info = makeGitHubApiRequest('/user', $access_token);
        if (isset($user_info['error'])) throw new Exception('Failed to fetch user data.', $user_info['code']);

        $repos = makeGitHubApiRequest('/user/repos?sort=pushed&per_page=5', $access_token);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'name' => $user_info['name'] ?? $user_info['login'],
                'login' => $user_info['login'],
                'avatar_url' => $user_info['avatar_url'],
                'public_repos' => $user_info['public_repos'],
                'followers' => $user_info['followers'],
                'following' => $user_info['following']
            ],
            'recent_repos' => $repos ?? []
        ]);
    }
    elseif ($action === 'list_repos') {
        $page = $_GET['page'] ?? 1;
        $repos = makeGitHubApiRequest("/user/repos?sort=updated&per_page=100&page={$page}", $access_token);
        if (isset($repos['error'])) throw new Exception('Failed to list repositories.', $repos['code']);
        echo json_encode(['success' => true, 'data' => $repos]);
    }
    else {
        throw new Exception("Invalid action.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>