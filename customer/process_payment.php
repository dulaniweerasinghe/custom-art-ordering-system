<?php
require_once '../includes/config.php';

// Clear any buffered output and set JSON header
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if(!$input || !isset($input['order_id'], $input['transaction_id'], $input['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$order_id         = intval($input['order_id']);
$transaction_id   = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($input['transaction_id']));
$payment_method   = sanitize($input['payment_method']);
$is_cod           = ($payment_method === 'cod');
$delivery_address = ($is_cod && isset($input['delivery_address'])) ? sanitize($input['delivery_address']) : null;

// Verify order belongs to this customer and is still pending
$stmt = $pdo->prepare("SELECT o.*, u.email, u.full_name FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ? AND o.customer_id = ? AND o.payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or already paid']);
    exit;
}

// Fall back to pre-saved delivery address if not provided in request
if (empty($delivery_address) && !empty($order['delivery_address'])) {
    $delivery_address = $order['delivery_address'];
}

// Calculate amounts
$subtotal      = floatval($order['price']);
$tax           = $subtotal * 0.10;
$total         = $subtotal + $tax;
$cod_deposit   = round($total * 0.50, 2);
$cod_remaining = round($total - $cod_deposit, 2);

if($is_cod) {
    $stmt = $pdo->prepare("UPDATE orders SET 
        payment_status   = 'cod_deposit_paid',
        transaction_id   = ?,
        payment_method   = 'cod',
        status           = 'in_progress',
        cod_deposit      = ?,
        cod_remaining    = ?,
        cod_deposit_paid = 1,
        delivery_address = ?
        WHERE id = ? AND customer_id = ?");
    $updated = $stmt->execute([$transaction_id, $cod_deposit, $cod_remaining, $delivery_address, $order_id, $_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("UPDATE orders SET 
        payment_status = 'paid',
        transaction_id = ?,
        payment_method = ?,
        status         = 'in_progress'
        WHERE id = ? AND customer_id = ?");
    $updated = $stmt->execute([$transaction_id, $payment_method, $order_id, $_SESSION['user_id']]);
}

if(!$updated || $stmt->rowCount() === 0) {
    // Extra debug: check PDO error
    $err = $stmt->errorInfo();
    echo json_encode(['success' => false, 'message' => 'Failed to update order', 'db_error' => $err[2]]);
    exit;
}

// ---- Notifications ----
if($is_cod) {
    sendNotification($_SESSION['user_id'], 'COD Order Confirmed',
        'Your COD deposit of Rs. ' . number_format($cod_deposit, 2) . ' for Order #' . $order['order_number'] . ' has been received. Pay the remaining Rs. ' . number_format($cod_remaining, 2) . ' on delivery.',
        $order_id);
    sendNotification(1, 'New COD Order — Deposit Paid',
        'COD deposit received for Order #' . $order['order_number'] . ' from ' . $order['full_name'] . '. Deposit: Rs. ' . number_format($cod_deposit, 2) . ' | Remaining on delivery: Rs. ' . number_format($cod_remaining, 2),
        $order_id);
} else {
    sendNotification($_SESSION['user_id'], 'Payment Successful',
        'Your payment of Rs. ' . number_format($total, 2) . ' for order #' . $order['order_number'] . ' has been received. Your artwork is now in progress!',
        $order_id);
    sendNotification(1, 'Payment Received',
        'Payment received for Order #' . $order['order_number'] . ' from ' . $order['full_name'] . '. Method: ' . strtoupper($payment_method),
        $order_id);
}

// ---- Email formatting ----
$totalFormatted   = 'Rs. ' . number_format($total, 2);
$depositFormatted = 'Rs. ' . number_format($cod_deposit, 2);
$remainFormatted  = 'Rs. ' . number_format($cod_remaining, 2);

// ---- Customer Email ----
if($is_cod) {
    $customerSubject = "✅ COD Order Confirmed - Order " . $order['order_number'] . " | SKETCH-CORE";
    $customerBody = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
  body{font-family:Georgia,serif;background:#f5f2e9;margin:0;padding:20px;}
  .wrapper{max-width:600px;margin:0 auto;}
  .header{background:linear-gradient(135deg,#5d4037,#3e2723);padding:40px 30px;text-align:center;border-radius:12px 12px 0 0;}
  .header h1{color:#c9a959;font-size:28px;margin:0 0 8px;letter-spacing:3px;}
  .body{background:white;padding:35px 30px;}
  .cod-badge{background:linear-gradient(135deg,#fff3e0,#ffe0b2);border:2px solid #e65100;border-radius:12px;padding:20px;text-align:center;margin-bottom:25px;}
  .cod-badge h2{color:#e65100;margin:0 0 5px;font-size:22px;}
  .amounts-row{display:flex;gap:12px;margin:20px 0;}
  .amt-card{flex:1;padding:16px;border-radius:10px;text-align:center;}
  .amt-card.paid{background:linear-gradient(135deg,#e65100,#bf360c);color:white;}
  .amt-card.due{background:#f5f5f5;border:2px dashed #bdbdbd;color:#616161;}
  .amt-card .lbl{font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.06em;opacity:0.8;}
  .amt-card .val{font-size:22px;font-weight:800;margin:4px 0;}
  .details{background:#faf8f3;border:1px solid #c9a959;border-radius:10px;padding:20px;margin:20px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed rgba(201,169,89,0.3);font-size:14px;}
  .detail-row:last-child{border-bottom:none;}
  .cta{text-align:center;margin:25px 0;}
  .cta a{background:linear-gradient(135deg,#e65100,#bf360c);color:white;padding:14px 30px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:bold;display:inline-block;}
  .footer{background:#2c241b;padding:20px 30px;text-align:center;border-radius:0 0 12px 12px;}
  .footer p{color:rgba(255,255,255,0.5);font-size:12px;margin:0;}
</style></head><body>
<div class="wrapper">
  <div class="header"><h1>✦ SKETCH-CORE ✦</h1><p>Premium Custom Artwork Studio</p></div>
  <div class="body">
    <div class="cod-badge"><h2>🚚 COD Order Confirmed!</h2><p>Deposit received — artwork in progress</p></div>
    <p style="color:#5d4037;font-size:16px;">Dear <strong>' . htmlspecialchars($order['full_name']) . '</strong>,</p>
    <p style="color:#8c7b65;font-size:14px;line-height:1.7;">Your Cash on Delivery order is confirmed! We received your 50% deposit and our artist will begin crafting your artwork immediately.</p>
    <div class="amounts-row">
      <div class="amt-card paid"><div class="lbl">✅ Deposit Paid</div><div class="val">' . $depositFormatted . '</div><div class="lbl">50% — Charged to card</div></div>
      <div class="amt-card due"><div class="lbl">💵 Pay on Delivery</div><div class="val">' . $remainFormatted . '</div><div class="lbl">50% — Cash on arrival</div></div>
    </div>
    <div class="details">
      <div class="detail-row"><span>Order Number</span><span>' . $order['order_number'] . '</span></div>
      <div class="detail-row"><span>Artwork Type</span><span>' . htmlspecialchars($order['artwork_type']) . '</span></div>
      <div class="detail-row"><span>Transaction ID</span><span style="font-size:11px;">' . $transaction_id . '</span></div>
      <div class="detail-row"><span>Total Order Value</span><span>' . $totalFormatted . '</span></div>
      <div class="detail-row"><span>Deposit Paid (Card)</span><span style="color:#e65100;">' . $depositFormatted . '</span></div>
      <div class="detail-row"><span>Remaining (Cash on Delivery)</span><span style="color:#5d4037;font-weight:bold;">' . $remainFormatted . '</span></div>
    </div>
    <div class="cta"><a href="' . SITE_URL . '/customer/dashboard.php">View My Order Dashboard →</a></div>
  </div>
  <div class="footer"><p>© ' . date('Y') . ' SKETCH-CORE Art Studio</p></div>
</div></body></html>';
} else {
    $customerSubject = "✅ Payment Confirmed - Order " . $order['order_number'] . " | SKETCH-CORE";
    $customerBody = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
  body{font-family:Georgia,serif;background:#f5f2e9;margin:0;padding:20px;}
  .wrapper{max-width:600px;margin:0 auto;}
  .header{background:linear-gradient(135deg,#5d4037,#3e2723);padding:40px 30px;text-align:center;border-radius:12px 12px 0 0;}
  .header h1{color:#c9a959;font-size:28px;margin:0 0 8px;letter-spacing:3px;}
  .body{background:white;padding:35px 30px;}
  .success-badge{background:#e8f5e9;border:2px solid #4caf50;border-radius:12px;padding:20px;text-align:center;margin-bottom:25px;}
  .success-badge h2{color:#2e7d32;margin:0 0 5px;font-size:22px;}
  .details{background:#faf8f3;border:1px solid #c9a959;border-radius:10px;padding:20px;margin:20px 0;}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed rgba(201,169,89,0.3);font-size:14px;}
  .detail-row:last-child{border-bottom:none;font-weight:bold;font-size:16px;color:#5d4037;}
  .cta{text-align:center;margin:25px 0;}
  .cta a{background:linear-gradient(135deg,#5d4037,#3e2723);color:white;padding:14px 30px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:bold;display:inline-block;}
  .footer{background:#2c241b;padding:20px;text-align:center;border-radius:0 0 12px 12px;}
  .footer p{color:rgba(255,255,255,0.5);font-size:12px;margin:0;}
</style></head><body>
<div class="wrapper">
  <div class="header"><h1>✦ SKETCH-CORE ✦</h1><p>Premium Custom Artwork Studio</p></div>
  <div class="body">
    <div class="success-badge"><h2>✅ Payment Confirmed!</h2><p>Your order is now in progress</p></div>
    <p style="color:#5d4037;font-size:16px;">Dear <strong>' . htmlspecialchars($order['full_name']) . '</strong>,</p>
    <p style="color:#8c7b65;font-size:14px;line-height:1.7;">We have successfully received your payment. Our talented artist will begin crafting your custom artwork immediately.</p>
    <div class="details">
      <div class="detail-row"><span>Order Number</span><span>' . $order['order_number'] . '</span></div>
      <div class="detail-row"><span>Artwork Type</span><span>' . htmlspecialchars($order['artwork_type']) . '</span></div>
      <div class="detail-row"><span>Payment Method</span><span>' . ucfirst($payment_method) . '</span></div>
      <div class="detail-row"><span>Transaction ID</span><span style="font-size:11px;">' . $transaction_id . '</span></div>
      <div class="detail-row"><span>Subtotal</span><span>Rs. ' . number_format($subtotal, 2) . '</span></div>
      <div class="detail-row"><span>Tax (10%)</span><span>Rs. ' . number_format($tax, 2) . '</span></div>
      <div class="detail-row"><span>Total Paid</span><span>' . $totalFormatted . '</span></div>
    </div>
    <div class="cta"><a href="' . SITE_URL . '/customer/dashboard.php">View My Order Dashboard →</a></div>
  </div>
  <div class="footer"><p>© ' . date('Y') . ' SKETCH-CORE Art Studio</p></div>
</div></body></html>';
}

sendEmail($order['email'], $customerSubject, $customerBody);

// ---- Admin Email ----
if($is_cod) {
    $adminSubject = "🚚 New COD Order — Deposit Paid - Order " . $order['order_number'];
    $adminBody = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
  body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
  .wrapper{max-width:600px;margin:0 auto;}
  .header{background:#e65100;padding:25px;text-align:center;border-radius:8px 8px 0 0;}
  .header h1{color:white;font-size:22px;margin:0;}
  .body{background:white;padding:30px;}
  .alert{background:#fff3e0;border-left:4px solid #e65100;padding:15px;margin-bottom:20px;border-radius:4px;}
  .detail-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee;font-size:14px;}
  .footer{background:#333;color:#999;padding:15px;text-align:center;border-radius:0 0 8px 8px;font-size:12px;}
</style></head><body>
<div class="wrapper">
  <div class="header"><h1>🚚 SKETCH-CORE — COD Order Alert</h1></div>
  <div class="body">
    <div class="alert"><strong>🚚 New COD Order — Deposit Received!</strong><br>A customer placed a Cash on Delivery order and paid the 50% deposit.</div>
    <div class="detail-row"><span><b>Customer</b></span><span>' . htmlspecialchars($order['full_name']) . '</span></div>
    <div class="detail-row"><span><b>Order #</b></span><span>' . $order['order_number'] . '</span></div>
    <div class="detail-row"><span><b>Total Order Value</b></span><span><strong>' . $totalFormatted . '</strong></span></div>
    <div class="detail-row"><span><b>Deposit Paid (Card)</b></span><span style="color:#e65100;"><strong>' . $depositFormatted . '</strong></span></div>
    <div class="detail-row"><span><b>Remaining (Cash on Delivery)</b></span><span style="color:#bf360c;"><strong>' . $remainFormatted . '</strong></span></div>
    <div class="detail-row"><span><b>Transaction ID</b></span><span style="font-size:11px;">' . $transaction_id . '</span></div>
    ' . (!empty($delivery_address) ? '<div class="detail-row"><span><b>📍 Delivery Address</b></span><span style="font-size:13px;">' . nl2br(htmlspecialchars($delivery_address)) . '</span></div>' : '') . '
    <br>
    <p style="text-align:center;">
      <a href="' . SITE_URL . '/admin/orders.php" style="background:#e65100;color:white;padding:12px 25px;border-radius:6px;text-decoration:none;font-weight:bold;">View Order in Admin Panel →</a>
    </p>
    <p style="font-size:12px;color:#888;text-align:center;">⚠️ Remember to collect <strong>' . $remainFormatted . '</strong> in cash upon delivery.</p>
  </div>
  <div class="footer">SKETCH-CORE — Admin COD Notification</div>
</div></body></html>';
} else {
    $adminSubject = "💰 New Payment Received - Order " . $order['order_number'];
    $adminBody = '
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
  body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
  .wrapper{max-width:600px;margin:0 auto;}
  .header{background:#5d4037;padding:25px;text-align:center;border-radius:8px 8px 0 0;}
  .header h1{color:#c9a959;font-size:22px;margin:0;}
  .body{background:white;padding:30px;}
  .alert{background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin-bottom:20px;border-radius:4px;}
  .detail-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee;font-size:14px;}
  .footer{background:#333;color:#999;padding:15px;text-align:center;border-radius:0 0 8px 8px;font-size:12px;}
</style></head><body>
<div class="wrapper">
  <div class="header"><h1>SKETCH-CORE Admin Alert</h1></div>
  <div class="body">
    <div class="alert"><strong>💰 New Payment Received!</strong><br>A customer has successfully completed their payment.</div>
    <div class="detail-row"><span><b>Customer</b></span><span>' . htmlspecialchars($order['full_name']) . '</span></div>
    <div class="detail-row"><span><b>Order #</b></span><span>' . $order['order_number'] . '</span></div>
    <div class="detail-row"><span><b>Payment Method</b></span><span>' . ucfirst($payment_method) . '</span></div>
    <div class="detail-row"><span><b>Amount</b></span><span><strong>' . $totalFormatted . '</strong></span></div>
    <div class="detail-row"><span><b>Transaction ID</b></span><span style="font-size:11px;">' . $transaction_id . '</span></div>
    <div class="detail-row"><span><b>Date/Time</b></span><span>' . date('Y-m-d H:i:s') . '</span></div>
    ' . (!empty($delivery_address) ? '<div class="detail-row"><span><b>Delivery Address</b></span><span style="font-size:13px;">' . nl2br(htmlspecialchars($delivery_address)) . '</span></div>' : '') . '
    <br>
    <p style="text-align:center;">
      <a href="' . SITE_URL . '/admin/orders.php" style="background:#5d4037;color:white;padding:12px 25px;border-radius:6px;text-decoration:none;font-weight:bold;">View Order in Admin Panel →</a>
    </p>
  </div>
  <div class="footer">SKETCH-CORE Art Ordering System — Admin Notification</div>
</div></body></html>';
}

sendEmail(ADMIN_EMAIL, $adminSubject, $adminBody);

echo json_encode([
    'success'       => true,
    'transaction_id'=> $transaction_id,
    'message'       => $is_cod ? 'COD deposit processed successfully' : 'Payment processed successfully',
    'is_cod'        => $is_cod,
    'cod_deposit'   => $is_cod ? $cod_deposit : null,
    'cod_remaining' => $is_cod ? $cod_remaining : null,
]);
