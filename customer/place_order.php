<?php 
$pageTitle = 'Place Order';
require_once '../includes/header.php';

checkAuth();

if(isAdmin()) {
    redirect('/admin/dashboard.php');
}

$artwork_id = isset($_GET['artwork_id']) ? intval($_GET['artwork_id']) : 0;
$artwork = null;

if($artwork_id) {
    $stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = ? AND is_available = 1");
    $stmt->execute([$artwork_id]);
    $artwork = $stmt->fetch();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $artwork_type = sanitize($_POST['artwork_type']);
    $description = sanitize($_POST['description']);
    $requirements = sanitize($_POST['requirements']);
    $price = floatval($_POST['price']);
    
    // Handle file upload
    $reference_image = null;
    if(isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] == 0) {
        $reference_image = uploadFile($_FILES['reference_image'], '../uploads/references/');
        if(!$reference_image) {
            $error = "Failed to upload image. Please ensure it's a valid image file under 5MB.";
        }
    }
    
    if(!isset($error)) {
        $order_number = generateOrderNumber();
        
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, artwork_type, description, reference_image, requirements, price, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if($stmt->execute([$order_number, $_SESSION['user_id'], $artwork_type, $description, $reference_image, $requirements, $price])) {
            $order_id = $pdo->lastInsertId();
            
            // Notify admin (in-app only)
            sendNotification(1, 'New Order Received', "Order #$order_number has been placed by {$_SESSION['name']}.", $order_id);
            
            redirect("/customer/delivery_details.php?order_id=$order_id");
        } else {
            $error = "Failed to place order. Please try again.";
        }
    }
}
?>

<style>
    .order-container {
        max-width: 800px;
        margin: 3rem auto;
        background: white;
        padding: 3rem;
        border: 3px double var(--vintage-gold);
        box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    }
    
    .price-display {
        background: var(--vintage-cream);
        padding: 1.5rem;
        border-left: 4px solid var(--vintage-gold);
        margin: 1.5rem 0;
        font-size: 1.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .price-display strong {
        color: var(--vintage-brown);
        font-size: 1.3rem;
    }
    
    .file-upload {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .file-upload input[type="file"] {
        position: absolute;
        left: -9999px;
    }
    
    .file-upload-label {
        display: block;
        padding: 2rem;
        background: var(--vintage-paper);
        border: 2px dashed var(--vintage-gold);
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .file-upload-label:hover {
        background: var(--vintage-cream);
        border-style: solid;
    }
</style>

<div class="container">
    <div class="order-container">
        <h1 style="text-align: center; margin-bottom: 0.5rem; font-size: 2.2rem;">
            <i class="fas fa-paint-brush" style="color: var(--vintage-gold);"></i>
            Commission Artwork
        </h1>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Fill in the details below to place your custom order</p>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Artwork Type *</label>
                <select name="artwork_type" class="form-control" required>
                    <option value="">Select Artwork Type</option>
                    <option value="Acrylic Painting" <?php echo ($artwork && $artwork['category'] == 'acrylic') ? 'selected' : ''; ?>>Acrylic Painting</option>
                    <option value="Pencil Sketch" <?php echo ($artwork && $artwork['category'] == 'pencil') ? 'selected' : ''; ?>>Pencil Sketch</option>
                    <option value="Oil Painting">Oil Painting</option>
                    <option value="Watercolor">Watercolor</option>
                    <option value="Digital Art">Digital Art</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" class="form-control" placeholder="Describe what you'd like the artist to create. Include details about subject, style, mood, etc." required></textarea>
            </div>
            
            <div class="form-group">
                <label>Specific Requirements</label>
                <textarea name="requirements" class="form-control" placeholder="Size dimensions, color preferences, deadline, framing options, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label>Reference Image</label>
                <div class="file-upload">
                    <input type="file" name="reference_image" id="reference_image" accept="image/*">
                    <label for="reference_image" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--vintage-gold); display: block; margin-bottom: 0.5rem;"></i>
                        <span id="file-name">Click to upload reference image (Optional)</span>
                        <small style="display: block; margin-top: 0.5rem; color: var(--text-muted);">Max 5MB (JPG, PNG)</small>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Budget / Agreed Price ($) *</label>
                <input type="number" name="price" class="form-control" step="0.01" min="1" value="<?php echo $artwork ? $artwork['price'] : ''; ?>" required>
                <small style="color: var(--text-muted);">Enter the agreed price or your budget. Final price will be confirmed by the artist.</small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                Proceed to Payment <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('reference_image').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'Click to upload reference image (Optional)';
        document.getElementById('file-name').textContent = fileName;
    });
</script>

<?php require_once '../includes/footer.php'; ?>