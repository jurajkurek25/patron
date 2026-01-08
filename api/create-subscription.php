<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$tierId = intval($input['tier_id'] ?? 0);

if (!$tierId) {
    echo json_encode(['error' => 'Neplatný tier']);
    exit();
}

try {
    $db = getDB();

    // Get tier details
    $stmt = $db->prepare("SELECT * FROM subscription_tiers WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$tierId]);
    $tier = $stmt->fetch();

    if (!$tier) {
        throw new Exception('Tier nebol nájdený');
    }

    // Create Stripe Checkout Session
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $params = [
        'mode' => 'subscription',
        'success_url' => SITE_URL . '/subscription-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => SITE_URL . '/support.php',
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $tier['name'],
                        'description' => $tier['description']
                    ],
                    'unit_amount' => $tier['price'] * 100,
                    'recurring' => [
                        'interval' => 'month'
                    ]
                ],
                'quantity' => 1
            ]
        ],
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'tier_id' => $tierId
        ]
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    $response = curl_exec($ch);
    curl_close($ch);

    $session = json_decode($response, true);

    if (isset($session['error'])) {
        throw new Exception($session['error']['message']);
    }

    echo json_encode([
        'url' => $session['url']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
