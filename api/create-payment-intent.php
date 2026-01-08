<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

// Include Stripe PHP library (install via composer: composer require stripe/stripe-php)
// For now, we'll use a simple cURL implementation

$input = json_decode(file_get_contents('php://input'), true);
$amount = floatval($input['amount'] ?? 0);
$message = $input['message'] ?? '';

if ($amount < 1) {
    echo json_encode(['error' => 'Minimálna suma je 1 EUR']);
    exit();
}

try {
    // Create Stripe payment intent
    $ch = curl_init('https://api.stripe.com/v1/payment_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'amount' => $amount * 100, // Convert to cents
        'currency' => 'eur',
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'message' => $message
        ]
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $paymentIntent = json_decode($response, true);

    if (isset($paymentIntent['error'])) {
        throw new Exception($paymentIntent['error']['message']);
    }

    // Save contribution to database
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO contributions (user_id, amount, currency, stripe_payment_intent_id, message, status)
        VALUES (?, ?, 'EUR', ?, ?, 'pending')
    ");
    $stmt->execute([$_SESSION['user_id'], $amount, $paymentIntent['id'], $message]);

    echo json_encode([
        'clientSecret' => $paymentIntent['client_secret']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
