<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $required_tier_id = $_POST['required_tier_id'] ?? null;

    if (empty($title)) {
        $error = 'Názov je povinný';
    } elseif (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Prosím vyberte video súbor';
    } else {
        try {
            // Upload video file
            $videoUrl = uploadFile($_FILES['video']);

            // Upload thumbnail if provided
            $thumbnailUrl = null;
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $thumbnailUrl = uploadFile($_FILES['thumbnail'], ['image/jpeg', 'image/png', 'image/jpg']);
            }

            // Insert into database
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO videos (title, description, video_url, thumbnail_url, is_public, required_tier_id, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $description,
                $videoUrl,
                $thumbnailUrl,
                $is_public,
                $required_tier_id ?: null,
                $_SESSION['user_id']
            ]);

            $success = 'Video bolo úspešne nahrané!';
        } catch (Exception $e) {
            $error = 'Chyba pri nahrávaní: ' . $e->getMessage();
        }
    }
}

// Get subscription tiers
$db = getDB();
$stmt = $db->query("SELECT * FROM subscription_tiers WHERE is_active = TRUE ORDER BY price ASC");
$tiers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nahrať video - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Nahrať nové video</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
                <a href="manage-videos.php">Zobraziť všetky videá</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="title">Názov videa *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Popis</label>
                <textarea id="description" name="description" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="video">Video súbor * (max 500MB)</label>
                <input type="file" id="video" name="video" accept="video/*" required>
            </div>

            <div class="form-group">
                <label for="thumbnail">Náhľadový obrázok</label>
                <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_public" checked>
                    Verejné video
                </label>
            </div>

            <div class="form-group">
                <label for="required_tier_id">Vyžadované predplatné</label>
                <select id="required_tier_id" name="required_tier_id">
                    <option value="">Žiadne (prístupné pre všetkých)</option>
                    <?php foreach ($tiers as $tier): ?>
                        <option value="<?php echo $tier['id']; ?>">
                            <?php echo e($tier['name']); ?> (<?php echo formatPrice($tier['price'], 'EUR'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Nahrať video</button>
            <a href="index.php" class="btn btn-secondary">Zrušiť</a>
        </form>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
