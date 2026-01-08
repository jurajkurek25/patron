<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();

// Get all public videos
$stmt = $db->query("
    SELECT v.*, u.username, st.name as tier_name,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comments_count
    FROM videos v
    JOIN users u ON v.uploaded_by = u.id
    LEFT JOIN subscription_tiers st ON v.required_tier_id = st.id
    WHERE v.is_public = TRUE
    ORDER BY v.created_at DESC
");
$videos = $stmt->fetchAll();

// Check if user has active subscription
$hasSubscription = false;
$userTierId = 0;
if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT tier_id
        FROM user_subscriptions
        WHERE user_id = ? AND status = 'active' AND current_period_end > NOW()
        ORDER BY tier_id DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sub = $stmt->fetch();
    if ($sub) {
        $hasSubscription = true;
        $userTierId = $sub['tier_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Patreon Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="hero">
        <div class="container">
            <h1>Vitajte na našej Patreon platforme</h1>
            <p>Podporte tvorcov a získajte prístup k exkluzívnemu obsahu</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary btn-large">Zaregistrovať sa</a>
            <?php else: ?>
                <a href="support.php" class="btn btn-primary btn-large">Podporiť</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h2>Najnovšie videá</h2>

        <div class="videos-grid">
            <?php foreach ($videos as $video): ?>
                <?php
                $canAccess = !$video['required_tier_id'] || $userTierId >= $video['required_tier_id'];
                $isLocked = !$canAccess;
                ?>
                <div class="video-card <?php echo $isLocked ? 'locked' : ''; ?>">
                    <a href="watch.php?id=<?php echo $video['id']; ?>" class="video-thumbnail">
                        <?php if ($video['thumbnail_url']): ?>
                            <img src="<?php echo e($video['thumbnail_url']); ?>" alt="<?php echo e($video['title']); ?>">
                        <?php else: ?>
                            <div class="no-thumbnail">
                                <span>🎬</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($isLocked): ?>
                            <div class="lock-overlay">
                                <span class="lock-icon">🔒</span>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="video-info">
                        <h3>
                            <a href="watch.php?id=<?php echo $video['id']; ?>">
                                <?php echo e($video['title']); ?>
                            </a>
                        </h3>
                        <p class="video-meta">
                            <span class="username"><?php echo e($video['username']); ?></span>
                            <span class="views"><?php echo $video['views']; ?> zhliadnutí</span>
                        </p>
                        <p class="video-meta">
                            <span>❤️ <?php echo $video['likes_count']; ?></span>
                            <span>💬 <?php echo $video['comments_count']; ?></span>
                            <?php if ($video['tier_name']): ?>
                                <span class="tier-badge"><?php echo e($video['tier_name']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($videos)): ?>
            <p class="no-content">Zatiaľ nie sú žiadne videá.</p>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
