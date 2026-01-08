<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Úspešné predplatné - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="success-message">
            <h1>✓ Ďakujeme za vašu podporu!</h1>
            <p>Vaše predplatné bolo úspešne aktivované.</p>
            <p>Teraz máte prístup k exkluzívnemu obsahu.</p>
            <a href="index.php" class="btn btn-primary">Späť na hlavnú stránku</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
