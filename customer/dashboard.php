<?php 
$pageTitle = 'My Dashboard';
require_once '../includes/header.php';

checkAuth();

// Fetch customer orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Fetch unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Count stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM orders WHERE customer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>

<style>
    .dashboard {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2.5rem;
        margin: 2.5rem 0;
    }
    
    .sidebar {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2rem;
        height: fit-content;
        position: sticky;
        top: 100px;
    }
    
    .user-info {
        text-align: center;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(201, 169, 89, 0.3);
        margin-bottom: 1.5rem;
    }
    
    .user-avatar {
        width: 90px;
        height: 90px;
        background: var(--vintage-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        margin: 0 auto 1rem;
        overflow: hidden;
        border: 3px solid var(--vintage-gold);
        box-shadow: 0 2px 12px rgba(201,169,89,0.3);
    }
    .user-avatar img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .edit-profile-link {
        display: inline-flex; align-items: center; gap: 0.35rem;
        font-size: 0.78rem; color: var(--vintage-gold);
        text-decoration: none; font-weight: 600;
        margin-top: 0.3rem; transition: color 0.2s;
    }
    .edit-profile-link:hover { color: var(--vintage-brown); }
    
    .sidebar-menu {
        list-style: none;
    }
    
    .sidebar-menu li {
        margin-bottom: 0.5rem;
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem;
        color: var(--vintage-dark);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.3s;
        border-radius: 4px;
    }
    
    .sidebar-menu a:hover, .sidebar-menu a.active {
        background: var(--vintage-cream);
        border-left-color: var(--vintage-gold);
        color: var(--vintage-brown);
    }
    
    .main-content {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-box {
        background: var(--vintage-cream);
        padding: 1.5rem;
        text-align: center;
        border: 1px solid var(--vintage-gold);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-top: 0.3rem;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }
    
    .orders-table th, .orders-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .orders-table th {
        background: var(--vintage-cream);
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
        font-weight: 700;
    }
    
    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-in_progress { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    .payment-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        margin-left: 0.5rem;
    }
    
    .payment-paid { background: #28a745; color: white; }
    .payment-pending { background: #ffc107; color: #000; }
    
    .notification-item {
        background: var(--vintage-cream);
        padding: 1rem;
        margin-bottom: 0.8rem;
        border-left: 3px solid var(--vintage-gold);
        font-size: 0.9rem;
    }
    
    .notification-item small {
        color: var(--text-muted);
        display: block;
        margin-bottom: 0.3rem;
    }
    
    @media (max-width: 968px) {
        .dashboard {
            grid-template-columns: 1fr;
        }
        .sidebar {
            position: static;
        }
    }
</style>

<div class="container">
    <div class="dashboard">
        <aside class="sidebar">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if(!empty($_SESSION['profile_image'])): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <h3 style="color: var(--vintage-brown); margin-bottom: 0.3rem;"><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <small style="color: var(--text-muted);">Customer</small><br>
                <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="edit-profile-link">
                    <i class="fas fa-pencil-alt"></i> Edit Profile
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>/gallery.php"><i class="fas fa-plus"></i> New Order</a></li>
                <li><a href="<?php echo SITE_URL; ?>/customer/messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <?php if(count($notifications) > 0): ?>
                <div style="margin-top: 2rem;">
                    <h4 style="color: var(--vintage-brown); margin-bottom: 1rem; font-size: 1rem;">Notifications</h4>
                    <?php foreach($notifications as $notif): ?>
                        <div class="notification-item">
                            <small><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                            <strong style="display: block; margin-bottom: 0.2rem;"><?php echo $notif['title']; ?></strong>
                            <?php echo $notif['message']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
        
        <div class="main-content">
            <h2 style="color: var(--vintage-brown); margin-bottom: 1.5rem;">My Orders</h2>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <?php if(count($orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $order['order_number']; ?></strong>
                                    <?php if($order['payment_status'] == 'paid'): ?>
                                        <span class="payment-badge payment-paid">Paid</span>
                                    <?php elseif($order['payment_status'] == 'cod_deposit_paid'): ?>
                                        <span class="payment-badge" style="background:#e65100;color:white;padding:3px 8px;border-radius:4px;font-size:0.75rem;font-weight:700;">COD 50%</span>
                                    <?php else: ?>
                                        <span class="payment-badge payment-pending">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $order['artwork_type']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>$<?php echo formatPrice($order['price']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                   <a href="<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
    View
</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-paint-brush" style="font-size: 3rem; color: var(--vintage-gold); margin-bottom: 1rem;"></i>
                    <h3>No orders yet</h3>
                    <p style="color: var(--text-muted); margin: 1rem 0;">Start by browsing our gallery and placing your first order.</p>
                    <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-primary">Browse Gallery</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>