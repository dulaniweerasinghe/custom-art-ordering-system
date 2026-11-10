<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php'; // Make sure to install: composer require stripe/stripe-php

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['order_id']) || !isset($data['amount'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$order_id = $data['order_id'];
$amount = $data['amount'];
$email = $data['email'] ?? '';

// Verify order belongs to current user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ? AND payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    echo json_encode(['error' => 'Order not found or already paid']);
    exit;
}

try {
    // Create PaymentIntent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'usd',
        'receipt_email' => $email,
        'metadata' => [
            'order_id' => $order_id,
            'order_number' => $order['order_number'],
            'customer_id' => $_SESSION['user_id']
        ]
    ]);
    
    // Store payment intent ID in order
    $stmt = $pdo->prepare("UPDATE orders SET stripe_payment_intent = ? WHERE id = ?");
    $stmt->execute([$paymentIntent->id, $order_id]);
    
    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>