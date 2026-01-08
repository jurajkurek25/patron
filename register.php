<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($username) || empty($password)) {
        $error = 'Prosím vyplňte všetky povinné polia';
    } elseif ($password !== $confirm_password) {
        $error = 'Heslá sa nezhodujú';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo musí mať aspoň 6 znakov';
    } else {
        $db = getDB();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email už existuje';
        } else {
            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Používateľské meno už existuje';
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (email, username, password, full_name)
                    VALUES (?, ?, ?, ?)
                ");

                if ($stmt->execute([$email, $username, $hashed_password, $full_name])) {
                    $success = 'Účet bol úspešne vytvorený! Môžete sa prihlásiť.';
                } else {
                    $error = 'Chyba pri vytváraní účtu';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrácia - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Registrácia</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="username">Používateľské meno *</label>
                    <input type="text" id="username" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="full_name">Celé meno</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Heslo *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Potvrdiť heslo *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Registrovať sa</button>
            </form>

            <p class="auth-link">
                Už máte účet? <a href="login.php">Prihláste sa</a>
            </p>
        </div>
    </div>
</body>
</html>
