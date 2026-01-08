<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$videoId = intval($input['video_id'] ?? 0);

if (!$videoId) {
    echo json_encode(['error' => 'Invalid video ID']);
    exit();
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];

    // Check if already liked
    $stmt = $db->prepare("SELECT id FROM likes WHERE video_id = ? AND user_id = ?");
    $stmt->execute([$videoId, $userId]);
    $existingLike = $stmt->fetch();

    if ($existingLike) {
        // Unlike
        $stmt = $db->prepare("DELETE FROM likes WHERE video_id = ? AND user_id = ?");
        $stmt->execute([$videoId, $userId]);
        $liked = false;
    } else {
        // Like
        $stmt = $db->prepare("INSERT INTO likes (video_id, user_id) VALUES (?, ?)");
        $stmt->execute([$videoId, $userId]);
        $liked = true;
    }

    // Get updated likes count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE video_id = ?");
    $stmt->execute([$videoId]);
    $likesCount = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $likesCount
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
