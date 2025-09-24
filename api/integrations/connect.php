<?php
// api/integrations/connect.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'You must be logged in to connect an app.';
    exit;
}

$provider = $_GET['provider'] ?? '';
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

if ($provider === 'reddit') {
    // ... existing reddit code ...
} elseif ($provider === 'notion') {
    // ... existing notion code ...
} elseif ($provider === 'dropbox') {
    // ... existing dropbox code ...
} elseif ($provider === 'github') {
    $_SESSION['oauth_provider'] = 'github';

    $scopes = [
        'repo', 'workflow', 'write:packages', 'delete:packages', 'admin:repo_hook', 
        'admin:org_hook', 'gist', 'notifications', 'user', 'delete_repo', 'write:discussion', 
        'write:org', 'admin:enterprise', 'admin:public_key', 'admin:gpg_key'
    ];
    
    $params = [
        'client_id' => GITHUB_CLIENT_ID,
        'redirect_uri' => GITHUB_REDIRECT_URI,
        'scope' => implode(' ', $scopes),
        'state' => $state,
    ];
    $auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
}

echo 'Invalid provider specified.';
exit;
?>