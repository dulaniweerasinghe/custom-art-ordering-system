<?php
$pageTitle = 'Messages';
require_once '../includes/header.php';

checkAuth();

if (isAdmin()) {
    redirect('/admin/messages.php');
}

// Get admin ID
$adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
$admin = $adminStmt->fetch();

if (!$admin) {
    die("Admin account not found.");
}

$admin_id = $admin['id'];
$customer_id = $_SESSION['user_id'];

// Fetch customer orders
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        (
            SELECT COUNT(*) 
            FROM messages m 
            WHERE m.order_id = o.id 
            AND m.receiver_id = ? 
            AND m.is_read = 0
        ) AS unread_count
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.updated_at DESC
");
$stmt->execute([$customer_id, $customer_id]);
$orders = $stmt->fetchAll();

// Select order
$selected_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($selected_order_id == 0 && count($orders) > 0) {
    $selected_order_id = $orders[0]['id'];
}

// Validate selected order belongs to customer
$current_order = null;

if ($selected_order_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->execute([$selected_order_id, $customer_id]);
    $current_order = $stmt->fetch();

    if (!$current_order) {
        $selected_order_id = 0;
    }
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $current_order) {
    $message = trim($_POST['message']);

    if ($message !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO messages (order_id, sender_id, receiver_id, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$selected_order_id, $customer_id, $admin_id, $message]);

        sendNotification(
            $admin_id,
            'New Customer Message',
            'You received a new message for order #' . $current_order['order_number'],
            $selected_order_id
        );

        redirect('/customer/messages.php?order_id=' . $selected_order_id);
    }
}

// Mark admin messages as read
if ($selected_order_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE order_id = ? 
        AND receiver_id = ?
    ");
    $stmt->execute([$selected_order_id, $customer_id]);
}

// Fetch messages
$messages = [];

if ($selected_order_id > 0) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name, u.role
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.order_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$selected_order_id]);
    $messages = $stmt->fetchAll();
}
?>

<style>
    .message-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .order-list {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 1.5rem;
        height: fit-content;
    }

    .order-item {
        display: block;
        padding: 1rem;
        margin-bottom: 0.8rem;
        background: var(--vintage-paper);
        border-left: 4px solid transparent;
        text-decoration: none;
        color: var(--vintage-dark);
        transition: 0.3s;
    }

    .order-item:hover,
    .order-item.active {
        background: var(--vintage-cream);
        border-left-color: var(--vintage-gold);
    }

    .unread-badge {
        background: #dc3545;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 20px;
        font-size: 0.75rem;
        float: right;
    }

    .chat-box {
        background: white;
        border: 2px solid var(--vintage-gold);
        display: flex;
        flex-direction: column;
        min-height: 600px;
    }

    .chat-header {
        padding: 1.5rem;
        background: var(--vintage-cream);
        border-bottom: 1px solid var(--vintage-gold);
    }

    .chat-messages {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        max-height: 450px;
    }

    .message-row {
        margin-bottom: 1rem;
        display: flex;
    }

    .message-row.me {
        justify-content: flex-end;
    }

    .message-bubble {
        max-width: 70%;
        padding: 1rem;
        border-radius: 10px;
        background: var(--vintage-cream);
        border: 1px solid rgba(201, 169, 89, 0.4);
    }

    .message-row.me .message-bubble {
        background: var(--vintage-brown);
        color: white;
    }

    .message-meta {
        font-size: 0.75rem;
        opacity: 0.8;
        margin-bottom: 0.3rem;
    }

    .chat-form {
        padding: 1.5rem;
        border-top: 1px solid var(--vintage-gold);
        display: flex;
        gap: 1rem;
    }

    .chat-form textarea {
        flex: 1;
        resize: none;
        min-height: 55px;
    }

    @media (max-width: 900px) {
        .message-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <h1>Messages</h1>
    <p style="color: var(--text-muted);">Communicate with the artist about your orders.</p>

    <div class="message-layout">

        <aside class="order-list">
            <h3 style="margin-bottom: 1rem;">My Orders</h3>

            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <a 
                        href="<?php echo SITE_URL; ?>/customer/messages.php?order_id=<?php echo $order['id']; ?>" 
                        class="order-item <?php echo ($selected_order_id == $order['id']) ? 'active' : ''; ?>"
                    >
                        <?php if ($order['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $order['unread_count']; ?></span>
                        <?php endif; ?>

                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                        <small><?php echo htmlspecialchars($order['artwork_type']); ?></small><br>
                        <small>Status: <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?></small>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">No orders found.</p>
                <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-primary btn-sm">Place Order</a>
            <?php endif; ?>
        </aside>

        <section class="chat-box">
            <?php if ($current_order): ?>
                <div class="chat-header" style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
    <div>
        <h3>Order #<?php echo htmlspecialchars($current_order['order_number']); ?></h3>
        <p style="color: var(--text-muted); margin: 0;">
            <?php echo htmlspecialchars($current_order['artwork_type']); ?> |
            Status: <?php echo ucwords(str_replace('_', ' ', $current_order['status'])); ?>
        </p>
    </div>

    <a 
        href="<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $current_order['id']; ?>" 
        class="btn btn-primary btn-sm"
    >
        <i class="fas fa-eye"></i> View Order
    </a>
</div>

                <div class="chat-messages" id="chatMessages">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-row <?php echo ($msg['sender_id'] == $customer_id) ? 'me' : ''; ?>">
                                <div class="message-bubble">
                                    <div class="message-meta">
                                        <?php echo htmlspecialchars($msg['full_name']); ?> |
                                        <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                    <div>
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; color: var(--text-muted); margin-top: 3rem;">
                            No messages yet. Start conversation with the artist.
                        </p>
                    <?php endif; ?>
                </div>

                <form method="POST" class="chat-form">
                    <textarea name="message" class="form-control" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div style="padding: 4rem; text-align: center;">
                    <i class="fas fa-comments" style="font-size: 4rem; color: var(--vintage-gold); margin-bottom: 1rem;"></i>
                    <h3>Select an order</h3>
                    <p style="color: var(--text-muted);">Please select an order to view messages.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<script>
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
</script>

<?php require_once '../includes/footer.php'; ?>