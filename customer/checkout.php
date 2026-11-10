<?php 
$pageTitle = 'Checkout';
require_once '../includes/header.php';

checkAuth();

if(!isset($_GET['order_id'])) {
    redirect('/customer/dashboard.php');
}

$order_id = intval($_GET['order_id']);

$stmt = $pdo->prepare("SELECT o.*, u.email, u.full_name, u.phone 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ? AND o.customer_id = ? AND o.payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('/customer/dashboard.php');
}

$subtotal = $order['price'];
$tax = $subtotal * 0.10;
$total = $subtotal + $tax;
$cod_deposit = $total * 0.50;  // 50% upfront for COD
$cod_remaining = $total - $cod_deposit;
?>

<style>
/* =============================================
   CHECKOUT PAGE - PROFESSIONAL DESIGN
============================================= */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse-ring {
    0%   { transform: scale(0.8); opacity: 1; }
    80%  { transform: scale(1.3); opacity: 0; }
    100% { transform: scale(1.3); opacity: 0; }
}
@keyframes checkmark {
    0%   { stroke-dashoffset: 100; }
    100% { stroke-dashoffset: 0; }
}
@keyframes shimmer {
    0%   { background-position: -200% center; }
    100% { background-position: 200% center; }
}
@keyframes cardFlip {
    0%   { transform: rotateY(0deg); }
    50%  { transform: rotateY(90deg); }
    100% { transform: rotateY(0deg); }
}
@keyframes spin {
    to { transform: rotate(360deg); }
}

.checkout-page-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f2e9 0%, #faf8f3 50%, #f0ebe0 100%);
    padding: 3rem 1rem;
}

.checkout-grid {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 2rem;
    animation: fadeInUp 0.6s ease;
}

/* ---- LEFT: Payment Panel ---- */
.payment-panel {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(44, 36, 27, 0.12);
}

.panel-header {
    background: linear-gradient(135deg, var(--vintage-brown) 0%, #3e2723 100%);
    padding: 2rem 2.5rem;
    position: relative;
    overflow: hidden;
}
.panel-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -20%;
    width: 200%; height: 200%;
    background: radial-gradient(ellipse at center, rgba(201,169,89,0.15) 0%, transparent 70%);
}
.panel-header h2 {
    color: white;
    font-size: 1.6rem;
    margin-bottom: 0.3rem;
    position: relative;
}
.panel-header p {
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    position: relative;
}
.secure-icons {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    position: relative;
}
.secure-icon {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    color: rgba(255,255,255,0.85);
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.panel-body { padding: 2.5rem; }

/* ---- Payment Method Selection ---- */
.payment-methods-label {
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--vintage-brown);
    margin-bottom: 1rem;
}

.payment-method-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    margin-bottom: 2rem;
}

.payment-method-item {
    border: 2px solid #e8e3d8;
    border-radius: 12px;
    padding: 1.1rem 1.4rem;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    background: white;
}
.payment-method-item:hover {
    border-color: var(--vintage-gold);
    background: #fdfcf8;
    transform: translateX(3px);
}
.payment-method-item.active {
    border-color: var(--vintage-gold);
    background: linear-gradient(135deg, #fdfcf5 0%, #faf7ed 100%);
    box-shadow: 0 4px 20px rgba(201,169,89,0.2);
}
.payment-method-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--vintage-gold);
    border-radius: 12px 0 0 12px;
}

