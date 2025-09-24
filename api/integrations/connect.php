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
    // ... (existing reddit code remains the same)

} elseif ($provider === 'notion') {
    $_SESSION['oauth_provider'] = 'notion';
    // ... (existing notion code remains the same)

} elseif ($provider === 'dropbox') {
    $_SESSION['oauth_provider'] = 'dropbox';
    // ... (existing dropbox code remains the same)

} elseif ($provider === 'github') {
    $_SESSION['oauth_provider'] = 'github';

    // For GitHub Apps, scopes are not passed in the URL.
    // Permissions are granted during app installation.
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
