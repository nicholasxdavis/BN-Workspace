<?php
// api/auth/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Database connection settings
$host = 'roscwoco0sc8w08kwsko8ko8';
$db = 'default'; // Using the default database name
$user = 'mariadb';
$pass = 'JswmqQok4swQf1JDKQD1WE311UPXBBE6NYJv6jRSP91dbkZDYj5sMc5sehC1LQTu';
$charset = 'utf8mb4';
$port = 3306;

// Create connection
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check for existing session
if (isset($_COOKIE['session_token'])) {
    try {
        $stmt = $pdo->prepare('SELECT u.* FROM users u JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = ? AND s.expires_at > NOW()');
        $stmt->execute([$_COOKIE['session_token']]);
        $user = $stmt->fetch();

        if ($user) {
            unset($user['password_hash']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
            exit;
        }
    } catch (\PDOException $e) {
        // Fail silently and proceed to login form
    }
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$rememberMe = $data['rememberMe'] ?? false;

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Check user credentials
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Successful login
        unset($user['password_hash']); // Don't send password back

        // Create session
        $session_token = bin2hex(random_bytes(32));
        $remember_token = null;
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if ($rememberMe) {
            $remember_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        // Store session in the database
        $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, remember_token, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $session_token, $remember_token, $expires_at]);

        // Set session cookie
        $cookie_options = [
            'expires' => $rememberMe ? time() + (86400 * 30) : 0, // 30 days or session
            'path' => '/',
            'domain' => '.blacnova.net', // Set your domain here to share cookie across subdomains
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        setcookie('session_token', $session_token, $cookie_options);

        if ($rememberMe) {
            setcookie('remember_token', $remember_token, $cookie_options);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        // Invalid credentials
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