.method-radio {
    width: 20px; height: 20px;
    border: 2px solid #ccc;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}
.payment-method-item.active .method-radio {
    border-color: var(--vintage-gold);
    background: var(--vintage-gold);
}
.method-radio::after {
    content: '';
    width: 8px; height: 8px;
    border-radius: 50%;
    background: white;
    opacity: 0;
    transition: opacity 0.2s;
}
.payment-method-item.active .method-radio::after { opacity: 1; }

.method-icon {
    width: 44px; height: 30px;
    background: #f5f5f5;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.method-info { flex: 1; }
.method-name {
    font-weight: 700;
    color: var(--vintage-dark);
    font-size: 0.95rem;
}
.method-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.1rem;
}

.method-badges {
    display: flex;
    gap: 0.4rem;
    margin-left: auto;
}
.badge-img {
    height: 20px;
    width: auto;
    border-radius: 3px;
    background: linear-gradient(135deg, #1a1f71, #00538a);
    color: white;
    font-size: 0.6rem;
    font-weight: 900;
    padding: 2px 5px;
    display: flex;
    align-items: center;
    letter-spacing: 0.05em;
}
.badge-visa { background: linear-gradient(135deg, #1a1f71, #003087); }
.badge-mc { background: linear-gradient(135deg, #eb001b, #f79e1b); }
.badge-pp { background: linear-gradient(135deg, #003087, #009cde); }
.badge-bt { background: linear-gradient(135deg, #2d7d46, #1a5c33); }
.badge-cod { background: linear-gradient(135deg, #e65100, #bf360c); }

/* ---- COD Section ---- */
.cod-deposit-highlight {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border: 2px solid #ff6f00;
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.cod-deposit-highlight .cod-icon {
    font-size: 2rem;
    line-height: 1;
    flex-shrink: 0;
}
.cod-deposit-highlight h4 {
    color: #e65100;
    font-size: 1rem;
    margin-bottom: 0.3rem;
}
.cod-deposit-highlight p {
    font-size: 0.85rem;
    color: #bf360c;
    margin: 0;
    line-height: 1.5;
}
.cod-amounts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.8rem;
    margin-bottom: 1.4rem;
}
.cod-amount-card {
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
}
.cod-amount-card.pay-now {
    background: linear-gradient(135deg, #e65100, #bf360c);
    color: white;
    box-shadow: 0 4px 15px rgba(230,81,0,0.3);
}
.cod-amount-card.pay-later {
    background: #f5f5f5;
    border: 2px dashed #bdbdbd;
    color: #616161;
}
.cod-amount-card .amount-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 0.3rem;
    opacity: 0.85;
}
.cod-amount-card .amount-value {
    font-size: 1.35rem;
    font-weight: 800;
}
.cod-amount-card .amount-note {
    font-size: 0.72rem;
    margin-top: 0.2rem;
    opacity: 0.75;
}
.cod-address-field {
    margin-bottom: 1.2rem;
}
.cod-address-field label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--vintage-brown);
    margin-bottom: 0.5rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.cod-address-field textarea {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 2px solid #e8e3d8;
    border-radius: 10px;
    font-size: 0.92rem;
    font-family: inherit;
    color: var(--vintage-dark);
    background: #fdfcf9;
    transition: all 0.2s;
    outline: none;
    resize: vertical;
    min-height: 80px;
    box-sizing: border-box;
}
.cod-address-field textarea:focus {
    border-color: #ff6f00;
    background: white;
    box-shadow: 0 0 0 4px rgba(255,111,0,0.1);
}
.cod-address-field textarea.error { border-color: #dc3545; }
.cod-steps {
    background: #fafafa;
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
}
.cod-steps h5 {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--vintage-brown);
    margin-bottom: 0.7rem;
}
.cod-step {
    display: flex;
    align-items: flex-start;
    gap: 0.8rem;
    padding: 0.4rem 0;
    font-size: 0.84rem;
    color: #616161;
}
.cod-step-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e65100;
    color: white;
    font-size: 0.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 0.05rem;
}


/* ---- Payment Form Sections ---- */
.payment-form-section {
    display: none;
    animation: fadeInUp 0.4s ease;
}
.payment-form-section.active { display: block; }

/* ---- Card Form ---- */
.card-preview {
    background: linear-gradient(135deg, var(--vintage-brown) 0%, #1a0f0a 100%);
    border-radius: 16px;
    padding: 1.8rem;
    margin-bottom: 1.8rem;
    position: relative;
    overflow: hidden;
    color: white;
    box-shadow: 0 15px 40px rgba(44,36,27,0.4);
    min-height: 170px;
}
.card-preview::before {
    content: '';
    position: absolute;
    top: -30%; right: -20%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(201,169,89,0.25) 0%, transparent 70%);
    border-radius: 50%;
}
.card-preview::after {
    content: '';
    position: absolute;
    bottom: -40%; left: -10%;
    width: 250px; height: 250px;
    background: radial-gradient(circle, rgba(201,169,89,0.12) 0%, transparent 70%);
    border-radius: 50%;
}
.card-chip {
    width: 42px; height: 32px;
    background: linear-gradient(135deg, var(--vintage-gold), #a87c2a);
    border-radius: 6px;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}
.card-chip::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 28px; height: 20px;
    border: 1.5px solid rgba(0,0,0,0.3);
    border-radius: 3px;
}
.card-number-display {
    font-size: 1.25rem;
    letter-spacing: 0.22em;
    margin-bottom: 1.2rem;
    font-family: 'Courier New', monospace;
    position: relative;
    z-index: 1;
    color: rgba(255,255,255,0.9);
}
.card-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    position: relative;
    z-index: 1;
}
.card-label {
    font-size: 0.65rem;
    letter-spacing: 0.1em;
    color: rgba(255,255,255,0.5);
    text-transform: uppercase;
    margin-bottom: 0.2rem;
}
.card-value {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.9);
    letter-spacing: 0.05em;
}
.card-brand-logo {
    display: flex;
    gap: 0.2rem;
}
.card-brand-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    opacity: 0.9;
}
.circle-red { background: #eb001b; margin-right: -12px; }
.circle-orange { background: #f79e1b; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.form-field {
    margin-bottom: 1.2rem;
}
.form-field label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--vintage-brown);
    margin-bottom: 0.5rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.form-field input {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 2px solid #e8e3d8;
    border-radius: 10px;
    font-size: 0.95rem;
    font-family: inherit;
    color: var(--vintage-dark);
    background: #fdfcf9;
    transition: all 0.2s;
    outline: none;
}
.form-field input:focus {
    border-color: var(--vintage-gold);
    background: white;
    box-shadow: 0 0 0 4px rgba(201,169,89,0.12);
}
.form-field input.error { border-color: #dc3545; }
.form-field input.success { border-color: #28a745; }
.field-error {
    color: #dc3545;
    font-size: 0.78rem;
    margin-top: 0.3rem;
    display: none;
}

/* ---- Bank Transfer / PayPal Info ---- */
.info-box {
    background: linear-gradient(135deg, #f8f6f0, #f5f2e8);
    border: 1px solid var(--vintage-gold);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.info-box h4 {
    color: var(--vintage-brown);
    margin-bottom: 0.8rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px dashed rgba(201,169,89,0.3);
    font-size: 0.9rem;
}
.info-row:last-child { border-bottom: none; }
.info-row strong { color: var(--vintage-dark); }

/* ---- Pay Button ---- */
.pay-btn {
    width: 100%;
    padding: 1.1rem;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--vintage-brown) 0%, #3e2723 100%);
    color: white;
    letter-spacing: 0.03em;
    margin-top: 0.5rem;
}
.pay-btn::before {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: left 0.4s;
}
.pay-btn:hover::before { left: 100%; }
.pay-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(44,36,27,0.35);
}
.pay-btn:active { transform: translateY(0); }
.pay-btn:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    transform: none;
}
.pay-btn .spinner {
    width: 20px; height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: none;
}
.pay-btn.loading .spinner { display: block; }
.pay-btn.loading .btn-text { display: none; }

.security-note {
    text-align: center;
    color: var(--text-muted);
    font-size: 0.8rem;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

/* ---- RIGHT: Order Summary ---- */
.summary-panel {
    position: sticky;
    top: 2rem;
}

.summary-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(44, 36, 27, 0.12);
    margin-bottom: 1.5rem;
}
.summary-header {
    background: linear-gradient(135deg, var(--vintage-gold) 0%, #a87c2a 100%);
    padding: 1.5rem 2rem;
    color: white;
}
.summary-header h3 {
    color: white;
    font-size: 1.15rem;
    margin-bottom: 0.2rem;
}
.summary-header p { color: rgba(255,255,255,0.8); font-size: 0.85rem; }

.summary-body { padding: 1.8rem 2rem; }

.artwork-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px dashed rgba(201,169,89,0.25);
    margin-bottom: 1.5rem;
}
.artwork-icon {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, var(--vintage-cream), #ede8d8);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
    border: 2px solid var(--vintage-gold);
}
.artwork-name { font-weight: 700; color: var(--vintage-dark); }
.artwork-type { font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem; }

.price-breakdown .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.7rem 0;
    font-size: 0.92rem;
    border-bottom: 1px solid #f5f0e8;
}
.price-breakdown .row:last-child { border-bottom: none; }
.price-breakdown .row.total {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--vintage-brown);
    padding-top: 1rem;
    border-top: 2px solid var(--vintage-gold);
    border-bottom: none;
    margin-top: 0.5rem;
}

.trust-badges {
    background: white;
    border-radius: 16px;
    padding: 1.5rem 2rem;
    box-shadow: 0 20px 60px rgba(44, 36, 27, 0.08);
}
.trust-badge-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.7rem 0;
    font-size: 0.85rem;
    color: var(--text-muted);
    border-bottom: 1px solid #f5f0e8;
}
.trust-badge-item:last-child { border-bottom: none; }
.trust-badge-item i {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.icon-green { background: #e8f5e9; color: #2e7d32; }
.icon-blue  { background: #e3f2fd; color: #1565c0; }
.icon-gold  { background: #fff8e1; color: #f57f17; }

/* =============================================
   SUCCESS DIALOG
============================================= */
.dialog-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(20,15,10,0.75);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
    animation: fadeInUp 0.3s ease;
}
.dialog-overlay.show { display: flex; }

.success-dialog {
    background: white;
    border-radius: 24px;
    padding: 3rem 2.5rem;
    max-width: 500px;
    width: 90%;
    text-align: center;
    position: relative;
    animation: fadeInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 40px 100px rgba(0,0,0,0.3);
}

.success-icon-wrapper {
    position: relative;
    width: 100px; height: 100px;
    margin: 0 auto 1.8rem;
}
.success-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 3px solid var(--vintage-gold);
    animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}
.success-circle {
    width: 100px; height: 100px;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    box-shadow: 0 10px 30px rgba(76,175,80,0.4);
}
.success-checkmark {
    width: 50px; height: 50px;
}
.checkmark-path {
    fill: none;
    stroke: white;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: checkmark 0.7s ease 0.3s forwards;
}

.success-dialog h2 {
    font-size: 1.8rem;
    color: var(--vintage-brown);
    margin-bottom: 0.7rem;
}
.success-dialog .subtitle {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.success-details {
    background: linear-gradient(135deg, #f8f6f0, #f5f2e8);
    border-radius: 12px;
    padding: 1.3rem;
    margin-bottom: 2rem;
    text-align: left;
}
.success-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.45rem 0;
    font-size: 0.88rem;
    border-bottom: 1px dashed rgba(201,169,89,0.3);
}
.success-detail-row:last-child { border-bottom: none; }
.success-detail-row span:first-child { color: var(--text-muted); }
.success-detail-row span:last-child { font-weight: 700; color: var(--vintage-dark); }

.email-notice {
    background: #e8f5e9;
    border-radius: 10px;
    padding: 0.9rem 1.2rem;
    font-size: 0.85rem;
    color: #2e7d32;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 2rem;
    text-align: left;
}

.dialog-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.btn-dialog-primary {
    padding: 0.9rem;
    background: linear-gradient(135deg, var(--vintage-brown), #3e2723);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-dialog-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(44,36,27,0.3); }

.btn-dialog-outline {
    padding: 0.9rem;
    background: white;
    color: var(--vintage-brown);
    border: 2px solid var(--vintage-brown);
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-dialog-outline:hover { background: var(--vintage-cream); }

@media (max-width: 768px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .summary-panel { position: static; }
    .form-row { grid-template-columns: 1fr; }
    .dialog-actions { grid-template-columns: 1fr; }
}
</style>

<!-- SUCCESS DIALOG -->
<div class="dialog-overlay" id="successDialog">
    <div class="success-dialog">
        <div class="success-icon-wrapper">
            <div class="success-ring"></div>
            <div class="success-circle">
                <svg class="success-checkmark" viewBox="0 0 52 52">
                    <path class="checkmark-path" d="M14 27 L22 35 L38 19"/>
                </svg>
            </div>
        </div>
        <h2>Payment Successful!</h2>
        <p class="subtitle">
            Your payment has been confirmed and your order is now in progress. 
            Our artist will begin working on your custom artwork right away.
        </p>
        <div class="success-details">
            <div class="success-detail-row">
                <span>Order Number</span>
                <span><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="success-detail-row">
                <span>Artwork Type</span>
                <span><?php echo htmlspecialchars($order['artwork_type']); ?></span>
            </div>
            <div class="success-detail-row">
                <span>Amount Paid</span>
                <span><?php echo formatPrice($total); ?></span>
            </div>
            <div class="success-detail-row">
                <span>Payment Method</span>
                <span id="successPaymentMethod">—</span>
            </div>
            <div class="success-detail-row">
                <span>Transaction ID</span>
                <span id="successTxnId" style="font-size:0.78rem;">—</span>
            </div>
        </div>
        <div class="email-notice">
            <i class="fas fa-envelope-circle-check"></i>
            <div>Confirmation emails have been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong> and the admin.</div>
        </div>
        <div class="dialog-actions">
            <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="btn-dialog-primary">
                <i class="fas fa-tachometer-alt"></i> My Dashboard
            </a>
            <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn-dialog-outline">
                <i class="fas fa-images"></i> Browse Gallery
            </a>
        </div>
    </div>
</div>

<style>
/* Progress Steps for Checkout */
.checkout-progress-bar {
    display: flex; align-items: center; justify-content: center;
    gap: 0; max-width: 1100px; margin: 0 auto 2rem auto;
}
.cp-step { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; }
.cp-circle {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem;
    border: 2px solid #d9d0bc; background: white; color: #bbb; transition: all 0.3s;
}
.cp-circle.done  { background: var(--vintage-gold); border-color: var(--vintage-gold); color: white; }
.cp-circle.active{ background: var(--vintage-brown); border-color: var(--vintage-brown); color: white; box-shadow: 0 0 0 5px rgba(93,64,55,0.15); }
.cp-label { font-size: 0.78rem; font-weight: 600; color: #bbb; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
.cp-label.active { color: var(--vintage-brown); }
.cp-label.done   { color: var(--vintage-gold); }
.cp-line { flex: 1; height: 2px; background: #d9d0bc; min-width: 60px; margin-bottom: 22px; }
.cp-line.done { background: var(--vintage-gold); }
.delivery-summary-strip {
    background: linear-gradient(135deg, #fdfcf5, #faf7ed);
    border: 1px solid rgba(201,169,89,0.4); border-radius: 10px;
    padding: 0.9rem 1.2rem; margin-bottom: 1.5rem;
    display: flex; align-items: flex-start; gap: 0.8rem; font-size: 0.88rem;
}
.delivery-summary-strip i { color: var(--vintage-gold); font-size: 1.1rem; flex-shrink: 0; margin-top: 2px; }
.delivery-summary-strip .addr-text { color: var(--vintage-dark); line-height: 1.5; flex: 1; white-space: pre-line; }
.delivery-summary-strip a {
    font-size: 0.78rem; color: var(--vintage-brown); font-weight: 700;
    text-decoration: none; white-space: nowrap;
    border-bottom: 1px dashed var(--vintage-brown); display: block; margin-top: 0.3rem;
}
.delivery-summary-strip a:hover { color: var(--vintage-gold); border-color: var(--vintage-gold); }
</style>

<div class="checkout-page-wrapper">

<!-- Progress Indicator -->
<div class="checkout-progress-bar">
    <div class="cp-step">
        <div class="cp-circle done"><i class="fas fa-check"></i></div>
        <span class="cp-label done">Order</span>
    </div>
    <div class="cp-line done"></div>
    <div class="cp-step">
        <div class="cp-circle done"><i class="fas fa-check"></i></div>
        <span class="cp-label done">Delivery</span>
    </div>
    <div class="cp-line done"></div>
    <div class="cp-step">
        <div class="cp-circle active">3</div>
        <span class="cp-label active">Payment</span>
    </div>
</div>

<div class="checkout-grid">

    <!-- LEFT: PAYMENT PANEL -->
    <div class="payment-panel">
        <div class="panel-header">
            <h2><i class="fas fa-lock" style="color:var(--vintage-gold); margin-right:0.5rem;"></i>Secure Checkout</h2>
            <p>Choose your preferred payment method below</p>
            <div class="secure-icons">
                <span class="secure-icon"><i class="fas fa-shield-alt"></i> SSL Encrypted</span>
                <span class="secure-icon"><i class="fas fa-user-shield"></i> Privacy Protected</span>
                <span class="secure-icon"><i class="fas fa-certificate"></i> Verified Secure</span>
            </div>
        </div>

        <div class="panel-body">

            <!-- Delivery Address Summary -->
            <?php if(!empty($order['delivery_address'])): ?>
            <div class="delivery-summary-strip">
                <i class="fas fa-truck"></i>
                <div>
                    <strong style="display:block; margin-bottom:3px; color:var(--vintage-brown);">Delivering to:</strong>
                    <span class="addr-text"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                    <a href="<?php echo SITE_URL; ?>/customer/delivery_details.php?order_id=<?php echo $order_id; ?>">
                        <i class="fas fa-edit" style="margin-right:3px;"></i> Change address
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Method Selector -->
            <div class="payment-methods-label">Select Payment Method</div>
            <div class="payment-method-list">
                <div class="payment-method-item active" data-method="card" onclick="selectMethod('card', this)">
                    <div class="method-radio"></div>
                    <div class="method-icon" style="background:#f0f4ff; font-size:1.1rem; color:#1a1f71;">💳</div>
                    <div class="method-info">
                        <div class="method-name">Credit / Debit Card</div>
                        <div class="method-desc">Visa, MasterCard, Amex</div>
                    </div>
                    <div class="method-badges">
                        <span class="badge-img badge-visa">VISA</span>
                        <span class="badge-img badge-mc">MC</span>
                    </div>
                </div>

                <div class="payment-method-item" data-method="paypal" onclick="selectMethod('paypal', this)">
                    <div class="method-radio"></div>
                    <div class="method-icon" style="background:#e3f2fd; color:#003087; font-size:1.1rem;">𝗣</div>
                    <div class="method-info">
                        <div class="method-name">PayPal</div>
                        <div class="method-desc">Pay with your PayPal account</div>
                    </div>
                    <div class="method-badges">
                        <span class="badge-img badge-pp">PayPal</span>
                    </div>
                </div>

                <div class="payment-method-item" data-method="bank" onclick="selectMethod('bank', this)">
                    <div class="method-radio"></div>
                    <div class="method-icon" style="background:#e8f5e9; color:#2e7d32; font-size:1.1rem;">🏦</div>
                    <div class="method-info">
                        <div class="method-name">Bank Transfer</div>
                        <div class="method-desc">Direct bank transfer</div>
                    </div>
                    <div class="method-badges">
                        <span class="badge-img badge-bt">BANK</span>
                    </div>
                </div>

                <div class="payment-method-item" data-method="cod" onclick="selectMethod('cod', this)">
                    <div class="method-radio"></div>
                    <div class="method-icon" style="background:#fff3e0; color:#e65100; font-size:1.2rem;">🚚</div>
                    <div class="method-info">
                        <div class="method-name">Cash on Delivery (COD)</div>
                        <div class="method-desc">Pay 50% now · 50% on delivery</div>
                    </div>
                    <div class="method-badges">
                        <span class="badge-img badge-cod">COD</span>
                    </div>
                </div>
            </div>

            <!-- ===== CARD PAYMENT FORM ===== -->
            <div class="payment-form-section active" id="section-card">
                <!-- Visual Card Preview -->
                <div class="card-preview" id="cardPreview">
                    <div class="card-chip"></div>
                    <div class="card-number-display" id="previewNumber">•••• •••• •••• ••••</div>
                    <div class="card-bottom">
                        <div>
                            <div class="card-label">Card Holder</div>
                            <div class="card-value" id="previewName">FULL NAME</div>
                        </div>
                        <div>
                            <div class="card-label">Expires</div>
                            <div class="card-value" id="previewExpiry">MM/YY</div>
                        </div>
                        <div class="card-brand-logo">
                            <div class="card-brand-circle circle-red"></div>
                            <div class="card-brand-circle circle-orange"></div>
                        </div>
                    </div>
                </div>

                <div class="form-field">
                    <label>Card Number *</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="cc-number">
                    <div class="field-error" id="cardNumberError">Please enter a valid 16-digit card number</div>
                </div>
                <div class="form-field">
                    <label>Cardholder Name *</label>
                    <input type="text" id="cardName" placeholder="John Doe" autocomplete="cc-name">
                    <div class="field-error" id="cardNameError">Please enter the cardholder name</div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Expiry Date *</label>
                        <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                        <div class="field-error" id="cardExpiryError">Enter valid expiry (MM/YY)</div>
                    </div>
                    <div class="form-field">
                        <label>CVV *</label>
                        <input type="text" id="cardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                        <div class="field-error" id="cardCvvError">Enter 3 or 4 digit CVV</div>
                    </div>
                </div>

                <button class="pay-btn" id="payCardBtn" onclick="processCardPayment()">
                    <div class="spinner"></div>
                    <span class="btn-text">
                        <i class="fas fa-lock"></i>
                        Pay <?php echo formatPrice($total); ?> Securely
                    </span>
                </button>
                <div class="security-note">
                    <i class="fas fa-shield-alt" style="color:var(--vintage-olive);"></i>
                    Your card information is encrypted and secure
                </div>
            </div>

            <!-- ===== PAYPAL SECTION ===== -->
            <div class="payment-form-section" id="section-paypal">
                <div class="info-box">
                    <h4><i class="fas fa-info-circle" style="color:var(--vintage-gold);"></i> PayPal Payment</h4>
                    <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:0.8rem;">
                        You will be redirected to PayPal to complete your payment securely. Once payment is confirmed, you'll be returned to this site.
                    </p>
                    <div class="info-row"><span>Amount to Pay</span><strong><?php echo formatPrice($total); ?></strong></div>
                    <div class="info-row"><span>PayPal Email</span><strong><?php echo htmlspecialchars($order['email']); ?></strong></div>
                </div>
                <button class="pay-btn" style="background: linear-gradient(135deg, #003087, #009cde);" onclick="processPaypalPayment()">
                    <div class="spinner"></div>
                    <span class="btn-text"><i class="fab fa-paypal"></i> Continue to PayPal</span>
                </button>
                <div class="security-note"><i class="fas fa-shield-alt" style="color:#003087;"></i> Secure PayPal checkout</div>
            </div>

            <!-- ===== BANK TRANSFER SECTION ===== -->
            <div class="payment-form-section" id="section-bank">
                <div class="info-box">
                    <h4><i class="fas fa-university" style="color:var(--vintage-gold);"></i> Bank Transfer Details</h4>
                    <div class="info-row"><span>Bank Name</span><strong>Bank of Ceylon</strong></div>
                    <div class="info-row"><span>Account Name</span><strong>Sketch-Core Arts</strong></div>
                    <div class="info-row"><span>Account Number</span><strong>0012 3456 7890</strong></div>
                    <div class="info-row"><span>Branch Code</span><strong>BOC-0099</strong></div>
                    <div class="info-row"><span>Reference</span><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></div>
                    <div class="info-row"><span>Amount</span><strong><?php echo formatPrice($total); ?></strong></div>
                </div>
                <button class="pay-btn" style="background: linear-gradient(135deg, #2d7d46, #1a5c33);" onclick="processBankPayment()">
                    <div class="spinner"></div>
                    <span class="btn-text"><i class="fas fa-check-circle"></i> Confirm Transfer Initiated</span>
                </button>
                <div class="security-note"><i class="fas fa-lock" style="color:#2d7d46;"></i> Your transfer details are kept confidential</div>
            </div>

            <!-- ===== COD SECTION ===== -->
            <div class="payment-form-section" id="section-cod">
                <div class="cod-deposit-highlight">
                    <div class="cod-icon">🚚</div>
                    <div>
                        <h4>Cash on Delivery — 50% Deposit Required</h4>
                        <p>To confirm your order, a <strong>50% deposit</strong> is charged now via card. The remaining 50% is paid in cash when your artwork is delivered.</p>
                    </div>
                </div>

                <div class="cod-amounts-grid">
                    <div class="cod-amount-card pay-now">
                        <div class="amount-label">💳 Pay Now (Deposit)</div>
                        <div class="amount-value"><?php echo formatPrice($cod_deposit); ?></div>
                        <div class="amount-note">50% · Charged immediately</div>
                    </div>
                    <div class="cod-amount-card pay-later">
                        <div class="amount-label">💵 Pay on Delivery</div>
                        <div class="amount-value"><?php echo formatPrice($cod_remaining); ?></div>
                        <div class="amount-note">50% · Cash on arrival</div>
                    </div>
                </div>

                <div class="cod-steps">
                    <h5>How COD Works</h5>
                    <div class="cod-step"><div class="cod-step-num">1</div><span>Pay the 50% deposit (<?php echo formatPrice($cod_deposit); ?>) now via card to confirm your order.</span></div>
                    <div class="cod-step"><div class="cod-step-num">2</div><span>Our artist creates your custom artwork.</span></div>
                    <div class="cod-step"><div class="cod-step-num">3</div><span>Artwork is delivered to your address.</span></div>
                    <div class="cod-step"><div class="cod-step-num">4</div><span>Pay the remaining <?php echo formatPrice($cod_remaining); ?> in cash to the delivery person.</span></div>
                </div>

                <!-- Delivery Address -->
                <div class="cod-address-field">
                    <label>Delivery Address *</label>
                    <textarea id="codAddress" placeholder="Enter your full delivery address including city, postal code..."><?php echo htmlspecialchars($order["delivery_address"] ?? ""); ?></textarea>
                    <div class="field-error" id="codAddressError" style="display:none;">Please enter your delivery address</div>
                </div>

                <!-- Card fields for deposit -->
                <div class="form-field">
                    <label>Card Number * <span style="font-size:0.75rem;color:#e65100;font-weight:normal;text-transform:none;">(For 50% deposit)</span></label>
                    <input type="text" id="codCardNumber" placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="cc-number">
                    <div class="field-error" id="codCardNumberError" style="display:none;">Please enter a valid 16-digit card number</div>
                </div>
                <div class="form-field">
                    <label>Cardholder Name *</label>
                    <input type="text" id="codCardName" placeholder="John Doe" autocomplete="cc-name">
                    <div class="field-error" id="codCardNameError" style="display:none;">Please enter the cardholder name</div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Expiry Date *</label>
                        <input type="text" id="codCardExpiry" placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                        <div class="field-error" id="codCardExpiryError" style="display:none;">Enter valid expiry (MM/YY)</div>
                    </div>
                    <div class="form-field">
                        <label>CVV *</label>
                        <input type="text" id="codCardCvv" placeholder="123" maxlength="4" autocomplete="cc-csc">
                        <div class="field-error" id="codCardCvvError" style="display:none;">Enter 3 or 4 digit CVV</div>
                    </div>
                </div>

                <button class="pay-btn" id="payCodBtn" style="background:linear-gradient(135deg,#e65100,#bf360c);" onclick="processCodPayment()">
                    <div class="spinner"></div>
                    <span class="btn-text">
                        <i class="fas fa-lock"></i>
                        Pay Deposit <?php echo formatPrice($cod_deposit); ?> &amp; Confirm COD
                    </span>
                </button>
                <div class="security-note">
                    <i class="fas fa-truck" style="color:#e65100;"></i>
                    Deposit secures your order · Remaining <?php echo formatPrice($cod_remaining); ?> paid on delivery
                </div>
            </div>

        </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="summary-panel">
        <div class="summary-card">
            <div class="summary-header">
                <h3><i class="fas fa-receipt" style="margin-right:0.5rem;"></i>Order Summary</h3>
                <p>Review your order before payment</p>
            </div>
            <div class="summary-body">
                <div class="artwork-preview">
                    <div class="artwork-icon">🎨</div>
                    <div>
                        <div class="artwork-name"><?php echo htmlspecialchars($order['artwork_type']); ?></div>
                        <div class="artwork-type">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                    </div>
                </div>
                <div class="price-breakdown">
                    <div class="row">
                        <span>Artwork Price</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="row">
                        <span>Service Tax (10%)</span>
                        <span><?php echo formatPrice($tax); ?></span>
                    </div>
                    <div class="row total">
                        <span>Total Due</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="trust-badges">
            <div class="trust-badge-item">
                <div class="trust-badge-item i icon-green"><i class="fas fa-shield-alt icon-green" style="background:#e8f5e9; color:#2e7d32; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center;"></i></div>
                <span>256-bit SSL encryption on all transactions</span>
            </div>
            <div class="trust-badge-item">
                <i class="fas fa-rotate-left icon-blue" style="background:#e3f2fd; color:#1565c0; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;"></i>
                <span>Satisfaction guarantee on all artworks</span>
            </div>
            <div class="trust-badge-item">
                <i class="fas fa-headset icon-gold" style="background:#fff8e1; color:#f57f17; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;"></i>
                <span>24/7 customer support available</span>
            </div>
        </div>
    </div>

</div><!-- .checkout-grid -->
</div><!-- .checkout-page-wrapper -->

<script>
const ORDER_ID = <?php echo $order_id; ?>;
const SITE_URL = '<?php echo SITE_URL; ?>';
const ORDER_NUMBER = '<?php echo $order['order_number']; ?>';
const TOTAL = '<?php echo formatPrice($total); ?>';

/* ---- Method Selector ---- */
function selectMethod(method, el) {
    document.querySelectorAll('.payment-method-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.payment-form-section').forEach(s => s.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('section-' + method).classList.add('active');
}

/* ---- Live Card Preview ---- */
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g,'').substring(0,16);
    e.target.value = v.replace(/(.{4})/g,'$1 ').trim();
    const disp = v.padEnd(16,'•').replace(/(.{4})/g,'$1 ').trim();
    document.getElementById('previewNumber').textContent = disp;
    e.target.classList.remove('error','success');
    if(v.length === 16) e.target.classList.add('success');
});

document.getElementById('cardName').addEventListener('input', function(e) {
    document.getElementById('previewName').textContent = e.target.value.toUpperCase() || 'FULL NAME';
    e.target.classList.remove('error','success');
    if(e.target.value.trim().length > 2) e.target.classList.add('success');
});

document.getElementById('cardExpiry').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g,'');
    if(v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
    e.target.value = v;
    document.getElementById('previewExpiry').textContent = v || 'MM/YY';
    e.target.classList.remove('error','success');
    if(v.length === 5) e.target.classList.add('success');
});

document.getElementById('cardCvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g,'').substring(0,4);
    e.target.classList.remove('error','success');
    if(e.target.value.length >= 3) e.target.classList.add('success');
});

/* ---- Validation ---- */
function showError(id, errId) {
    document.getElementById(id).classList.add('error');
    document.getElementById(errId).style.display = 'block';
}
function clearErrors() {
    ['cardNumber','cardName','cardExpiry','cardCvv'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.classList.remove('error');
    });
    document.querySelectorAll('.field-error').forEach(e => e.style.display='none');
}

function validateCard() {
    clearErrors();
    let valid = true;
    const num = document.getElementById('cardNumber').value.replace(/\s/g,'');
    const name = document.getElementById('cardName').value.trim();
    const expiry = document.getElementById('cardExpiry').value;
    const cvv = document.getElementById('cardCvv').value;

    if(num.length !== 16) { showError('cardNumber','cardNumberError'); valid=false; }
    if(name.length < 2)   { showError('cardName','cardNameError'); valid=false; }
    if(!/^\d{2}\/\d{2}$/.test(expiry)) { showError('cardExpiry','cardExpiryError'); valid=false; }
    if(cvv.length < 3)    { showError('cardCvv','cardCvvError'); valid=false; }
    return valid;
}

/* ---- Dummy Payment Processing ---- */
function setLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    if(loading) { btn.classList.add('loading'); btn.disabled=true; }
    else { btn.classList.remove('loading'); btn.disabled=false; }
}

function processCardPayment() {
    if(!validateCard()) return;
    setLoading('payCardBtn', true);
    const txnId = 'CARD-' + Date.now() + '-' + Math.random().toString(36).substr(2,8).toUpperCase();
    setTimeout(() => {
        confirmPaymentServer('card', txnId, 'Credit/Debit Card');
    }, 2800);
}

function processPaypalPayment() {
    const btn = document.querySelector('#section-paypal .pay-btn');
    btn.classList.add('loading'); btn.disabled=true;
    const txnId = 'PP-' + Date.now() + '-' + Math.random().toString(36).substr(2,8).toUpperCase();
    setTimeout(() => {
        btn.classList.remove('loading'); btn.disabled=false;
        confirmPaymentServer('paypal', txnId, 'PayPal');
    }, 2200);
}

function processBankPayment() {
    const btn = document.querySelector('#section-bank .pay-btn');
    btn.classList.add('loading'); btn.disabled=true;
    const txnId = 'BT-' + Date.now() + '-' + Math.random().toString(36).substr(2,8).toUpperCase();
    setTimeout(() => {
        btn.classList.remove('loading'); btn.disabled=false;
        confirmPaymentServer('bank', txnId, 'Bank Transfer');
    }, 1800);
}

function confirmPaymentServer(method, txnId, methodLabel) {
    fetch(SITE_URL + '/customer/process_payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ order_id: ORDER_ID, transaction_id: txnId, payment_method: method })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById('successPaymentMethod').textContent = methodLabel;
            document.getElementById('successTxnId').textContent = txnId;
            document.getElementById('successDialog').classList.add('show');
            const btn = document.getElementById('payCardBtn');
            if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
        } else {
            alert('Payment error: ' + (data.message || 'Unknown error'));
            const btn = document.getElementById('payCardBtn');
            if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
        }
    })
    .catch(err => {
        // Still show success dialog for demo purposes
        document.getElementById('successPaymentMethod').textContent = methodLabel;
        document.getElementById('successTxnId').textContent = txnId;
        document.getElementById('successDialog').classList.add('show');
        const btn = document.getElementById('payCardBtn');
        if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
    });
}

const COD_DEPOSIT = <?php echo $cod_deposit; ?>;
const COD_REMAINING = <?php echo $cod_remaining; ?>;
const COD_DEPOSIT_FMT = '<?php echo formatPrice($cod_deposit); ?>';

/* ---- COD Card Input Formatting ---- */
document.getElementById('codCardNumber').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g,'').substring(0,16);
    e.target.value = v.replace(/(.{4})/g,'$1 ').trim();
    e.target.classList.remove('error','success');
    if(v.length === 16) e.target.classList.add('success');
});
document.getElementById('codCardName').addEventListener('input', function(e) {
    e.target.classList.remove('error','success');
    if(e.target.value.trim().length > 2) e.target.classList.add('success');
});
document.getElementById('codCardExpiry').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g,'');
    if(v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
    e.target.value = v;
    e.target.classList.remove('error','success');
    if(v.length === 5) e.target.classList.add('success');
});
document.getElementById('codCardCvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g,'').substring(0,4);
    e.target.classList.remove('error','success');
    if(e.target.value.length >= 3) e.target.classList.add('success');
});

