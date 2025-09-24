<?php
// api/integrations/reddit/reddit_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../../integrations/config.php';
require_once '../../integrations/encryption.php';

header('Content-Type: application/json');

// --- Central function to manage and refresh the OAuth token ---
function getValidAccessToken($pdo, $user_id) {
    // 1. Fetch the current tokens from the database
    $stmt = $pdo->prepare("SELECT access_token, refresh_token, expires_at FROM user_integrations WHERE user_id = ? AND provider = 'reddit'");
    $stmt->execute([$user_id]);
    $integration = $stmt->fetch();

    if (!$integration || !$integration['refresh_token']) {
        throw new Exception("Reddit integration or refresh token not found. Please re-authenticate.", 401);
    }

    $expires_at = new DateTime($integration['expires_at']);
    $now = new DateTime();

    // 2. Check if the token is expired or close to expiring
    if ($now < $expires_at) {
        return decrypt_token($integration['access_token']); // Token is still valid
    }

    // 3. If expired, use the refresh token to get a new access token
    $token_url = 'https://www.reddit.com/api/v1/access_token';
    $post_data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => decrypt_token($integration['refresh_token']),
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_USERPWD, REDDIT_CLIENT_ID . ':' . REDDIT_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: BlacnovaWorkspace/1.0']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if ($http_code !== 200 || !isset($token_data['access_token'])) {
        // If refresh fails, the user must re-authenticate
        throw new Exception("Could not refresh Reddit session. Please re-authenticate.", 401);
    }
    
    // 4. Update the database with the new token and expiry time
    $new_access_token = $token_data['access_token'];
    $new_expires_in = $token_data['expires_in'];
    $new_expires_at = (new DateTime())->add(new DateInterval('PT' . $new_expires_in . 'S'))->format('Y-m-d H:i:s');
    
    $update_stmt = $pdo->prepare(
        "UPDATE user_integrations SET access_token = ?, expires_at = ?, updated_at = NOW() 
         WHERE user_id = ? AND provider = 'reddit'"
    );
    $update_stmt->execute([encrypt_token($new_access_token), $new_expires_at, $user_id]);

    return $new_access_token;
}


// --- Helper function to make requests to the Reddit API ---
function makeRedditApiRequest($endpoint, $access_token) {
    $api_url = 'https://oauth.reddit.com' . $endpoint;
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . $access_token,
        'User-Agent: BlacnovaWorkspace/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        return ['error' => true, 'code' => $http_code, 'message' => json_decode($response, true)];
    }
    return json_decode($response, true);
}


// --- Main Logic ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

try {
    $access_token = getValidAccessToken($pdo, $_SESSION['user_id']);
    
    // Get User Account Info
    $me_data = makeRedditApiRequest('/api/v1/me', $access_token);
    if (isset($me_data['error'])) throw new Exception('Failed to fetch user data.', $me_data['code']);
    
    $username = $me_data['name'];
    
    // Get User's Top Posts (last month)
    $posts_data = makeRedditApiRequest("/user/{$username}/submitted?sort=top&t=month&limit=100", $access_token);
    if (isset($posts_data['error'])) throw new Exception('Failed to fetch user posts.', $posts_data['code']);
    
    $topPosts = [];
    $total_upvote_ratio = 0;
    $total_comments = 0;
    $post_count = 0;

    if (isset($posts_data['data']['children'])) {
        foreach ($posts_data['data']['children'] as $post) {
            $post_count++;
            $total_upvote_ratio += $post['data']['upvote_ratio'];
            $total_comments += $post['data']['num_comments'];
            
            if (count($topPosts) < 5) {
                $topPosts[] = [
                    'title' => $post['data']['title'],
                    'subreddit' => $post['data']['subreddit_name_prefixed'],
                    'upvotes' => number_format($post['data']['score']),
                    'comments' => number_format($post['data']['num_comments'])
                ];
            }
        }
    }

    $stats = [
        'karma' => number_format($me_data['total_karma']),
        'averageUpvoteRate' => ($post_count > 0) ? round(($total_upvote_ratio / $post_count) * 100, 1) . '%' : 'N/A',
        'averageComments' => ($post_count > 0) ? round($total_comments / $post_count, 1) : 'N/A'
    ];

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'topPosts' => $topPosts
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

