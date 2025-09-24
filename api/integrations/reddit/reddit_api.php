<?php
// api/integrations/reddit/reddit_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../encryption.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// --- 1. Fetch and Decrypt the Access Token ---
try {
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'reddit'");
    $stmt->execute([$user_id]);
    $integration = $stmt->fetch();

    if (!$integration) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reddit integration not found for this user.']);
        exit;
    }

    $access_token = decrypt_token($integration['access_token']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// --- 2. Generic function to call Reddit API ---
function callRedditApi($url, $token, $method = 'GET', $post_fields = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . $token,
        'User-Agent: BN-Workspace/1.0'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 300) {
        return ['error' => true, 'http_code' => $http_code, 'response' => json_decode($response, true)];
    }
    
    return json_decode($response, true);
}

// --- 3. Handle Different Actions ---

if ($action === 'dashboard_data') {
    // --- Fetch User Profile Data ---
    $me_data = callRedditApi('https://oauth.reddit.com/api/v1/me', $access_token);
    if (isset($me_data['error'])) {
        http_response_code($me_data['http_code']);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired Reddit token.', 'details' => $me_data['response']]);
        exit;
    }
    $username = $me_data['name'];
    $total_karma = $me_data['total_karma'];

    // --- Fetch User's Top Posts ---
    $overview_data = callRedditApi("https://oauth.reddit.com/user/{$username}/overview?sort=top&limit=5&t=month", $access_token);
    $top_posts = [];
    if (isset($overview_data['data']['children'])) {
        foreach ($overview_data['data']['children'] as $item) {
            if ($item['kind'] === 't3') { 
                $post = $item['data'];
                $top_posts[] = ['id' => $post['id'], 'title' => $post['title'], 'subreddit' => $post['subreddit_name_prefixed'], 'upvotes' => number_format($post['score']), 'comments' => number_format($post['num_comments'])];
            }
        }
    }
    
    // --- Fetch Trending Data ---
    $trending_subreddits = ['AskReddit', 'gaming', 'science', 'worldnews', 'movies', 'funny', 'todayilearned', 'pics', 'IAmA', 'wallstreetbets'];
    $trending_posts = [];
    foreach($trending_subreddits as $sub) {
        $sub_data = callRedditApi("https://oauth.reddit.com/r/{$sub}/top?t=day&limit=1", $access_token);
        if (isset($sub_data['data']['children'][0])) {
            $trending_posts[] = $sub_data['data']['children'][0]['data'];
        }
    }
    
    $trending_data_for_chart = ['labels' => [], 'scores' => []];
    if (!empty($trending_posts)) {
        usort($trending_posts, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        $top_trending = array_slice($trending_posts, 0, 5);
        
        $trending_data_for_chart['labels'] = array_column($top_trending, 'title');
        $trending_data_for_chart['scores'] = array_column($top_trending, 'score');
    }
    

    // --- Combine and return the data ---
    $dashboard_data = [
        'success' => true,
        'stats' => ['karma' => number_format($total_karma), 'upvoteRate' => 'N/A', 'followerGrowth' => 'N/A'],
        'topPosts' => $top_posts,
        'trendingData' => $trending_data_for_chart
    ];
    echo json_encode($dashboard_data);

} elseif ($action === 'submit_post') {
    $post_data = json_decode(file_get_contents('php://input'), true);

    $api_params = [
        'sr' => str_replace('r/', '', $post_data['subreddit']), // Ensure 'r/' is removed
        'title' => $post_data['title'],
        'text' => $post_data['content'],
        'kind' => 'self',
        'api_type' => 'json'
    ];

    $result = callRedditApi('https://oauth.reddit.com/api/submit', $access_token, 'POST', $api_params);

    if (isset($result['error'])) {
        http_response_code($result['http_code']);
        echo json_encode(['success' => false, 'error' => 'Reddit API Error', 'details' => $result['response']]);
    } elseif (isset($result['json']['errors']) && count($result['json']['errors']) > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Validation Error', 'details' => $result['json']['errors']]);
    } else {
        echo json_encode(['success' => true, 'data' => $result]);
    }
} else {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'No valid action specified.']);
}
?>

