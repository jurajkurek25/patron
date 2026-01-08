<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$videoId = intval($input['video_id'] ?? 0);
$commentText = trim($input['comment_text'] ?? '');

if (!$videoId) {
    echo json_encode(['error' => 'Invalid video ID']);
    exit();
}

if (empty($commentText)) {
    echo json_encode(['error' => 'Komentár nemôže byť prázdny']);
    exit();
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];

    // Insert comment
    $stmt = $db->prepare("
        INSERT INTO comments (video_id, user_id, comment_text)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$videoId, $userId, $commentText]);

    $commentId = $db->lastInsertId();

    // Get comment with username
    $stmt = $db->prepare("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'comment' => $comment
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
