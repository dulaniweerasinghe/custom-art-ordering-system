<?php
$pageTitle = 'Admin Messages';
require_once '../includes/header.php';

checkAdmin();

$admin_id = $_SESSION['user_id'];

// Fetch all orders
$stmt = $pdo->query("
    SELECT 
        o.*,
        u.full_name AS customer_name,
        u.email AS customer_email,
        (
            SELECT COUNT(*) 
            FROM messages m 
            WHERE m.order_id = o.id 
            AND m.receiver_id = {$admin_id}
            AND m.is_read = 0
        ) AS unread_count,
        (
            SELECT MAX(created_at)
            FROM messages m2
            WHERE m2.order_id = o.id
        ) AS last_message_time
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    ORDER BY COALESCE(last_message_time, o.created_at) DESC
");
$orders = $stmt->fetchAll();

// Select order
$selected_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($selected_order_id == 0 && count($orders) > 0) {
    $selected_order_id = $orders[0]['id'];
}

// Validate selected order
$current_order = null;

if ($selected_order_id > 0) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name AS customer_name, u.email AS customer_email
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$selected_order_id]);
    $current_order = $stmt->fetch();
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $current_order) {
    $message = trim($_POST['message']);

    if ($message !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO messages (order_id, sender_id, receiver_id, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $selected_order_id,
            $admin_id,
            $current_order['customer_id'],
            $message
        ]);

        sendNotification(
            $current_order['customer_id'],
            'New Message from Artist',
            'You received a new message for order #' . $current_order['order_number'],
            $selected_order_id
        );

        redirect('/admin/messages.php?order_id=' . $selected_order_id);
    }
}

// Mark customer messages as read
if ($selected_order_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE order_id = ? 
        AND receiver_id = ?
    ");
    $stmt->execute([$selected_order_id, $admin_id]);
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
        grid-template-columns: 340px 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .order-list {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 1.5rem;
        height: fit-content;
        max-height: 700px;
        overflow-y: auto;
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
        min-height: 650px;
    }

    .chat-header {
        padding: 1.5rem;
        background: var(--vintage-cream);
        border-bottom: 1px solid var(--vintage-gold);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .chat-messages {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        max-height: 480px;
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
    <h1>Customer Messages</h1>
    <p style="color: var(--text-muted);">Reply to customers and discuss order requirements.</p>

    <div style="margin-top: 1rem;">
        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="message-layout">

        <aside class="order-list">
            <h3 style="margin-bottom: 1rem;">Orders</h3>

            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <a 
                        href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" 
                        class="order-item <?php echo ($selected_order_id == $order['id']) ? 'active' : ''; ?>"
                    >
                        <?php if ($order['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $order['unread_count']; ?></span>
                        <?php endif; ?>

                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                        <small><?php echo htmlspecialchars($order['customer_name']); ?></small><br>
                        <small><?php echo htmlspecialchars($order['artwork_type']); ?></small><br>
                        <small>Status: <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?></small>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">No orders found.</p>
            <?php endif; ?>
        </aside>

        <section class="chat-box">
            <?php if ($current_order): ?>
                <div class="chat-header">
                    <div>
                        <h3>Order #<?php echo htmlspecialchars($current_order['order_number']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">
                            Customer: <?php echo htmlspecialchars($current_order['customer_name']); ?> |
                            <?php echo htmlspecialchars($current_order['customer_email']); ?>
                        </p>
                    </div>

                    <a 
                        href="<?php echo SITE_URL; ?>/admin/view_order.php?id=<?php echo $current_order['id']; ?>" 
                        class="btn btn-primary btn-sm"
                    >
                        View Order
                    </a>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-row <?php echo ($msg['sender_id'] == $admin_id) ? 'me' : ''; ?>">
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
                            No messages yet for this order.
                        </p>
                    <?php endif; ?>
                </div>

                <form method="POST" class="chat-form">
                    <textarea name="message" class="form-control" placeholder="Type reply to customer..." required></textarea>
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