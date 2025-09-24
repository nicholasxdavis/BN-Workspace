<?php
// api/integrations/callback.php

// ENABLE FULL ERROR REPORTING - IMPORTANT FOR DEBUGGING
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "DEBUG: Script started.<br>";

session_start();
echo "DEBUG: Session started.<br>";

// Manually check if required files exist before including them
$db_connect_path = __DIR__ . '/../auth/db_connect.php';
$config_path = __DIR__ . '/config.php';
$encryption_path = __DIR__ . '/encryption.php';

if (!file_exists($db_connect_path)) { die("FATAL ERROR: db_connect.php not found at: " . $db_connect_path); }
if (!file_exists($config_path)) { die("FATAL ERROR: config.php not found at: " . $config_path); }
if (!file_exists($encryption_path)) { die("FATAL ERROR: encryption.php not found at: " . $encryption_path); }

require_once $db_connect_path;
echo "DEBUG: db_connect.php included.<br>";
require_once $config_path;
echo "DEBUG: config.php included.<br>";
require_once $encryption_path;
echo "DEBUG: encryption.php included.<br>";


// --- 1. Security Check: Validate State ---
echo "DEBUG: Checking state...<br>";
if (empty($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    unset($_SESSION['oauth_state']);
    die("ERROR: State mismatch. Possible CSRF attack. Your state: " . ($_GET['state'] ?? 'Not Set') . " | Expected state: " . ($_SESSION['oauth_state'] ?? 'Not Set'));
}
unset($_SESSION['oauth_state']);
echo "DEBUG: State validation passed.<br>";

// Check for login
if (!isset($_SESSION['user_id'])) {
    die("ERROR: User is not logged in.");
}
echo "DEBUG: User is logged in. User ID: " . $_SESSION['user_id'] . "<br>";

// Check if Reddit returned an error
if (isset($_GET['error'])) {
    die("ERROR: Reddit returned an error in the URL: " . htmlspecialchars($_GET['error']));
}

// --- 2. Exchange Authorization Code for Access Token ---
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    echo "DEBUG: Authorization code received: " . htmlspecialchars($code) . "<br>";

    $token_url = 'https://www.reddit.com/api/v1/access_token';
    $post_data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => REDDIT_REDIRECT_URI,
    ];

    echo "DEBUG: Preparing to send cURL request to: " . $token_url . "<br>";

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_USERPWD, REDDIT_CLIENT_ID . ':' . REDDIT_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: BN-Workspace/1.0']);
    // Add timeout to prevent script from hanging indefinitely
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);


    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo "DEBUG: cURL request finished.<br>";

    if ($curl_errno > 0) {
        die("FATAL cURL ERROR: #" . $curl_errno . " - " . htmlspecialchars($curl_error));
    }

    echo "DEBUG: Raw response from Reddit: <pre>" . htmlspecialchars($response) . "</pre><br>";

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        echo "DEBUG: Access token received successfully!<br>";
        // --- 3. Save the Tokens to the Database ---
        $user_id = $_SESSION['user_id'];
        $provider = 'reddit';
        $access_token = encrypt_token($token_data['access_token']);
        $refresh_token = isset($token_data['refresh_token']) ? encrypt_token($token_data['refresh_token']) : null;
        $scope = $token_data['scope'];
        
        $expires_in = $token_data['expires_in'];
        $expires_at = (new DateTime())->add(new DateInterval('PT' . $expires_in . 'S'))->format('Y-m-d H:i:s');
        echo "DEBUG: Tokens encrypted and expiry calculated.<br>";

        try {
            echo "DEBUG: Attempting to save tokens to database...<br>";
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
            
            echo "DEBUG: Tokens saved to database successfully!<br>";
            echo "DEBUG: Redirecting back to workspace...<br>";

            // --- 4. Redirect back to the workspace with a success message ---
            // header('Location: ' . ROOT_URL . 'integrations/reddit/'); // Redirect is commented out for debugging
            echo '<a href="' . ROOT_URL . 'integrations/reddit/">Click here to continue</a>';
            exit;

        } catch (PDOException $e) {
            die("FATAL DATABASE ERROR: " . $e->getMessage());
        }

    } else {
        die("ERROR: Reddit did not return an access token. Response: " . htmlspecialchars($response));
    }
} else {
    die("ERROR: No authorization code provided in the URL.");
}
?>
