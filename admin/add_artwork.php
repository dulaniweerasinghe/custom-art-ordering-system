<?php
$pageTitle = 'Add Artwork';
require_once '../includes/header.php';

checkAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $price = floatval($_POST['price']);

    if ($title == '' || $description == '' || $category == '' || $price <= 0) {
        $error = "Please fill all required fields correctly.";
    } else {
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] == 0) {
            $imageName = uploadFile($_FILES['artwork_image'], '../uploads/artworks/');

            if ($imageName) {
                $stmt = $pdo->prepare("
                    INSERT INTO artworks 
                    (title, description, category, image_path, price, is_available) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");

                if ($stmt->execute([$title, $description, $category, $imageName, $price])) {
                    $success = "Artwork added successfully!";
                } else {
                    $error = "Failed to save artwork.";
                }
            } else {
                $error = "Image upload failed. Please upload JPG, JPEG, PNG or GIF under 5MB.";
            }
        } else {
            $error = "Please select an artwork image.";
        }
    }
}
?>

<style>
    .artwork-form-box {
        max-width: 750px;
        margin: 3rem auto;
        background: white;
        padding: 3rem;
        border: 3px double var(--vintage-gold);
        box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    }

    .preview-box {
        margin-top: 1rem;
        text-align: center;
        display: none;
    }

    .preview-box img {
        max-width: 250px;
        border: 2px solid var(--vintage-gold);
        padding: 5px;
        background: var(--vintage-paper);
    }
</style>

<div class="container">
    <div class="artwork-form-box">
        <h1 style="text-align:center; margin-bottom: 0.5rem;">
            <i class="fas fa-image" style="color: var(--vintage-gold);"></i>
            Add Gallery Artwork
        </h1>

        <p style="text-align:center; color: var(--text-muted); margin-bottom: 2rem;">
            Upload artwork image and details to display in gallery.
        </p>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Artwork Title *</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="acrylic">Acrylic Painting</option>
                    <option value="pencil">Pencil Sketch</option>
                    <option value="oil">Oil Painting</option>
                    <option value="watercolor">Watercolor</option>
                    <option value="digital">Digital Art</option>
                </select>
            </div>

            <div class="form-group">
                <label>Price *</label>
                <input type="number" name="price" class="form-control" step="0.01" min="1" required>
            </div>

            <div class="form-group">
                <label>Artwork Image *</label>
                <input type="file" name="artwork_image" id="artwork_image" class="form-control" accept="image/*" required>
                <small style="color: var(--text-muted);">Allowed: JPG, JPEG, PNG, GIF. Max size: 5MB.</small>

                <div class="preview-box" id="previewBox">
                    <p style="color: var(--text-muted); margin-top: 1rem;">Image Preview</p>
                    <img id="previewImage" src="" alt="Preview">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem;">
                <i class="fas fa-plus"></i> Add Artwork
            </button>

            <div style="text-align:center; margin-top:1.5rem;">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline btn-sm">
                    Back to Dashboard
                </a>

                <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-gold btn-sm">
                    View Gallery
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('artwork_image').addEventListener('change', function(event) {
        const file = event.target.files[0];

        if (file) {
            const previewBox = document.getElementById('previewBox');
            const previewImage = document.getElementById('previewImage');

            previewImage.src = URL.createObjectURL(file);
            previewBox.style.display = 'block';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>