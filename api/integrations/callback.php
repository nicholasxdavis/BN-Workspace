<?php
// api/integrations/callback.php
session_start();
require_once '../auth/db_connect.php'; // pdo is here
require_once 'config.php';
require_once 'encryption.php';

// --- 1. Security Check: Validate State ---
if (empty($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_provider']);
    header('Location: ' . ROOT_URL . '?integration_error=state_mismatch');
    exit;
}

$provider = $_SESSION['oauth_provider'] ?? '';
unset($_SESSION['oauth_state']);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '?integration_error=not_logged_in');
    exit;
}

if (isset($_GET['error'])) {
    header('Location: ' . ROOT_URL . '?integration_error=' . urlencode($_GET['error']));
    exit;
}

// --- 2. Exchange Authorization Code for Access Token ---
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $token_data = null;

    // --- Handle Reddit Token Exchange ---
    if ($provider === 'reddit') {
        // ... existing reddit code ...
    // --- Handle Notion Token Exchange ---
    } elseif ($provider === 'notion') {
        // ... existing notion code ...
    // --- Handle Dropbox Token Exchange ---
    } elseif ($provider === 'dropbox') {
        $token_url = 'https://api.dropboxapi.com/oauth2/token';
        $post_data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => DROPBOX_REDIRECT_URI,
            'code_verifier' => $_SESSION['dropbox_code_verifier'],
            'client_id' => DROPBOX_APP_KEY,
            'client_secret' => DROPBOX_SECRET,
        ];
        unset($_SESSION['dropbox_code_verifier']); // Clean up

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $response = curl_exec($ch);
        curl_close($ch);
        $token_data = json_decode($response, true);
    }

    if (isset($token_data['access_token'])) {
        // --- 3. Prepare and Save the Tokens to the Database ---
        $user_id = $_SESSION['user_id'];
        $access_token = encrypt_token($token_data['access_token']);
        
        $refresh_token = null;
        $scope = null;
        $expires_at = null;
        $provider_user_id = $token_data['account_id'] ?? null;

        if ($provider === 'reddit' || $provider === 'dropbox') {
            $refresh_token = isset($token_data['refresh_token']) ? encrypt_token($token_data['refresh_token']) : null;
            $scope = $token_data['scope'] ?? null;
            if (isset($token_data['expires_in'])) {
                $expires_at = (new DateTime())->add(new DateInterval('PT' . $token_data['expires_in'] . 'S'))->format('Y-m-d H:i:s');
            }
        }
        
        // Unset provider from session after use
        unset($_SESSION['oauth_provider']);

        try {
            // Use an UPSERT query
            $stmt = $pdo->prepare(
                "INSERT INTO user_integrations (user_id, provider, access_token, refresh_token, expires_at, scope, provider_user_id)
                 VALUES (:user_id, :provider, :access_token, :refresh_token, :expires_at, :scope, :provider_user_id)
                 ON DUPLICATE KEY UPDATE
                 access_token = VALUES(access_token),
                 refresh_token = VALUES(refresh_token),
                 expires_at = VALUES(expires_at),
                 scope = VALUES(scope),
                 provider_user_id = VALUES(provider_user_id),
                 updated_at = NOW()"
            );

            $stmt->execute([
                ':user_id' => $user_id,
                ':provider' => $provider,
                ':access_token' => $access_token,
                ':refresh_token' => $refresh_token,
                ':expires_at' => $expires_at,
                ':scope' => $scope,
                ':provider_user_id' => $provider_user_id
            ]);

            header('Location: ' . ROOT_URL . '?integration_success=' . urlencode($provider));
            exit;

        } catch (PDOException $e) {
            header('Location: ' . ROOT_URL . '?integration_error=db_error');
            exit;
        }

    } else {
        unset($_SESSION['oauth_provider']);
        header('Location: ' . ROOT_URL . '?integration_error=token_exchange_failed');
        exit;
    }
}
?>
