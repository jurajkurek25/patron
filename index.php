<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();

// Get creator profile
$stmt = $db->query("
    SELECT cp.*, u.username
    FROM creator_profile cp
    JOIN users u ON cp.user_id = u.id
    LIMIT 1
");
$creator = $stmt->fetch();

// Get active tier count
$stmt = $db->query("SELECT COUNT(*) as count FROM subscription_tiers WHERE is_active = TRUE");
$tierCount = $stmt->fetch()['count'];

// Get subscriber count
$stmt = $db->query("
    SELECT COUNT(DISTINCT user_id) as count
    FROM user_subscriptions
    WHERE status = 'active' AND current_period_end > NOW()
");
$subscriberCount = $stmt->fetch()['count'];

// Get video categories
$stmt = $db->query("SELECT * FROM video_categories ORDER BY id");
$categories = $stmt->fetchAll();

// Get selected category
$selectedCategory = $_GET['category'] ?? 'all';

// Get videos
if ($selectedCategory === 'all') {
    $stmt = $db->query("
        SELECT v.*,
               vc.name as category_name,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comments_count
        FROM videos v
        LEFT JOIN video_categories vc ON v.category_id = vc.id
        WHERE v.is_public = TRUE
        ORDER BY v.created_at DESC
    ");
} else {
    $stmt = $db->prepare("
        SELECT v.*,
               vc.name as category_name,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comments_count
        FROM videos v
        LEFT JOIN video_categories vc ON v.category_id = vc.id
        WHERE v.is_public = TRUE AND vc.slug = ?
        ORDER BY v.created_at DESC
    ");
    $stmt->execute([$selectedCategory]);
}
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
    <title><?php echo e($creator['display_name'] ?? 'OSKI'); ?> - HeroHero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="modern-layout">
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="container">
            <div class="nav-content">
                <div class="nav-logo">
                    <a href="/"><?php echo e($creator['display_name'] ?? 'OSKI'); ?></a>
                    <span class="verified-badge">✓</span>
                </div>
                <div class="nav-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="/admin/" class="btn-nav">Admin</a>
                        <?php endif; ?>
                        <span class="user-greeting"><?php echo e($_SESSION['username']); ?></span>
                        <a href="/logout.php" class="btn-nav">Odhlásiť sa</a>
                    <?php else: ?>
                        <a href="/login.php" class="btn-nav">Prihlásiť sa</a>
                        <a href="/register.php" class="btn-primary-small">Registrovať</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Creator Profile Section -->
    <div class="creator-hero">
        <div class="container">
            <div class="creator-profile">
                <div class="creator-avatar">
                    <?php if ($creator && $creator['avatar_url']): ?>
                        <img src="<?php echo e($creator['avatar_url']); ?>" alt="<?php echo e($creator['display_name']); ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($creator['display_name'] ?? 'O', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="creator-info">
                    <h1>
                        <?php echo e($creator['display_name'] ?? 'OSKI'); ?>
                        <span class="verified-badge-large">✓</span>
                    </h1>
                    <?php if ($creator && $creator['bio']): ?>
                        <p class="creator-bio"><?php echo nl2br(e($creator['bio'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="creator-stats">
                <div class="stat-item">
                    <span class="stat-label">Odoberá</span>
                    <span class="stat-value"><?php echo $tierCount; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Odberateľia</span>
                    <span class="stat-value"><?php echo number_format($subscriberCount); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="category-tabs">
        <div class="container">
            <div class="tabs-wrapper">
                <a href="?category=all" class="tab <?php echo $selectedCategory === 'all' ? 'active' : ''; ?>">
                    Všetky videá
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo e($category['slug']); ?>"
                       class="tab <?php echo $selectedCategory === $category['slug'] ? 'active' : ''; ?>">
                        <?php echo e($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Subscription CTA -->
    <?php if (!$hasSubscription): ?>
        <div class="subscription-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>Začni odoberať tohto tvorcu a odomkni si všetky jeho príspevky</h2>
                    <a href="/support.php" class="btn-subscribe">Odoberať za 6 € mesačne</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Videos Grid -->
    <div class="main-content">
        <div class="container">
            <div class="videos-grid-modern">
                <?php foreach ($videos as $video): ?>
                    <?php
                    $canAccess = !$video['required_tier_id'] || $userTierId >= $video['required_tier_id'];
                    $isLocked = !$canAccess;
                    ?>
                    <div class="video-card-modern <?php echo $isLocked ? 'locked' : ''; ?>">
                        <a href="watch.php?id=<?php echo $video['id']; ?>" class="video-thumbnail-modern">
                            <?php if ($video['thumbnail_url']): ?>
                                <img src="<?php echo e($video['thumbnail_url']); ?>" alt="<?php echo e($video['title']); ?>">
                            <?php else: ?>
                                <div class="thumbnail-placeholder"></div>
                            <?php endif; ?>

                            <?php if ($video['duration']): ?>
                                <span class="video-duration"><?php echo gmdate("i:s", $video['duration']); ?></span>
                            <?php endif; ?>

                            <?php if ($isLocked): ?>
                                <div class="lock-overlay-modern">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="white"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="video-info-modern">
                            <h3 class="video-title-modern">
                                <a href="watch.php?id=<?php echo $video['id']; ?>">
                                    <?php echo e($video['title']); ?>
                                </a>
                            </h3>
                            <div class="video-meta-modern">
                                <span class="video-meta-item">
                                    💬 <?php echo $video['comments_count']; ?>
                                </span>
                                <span class="video-meta-date">
                                    <?php echo date('j. F Y', strtotime($video['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($videos)): ?>
                <div class="no-videos">
                    <p>Žiadne videá v tejto kategórii.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
