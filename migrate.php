<?php
// Database migration script
// Run this once to add new tables for creator profile redesign

require_once 'config/config.php';
require_once 'config/database.php';

// Require admin
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die('Access denied. Admin only.');
}

$db = getDB();
$errors = [];
$success = [];

try {
    // 1. Create creator_profile table
    $db->exec("
        CREATE TABLE IF NOT EXISTS creator_profile (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            display_name VARCHAR(255) NOT NULL,
            bio TEXT,
            avatar_url VARCHAR(500),
            cover_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $success[] = "✓ Created creator_profile table";

    // 2. Create video_categories table
    $db->exec("
        CREATE TABLE IF NOT EXISTS video_categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $success[] = "✓ Created video_categories table";

    // 3. Add category_id column to videos table
    try {
        $db->exec("ALTER TABLE videos ADD COLUMN category_id INT AFTER thumbnail_url");
        $success[] = "✓ Added category_id column to videos table";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { // Column already exists
            $success[] = "- category_id column already exists";
        } else {
            throw $e;
        }
    }

    // 4. Add foreign key for category_id
    try {
        $db->exec("ALTER TABLE videos ADD FOREIGN KEY (category_id) REFERENCES video_categories(id) ON DELETE SET NULL");
        $success[] = "✓ Added foreign key for category_id";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000' || $e->getCode() == 'HY000') {
            $success[] = "- Foreign key already exists";
        } else {
            throw $e;
        }
    }

    // 5. Add index for category_id
    try {
        $db->exec("ALTER TABLE videos ADD INDEX idx_category (category_id)");
        $success[] = "✓ Added index for category_id";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000') {
            $success[] = "- Index already exists";
        } else {
            throw $e;
        }
    }

    // 6. Insert creator profile
    $stmt = $db->prepare("
        INSERT IGNORE INTO creator_profile (user_id, display_name, bio)
        SELECT id, COALESCE(full_name, username), 'Vítajte na mojom HeroHero\n- exkluzívny obsah\n- obsah DOPREDU...'
        FROM users
        WHERE is_admin = TRUE
        LIMIT 1
    ");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $success[] = "✓ Created creator profile";
    } else {
        $success[] = "- Creator profile already exists";
    }

    // 7. Insert video categories
    $stmt = $db->prepare("INSERT IGNORE INTO video_categories (name, slug) VALUES (?, ?)");
    $categories = [
        ['JOVI A BERGI', 'jovi-a-bergi'],
        ['SNAMI epizódy', 'snami-epizody'],
        ['NEZOSTRIHANÉ epizódy', 'nezostihane-epizody'],
        ['POLITIKA', 'politika']
    ];

    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    $success[] = "✓ Created video categories";

    // 8. Update currency to EUR
    $stmt = $db->exec("UPDATE subscription_tiers SET currency = 'EUR' WHERE currency != 'EUR'");
    $success[] = "✓ Updated currency to EUR";

    // 9. Add basic tier if needed
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscription_tiers WHERE price = 6.00");
    if ($stmt->fetch()['count'] == 0) {
        $db->exec("
            INSERT INTO subscription_tiers (name, description, price, currency, benefits, is_active) VALUES
            ('Základný', 'Prístup k exkluzívnemu obsahu', 6.00, 'EUR', 'Prístup k exkluzívnym videám\nPodpora tvorcu\nŠpeciálny odznak', TRUE)
        ");
        $success[] = "✓ Created basic subscription tier (6 EUR)";
    } else {
        $success[] = "- Basic tier already exists";
    }

} catch (PDOException $e) {
    $errors[] = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f0f0f;
            color: #fff;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 { color: #6366f1; }
        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            color: #fca5a5;
        }
        .message {
            padding: 0.5rem 0;
        }
        a {
            color: #6366f1;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid #6366f1;
            border-radius: 8px;
            display: inline-block;
            margin-top: 1rem;
        }
        a:hover {
            background: rgba(99, 102, 241, 0.2);
        }
    </style>
</head>
<body>
    <h1>🚀 Database Migration</h1>

    <?php if (!empty($success)): ?>
        <div class="success">
            <h2>✓ Migration Successful!</h2>
            <?php foreach ($success as $msg): ?>
                <div class="message"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <h2>✗ Migration Errors</h2>
            <?php foreach ($errors as $err): ?>
                <div class="message"><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p><strong>Next steps:</strong></p>
    <ul>
        <li>Visit your homepage to see the new design</li>
        <li>Check Admin panel to upload videos</li>
        <li>Delete this migrate.php file for security</li>
    </ul>

    <a href="index.php">← Back to Homepage</a>
    <a href="admin/">Admin Panel</a>
</body>
</html>
