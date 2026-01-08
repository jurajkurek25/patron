<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM videos");
$totalVideos = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM user_subscriptions WHERE status = 'active'");
$activeSubscriptions = $stmt->fetch()['count'];

$stmt = $db->query("SELECT SUM(amount) as total FROM contributions WHERE status = 'succeeded'");
$totalContributions = $stmt->fetch()['total'] ?? 0;

// Get recent videos
$stmt = $db->query("
    SELECT v.*, u.username
    FROM videos v
    JOIN users u ON v.uploaded_by = u.id
    ORDER BY v.created_at DESC
    LIMIT 10
");
$recentVideos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Admin Panel</h1>

        <div class="admin-stats">
            <div class="stat-card">
                <h3>Používatelia</h3>
                <div class="stat-number"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <h3>Videá</h3>
                <div class="stat-number"><?php echo $totalVideos; ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktívne predplatné</h3>
                <div class="stat-number"><?php echo $activeSubscriptions; ?></div>
            </div>
            <div class="stat-card">
                <h3>Celkové príspevky</h3>
                <div class="stat-number"><?php echo formatPrice($totalContributions, 'EUR'); ?></div>
            </div>
        </div>

        <div class="admin-actions">
            <a href="upload-video.php" class="btn btn-primary">Nahrať video</a>
            <a href="manage-videos.php" class="btn btn-secondary">Spravovať videá</a>
            <a href="manage-tiers.php" class="btn btn-secondary">Spravovať predplatné</a>
        </div>

        <h2>Posledné videá</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Názov</th>
                    <th>Nahral</th>
                    <th>Zhliadnutia</th>
                    <th>Dátum</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentVideos as $video): ?>
                    <tr>
                        <td><?php echo $video['id']; ?></td>
                        <td><?php echo e($video['title']); ?></td>
                        <td><?php echo e($video['username']); ?></td>
                        <td><?php echo $video['views']; ?></td>
                        <td><?php echo formatDate($video['created_at']); ?></td>
                        <td>
                            <a href="edit-video.php?id=<?php echo $video['id']; ?>" class="btn-small">Upraviť</a>
                            <a href="../watch.php?id=<?php echo $video['id']; ?>" class="btn-small">Pozrieť</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
