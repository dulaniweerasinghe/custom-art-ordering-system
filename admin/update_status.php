<?php
require_once '../includes/config.php';

checkAdmin();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    
    // Validate status
    $allowed_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if(!in_array($status, $allowed_statuses)) {
        redirect('/admin/dashboard.php');
    }
    
    // Update order
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if($stmt->execute([$status, $order_id])) {
        // Get customer ID for notification
        $stmt = $pdo->prepare("SELECT customer_id, order_number FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        // Send notification to customer
        $status_text = ucwords(str_replace('_', ' ', $status));
        sendNotification($order['customer_id'], 'Order Updated', "Your order #{$order['order_number']} status has been updated to: $status_text", $order_id);
        
        // Send email
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$order['customer_id']]);
        $customer = $stmt->fetch();
        
        $subject = "Order Status Updated - #{$order['order_number']}";
        $body = "Dear Customer,<br><br>Your order status has been updated to: <strong>$status_text</strong>.<br><br>Order Number: {$order['order_number']}<br><br>Thank you for your business.";
        sendEmail($customer['email'], $subject, $body);
        
        $_SESSION['success'] = "Order status updated successfully";
    }
    
    redirect('/admin/dashboard.php');
}
?>