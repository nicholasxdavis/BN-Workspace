<?php
// api/integrations/token_manager.php
require_once 'config.php';
require_once 'encryption.php';

// Function to refresh an expired token for a given provider
function refresh_token_for_provider($provider, $refresh_token) {
    $new_token_data = null;

    if ($provider === 'reddit') {
        $token_url = 'https://www.reddit.com/api/v1/access_token';
        $post_data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
        ];
        $auth_header = REDDIT_CLIENT_ID . ':' . REDDIT_CLIENT_SECRET;

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_USERPWD, $auth_header);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: BN-Workspace/1.0']);
        $response = curl_exec($ch);
        curl_close($ch);
        $new_token_data = json_decode($response, true);
    } elseif ($provider === 'dropbox') {
        $token_url = 'https://api.dropboxapi.com/oauth2/token';
        $post_data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => DROPBOX_APP_KEY,
            'client_secret' => DROPBOX_SECRET,
        ];
        
        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $response = curl_exec($ch);
        curl_close($ch);
        $new_token_data = json_decode($response, true);
    }
    // Notion tokens are short-lived and don't have refresh tokens.

    return $new_token_data;
}

// Main function to get a valid access token
function get_valid_access_token($pdo, $user_id, $provider) {
    $stmt = $pdo->prepare("SELECT * FROM user_integrations WHERE user_id = ? AND provider = ?");
    $stmt->execute([$user_id, $provider]);
    $integration = $stmt->fetch();

    if (!$integration) {
        throw new Exception("Integration not found for this user.", 404);
    }

    $now = new DateTime();
    $expires_at = new DateTime($integration['expires_at']);

    if ($now < $expires_at) {
        // Token is not expired
        return decrypt_token($integration['access_token']);
    }

    // Token is expired, try to refresh it
    $decrypted_refresh_token = decrypt_token($integration['refresh_token']);
    $new_token_data = refresh_token_for_provider($provider, $decrypted_refresh_token);

    if (isset($new_token_data['access_token'])) {
        $new_access_token = encrypt_token($new_token_data['access_token']);
        $new_expires_in = $new_token_data['expires_in'];
        $new_expires_at = (new DateTime())->add(new DateInterval('PT' . $new_expires_in . 'S'))->format('Y-m-d H:i:s');
        
        // Some providers might issue a new refresh token
        $new_refresh_token = isset($new_token_data['refresh_token']) ? encrypt_token($new_token_data['refresh_token']) : $integration['refresh_token'];
        
        $update_stmt = $pdo->prepare(
            "UPDATE user_integrations SET access_token = ?, refresh_token = ?, expires_at = ? WHERE id = ?"
        );
        $update_stmt->execute([$new_access_token, $new_refresh_token, $new_expires_at, $integration['id']]);

        return $new_token_data['access_token'];
    }

    // If refresh fails, re-authentication is needed
    throw new Exception("Could not refresh token. Please re-authenticate.", 401);
}
?>
