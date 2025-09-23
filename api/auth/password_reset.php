<?php
require_once 'db_connect.php';

// --- SERVER-SIDE EMAIL FUNCTION USING EmailJS REST API ---
function sendPasswordResetEmail($email, $token, $customerName) {
    $service_id = 'service_c1ddi0x';
    $template_id = 'template_fakljk5';
    $user_id = 'vSfGjeaE52Lj_2lav'; // Public Key
    $accessToken = '0PQGh1CKPqu8REv5mnotS'; // Your Private Key

    $resetLink = "https://admin.blacnova.net/reset_password.html?token=" . $token; // Important: Replace with your actual domain and path

    $template_params = [
        'customerName' => $customerName,
        'email' => $email,
        'resetLink' => $resetLink
    ];

    $data = [
        'service_id' => $service_id,
        'template_id' => $template_id,
        'user_id' => $user_id,
        'template_params' => $template_params,
        'accessToken' => $accessToken
    ];

    $payload = json_encode($data);
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpcode == 200;
}


// Set the correct content type header
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($data['action'])) {
        throw new Exception('Action is required.');
    }

    // Action to request a password reset link
    if ($data['action'] === 'request_reset') {
        if (empty($data['email'])) {
            throw new Exception('Email is required.');
        }

        $email = $data['email'];

        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = new DateTime('now', new DateTimeZone('UTC'));
            $expires->add(new DateInterval('PT1H')); // Token expires in 1 hour
            $expires_str = $expires->format('Y-m-d H:i:s');

            $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del_stmt->execute([$email]);

            $ins_stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins_stmt->execute([$email, $token, $expires_str]);

            // Send the password reset email
            sendPasswordResetEmail($email, $token, $user['full_name']);

            echo json_encode(['success' => true, 'message' => 'If a user with that email exists, a reset link will be sent.']);
        } else {
             // To prevent user enumeration, we send a generic success response even if the email is not found.
             echo json_encode(['success' => true, 'message' => 'If a user with that email exists, a reset link will be sent.']);
        }

    // Action to perform the password update with a valid token
    } elseif ($data['action'] === 'perform_reset') {
        if (empty($data['token']) || empty($data['password'])) {
            throw new Exception('Token and new password are required.');
        }

        $token = $data['token'];
        $new_password = $data['password'];

        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();

        if (!$reset_request) {
            throw new Exception('Invalid or expired token.');
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $expires = new DateTime($reset_request['expires_at'], new DateTimeZone('UTC'));

        if ($now > $expires) {
            $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $del_stmt->execute([$token]);
            throw new Exception('Token has expired.');
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $update_stmt->execute([$password_hash, $reset_request['email']]);

        $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $del_stmt->execute([$token]);

        echo json_encode(['success' => true, 'message' => 'Password has been reset successfully.']);

    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>