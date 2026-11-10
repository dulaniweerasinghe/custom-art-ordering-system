<?php 
$pageTitle = 'Delivery Details';
require_once '../includes/header.php';

checkAuth();

if(isAdmin()) {
    redirect('/admin/dashboard.php');
}

if(!isset($_GET['order_id'])) {
    redirect('/customer/dashboard.php');
}

$order_id = intval($_GET['order_id']);

// Fetch order with user details
$stmt = $pdo->prepare("SELECT o.*, u.email, u.full_name, u.phone 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ? AND o.customer_id = ? AND o.payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('/customer/dashboard.php');
}

$error = '';
$success = false;

// Handle POST - save delivery details and redirect to checkout
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_name    = sanitize($_POST['recipient_name']);
    $recipient_phone   = sanitize($_POST['recipient_phone']);
    $delivery_address  = sanitize($_POST['delivery_address']);
    $city              = sanitize($_POST['city']);
    $postal_code       = sanitize($_POST['postal_code']);
    $special_notes     = sanitize($_POST['special_notes'] ?? '');

    // Validation
    if(empty($recipient_name) || empty($recipient_phone) || empty($delivery_address) || empty($city)) {
        $error = "Please fill in all required fields.";
    } else {
        // Build full delivery address string
        $full_address = $recipient_name . "\n"
                      . $recipient_phone . "\n"
                      . $delivery_address . "\n"
                      . $city
                      . ($postal_code ? ", " . $postal_code : "")
                      . ($special_notes ? "\nNotes: " . $special_notes : "");

        // Store delivery details in the orders table
        $stmt2 = $pdo->prepare("UPDATE orders SET delivery_address = ? WHERE id = ? AND customer_id = ?");
        if($stmt2->execute([$full_address, $order_id, $_SESSION['user_id']])) {
            // Redirect to payment page
            redirect("/customer/checkout.php?order_id=$order_id");
        } else {
            $error = "Failed to save delivery details. Please try again.";
        }
    }
}

$subtotal = $order['price'];
$tax      = $subtotal * 0.10;
$total    = $subtotal + $tax;
?>

<style>
/* =============================================
   DELIVERY DETAILS PAGE
============================================= */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

.delivery-page-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f2e9 0%, #faf8f3 50%, #f0ebe0 100%);
    padding: 3rem 1rem;
}

.delivery-container {
    max-width: 900px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease;
}

/* ---- Progress Steps ---- */
.checkout-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 2.5rem;
}
.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    position: relative;
}
.step-circle {
    width: 44px; height: 44px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem;
    border: 2px solid #d9d0bc;
    background: white;
    color: #bbb;
    transition: all 0.3s;
}
.step-circle.done {
    background: var(--vintage-gold);
    border-color: var(--vintage-gold);
    color: white;
}
.step-circle.active {
    background: var(--vintage-brown);
    border-color: var(--vintage-brown);
    color: white;
    box-shadow: 0 0 0 5px rgba(93,64,55,0.15);
}
.step-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #bbb;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}
.step-label.active { color: var(--vintage-brown); }
.step-label.done   { color: var(--vintage-gold); }
.progress-line {
    flex: 1;
    height: 2px;
    background: #d9d0bc;
    min-width: 60px;
    margin-bottom: 22px;
}
.progress-line.done { background: var(--vintage-gold); }

/* ---- Main Card ---- */
.delivery-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(44, 36, 27, 0.12);
}

.card-header {
    background: linear-gradient(135deg, var(--vintage-brown) 0%, #3e2723 100%);
    padding: 2rem 2.5rem;
    position: relative;
    overflow: hidden;
}
.card-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -20%;
    width: 200%; height: 200%;
    background: radial-gradient(ellipse at center, rgba(201,169,89,0.15) 0%, transparent 70%);
}
.card-header h2 {
    color: white;
    font-size: 1.6rem;
    margin-bottom: 0.3rem;
    position: relative;
}
.card-header p {
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    position: relative;
}

.card-body { padding: 2.5rem; }

/* ---- Form Styling ---- */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.2rem;
}
.form-grid .full-width { grid-column: 1 / -1; }