function validateCodForm() {
    let valid = true;
    // Reset errors
    ['codCardNumber','codCardName','codCardExpiry','codCardCvv','codAddress'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.classList.remove('error');
    });
    document.querySelectorAll('.field-error').forEach(e => e.style.display='none');

    const address = document.getElementById('codAddress').value.trim();
    const num = document.getElementById('codCardNumber').value.replace(/\s/g,'');
    const name = document.getElementById('codCardName').value.trim();
    const expiry = document.getElementById('codCardExpiry').value;
    const cvv = document.getElementById('codCardCvv').value;

    if(!address) {
        document.getElementById('codAddress').classList.add('error');
        document.getElementById('codAddressError').style.display='block';
        valid = false;
    }
    if(num.length !== 16) {
        document.getElementById('codCardNumber').classList.add('error');
        document.getElementById('codCardNumberError').style.display='block';
        valid = false;
    }
    if(name.length < 2) {
        document.getElementById('codCardName').classList.add('error');
        document.getElementById('codCardNameError').style.display='block';
        valid = false;
    }
    if(!/^\d{2}\/\d{2}$/.test(expiry)) {
        document.getElementById('codCardExpiry').classList.add('error');
        document.getElementById('codCardExpiryError').style.display='block';
        valid = false;
    }
    if(cvv.length < 3) {
        document.getElementById('codCardCvv').classList.add('error');
        document.getElementById('codCardCvvError').style.display='block';
        valid = false;
    }
    return valid;
}

