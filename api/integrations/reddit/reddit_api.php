<?php
// api/integrations/reddit/reddit_api.php
session_start();
require_once '../../auth/db_connect.php';
require_once '../../integrations/config.php';
require_once '../../integrations/encryption.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

// --- Helper function to make authenticated requests to the Reddit API ---
function makeRedditApiRequest($endpoint, $access_token, $method = 'GET', $post_fields = []) {
    $api_url = 'https://oauth.reddit.com' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: bearer ' . $access_token,
        'User-Agent: BlacnovaWorkspace/1.0'
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        return ['error' => true, 'code' => $http_code, 'message' => json_decode($response, true)];
    }

    return json_decode($response, true);
}


// --- Main Logic ---
$action = $_GET['action'] ?? '';

try {
    // --- Fetch the user's encrypted access token from the database ---
    $stmt = $pdo->prepare("SELECT access_token FROM user_integrations WHERE user_id = ? AND provider = 'reddit'");
    $stmt->execute([$_SESSION['user_id']]);
    $integration = $stmt->fetch();

    if (!$integration) {
        throw new Exception("Reddit integration not found for this user.", 404);
    }

    $access_token = decrypt_token($integration['access_token']);

    // --- Action: Fetch all data for the main dashboard ---
    if ($action === 'dashboard_data') {
        // 1. Get User Account Info
        $me_data = makeRedditApiRequest('/api/v1/me', $access_token);
        if (isset($me_data['error'])) throw new Exception('Failed to fetch user data from Reddit.', $me_data['code']);

        $username = $me_data['name'];
        $encoded_username = urlencode($username);

        // 2. Get User's Top 5 Posts (last month)
        $posts_data = makeRedditApiRequest("/user/{$encoded_username}/submitted?sort=top&t=month&limit=5", $access_token);
        if (isset($posts_data['error'])) throw new Exception('Failed to fetch user posts.', $posts_data['code']);
        $topPostsTitle = 'Your Top 5 Posts (This Month)';

        // If no posts this month, get all-time top posts as a fallback
        if (empty($posts_data['data']['children'])) {
            $posts_data = makeRedditApiRequest("/user/{$encoded_username}/submitted?sort=top&t=all&limit=5", $access_token);
            if (isset($posts_data['error'])) throw new Exception('Failed to fetch all-time user posts.', $posts_data['code']);
            $topPostsTitle = 'Your Top 5 Posts (All Time)';
        }
        
        $topPosts = [];
        $total_upvote_ratio = 0;
        $total_comments = 0;
        $post_count = 0;

        if (isset($posts_data['data']['children']) && is_array($posts_data['data']['children'])) {
            foreach ($posts_data['data']['children'] as $post) {
                if (isset($post['data'])) {
                    $post_count++;
                    $total_upvote_ratio += $post['data']['upvote_ratio'] ?? 0;
                    $total_comments += $post['data']['num_comments'] ?? 0;
                    
                    $topPosts[] = [
                        'id' => $post['data']['id'] ?? 'N/A',
                        'title' => $post['data']['title'] ?? 'No Title',
                        'subreddit' => $post['data']['subreddit_name_prefixed'] ?? 'N/A',
                        'upvotes' => isset($post['data']['score']) ? number_format($post['data']['score']) : '0',
                        'comments' => isset($post['data']['num_comments']) ? number_format($post['data']['num_comments']) : '0'
                    ];
                }
            }
        }

        // Calculate custom stats
        $avg_upvote_rate = ($post_count > 0) ? round(($total_upvote_ratio / $post_count) * 100, 1) . '%' : 'N/A';
        $avg_comments = ($post_count > 0) ? round($total_comments / $post_count, 1) : 'N/A';

        $stats = [
            'karma' => number_format($me_data['total_karma']),
            'averageUpvoteRate' => $avg_upvote_rate,
            'averageComments' => $avg_comments
        ];

        // 3. Get Custom Trending Data
        $trending_subreddits = ['funny', 'AskReddit', 'gaming', 'aww', 'Music', 'pics', 'science', 'worldnews', 'todayilearned', 'movies', 'memes', 'news', 'space'];
        $all_trending_posts = [];
        foreach ($trending_subreddits as $sub) {
            $trending_data = makeRedditApiRequest("/r/{$sub}/top?t=day&limit=5", $access_token);
            if (isset($trending_data['data']['children'])) {
                $all_trending_posts = array_merge($all_trending_posts, $trending_data['data']['children']);
            }
        }
        
        usort($all_trending_posts, fn($a, $b) => $b['data']['score'] <=> $a['data']['score']);
        $top_trending = array_slice($all_trending_posts, 0, 7);

        $trendingData = [
            'labels' => array_map(fn($post) => $post['data']['title'], $top_trending),
            'scores' => array_map(fn($post) => $post['data']['score'], $top_trending)
        ];

        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'topPosts' => $topPosts,
            'topPostsTitle' => $topPostsTitle,
            'trendingData' => $trendingData
        ]);
    }
    // --- Action: Submit a new post ---
    elseif ($action === 'submit_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $post_fields = [
            'sr' => $input['subreddit'],
            'title' => $input['title'],
            'text' => $input['content'],
            'kind' => 'self',
            'api_type' => 'json'
        ];

        $result = makeRedditApiRequest('/api/submit', $access_token, 'POST', $post_fields);

        if (isset($result['error'])) {
             throw new Exception('Reddit API error: ' . ($result['message']['json']['errors'][0][1] ?? 'Unknown error'), $result['code']);
        }

        if (isset($result['json']['errors']) && count($result['json']['errors']) > 0) {
             throw new Exception('Failed to post: ' . $result['json']['errors'][0][1]);
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
    }
    else {
        throw new Exception("Invalid action specified.", 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
