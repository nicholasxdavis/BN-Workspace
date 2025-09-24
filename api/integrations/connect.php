<?php
// api/integrations/connect.php
session_start();
require_once 'config.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'You must be logged in to connect an app.';
    exit;
}

// 2. Get the provider from the URL
$provider = $_GET['provider'] ?? '';
// Generate a random 'state' string for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

if ($provider === 'reddit') {
    // ... existing reddit code ...
} elseif ($provider === 'notion') {
    // ... existing notion code ...
} elseif ($provider === 'dropbox') {
    $_SESSION['oauth_provider'] = 'dropbox';

    // PKCE Flow: Generate code verifier and challenge
    $code_verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['dropbox_code_verifier'] = $code_verifier;
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');

    $params = [
        'client_id' => DROPBOX_APP_KEY,
        'response_type' => 'code',
        'redirect_uri' => DROPBOX_REDIRECT_URI,
        'state' => $state,
        'token_access_type' => 'offline', // To get a refresh token
        'code_challenge_method' => 'S256',
        'code_challenge' => $code_challenge,
    ];
    $auth_url = 'https://www.dropbox.com/oauth2/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
}


// Handle other providers here in the future...
echo 'Invalid provider specified.';
exit;
?>
