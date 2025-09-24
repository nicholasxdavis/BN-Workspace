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
    $_SESSION['oauth_provider'] = 'reddit';
    $params = [
        'client_id' => REDDIT_CLIENT_ID, 'response_type' => 'code', 'state' => $state,
        'redirect_uri' => REDDIT_REDIRECT_URI, 'duration' => 'permanent',
        'scope' => 'identity edit flair history modconfig modflair modlog modposts modwiki mysubreddits privatemessages read report save submit subscribe vote wikiedit wikiread'
    ];
    $auth_url = 'https://www.reddit.com/api/v1/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;

} elseif ($provider === 'notion') {
    $_SESSION['oauth_provider'] = 'notion';
    $params = [
        'client_id' => NOTION_CLIENT_ID, 'response_type' => 'code', 'owner' => 'user',
        'redirect_uri' => NOTION_REDIRECT_URI, 'state' => $state,
    ];
    $auth_url = 'https://api.notion.com/v1/oauth/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;

} elseif ($provider === 'dropbox') {
    $_SESSION['oauth_provider'] = 'dropbox';
    $code_verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['dropbox_code_verifier'] = $code_verifier;
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    $scopes = ['account_info.read', 'files.metadata.read', 'files.content.read', 'files.content.write'];
    $params = [
        'client_id' => DROPBOX_APP_KEY, 'response_type' => 'code', 'redirect_uri' => DROPBOX_REDIRECT_URI,
        'state' => $state, 'token_access_type' => 'offline', 'code_challenge_method' => 'S256',
        'code_challenge' => $code_challenge, 'scope' => implode(' ', $scopes),
    ];
    $auth_url = 'https://www.dropbox.com/oauth2/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;

} elseif ($provider === 'github') {
    $_SESSION['oauth_provider'] = 'github';
    // For GitHub Apps, permissions are handled during installation, not in the auth URL.
    $params = [
        'client_id' => GITHUB_CLIENT_ID,
        'redirect_uri' => GITHUB_REDIRECT_URI,
        'state' => $state,
    ];
    $auth_url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
}

echo 'Invalid provider specified.';
exit;
?>
