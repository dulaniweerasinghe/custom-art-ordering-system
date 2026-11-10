<?php
$pageTitle = 'Manage Orders';
require_once '../includes/header.php';

checkAdmin();

$allowed_statuses = ['all', 'pending', 'in_progress', 'completed', 'cancelled'];

/*
|--------------------------------------------------------------------------
| Update Order Status
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status']);

    if (in_array($new_status, ['pending', 'in_progress', 'completed', 'cancelled'])) {

        $stmt = $pdo->prepare("
            SELECT o.*, u.email, u.full_name 
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);

            $statusText = ucwords(str_replace('_', ' ', $new_status));

            // Customer notification
            sendNotification(
                $order['customer_id'],
                'Order Status Updated',
                'Your order #' . $order['order_number'] . ' status changed to ' . $statusText,
                $order_id
            );

            // Email notification
            $subject = "Order Status Updated - " . $order['order_number'];
            $body = "
                Dear " . htmlspecialchars($order['full_name']) . ",<br><br>
                Your order status has been updated.<br><br>
                <strong>Order Number:</strong> " . htmlspecialchars($order['order_number']) . "<br>
                <strong>New Status:</strong> " . $statusText . "<br><br>
                Thank you.
            ";

            sendEmail($order['email'], $subject, $body);

            $_SESSION['success'] = "Order status updated successfully.";
        }
    }

    $returnStatus = isset($_POST['return_status']) ? sanitize($_POST['return_status']) : 'all';
    redirect('/admin/orders.php?status=' . $returnStatus);
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

if (!in_array($status, $allowed_statuses)) {
    $status = 'all';
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders
    FROM orders
")->fetch();

/*
|--------------------------------------------------------------------------
| Fetch Orders
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        o.*,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE 1 = 1
";

$params = [];

if ($status !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $sql .= " AND (
        o.order_number LIKE ?
        OR o.artwork_type LIKE ?
        OR u.full_name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
    )";

    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

function orderStatusBadge($status) {
    $text = ucwords(str_replace('_', ' ', $status));
    return '<span class="status-badge status-' . htmlspecialchars($status) . '">' . $text . '</span>';
}

function paymentStatusBadge($status) {
    if($status === 'cod_deposit_paid') {
        return '<span class="payment-badge" style="background:#e65100;color:white;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:700;">COD 50% Paid</span>';
    }
    $text = ucfirst($status);
    return '<span class="payment-badge payment-' . htmlspecialchars($status) . '">' . $text . '</span>';
}
?>

<style>
    .admin-page-header {
        margin: 2rem 0;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--vintage-gold);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 1.3rem;
        text-align: center;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .orders-section {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2rem;
    }

    .filter-bar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        align-items: center;
    }

    .filter-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .filter-link {
        padding: 0.5rem 0.9rem;
        border: 1px solid var(--vintage-gold);
        text-decoration: none;
        color: var(--vintage-brown);
        background: var(--vintage-paper);
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .filter-link.active,
    .filter-link:hover {
        background: var(--vintage-brown);
        color: var(--vintage-cream);
        border-color: var(--vintage-brown);
    }

    .search-form {
        display: flex;
        gap: 0.5rem;
        min-width: 300px;
    }

    .search-form input {
        flex: 1;
    }

    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table th,
    .orders-table td {
        padding: 0.9rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .orders-table th {
        background: var(--vintage-cream);
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
    }

    .status-badge,
    .payment-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.78rem;
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

    .status-form {
        display: flex;
        gap: 0.4rem;
        align-items: center;
    }

    .status-form select {
        padding: 0.4rem;
        border: 1px solid var(--vintage-gold);
        background: var(--vintage-paper);
        border-radius: 3px;
    }

    .action-buttons {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .empty-box {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }

    @media (max-width: 900px) {
        .orders-table {
            display: block;
            overflow-x: auto;
        }

        .search-form {
            width: 100%;
            min-width: auto;
        }
    }
</style>

<div class="container">

    <div class="admin-page-header">
        <h1>
            <i class="fas fa-shopping-bag" style="color: var(--vintage-gold);"></i>
            Manage Orders
        </h1>
        <p style="color: var(--text-muted);">
            View, filter, and update customer artwork orders.
        </p>

        <div style="margin-top: 1rem;">
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
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

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['in_progress_orders'] ?? 0; ?></div>
            <div class="stat-label">In Progress</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['completed_orders'] ?? 0; ?></div>
            <div class="stat-label">Completed</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['cancelled_orders'] ?? 0; ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <div class="orders-section">

        <div class="filter-bar">
            <div class="filter-links">
                <a class="filter-link <?php echo $status == 'all' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/orders.php?status=all">
                    All
                </a>

                <a class="filter-link <?php echo $status == 'pending' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending">
                    Pending
                </a>

                <a class="filter-link <?php echo $status == 'in_progress' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/orders.php?status=in_progress">
                    In Progress
                </a>

                <a class="filter-link <?php echo $status == 'completed' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/orders.php?status=completed">
                    Completed
                </a>

                <a class="filter-link <?php echo $status == 'cancelled' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/orders.php?status=cancelled">
                    Cancelled
                </a>
            </div>

            <form method="GET" class="search-form">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">

                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Search order, customer, email..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=<?php echo htmlspecialchars($status); ?>" 
                       class="btn btn-outline btn-sm">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($orders) > 0): ?>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Artwork</th>
                        <th>Price</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Change Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>

                            <td>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($order['customer_email']); ?></small><br>
                                <small><?php echo htmlspecialchars($order['customer_phone'] ?? 'No phone'); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($order['artwork_type']); ?>
                            </td>

                            <td>
                                <?php 
                                    if (function_exists('formatPrice')) {
                                        echo formatPrice($order['price']);
                                    } else {
                                        echo '$' . number_format($order['price'], 2);
                                    }
                                ?>
                            </td>

                            <td>
                                <?php echo paymentStatusBadge($order['payment_status']); ?>
                            </td>

                            <td>
                                <?php echo orderStatusBadge($order['status']); ?>
                            </td>

                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="return_status" value="<?php echo htmlspecialchars($status); ?>">

                                    <select name="status">
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

                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                        Save
                                    </button>
                                </form>
                            </td>

                            <td>
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                                <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                            </td>

                            <td>
                                <div class="action-buttons">
                                    <?php if (file_exists(__DIR__ . '/view_order.php')): ?>
                                        <a href="<?php echo SITE_URL; ?>/admin/view_order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $order['id']; ?>" 
                                       class="btn btn-gold btn-sm">
                                        <i class="fas fa-comment"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <div class="empty-box">
                <i class="fas fa-shopping-bag" style="font-size: 4rem; color: var(--vintage-gold); margin-bottom: 1rem;"></i>
                <h3>No orders found</h3>

                <?php if ($status == 'pending'): ?>
                    <p>There are no pending orders right now.</p>
                <?php else: ?>
                    <p>No order records match your filter/search.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php require_once '../includes/footer.php'; ?>