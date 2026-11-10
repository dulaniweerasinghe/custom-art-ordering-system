<?php
$pageTitle = 'Order Details';
require_once '../includes/header.php';

checkAuth();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('/customer/dashboard.php');
}

$order_id = intval($_GET['id']);
$customer_id = $_SESSION['user_id'];

// Fetch order and verify owner
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/customer/dashboard.php');
}

// Fetch recent messages for this order
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name AS sender_name, u.role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.order_id = ?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

function customerOrderStatusBadge($status) {
    $text = ucwords(str_replace('_', ' ', $status));
    return '<span class="status-badge status-' . htmlspecialchars($status) . '">' . $text . '</span>';
}

function customerPaymentBadge($status) {
    if($status === 'cod_deposit_paid') {
        return '<span class="payment-badge" style="background:#e65100;color:white;padding:4px 12px;border-radius:6px;font-weight:700;">COD - 50% Deposit Paid</span>';
    }
    $text = ucfirst($status);
    return '<span class="payment-badge payment-' . htmlspecialchars($status) . '">' . $text . '</span>';
}
?>

<style>
    .order-detail-wrapper {
        max-width: 1000px;
        margin: 3rem auto;
    }

    .detail-header {
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

    .detail-grid {
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
        grid-template-columns: 180px 1fr;
        gap: 1rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid #eee;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 700;
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
        max-height: 350px;
        object-fit: cover;
        border: 2px solid var(--vintage-gold);
        padding: 5px;
        background: var(--vintage-paper);
    }

    .message-preview {
        padding: 1rem;
        background: var(--vintage-cream);
        border-left: 4px solid var(--vintage-gold);
        margin-bottom: 1rem;
    }

    .message-preview small {
        color: var(--text-muted);
        display: block;
        margin-bottom: 0.4rem;
    }

    .workflow-box {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .workflow-step {
        flex: 1;
        text-align: center;
        padding: 0.8rem;
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
        .detail-grid {
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

<div class="container order-detail-wrapper">

    <div class="detail-header">
        <div>
            <h1>
                <i class="fas fa-shopping-bag" style="color: var(--vintage-gold);"></i>
                Order Details
            </h1>
            <p style="color: var(--text-muted); margin: 0;">
                Order Number: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </p>
        </div>

        <div style="display:flex; gap:0.7rem; flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>

            <a href="<?php echo SITE_URL; ?>/customer/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-comments"></i> Messages
            </a>

            <?php if ($order['payment_status'] !== 'paid' && $order['payment_status'] !== 'cod_deposit_paid'): ?>
                <a href="<?php echo SITE_URL; ?>/customer/checkout.php?order_id=<?php echo $order['id']; ?>" class="btn btn-gold btn-sm">
                    <i class="fas fa-credit-card"></i> Pay Now
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-grid">

        <div>
            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Order Information</h2>

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
                        <?php 
                            if (function_exists('formatPrice')) {
                                echo formatPrice($order['price']);
                            } else {
                                echo '$' . number_format($order['price'], 2);
                            }
                        ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Order Status</div>
                    <div><?php echo customerOrderStatusBadge($order['status']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Payment Status</div>
                    <div><?php echo customerPaymentBadge($order['payment_status']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Order Date</div>
                    <div><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Last Updated</div>
                    <div><?php echo date('M d, Y h:i A', strtotime($order['updated_at'])); ?></div>
                </div>
            </div>

            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Workflow Progress</h2>

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
                        <div class="message-preview">
                            <small>
                                <?php echo htmlspecialchars($msg['sender_name']); ?> |
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </small>
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    <?php endforeach; ?>

                    <a href="<?php echo SITE_URL; ?>/customer/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                        View All Messages
                    </a>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No messages yet for this order.</p>
                    <a href="<?php echo SITE_URL; ?>/customer/messages.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                        Send Message
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <aside>
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

            <div class="detail-card">
                <h2 style="margin-bottom:1rem;">Customer Info</h2>

                <p><strong>Name:</strong><br><?php echo htmlspecialchars($order['full_name']); ?></p>
                <p><strong>Email:</strong><br><?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Phone:</strong><br><?php echo htmlspecialchars($order['phone'] ?? 'No phone'); ?></p>
            </div>
        </aside>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>