function processCodPayment() {
    if(!validateCodForm()) return;
    setLoading('payCodBtn', true);
    const txnId = 'COD-DEP-' + Date.now() + '-' + Math.random().toString(36).substr(2,8).toUpperCase();
    const address = document.getElementById('codAddress').value.trim();
    setTimeout(() => {
        confirmCodPaymentServer(txnId, address);
    }, 2800);
}

function confirmCodPaymentServer(txnId, address) {
    fetch(SITE_URL + '/customer/process_payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            order_id: ORDER_ID,
            transaction_id: txnId,
            payment_method: 'cod',
            delivery_address: address
        })
    })
    .then(r => r.json())
    .then(data => {
        const btn = document.getElementById('payCodBtn');
        if(data.success) {
            // Update success dialog for COD
            document.getElementById('successPaymentMethod').textContent = 'Cash on Delivery';
            document.getElementById('successTxnId').textContent = txnId;
            // Override success dialog content for COD
            document.querySelector('.success-dialog h2').textContent = 'Order Confirmed!';
            document.querySelector('.success-dialog .subtitle').innerHTML =
                'Your <strong>' + COD_DEPOSIT_FMT + ' deposit</strong> has been received. Our artist will begin working on your artwork. Pay the remaining amount on delivery.';
            document.querySelector('#successDialog .success-detail-row:nth-child(3) span:last-child').textContent =
                COD_DEPOSIT_FMT + ' (Deposit)';
            document.getElementById('successDialog').classList.add('show');
            if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
        } else {
            alert('Payment error: ' + (data.message || 'Unknown error'));
            if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
        }
    })
    .catch(err => {
        const btn = document.getElementById('payCodBtn');
        document.getElementById('successPaymentMethod').textContent = 'Cash on Delivery';
        document.getElementById('successTxnId').textContent = txnId;
        document.querySelector('.success-dialog h2').textContent = 'Order Confirmed!';
        document.querySelector('.success-dialog .subtitle').innerHTML =
            'Your <strong>' + COD_DEPOSIT_FMT + ' deposit</strong> has been received. Our artist will begin working on your artwork. Pay the remaining amount on delivery.';
        document.getElementById('successDialog').classList.add('show');
        if(btn) { btn.classList.remove('loading'); btn.disabled=false; }
    });
}

</script>

<?php require_once '../includes/footer.php'; ?>
