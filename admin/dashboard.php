<?php 
$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';

checkAdmin();

// Statistics
$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'in_progress') as active_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
    (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
    (SELECT COALESCE(SUM(price), 0) FROM orders WHERE payment_status = 'paid') as total_revenue
")->fetch();

// Recent Orders
$recent_orders = $pdo->query("SELECT o.*, u.full_name as customer_name, u.email as customer_email 
                              FROM orders o 
                              JOIN users u ON o.customer_id = u.id 
                              ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

// Recent Messages (unread)
$recent_messages = $pdo->query("SELECT m.*, u.full_name as sender_name, o.order_number 
                                FROM messages m 
                                JOIN users u ON m.sender_id = u.id 
                                JOIN orders o ON m.order_id = o.id 
                                WHERE m.receiver_id = 1 AND m.is_read = 0 
                                ORDER BY m.created_at DESC LIMIT 5")->fetchAll();
?>

<style>
    .admin-container {
        margin: 2.5rem auto;
    }
    
    .admin-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--vintage-gold);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.8rem;
        border: 2px solid var(--vintage-gold);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--vintage-gold);
    }
    
    .stat-icon {
        font-size: 2.2rem;
        color: var(--vintage-gold);
        margin-bottom: 0.8rem;
    }
    
    .stat-number {
        font-size: 2.2rem;
        font-weight: bold;
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
    }
    
    .stat-label {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-top: 0.3rem;
    }
    
    .admin-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
    
    .admin-section {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 1.5rem;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(201, 169, 89, 0.3);
    }
    
    .section-header h2 {
        font-size: 1.4rem;
        color: var(--vintage-brown);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th, .data-table td {
        padding: 0.8rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
    }
    
    .data-table th {
        background: var(--vintage-cream);
        color: var(--vintage-brown);
        font-weight: 700;
    }
    
    .status-select {
        padding: 0.4rem;
        border: 1px solid var(--vintage-gold);
        background: var(--vintage-paper);
        font-size: 0.85rem;
        cursor: pointer;
    }
    
    .message-preview {
        padding: 1rem;
        background: var(--vintage-cream);
        margin-bottom: 1rem;
        border-left: 3px solid var(--vintage-gold);
        font-size: 0.9rem;
    }
    
    .message-preview strong {
        color: var(--vintage-brown);
        display: block;
        margin-bottom: 0.3rem;
    }
    
    .message-preview small {
        color: var(--text-muted);
        display: block;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 968px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container admin-container">
    <div class="admin-header">
        <h1 style="color: var(--vintage-brown);">Artist Dashboard</h1>
        <p style="color: var(--text-muted);">Welcome back, <?php echo $_SESSION['name']; ?></p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-paint-brush"></i></div>
            <div class="stat-number"><?php echo $stats['active_orders']; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $stats['total_customers']; ?></div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-number">
                <?php echo formatPrice($stats['total_revenue']); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <div class="admin-grid">
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-shopping-bag" style="color: var(--vintage-gold); margin-right: 0.5rem;"></i> Recent Orders</h2>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td><strong><?php echo $order['order_number']; ?></strong></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['artwork_type']; ?></td>
                            <td><?php echo formatPrice($order['price']); ?></td>
                            <td>
                                <form method="POST" action="update_status.php" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $order['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div>
            <div class="admin-section" style="margin-bottom: 2rem;">
                <div class="section-header">
                    <h2><i class="fas fa-comments" style="color: var(--vintage-gold); margin-right: 0.5rem;"></i> Recent Messages</h2>
                    <a href="<?php echo SITE_URL; ?>/admin/messages.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                
                <?php if(count($recent_messages) > 0): ?>
                    <?php foreach($recent_messages as $msg): ?>
                        <div class="message-preview">
                            <strong><?php echo $msg['sender_name']; ?></strong>
                            <small><?php echo $msg['order_number']; ?> • <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></small>
                            <p style="margin: 0; color: var(--text-muted);"><?php echo substr($msg['message'], 0, 60); ?>...</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No new messages</p>
                <?php endif; ?>
            </div>
            
            <div class="admin-section">
    <div class="section-header">
        <h2>Quick Actions</h2>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.8rem;">

        <a href="<?php echo SITE_URL; ?>/admin/add_artwork.php" class="btn btn-outline" style="text-align: center;">
            <i class="fas fa-image" style="margin-right: 0.5rem;"></i> Add Gallery Artwork
        </a>

        <a href="<?php echo SITE_URL; ?>/admin/artworks.php" class="btn btn-outline" style="text-align: center;">
    <i class="fas fa-images" style="margin-right: 0.5rem;"></i> Manage Artworks
</a>

        <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending" class="btn btn-outline" style="text-align: center;">
    <i class="fas fa-clock" style="margin-right: 0.5rem;"></i> View Pending Orders
</a>


       <a href="<?php echo SITE_URL; ?>/admin/customers.php" class="btn btn-outline" style="text-align: center;">
    <i class="fas fa-users" style="margin-right: 0.5rem;"></i> Manage Customers
</a>

    </div>
</div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>