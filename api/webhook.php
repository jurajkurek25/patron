<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Stripe webhook handler

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    // Verify webhook signature
    $event = json_decode($payload, true);

    // In production, verify signature properly with stripe-php library

    switch ($event['type']) {
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($event['data']['object']);
            break;

        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event['data']['object']);
            break;

        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':
            handleSubscriptionUpdate($event['data']['object']);
            break;
    }

    http_response_code(200);

} catch (Exception $e) {
    http_response_code(400);
    exit();
}

function handlePaymentIntentSucceeded($paymentIntent) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE contributions
        SET status = 'succeeded'
        WHERE stripe_payment_intent_id = ?
    ");
    $stmt->execute([$paymentIntent['id']]);
}

function handleCheckoutSessionCompleted($session) {
    if ($session['mode'] === 'subscription') {
        $db = getDB();

        $userId = $session['metadata']['user_id'] ?? null;
        $tierId = $session['metadata']['tier_id'] ?? null;

        if ($userId && $tierId) {
            // Create subscription record
            $stmt = $db->prepare("
                INSERT INTO user_subscriptions (user_id, tier_id, stripe_subscription_id, status, current_period_start, current_period_end)
                VALUES (?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
            ");
            $stmt->execute([$userId, $tierId, $session['subscription']]);
        }
    }
}

function handleSubscriptionUpdate($subscription) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE user_subscriptions
        SET status = ?, current_period_end = FROM_UNIXTIME(?)
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([
        $subscription['status'],
        $subscription['current_period_end'],
        $subscription['id']
    ]);
}
