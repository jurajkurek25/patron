<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$videoId = intval($_GET['video_id'] ?? 0);

if (!$videoId) {
    echo json_encode(['error' => 'Invalid video ID']);
    exit();
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.video_id = ? AND c.parent_comment_id IS NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$videoId]);
    $comments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
