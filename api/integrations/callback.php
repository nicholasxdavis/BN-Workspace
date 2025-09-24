<?php
// api/integrations/callback.php
session_start();
require_once '../auth/db_connect.php'; // pdo is here
require_once 'config.php';
require_once 'encryption.php';

// --- 1. Security Check: Validate State ---
if (empty($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    // State mismatch, possible CSRF attack.
    unset($_SESSION['oauth_state']);
    header('Location: ' . ROOT_URL . '?integration_error=state_mismatch');
    exit;
}
unset($_SESSION['oauth_state']); // Clean up state

// Check for login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '?integration_error=not_logged_in');
    exit;
}

// Check if Reddit returned an error
if (isset($_GET['error'])) {
    header('Location: ' . ROOT_URL . '?integration_error=' . urlencode($_GET['error']));
    exit;
}

// --- 2. Exchange Authorization Code for Access Token ---
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $token_url = 'https://www.reddit.com/api/v1/access_token';
    $post_data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => REDDIT_REDIRECT_URI,
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_USERPWD, REDDIT_CLIENT_ID . ':' . REDDIT_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: BN-Workspace/1.0']);

    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        // --- 3. Save the Tokens to the Database ---
        $user_id = $_SESSION['user_id'];
        $provider = 'reddit';
        $access_token = encrypt_token($token_data['access_token']);
        $refresh_token = isset($token_data['refresh_token']) ? encrypt_token($token_data['refresh_token']) : null;
        $scope = $token_data['scope'];
        
        // Calculate expiry time
        $expires_in = $token_data['expires_in']; // in seconds
        $expires_at = (new DateTime())->add(new DateInterval('PT' . $expires_in . 'S'))->format('Y-m-d H:i:s');

        try {
            // Use an UPSERT query to either INSERT a new record or UPDATE an existing one
            $stmt = $pdo->prepare(
                "INSERT INTO user_integrations (user_id, provider, access_token, refresh_token, expires_at, scope)
                 VALUES (:user_id, :provider, :access_token, :refresh_token, :expires_at, :scope)
                 ON DUPLICATE KEY UPDATE
                 access_token = VALUES(access_token),
                 refresh_token = VALUES(refresh_token),
                 expires_at = VALUES(expires_at),
                 scope = VALUES(scope),
                 updated_at = NOW()"
            );

            $stmt->execute([
                ':user_id' => $user_id,
                ':provider' => $provider,
                ':access_token' => $access_token,
                ':refresh_token' => $refresh_token,
                ':expires_at' => $expires_at,
                ':scope' => $scope
            ]);

            // --- 4. Redirect back to the workspace with a success message ---
            header('Location: ' . ROOT_URL . './api/integrations/reddit/index.html');
            exit;

        } catch (PDOException $e) {
            // Handle database errors
             header('Location: ' . ROOT_URL . '?integration_error=db_error');
             exit;
        }

    } else {
        // Reddit did not return an access token
        header('Location: ' . ROOT_URL . '?integration_error=token_exchange_failed');
        exit;
    }
}
?>
