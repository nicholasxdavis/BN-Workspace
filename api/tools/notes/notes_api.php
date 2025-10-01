<?php
// api/tools/notes/notes_api.php
session_start();
require_once '../../auth/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

try {
    if ($action === 'get_notes') {
        $stmt = $pdo->prepare("SELECT id, title, LEFT(content, 100) as snippet, updated_at FROM user_notes WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        $notes = $stmt->fetchAll();
        echo json_encode(['success' => true, 'notes' => $notes]);
    } 
    elseif ($action === 'get_note') {
        $note_id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM user_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        $note = $stmt->fetch();
        if ($note) {
            echo json_encode(['success' => true, 'note' => $note]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Note not found']);
        }
    }
    elseif ($action === 'create_note') {
        $title = $data['title'] ?? 'Untitled Note';
        $content = $data['content'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO user_notes (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $content]);
        $new_note_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT * FROM user_notes WHERE id = ?");
        $stmt->execute([$new_note_id]);
        $new_note = $stmt->fetch();

        echo json_encode(['success' => true, 'note' => $new_note]);
    }
    elseif ($action === 'update_note') {
        $note_id = $data['id'] ?? 0;
        $title = $data['title'] ?? 'Untitled Note';
        $content = $data['content'] ?? '';

        $stmt = $pdo->prepare("UPDATE user_notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $note_id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Note updated successfully']);

    }
    elseif ($action === 'delete_note') {
        $note_id = $data['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM user_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Note not found or you do not have permission to delete it']);
        }
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action specified.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>