<?php
$pageTitle = 'View Order';
require_once '../includes/header.php';

checkAdmin();

if (!isset($_GET['id'])) {
    redirect('/admin/orders.php');
}

$order_id = intval($_GET['id']);

/*
|--------------------------------------------------------------------------
| Update Order Status from View Order Page
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status']);

    $allowed_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $pdo->prepare("
            SELECT o.*, u.email, u.full_name 
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $orderForUpdate = $stmt->fetch();

        if ($orderForUpdate) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);

            $statusText = ucwords(str_replace('_', ' ', $new_status));

            // Customer notification
            sendNotification(
                $orderForUpdate['customer_id'],
                'Order Status Updated',
                'Your order #' . $orderForUpdate['order_number'] . ' status changed to ' . $statusText,
                $order_id
            );

            // Email notification
            $subject = "Order Status Updated - " . $orderForUpdate['order_number'];
            $body = "
                Dear " . htmlspecialchars($orderForUpdate['full_name']) . ",<br><br>
                Your order status has been updated.<br><br>
                <strong>Order Number:</strong> " . htmlspecialchars($orderForUpdate['order_number']) . "<br>
                <strong>New Status:</strong> " . $statusText . "<br><br>
                Thank you.
            ";

            sendEmail($orderForUpdate['email'], $subject, $body);

            $_SESSION['success'] = "Order status updated successfully.";
        }
    }

    redirect('/admin/view_order.php?id=' . $order_id);
}

/*
|--------------------------------------------------------------------------
| Fetch Order
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        u.created_at AS customer_joined
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/admin/orders.php');
}

/*
|--------------------------------------------------------------------------
| Fetch Messages
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        u.full_name AS sender_name,
        u.role AS sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.order_id = ?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

function adminViewOrderStatusBadge($status) {
    $text = ucwords(str_replace('_', ' ', $status));
    return '<span class="status-badge status-' . htmlspecialchars($status) . '">' . $text . '</span>';
}

function adminViewPaymentBadge($status) {
    if($status === 'cod_deposit_paid') {
        return '<span class="payment-badge" style="background:#e65100;color:white;padding:4px 12px;border-radius:6px;font-weight:700;">COD 50% Paid</span>';
    }
    $text = ucfirst($status);
    return '<span class="payment-badge payment-' . htmlspecialchars($status) . '">' . $text . '</span>';
}
?>

<style>
    .order-view-wrapper {
        max-width: 1100px;
        margin: 3rem auto;
    }

    .order-header {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .order-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .detail-card {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 190px 1fr;
        gap: 1rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid #eee;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: bold;
        color: var(--vintage-brown);
    }

    .status-badge,
    .payment-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-in_progress {
        background: #cce5ff;
        color: #004085;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .payment-paid {
        background: #28a745;
        color: white;
    }

    .payment-pending {
        background: #ffc107;
        color: #000;
    }

    .payment-failed,
    .payment-refunded {
        background: #dc3545;
        color: white;
    }

    .reference-img {
        width: 100%;
        max-height: 360px;
        object-fit: cover;
        border: 2px solid var(--vintage-gold);
        padding: 5px;
        background: var(--vintage-paper);
    }

    .status-form {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .status-form select {
        padding: 0.7rem;
        border: 1px solid var(--vintage-gold);
        background: var(--vintage-paper);
        min-width: 180px;
    }

    .message-box {
        background: var(--vintage-cream);
        border-left: 4px solid var(--vintage-gold);
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .message-box small {
        display: block;
        color: var(--text-muted);
        margin-bottom: 0.4rem;
    }

    .workflow-box {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .workflow-step {
        flex: 1;
        text-align: center;
        padding: 0.9rem;
        background: var(--vintage-paper);
        border: 1px solid var(--vintage-gold);
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .workflow-step.active {
        background: var(--vintage-brown);
        color: var(--vintage-cream);
    }

    @media (max-width: 900px) {
        .order-grid {
            grid-template-columns: 1fr;
        }

        .detail-row {
            grid-template-columns: 1fr;
        }

        .workflow-box {
            flex-direction: column;
        }
    }
</style>

<div class="container order-view-wrapper">

    <div class="order-header">
        <div>
            <h1>
                <i class="fas fa-shopping-bag" style="color: var(--vintage-gold);"></i>
                Order Details
            </h1>
            <p style="color: var(--text-muted); margin: 0;">
                Order Number:
                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </p>
        </div>

        <div style="display:flex; gap:0.7rem; flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-home"></i> Dashboard
            </a>

            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-outline btn-sm">
                <i class="fas fa-list"></i> All Orders
            </a>

            <a href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-comments"></i> Messages
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']); 
            ?>
        </div>
    <?php endif; ?>

    <div class="order-grid">

        <div>
            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Order Information</h2>

                <div class="detail-row">
                    <div class="detail-label">Order Number</div>
                    <div><?php echo htmlspecialchars($order['order_number']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Artwork Type</div>
                    <div><?php echo htmlspecialchars($order['artwork_type']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div><?php echo nl2br(htmlspecialchars($order['description'])); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Requirements</div>
                    <div>
                        <?php if (!empty($order['requirements'])): ?>
                            <?php echo nl2br(htmlspecialchars($order['requirements'])); ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">No specific requirements provided.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Price</div>
                    <div>
                        <?php echo formatPrice($order['price']); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Order Status</div>
                    <div><?php echo adminViewOrderStatusBadge($order['status']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Payment Status</div>
                    <div><?php echo adminViewPaymentBadge($order['payment_status']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Payment Method</div>
                    <div>
                        <?php echo !empty($order['payment_method']) ? htmlspecialchars($order['payment_method']) : '<span style="color: var(--text-muted);">Not available</span>'; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Transaction ID</div>
                    <div>
                        <?php echo !empty($order['transaction_id']) ? htmlspecialchars($order['transaction_id']) : '<span style="color: var(--text-muted);">Not available</span>'; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Created Date</div>
                    <div><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Last Updated</div>
                    <div><?php echo date('M d, Y h:i A', strtotime($order['updated_at'])); ?></div>
                </div>
            </div>

            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Update Workflow Status</h2>

                <form method="POST" class="status-form">
                    <select name="status" required>
                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>
                            Pending
                        </option>

                        <option value="in_progress" <?php echo $order['status'] == 'in_progress' ? 'selected' : ''; ?>>
                            In Progress
                        </option>

                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>
                            Completed
                        </option>

                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>
                            Cancelled
                        </option>
                    </select>

                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>

                <div class="workflow-box">
                    <div class="workflow-step <?php echo in_array($order['status'], ['pending', 'in_progress', 'completed']) ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i><br>
                        Pending
                    </div>

                    <div class="workflow-step <?php echo in_array($order['status'], ['in_progress', 'completed']) ? 'active' : ''; ?>">
                        <i class="fas fa-paint-brush"></i><br>
                        In Progress
                    </div>

                    <div class="workflow-step <?php echo $order['status'] == 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i><br>
                        Completed
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Recent Messages</h2>

                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-box">
                            <small>
                                <?php echo htmlspecialchars($msg['sender_name']); ?>
                                —
                                <?php echo ucfirst($msg['sender_role']); ?>
                                |
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </small>

                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    <?php endforeach; ?>

                    <a href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                        View All Messages
                    </a>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No messages for this order yet.</p>

                    <a href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                        Send Message
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <aside>
            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Customer Information</h2>

                <p>
                    <strong>Name:</strong><br>
                    <?php echo htmlspecialchars($order['customer_name']); ?>
                </p>

                <p>
                    <strong>Email:</strong><br>
                    <?php echo htmlspecialchars($order['customer_email']); ?>
                </p>

                <p>
                    <strong>Phone:</strong><br>
                    <?php echo htmlspecialchars($order['customer_phone'] ?? 'No phone'); ?>
                </p>

                <p>
                    <strong>Joined:</strong><br>
                    <?php echo date('M d, Y', strtotime($order['customer_joined'])); ?>
                </p>

                <a href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-gold btn-sm">
                    <i class="fas fa-comment"></i> Message Customer
                </a>
            </div>

            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Reference Image</h2>

                <?php if (!empty($order['reference_image'])): ?>
                    <img 
                        src="<?php echo SITE_URL; ?>/uploads/references/<?php echo htmlspecialchars($order['reference_image']); ?>" 
                        alt="Reference Image"
                        class="reference-img"
                    >
                <?php else: ?>
                    <div style="text-align:center; padding:3rem 1rem; background:var(--vintage-cream);">
                        <i class="fas fa-image" style="font-size:3rem; color:var(--vintage-gold); margin-bottom:1rem;"></i>
                        <p style="color:var(--text-muted);">No reference image uploaded.</p>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>