.form-group-del {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.form-group-del label {
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--vintage-brown);
}
.form-group-del label .required {
    color: #e53935;
    margin-left: 2px;
}
.del-input {
    border: 2px solid #e8e3d8;
    border-radius: 10px;
    padding: 0.8rem 1rem;
    font-size: 0.95rem;
    font-family: 'Lato', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fdfcf9;
    color: var(--vintage-dark);
    width: 100%;
}
.del-input:focus {
    outline: none;
    border-color: var(--vintage-gold);
    box-shadow: 0 0 0 3px rgba(201,169,89,0.15);
    background: white;
}
.del-input.error { border-color: #e53935; }
textarea.del-input { resize: vertical; min-height: 90px; }

/* ---- Order Summary Sidebar ---- */
.page-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
    align-items: start;
}

.order-summary-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    position: sticky;
    top: 2rem;
}
.summary-header {
    background: linear-gradient(135deg, #faf8f3, #f5f0e5);
    border-bottom: 2px solid var(--vintage-gold);
    padding: 1.4rem 1.8rem;
}
.summary-header h3 {
    font-size: 1.1rem;
    color: var(--vintage-brown);
    margin-bottom: 0.2rem;
}
.summary-header p { font-size: 0.82rem; color: var(--text-muted); }
.summary-body { padding: 1.5rem 1.8rem; }

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.65rem 0;
    border-bottom: 1px dashed rgba(201,169,89,0.3);
    font-size: 0.9rem;
    color: var(--text-muted);
}
.summary-row:last-child { border-bottom: none; }
.summary-row.total {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--vintage-brown);
    padding-top: 1rem;
    border-top: 2px solid var(--vintage-gold);
    border-bottom: none;
}
.summary-row span:last-child { font-weight: 600; color: var(--vintage-dark); }

.artwork-badge {
    background: linear-gradient(135deg, var(--vintage-cream), #f0ebe0);
    border: 1px solid rgba(201,169,89,0.4);
    border-radius: 10px;
    padding: 0.8rem 1rem;
    margin-bottom: 1.2rem;
    font-size: 0.88rem;
}
.artwork-badge .badge-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--vintage-gold);
    margin-bottom: 0.25rem;
}
.artwork-badge .badge-value { color: var(--vintage-brown); font-weight: 600; }

