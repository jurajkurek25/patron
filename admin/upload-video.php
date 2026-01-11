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
    $category_id = $_POST['category_id'] ?? null;
    $duration = intval($_POST['duration'] ?? 0);
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
                INSERT INTO videos (title, description, video_url, thumbnail_url, category_id, duration, is_public, required_tier_id, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $description,
                $videoUrl,
                $thumbnailUrl,
                $category_id ?: null,
                $duration,
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

// Get categories
$db = getDB();
$stmt = $db->query("SELECT * FROM video_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get subscription tiers
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
<body class="modern-layout">
    <nav class="top-nav">
        <div class="container">
            <div class="nav-content">
                <div class="nav-logo">
                    <a href="/">OSKI Admin</a>
                </div>
                <div class="nav-actions">
                    <a href="../index.php" class="btn-nav">Hlavná stránka</a>
                    <a href="index.php" class="btn-nav">Dashboard</a>
                    <a href="../logout.php" class="btn-nav">Odhlásiť sa</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h1>Nahrať nové video</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo e($success); ?>
                    <a href="manage-videos.php" style="color: inherit; text-decoration: underline;">Zobraziť všetky videá</a>
                </div>
            <?php endif; ?>

            <div class="upload-form-container">
                <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="title">Názov videa *</label>
                        <input type="text" id="title" name="title" required value="<?php echo e($_POST['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Popis</label>
                        <textarea id="description" name="description" rows="5"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategória</label>
                        <select id="category_id" name="category_id">
                            <option value="">Žiadna kategória</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <label for="duration">Dĺžka videa (v sekundách)</label>
                        <input type="number" id="duration" name="duration" min="0" value="<?php echo e($_POST['duration'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_public" <?php echo ($_POST['is_public'] ?? true) ? 'checked' : ''; ?>>
                            Verejné video
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="required_tier_id">Vyžadované predplatné</label>
                        <select id="required_tier_id" name="required_tier_id">
                            <option value="">Žiadne (prístupné pre všetkých)</option>
                            <?php foreach ($tiers as $tier): ?>
                                <option value="<?php echo $tier['id']; ?>" <?php echo ($_POST['required_tier_id'] ?? '') == $tier['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($tier['name']); ?> (<?php echo formatPrice($tier['price'], $tier['currency']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-block">Nahrať video</button>
                        <a href="index.php" class="btn-secondary-block">Zrušiť</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .upload-form-container {
            max-width: 600px;
            margin: 2rem 0;
        }

        .upload-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .form-group input[type="file"] {
            padding: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-secondary-block {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-secondary-block:hover {
            background: var(--bg-dark);
        }
    </style>
</body>
</html>
