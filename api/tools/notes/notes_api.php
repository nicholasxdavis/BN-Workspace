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
    if ($action === 'get_all_data') {
        // Get Folders
        $stmt_folders = $pdo->prepare("SELECT * FROM user_note_folders WHERE user_id = ? ORDER BY name ASC");
        $stmt_folders->execute([$user_id]);
        $folders = $stmt_folders->fetchAll();

        // Get Notes
        $stmt_notes = $pdo->prepare("SELECT id, folder_id, title, LEFT(content_text, 100) as snippet, updated_at FROM user_notes WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt_notes->execute([$user_id]);
        $notes = $stmt_notes->fetchAll();
        
        echo json_encode(['success' => true, 'folders' => $folders, 'notes' => $notes]);
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
        $folder_id = $data['folder_id'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO user_notes (user_id, folder_id, title, content, content_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $folder_id, $title, '', '']);
        $new_note_id = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'id' => $new_note_id]);
    }
    elseif ($action === 'update_note') {
        $note_id = $data['id'] ?? 0;
        $title = $data['title'] ?? 'Untitled Note';
        $content = $data['content'] ?? ''; // HTML content from Quill
        $content_text = $data['content_text'] ?? ''; // Plain text for snippets
        $folder_id = $data['folder_id'] ?? null;

        $stmt = $pdo->prepare("UPDATE user_notes SET title = ?, content = ?, content_text = ?, folder_id = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $content_text, $folder_id, $note_id, $user_id]);

        echo json_encode(['success' => true]);

    }
    elseif ($action === 'delete_note') {
        $note_id = $data['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM user_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'create_folder') {
        $name = $data['name'] ?? 'New Folder';
        $stmt = $pdo->prepare("INSERT INTO user_note_folders (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        $new_folder_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $new_folder_id, 'name' => $name]);
    }
    elseif ($action === 'delete_folder') {
        $folder_id = $data['id'] ?? 0;
        // Set notes in this folder to have null folder_id
        $stmt = $pdo->prepare("UPDATE user_notes SET folder_id = NULL WHERE folder_id = ? AND user_id = ?");
        $stmt->execute([$folder_id, $user_id]);
        // Delete the folder
        $stmt = $pdo->prepare("DELETE FROM user_note_folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folder_id, $user_id]);
        echo json_encode(['success' => true]);
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