/* ---- Proceed Button ---- */
.proceed-btn {
    width: 100%;
    padding: 1.1rem;
    background: linear-gradient(135deg, var(--vintage-brown) 0%, #3e2723 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    font-family: 'Lato', sans-serif;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.3s ease;
    margin-top: 1.5rem;
    letter-spacing: 0.04em;
}
.proceed-btn:hover {
    background: linear-gradient(135deg, #3e2723 0%, #2c241b 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(44,36,27,0.3);
}
.proceed-btn:active { transform: translateY(0); }

.security-note {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.8rem;
}

/* ---- Alert ---- */
.alert-error-del {
    background: #fdecea;
    border: 1px solid #f5c6c6;
    border-left: 4px solid #e53935;
    border-radius: 8px;
    padding: 1rem 1.2rem;
    color: #b71c1c;
    margin-bottom: 1.5rem;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

/* ---- Delivery Icon Banner ---- */
.delivery-icon-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.8rem;
}
.delivery-feature {
    flex: 1;
    background: #fdfcf8;
    border: 1px solid #e8e3d8;
    border-radius: 10px;
    padding: 0.8rem;
    text-align: center;
    font-size: 0.8rem;
    color: var(--text-muted);
}
.delivery-feature i {
    display: block;
    font-size: 1.4rem;
    color: var(--vintage-gold);
    margin-bottom: 0.3rem;
}

@media (max-width: 768px) {
    .page-layout { grid-template-columns: 1fr; }
    .form-grid   { grid-template-columns: 1fr; }
    .order-summary-card { position: static; }
    .delivery-icon-row { flex-wrap: wrap; }
    .card-body { padding: 1.5rem; }
    .card-header { padding: 1.5rem; }
}
</style>

<div class="delivery-page-wrapper">
<div class="delivery-container">

    <!-- Progress Indicator -->
    <div class="checkout-progress">
        <div class="progress-step">
            <div class="step-circle done"><i class="fas fa-check"></i></div>
            <span class="step-label done">Order</span>
        </div>
        <div class="progress-line done"></div>
        <div class="progress-step">
            <div class="step-circle active">2</div>
            <span class="step-label active">Delivery</span>
        </div>
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-circle">3</div>
            <span class="step-label">Payment</span>
        </div>
    </div>

    <div class="page-layout">

        <!-- LEFT: Delivery Form -->
        <div class="delivery-card">
            <div class="card-header">
                <h2><i class="fas fa-truck" style="color:var(--vintage-gold); margin-right:0.6rem;"></i>Delivery Details</h2>
                <p>Please fill in where you'd like your artwork delivered</p>
            </div>

            <div class="card-body">

                <?php if($error): ?>
                <div class="alert-error-del">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Feature Icons -->
                <div class="delivery-icon-row">
                    <div class="delivery-feature">
                        <i class="fas fa-box-open"></i>
                        Carefully Packaged
                    </div>
                    <div class="delivery-feature">
                        <i class="fas fa-shield-alt"></i>
                        Insured Delivery
                    </div>
                    <div class="delivery-feature">
                        <i class="fas fa-clock"></i>
                        Tracked Shipping
                    </div>
                    <div class="delivery-feature">
                        <i class="fas fa-headset"></i>
                        Support 24/7
                    </div>
                </div>

                <form method="POST" action="" id="deliveryForm">
                    <div class="form-grid">

                        <!-- Recipient Name -->
                        <div class="form-group-del">
                            <label>Recipient Name <span class="required">*</span></label>
                            <input type="text" name="recipient_name" class="del-input" 
                                   placeholder="Full name of recipient"
                                   value="<?php echo htmlspecialchars($order['full_name']); ?>"
                                   required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group-del">
                            <label>Phone Number <span class="required">*</span></label>
                            <input type="tel" name="recipient_phone" class="del-input" 
                                   placeholder="e.g. 077 123 4567"
                                   value="<?php echo htmlspecialchars($order['phone'] ?? ''); ?>"
                                   required>
                        </div>

                        <!-- Address Line -->
                        <div class="form-group-del full-width">
                            <label>Street Address / House No. <span class="required">*</span></label>
                            <input type="text" name="delivery_address" class="del-input" 
                                   placeholder="No. 12, Lotus Road, Apartment 3B..."
                                   required>
                        </div>

                        <!-- City -->
                        <div class="form-group-del">
                            <label>City / Town <span class="required">*</span></label>
                            <input type="text" name="city" class="del-input" 
                                   placeholder="Colombo, Kandy, Galle..."
                                   required>
                        </div>

                        <!-- Postal Code -->
                        <div class="form-group-del">
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" class="del-input" 
                                   placeholder="e.g. 10001">
                        </div>

                        <!-- Special Notes -->
                        <div class="form-group-del full-width">
                            <label>Delivery Notes <small style="font-weight:400; text-transform:none; letter-spacing:0;">(optional)</small></label>
                            <textarea name="special_notes" class="del-input" 
                                      placeholder="Landmark, gate instructions, preferred delivery time, etc."></textarea>
                        </div>

                    </div>

                    <button type="submit" class="proceed-btn" id="proceedBtn">
                        <i class="fas fa-credit-card"></i>
                        Proceed to Payment
                        <i class="fas fa-arrow-right" style="margin-left:auto;"></i>
                    </button>
                    <div class="security-note">
                        <i class="fas fa-lock"></i> Your details are safe and encrypted
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="order-summary-card">
            <div class="summary-header">
                <h3><i class="fas fa-receipt" style="color:var(--vintage-gold); margin-right:0.5rem;"></i>Order Summary</h3>
                <p>Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
            </div>
            <div class="summary-body">

                <div class="artwork-badge">
                    <div class="badge-label"><i class="fas fa-paint-brush" style="margin-right:4px;"></i>Artwork Type</div>
                    <div class="badge-value"><?php echo htmlspecialchars($order['artwork_type']); ?></div>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatPrice($order['price']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (10%)</span>
                    <span><?php echo formatPrice($tax); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span><?php echo formatPrice($total); ?></span>
                </div>

                <div style="margin-top:1.2rem; padding:0.9rem; background:#faf8f3; border-radius:8px; font-size:0.82rem; color:var(--text-muted); line-height:1.6;">
                    <i class="fas fa-info-circle" style="color:var(--vintage-gold); margin-right:4px;"></i>
                    You will choose your payment method on the next step.
                </div>

            </div>
        </div>

    </div><!-- /page-layout -->

</div><!-- /delivery-container -->
</div><!-- /delivery-page-wrapper -->

<script>
// Client-side validation feedback
document.getElementById('deliveryForm').addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('input[required], textarea[required]');
    let valid = true;
    inputs.forEach(inp => {
        if(!inp.value.trim()) {
            inp.classList.add('error');
            valid = false;
        } else {
            inp.classList.remove('error');
        }
    });
    if(!valid) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        const btn = document.getElementById('proceedBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;
    }
});

// Remove error class on input
document.querySelectorAll('.del-input').forEach(inp => {
    inp.addEventListener('input', function() {
        this.classList.remove('error');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
