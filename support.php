<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();

// Get subscription tiers
$stmt = $db->query("SELECT * FROM subscription_tiers WHERE is_active = TRUE ORDER BY price ASC");
$tiers = $stmt->fetchAll();

// Get user's current subscription
$currentSubscription = null;
if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT us.*, st.name as tier_name, st.price
        FROM user_subscriptions us
        JOIN subscription_tiers st ON us.tier_id = st.id
        WHERE us.user_id = ? AND us.status = 'active' AND us.current_period_end > NOW()
        ORDER BY us.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $currentSubscription = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podpora - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Podporiť tvorcov</h1>

        <?php if ($currentSubscription): ?>
            <div class="alert alert-success">
                Máte aktívne predplatné: <strong><?php echo e($currentSubscription['tier_name']); ?></strong>
                (<?php echo formatPrice($currentSubscription['price']); ?>/mesiac)
                <br>
                Platí do: <?php echo formatDate($currentSubscription['current_period_end']); ?>
            </div>
        <?php endif; ?>

        <!-- One-time contribution -->
        <div class="support-section">
            <h2>Jednorázový príspevok</h2>
            <p>Pošlite jednorázový príspevok v ľubovoľnej výške</p>

            <form id="one-time-form" class="payment-form">
                <div class="form-group">
                    <label for="amount">Suma (€)</label>
                    <input type="number" id="amount" name="amount" min="1" step="0.01" value="5.00" required>
                </div>

                <div class="form-group">
                    <label for="message">Správa (voliteľné)</label>
                    <textarea id="message" name="message" rows="3" placeholder="Napíšte správu pre tvorcov..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Prispieť</button>
            </form>
        </div>

        <!-- Subscription tiers -->
        <div class="support-section">
            <h2>Mesačné predplatné</h2>
            <div class="tiers-grid">
                <?php foreach ($tiers as $tier): ?>
                    <div class="tier-card">
                        <h3><?php echo e($tier['name']); ?></h3>
                        <div class="tier-price"><?php echo formatPrice($tier['price']); ?>/mesiac</div>
                        <p><?php echo e($tier['description']); ?></p>

                        <?php if ($tier['benefits']): ?>
                            <ul class="tier-benefits">
                                <?php foreach (explode("\n", $tier['benefits']) as $benefit): ?>
                                    <?php if (trim($benefit)): ?>
                                        <li><?php echo e($benefit); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($currentSubscription && $currentSubscription['tier_id'] == $tier['id']): ?>
                            <button class="btn btn-secondary" disabled>Aktívne predplatné</button>
                        <?php else: ?>
                            <button class="btn btn-primary subscribe-btn" data-tier-id="<?php echo $tier['id']; ?>">
                                Predplatiť
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');

        // One-time contribution
        document.getElementById('one-time-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const amount = document.getElementById('amount').value;
            const message = document.getElementById('message').value;

            try {
                const response = await fetch('api/create-payment-intent.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount, message })
                });

                const data = await response.json();

                if (data.error) {
                    alert('Chyba: ' + data.error);
                    return;
                }

                const result = await stripe.confirmCardPayment(data.clientSecret);

                if (result.error) {
                    alert('Chyba: ' + result.error.message);
                } else {
                    alert('Ďakujeme za vašu podporu!');
                    location.reload();
                }
            } catch (error) {
                alert('Nastala chyba: ' + error.message);
            }
        });

        // Subscription
        document.querySelectorAll('.subscribe-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const tierId = btn.getAttribute('data-tier-id');

                try {
                    const response = await fetch('api/create-subscription.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tier_id: tierId })
                    });

                    const data = await response.json();

                    if (data.error) {
                        alert('Chyba: ' + data.error);
                        return;
                    }

                    // Redirect to Stripe Checkout
                    window.location.href = data.url;
                } catch (error) {
                    alert('Nastala chyba: ' + error.message);
                }
            });
        });
    </script>
</body>
</html>
