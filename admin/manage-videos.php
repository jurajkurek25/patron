<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Video bolo odstránené');
    header('Location: manage-videos.php');
    exit();
}

// Get all videos
$stmt = $db->query("
    SELECT v.*, u.username, st.name as tier_name
    FROM videos v
    JOIN users u ON v.uploaded_by = u.id
    LEFT JOIN subscription_tiers st ON v.required_tier_id = st.id
    ORDER BY v.created_at DESC
");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spravovať videá - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Spravovať videá</h1>

        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <a href="upload-video.php" class="btn btn-primary">Nahrať nové video</a>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Náhľad</th>
                    <th>Názov</th>
                    <th>Nahral</th>
                    <th>Tier</th>
                    <th>Zhliadnutia</th>
                    <th>Dátum</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos as $video): ?>
                    <tr>
                        <td><?php echo $video['id']; ?></td>
                        <td>
                            <?php if ($video['thumbnail_url']): ?>
                                <img src="../<?php echo e($video['thumbnail_url']); ?>" alt="" class="video-thumbnail-small">
                            <?php else: ?>
                                <div class="no-thumbnail">-</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($video['title']); ?></td>
                        <td><?php echo e($video['username']); ?></td>
                        <td><?php echo $video['tier_name'] ? e($video['tier_name']) : 'Verejné'; ?></td>
                        <td><?php echo $video['views']; ?></td>
                        <td><?php echo formatDate($video['created_at']); ?></td>
                        <td>
                            <a href="edit-video.php?id=<?php echo $video['id']; ?>" class="btn-small">Upraviť</a>
                            <a href="../watch.php?id=<?php echo $video['id']; ?>" class="btn-small">Pozrieť</a>
                            <a href="?delete=<?php echo $video['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Naozaj chcete odstrániť toto video?')">Odstrániť</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
