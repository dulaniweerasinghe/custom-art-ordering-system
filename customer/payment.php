<?php 
$pageTitle = 'Payment';
require_once '../includes/header.php';

checkAuth();

if(!isset($_GET['order_id'])) {
    redirect('/customer/dashboard.php');
}

$order_id = intval($_GET['order_id']);

// Fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.email, u.full_name, u.phone 
                       FROM orders o 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE o.id = ? AND o.customer_id = ? AND o.payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('/customer/dashboard.php');
}

// Calculate amounts
$subtotal = $order['price'];
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $tax;
$total_cents = round($total * 100);
?>

<style>
    .payment-wrapper {
        max-width: 600px;
        margin: 3rem auto;
    }
    
    .payment-container {
        background: white;
        padding: 3rem;
        border: 3px double var(--vintage-gold);
        box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    }
    
    .order-summary-box {
        background: var(--vintage-cream);
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid var(--vintage-gold);
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px dashed rgba(201, 169, 89, 0.3);
    }
    
    .summary-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .summary-row.total {
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--vintage-brown);
        border-top: 2px solid var(--vintage-gold);
        margin-top: 1rem;
        padding-top: 1rem;
    }
    
    #card-element {
        background: var(--vintage-paper);
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    #card-errors {
        color: #dc3545;
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }
    
    .secure-badge {
        text-align: center;
        color: var(--vintage-olive);
        margin-top: 1.5rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .order-details-small {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-bottom: 1rem;
        padding: 1rem;
        background: rgba(201, 169, 89, 0.1);
        border-radius: 4px;
    }
</style>

<div class="container">
    <div class="payment-wrapper">
        <div class="payment-container">
            <h1 style="text-align: center; margin-bottom: 0.5rem;">
                <i class="fas fa-lock" style="color: var(--vintage-gold);"></i>
                Secure Payment
            </h1>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Complete your order payment</p>
            
            <div class="order-details-small">
                <strong>Order:</strong> <?php echo $order['order_number']; ?><br>
                <strong>Artwork:</strong> <?php echo $order['artwork_type']; ?>
            </div>
            
            <div class="order-summary-box">
                <h3 style="margin-bottom: 1.5rem; color: var(--vintage-brown); font-size: 1.2rem;">Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatPrice($subtotal); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (10%)</span>
                    <span><?php echo formatPrice($tax); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span><?php echo formatPrice($total); ?></span>
                </div>
            </div>
            
            <form id="payment-form">
                <div class="form-group">
                    <label>Card Information</label>
                    <div id="card-element">
                        <!-- Stripe Card Element will be inserted here -->
                    </div>
                    <div id="card-errors" role="alert"></div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem; font-size: 1.1rem;" id="submit-button">
                    <i class="fas fa-lock"></i> Pay <?php echo formatPrice($total); ?>
                </button>
                
                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure online payment processing</span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize Stripe with your publishable key
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
    const elements = stripe.elements();
    
    // Custom styling for the card element
    const style = {
        base: {
            color: '#2c241b',
            fontFamily: '"Lato", sans-serif',
            fontSize: '16px',
            '::placeholder': {
                color: '#8c7b65'
            }
        },
        invalid: {
            color: '#dc3545'
        }
    };
    
    const card = elements.create('card', {
        style: style,
        hidePostalCode: false
    });
    
    card.mount('#card-element');
    
    // Handle validation errors
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const submitButton = document.getElementById('submit-button');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Create payment intent via AJAX
        fetch('<?php echo SITE_URL; ?>/customer/create_payment_intent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: <?php echo $order_id; ?>,
                amount: <?php echo $total_cents; ?>,
                email: '<?php echo $order['email']; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                throw new Error(data.error);
            }
            
            return stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: card,
                    billing_details: {
                        name: '<?php echo $order['full_name']; ?>',
                        email: '<?php echo $order['email']; ?>',
                        phone: '<?php echo $order['phone']; ?>'
                    }
                }
            });
        })
        .then(result => {
            if (result.error) {
                throw new Error(result.error.message);
            } else {
                if (result.paymentIntent.status === 'succeeded') {
                    window.location.href = '<?php echo SITE_URL; ?>/customer/payment_success.php?order_id=<?php echo $order_id; ?>&payment_intent=' + result.paymentIntent.id;
                }
            }
        })
        .catch(error => {
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = error.message;
            submitButton.disabled = false;
           submitButton.innerHTML = '<i class="fas fa-lock"></i> Pay <?php echo formatPrice($total); ?>';
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>