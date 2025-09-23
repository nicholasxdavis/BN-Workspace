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

// 2. Get the provider from the URL (e.g., ?provider=reddit)
$provider = $_GET['provider'] ?? '';

if ($provider === 'reddit') {
    // 3. Generate a random 'state' string for CSRF protection
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    // 4. Build the Reddit Authorization URL
    $params = [
        'client_id' => REDDIT_CLIENT_ID,
        'response_type' => 'code',
        'state' => $state,
        'redirect_uri' => REDDIT_REDIRECT_URI,
        'duration' => 'permanent', // To get a refresh_token
        'scope' => 'identity edit flair history modconfig modflair modlog modposts modwiki mysubreddits privatemessages read report save submit subscribe vote wikiedit wikiread' // request needed permissions
    ];

    $auth_url = 'https://www.reddit.com/api/v1/authorize?' . http_build_query($params);

    // 5. Redirect the user to Reddit to authorize the app
    header('Location: ' . $auth_url);
    exit;
}

// Handle other providers here in the future...
echo 'Invalid provider specified.';
exit;
?>
