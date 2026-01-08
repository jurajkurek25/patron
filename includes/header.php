<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/index.php"><?php echo SITE_NAME; ?></a>
            </div>

            <nav class="main-nav">
                <a href="/index.php">Domov</a>
                <a href="/support.php">Podpora</a>
                <?php if (isAdmin()): ?>
                    <a href="/admin/index.php">Admin</a>
                <?php endif; ?>
            </nav>

            <div class="user-menu">
                <?php if (isLoggedIn()): ?>
                    <span>Ahoj, <?php echo e($_SESSION['username']); ?>!</span>
                    <a href="/logout.php" class="btn btn-small">Odhlásiť sa</a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-small">Prihlásiť sa</a>
                    <a href="/register.php" class="btn btn-primary btn-small">Registrovať</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
