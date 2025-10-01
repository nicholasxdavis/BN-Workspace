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

// 2. Get the provider from the URL and generate state for security
$provider = $_GET['provider'] ?? '';
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

if ($provider === 'reddit') {
    $_SESSION['oauth_provider'] = 'reddit';

    // Build the Reddit Authorization URL with necessary permissions
    $params = [
        'client_id' => REDDIT_CLIENT_ID,
        'response_type' => 'code',
        'state' => $state,
        'redirect_uri' => REDDIT_REDIRECT_URI,
        'duration' => 'permanent', // To get a refresh_token
        'scope' => 'identity edit flair history modconfig modflair modlog modposts modwiki mysubreddits privatemessages read report save submit subscribe vote wikiedit wikiread'
    ];

    $auth_url = 'https://www.reddit.com/api/v1/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;

} elseif ($provider === 'notion') {
    $_SESSION['oauth_provider'] = 'notion';

    // Build the Notion Authorization URL
    $params = [
        'client_id' => NOTION_CLIENT_ID,
        'response_type' => 'code',
        'owner' => 'user',
        'redirect_uri' => NOTION_REDIRECT_URI,
        'state' => $state,
    ];

    $auth_url = 'https://api.notion.com/v1/oauth/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;

} elseif ($provider === 'dropbox') {
    $_SESSION['oauth_provider'] = 'dropbox';

    // PKCE Flow: Generate a code verifier and challenge for enhanced security
    $code_verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['dropbox_code_verifier'] = $code_verifier;
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    
    // Define all the permissions (scopes) your app needs for Dropbox
    $scopes = [
        'account_info.read', 'account_info.write',
        'files.metadata.read', 'files.metadata.write',
        'files.content.read', 'files.content.write',
        'sharing.read', 'sharing.write',
        'file_requests.read', 'file_requests.write',
        'contacts.read', 'contacts.write'
    ];

    // Build the Dropbox Authorization URL
    $params = [
        'client_id' => DROPBOX_APP_KEY,
        'response_type' => 'code',
        'redirect_uri' => DROPBOX_REDIRECT_URI,
        'state' => $state,
        'token_access_type' => 'offline', // To get a refresh token
        'code_challenge_method' => 'S256',
        'code_challenge' => $code_challenge,
        'scope' => implode(' ', $scopes), // Add the scopes to the request
    ];
    
    $auth_url = 'https://www.dropbox.com/oauth2/authorize?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
    
} elseif ($provider === 'facebook' || $provider === 'instagram') {
    $_SESSION['oauth_provider'] = 'meta';

    // Define the required permissions (scopes)
    $scopes = [
        'email',
        'public_profile',
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts'
    ];

    // Build the Meta (Facebook) Authorization URL
    $params = [
        'client_id' => META_APP_ID,
        'redirect_uri' => META_REDIRECT_URI,
        'state' => $state,
        'response_type' => 'code',
        'scope' => implode(',', $scopes),
    ];

    $auth_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
}

// Handle any other providers that might be requested
echo 'Invalid provider specified.';
exit;
?>
