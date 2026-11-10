<?php
$pageTitle = 'Manage Customers';
require_once '../includes/header.php';

checkAdmin();

/*
|--------------------------------------------------------------------------
| Ensure is_active column exists
|--------------------------------------------------------------------------
| Some database versions may not have is_active column.
| This automatically adds it if missing.
*/
try {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'")->fetch();
    if (!$col) {
        $pdo->query("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
    }
} catch (Exception $e) {
    // Ignore if column already exists or permission issue
}

// Activate / Deactivate customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $customer_id = intval($_POST['customer_id']);

    // Do not update admin accounts
    $stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if ($customer) {
        $new_status = $customer['is_active'] ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'");
        $stmt->execute([$new_status, $customer_id]);

        $_SESSION['success'] = $new_status ? "Customer activated successfully." : "Customer deactivated successfully.";
    }

    redirect('/admin/customers.php');
}

// Search
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Customer statistics
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_customers,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_customers,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_customers
    FROM users
    WHERE role = 'customer'
")->fetch();

// Fetch customers
if ($search !== '') {
    $searchParam = '%' . $search . '%';

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.is_active,
            u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.price ELSE 0 END), 0) AS total_spent,
            MAX(o.created_at) AS last_order_date,
            (
                SELECT id 
                FROM orders 
                WHERE customer_id = u.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) AS latest_order_id
        FROM users u
        LEFT JOIN orders o ON u.id = o.customer_id
        WHERE u.role = 'customer'
        AND (
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");

    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.is_active,
            u.created_at,
            COUNT(o.id) AS order_count,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.price ELSE 0 END), 0) AS total_spent,
            MAX(o.created_at) AS last_order_date,
            (
                SELECT id 
                FROM orders 
                WHERE customer_id = u.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) AS latest_order_id
        FROM users u
        LEFT JOIN orders o ON u.id = o.customer_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
}

$customers = $stmt->fetchAll();
?>

<style>
    .admin-page-header {
        margin: 2rem 0;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--vintage-gold);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border: 2px solid var(--vintage-gold);
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
        margin-top: 0.3rem;
    }

    .customer-section {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 2rem;
    }

    .section-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .search-form {
        display: flex;
        gap: 0.5rem;
        min-width: 320px;
    }

    .search-form input {
        flex: 1;
    }

    .customers-table {
        width: 100%;
        border-collapse: collapse;
    }

    .customers-table th,
    .customers-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .customers-table th {
        background: var(--vintage-cream);
        color: var(--vintage-brown);
        font-family: 'Playfair Display', serif;
    }

    .status-badge {
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
    }

    .status-active {
        background: #d4edda;
        color: #155724;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .empty-box {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--text-muted);
    }

    @media (max-width: 900px) {
        .customers-table {
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
            <i class="fas fa-users" style="color: var(--vintage-gold);"></i>
            Manage Customers
        </h1>
        <p style="color: var(--text-muted);">
            View customer details, order count, payment totals, and account status.
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
            <div class="stat-number"><?php echo $stats['total_customers'] ?? 0; ?></div>
            <div class="stat-label">Total Customers</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['active_customers'] ?? 0; ?></div>
            <div class="stat-label">Active Customers</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['inactive_customers'] ?? 0; ?></div>
            <div class="stat-label">Inactive Customers</div>
        </div>
    </div>

    <div class="customer-section">
        <div class="section-top">
            <h2>Customer List</h2>

            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="q" 
                    class="form-control" 
                    placeholder="Search name, email, phone..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>

                <?php if ($search !== ''): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/customers.php" class="btn btn-outline btn-sm">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($customers) > 0): ?>
            <table class="customers-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Total Paid</th>
                        <th>Last Order</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                <small>ID: <?php echo $customer['id']; ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($customer['email']); ?><br>
                                <small><?php echo htmlspecialchars($customer['phone'] ?? 'No phone'); ?></small>
                            </td>

                            <td>
                                <strong><?php echo $customer['order_count']; ?></strong>
                            </td>

                            <td>
                                $<?php echo number_format($customer['total_spent'], 2); ?>
                            </td>

                            <td>
                                <?php if ($customer['last_order_date']): ?>
                                    <?php echo date('M d, Y', strtotime($customer['last_order_date'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">No orders</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($customer['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <?php if ($customer['latest_order_id']): ?>
                                        <a 
                                            href="<?php echo SITE_URL; ?>/admin/messages.php?order_id=<?php echo $customer['latest_order_id']; ?>" 
                                            class="btn btn-gold btn-sm"
                                            title="Message Customer"
                                        >
                                            <i class="fas fa-comment"></i>
                                        </a>
                                    <?php endif; ?>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <button 
                                            type="submit" 
                                            name="toggle_status" 
                                            class="btn btn-outline btn-sm"
                                            onclick="return confirm('Are you sure you want to change this customer status?');"
                                        >
                                            <?php if ($customer['is_active']): ?>
                                                Deactivate
                                            <?php else: ?>
                                                Activate
                                            <?php endif; ?>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-box">
                <i class="fas fa-users" style="font-size: 3rem; color: var(--vintage-gold); margin-bottom: 1rem;"></i>
                <h3>No customers found</h3>
                <p>No customer records match your search.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>