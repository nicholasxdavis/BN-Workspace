<?php
// api/integrations/callback.php
session_start();
require_once '../auth/db_connect.php';
require_once 'config.php';
require_once 'encryption.php';

// --- 1. Security Check ---
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

// --- 2. Exchange Code for Token ---
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $token_data = null;

    if ($provider === 'reddit') {
        // ... existing code ...
    } elseif ($provider === 'notion') {
        // ... existing code ...
    } elseif ($provider === 'dropbox') {
        // ... existing code ...
    } elseif ($provider === 'github') {
        $token_url = 'https://github.com/login/oauth/access_token';
        $post_data = [
            'client_id' => GITHUB_CLIENT_ID,
            'client_secret' => GITHUB_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => GITHUB_REDIRECT_URI,
        ];

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: BN-Workspace/1.0'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $token_data = json_decode($response, true);
    }

    if (isset($token_data['access_token'])) {
        // --- 3. Save Tokens ---
        $user_id = $_SESSION['user_id'];
        $access_token = encrypt_token($token_data['access_token']);
        $refresh_token = isset($token_data['refresh_token']) ? encrypt_token($token_data['refresh_token']) : null;
        $scope = $token_data['scope'] ?? null;
        $expires_at = null; // GitHub tokens don't expire by default unless enabled
        if (isset($token_data['expires_in'])) {
             $expires_at = (new DateTime())->add(new DateInterval('PT' . $token_data['expires_in'] . 'S'))->format('Y-m-d H:i:s');
        }

        // We'll fetch the provider_user_id separately for GitHub
        $ch_user = curl_init('https://api.github.com/user');
        curl_setopt($ch_user, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_user, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token_data['access_token'],
            'User-Agent: BN-Workspace/1.0'
        ]);
        $user_response = curl_exec($ch_user);
        curl_close($ch_user);
        $user_info = json_decode($user_response, true);
        $provider_user_id = $user_info['id'] ?? null;

        unset($_SESSION['oauth_provider']);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO user_integrations (user_id, provider, access_token, refresh_token, expires_at, scope, provider_user_id)
                 VALUES (:user_id, :provider, :access_token, :refresh_token, :expires_at, :scope, :provider_user_id)
                 ON DUPLICATE KEY UPDATE
                 access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at), 
                 scope = VALUES(scope), provider_user_id = VALUES(provider_user_id), updated_at = NOW()"
            );
            $stmt->execute([
                ':user_id' => $user_id, ':provider' => $provider, ':access_token' => $access_token, ':refresh_token' => $refresh_token,
                ':expires_at' => $expires_at, ':scope' => $scope, ':provider_user_id' => $provider_user_id
            ]);
            header('Location: ' . ROOT_URL . '?integration_success=' . urlencode($provider));
            exit;
        } catch (PDOException $e) {
            header('Location: ' . ROOT_URL . '?integration_error=db_error&message=' . urlencode($e->getMessage()));
            exit;
        }
    } else {
        unset($_SESSION['oauth_provider']);
        header('Location: ' . ROOT_URL . '?integration_error=token_exchange_failed');
        exit;
    }
}
?>