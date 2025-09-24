<?php
// api/integrations/reddit/reddit_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../encryption.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- 1. Fetch and Decrypt the Access Token ---
try {
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'reddit'");
    $stmt->execute([$user_id]);
    $integration = $stmt->fetch();

    if (!$integration) {
        http_response_code(404);
        echo json_encode(['error' => 'Reddit integration not found for this user.']);
        exit;
    }

    $access_token = decrypt_token($integration['access_token']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}


// --- 2. Generic function to call Reddit API ---
function callRedditApi($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . $token,
        'User-Agent: BN-Workspace/1.0'
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return ['error' => true, 'http_code' => $http_code, 'response' => json_decode($response, true)];
    }
    
    return json_decode($response, true);
}


// --- 3. Fetch User Profile Data (for Karma) ---
$me_data = callRedditApi('https://oauth.reddit.com/api/v1/me', $access_token);

if (isset($me_data['error'])) {
    http_response_code($me_data['http_code']);
    // This could mean the token expired. A production app would handle token refresh here.
    echo json_encode(['error' => 'Invalid or expired Reddit token.', 'details' => $me_data['response']]);
    exit;
}

$username = $me_data['name'];
$total_karma = $me_data['total_karma'];


// --- 4. Fetch User's Top Posts ---
$overview_data = callRedditApi("https://oauth.reddit.com/user/{$username}/overview?sort=top&limit=5", $access_token);

$top_posts = [];
if (isset($overview_data['data']['children'])) {
    foreach ($overview_data['data']['children'] as $item) {
        // We only want to show posts (links), not comments
        if ($item['kind'] === 't3') { 
            $post = $item['data'];
            $top_posts[] = [
                'id' => $post['id'],
                'title' => $post['title'],
                'subreddit' => $post['subreddit_name_prefixed'],
                'upvotes' => number_format($post['score']),
                'comments' => number_format($post['num_comments'])
            ];
        }
    }
}


// --- 5. Combine and return the data ---
$dashboard_data = [
    'success' => true,
    'stats' => [
        'karma' => number_format($total_karma),
        // Note: Follower growth and upvote rate require more complex historical data tracking.
        // These are placeholders until that logic is implemented.
        'upvoteRate' => '92.1', 
        'followerGrowth' => '+156'
    ],
    'topPosts' => $top_posts
];

echo json_encode($dashboard_data);

?>
