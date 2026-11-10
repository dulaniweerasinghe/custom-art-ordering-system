<?php 
$pageTitle = 'Payment Successful';
require_once '../includes/header.php';

checkAuth();

if(!isset($_GET['order_id']) || !isset($_GET['payment_intent'])) {
    redirect('/customer/dashboard.php');
}

$order_id = intval($_GET['order_id']);
$payment_intent = sanitize($_GET['payment_intent']);

// Update order status
$stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', transaction_id = ?, status = 'in_progress' WHERE id = ? AND customer_id = ?");
$stmt->execute([$payment_intent, $order_id, $_SESSION['user_id']]);

if($stmt->rowCount() > 0) {
    // Send notifications
    sendNotification($_SESSION['user_id'], 'Payment Successful', 'Your payment has been received and your order is now in progress.', $order_id);
    sendNotification(1, 'Payment Received', 'Payment received for order #' . $order_id, $order_id);
    
    $success = true;
} else {
    $success = false;
}
?>

<div class="container" style="max-width: 600px; margin: 5rem auto; text-align: center;">
    <div class="auth-container">
        <?php if($success): ?>
            <i class="fas fa-check-circle" style="font-size: 5rem; color: #28a745; margin-bottom: 1.5rem;"></i>
            <h1 style="color: var(--vintage-brown); margin-bottom: 1rem;">Payment Successful!</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem;">
                Thank you for your payment. Your order is now confirmed and the artist will begin working on your custom artwork.
            </p>
            <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="btn btn-primary" style="margin-right: 1rem;">View My Orders</a>
            <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-outline">Browse Gallery</a>
        <?php else: ?>
            <i class="fas fa-exclamation-circle" style="font-size: 5rem; color: #dc3545; margin-bottom: 1.5rem;"></i>
            <h1 style="color: var(--vintage-brown); margin-bottom: 1rem;">Payment Verification Failed</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                We couldn't verify your payment. Please contact support if you believe this is an error.
            </p>
            <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>