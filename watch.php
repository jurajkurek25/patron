<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$videoId = intval($_GET['id'] ?? 0);

if (!$videoId) {
    header('Location: index.php');
    exit();
}

$db = getDB();

// Get video details
$stmt = $db->prepare("
    SELECT v.*, u.username, u.id as uploader_id, st.name as tier_name
    FROM videos v
    JOIN users u ON v.uploaded_by = u.id
    LEFT JOIN subscription_tiers st ON v.required_tier_id = st.id
    WHERE v.id = ?
");
$stmt->execute([$videoId]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: index.php');
    exit();
}

// Check access
$userId = $_SESSION['user_id'] ?? null;
$canAccess = canAccessVideo($userId, $video);

// Increment views if user can access
if ($canAccess && $userId) {
    $stmt = $db->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$videoId]);
}

// Get likes count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE video_id = ?");
$stmt->execute([$videoId]);
$likesCount = $stmt->fetch()['count'];

// Check if current user liked
$userLiked = false;
if ($userId) {
    $stmt = $db->prepare("SELECT id FROM likes WHERE video_id = ? AND user_id = ?");
    $stmt->execute([$videoId, $userId]);
    $userLiked = $stmt->fetch() !== false;
}

// Get comments
$stmt = $db->prepare("
    SELECT c.*, u.username
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.video_id = ? AND c.parent_comment_id IS NULL
    ORDER BY c.created_at DESC
");
$stmt->execute([$videoId]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($video['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="video-player-container">
            <?php if ($canAccess): ?>
                <video controls class="video-player">
                    <source src="<?php echo e($video['video_url']); ?>" type="video/mp4">
                    Váš prehliadač nepodporuje video element.
                </video>
            <?php else: ?>
                <div class="video-locked">
                    <div class="lock-message">
                        <span class="lock-icon-large">🔒</span>
                        <h2>Toto video je exkluzívne pre predplatiteľov</h2>
                        <p>Pre prístup k tomuto obsahu potrebujete predplatné: <strong><?php echo e($video['tier_name']); ?></strong></p>
                        <a href="support.php" class="btn btn-primary">Získať prístup</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="video-details">
                <h1><?php echo e($video['title']); ?></h1>

                <div class="video-meta-bar">
                    <div class="video-meta-left">
                        <span class="username">
                            <strong><?php echo e($video['username']); ?></strong>
                        </span>
                        <span><?php echo $video['views']; ?> zhliadnutí</span>
                        <span><?php echo formatDate($video['created_at']); ?></span>
                    </div>

                    <div class="video-meta-right">
                        <?php if ($userId): ?>
                            <button id="like-btn" class="btn-like <?php echo $userLiked ? 'liked' : ''; ?>" data-video-id="<?php echo $videoId; ?>">
                                ❤️ <span id="likes-count"><?php echo $likesCount; ?></span>
                            </button>
                        <?php else: ?>
                            <span class="like-display">❤️ <?php echo $likesCount; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($video['description']): ?>
                    <div class="video-description">
                        <p><?php echo nl2br(e($video['description'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Comments section -->
            <div class="comments-section">
                <h2>Komentáre (<?php echo count($comments); ?>)</h2>

                <?php if ($userId): ?>
                    <form id="comment-form" class="comment-form">
                        <textarea id="comment-text" placeholder="Pridajte komentár..." rows="3" required></textarea>
                        <button type="submit" class="btn btn-primary">Odoslať komentár</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt">
                        <a href="login.php">Prihláste sa</a> pre pridanie komentárov
                    </p>
                <?php endif; ?>

                <div id="comments-list" class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment" data-comment-id="<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <strong><?php echo e($comment['username']); ?></strong>
                                <span class="comment-time"><?php echo timeAgo($comment['created_at']); ?></span>
                            </div>
                            <div class="comment-text">
                                <?php echo nl2br(e($comment['comment_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/video.js"></script>
</body>
</html>
