<?php
$pageTitle = 'Edit Artwork';
require_once '../includes/header.php';

checkAdmin();

if (!isset($_GET['id'])) {
    redirect('/admin/artworks.php');
}

$id = intval($_GET['id']);

// Fetch artwork
$stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = ?");
$stmt->execute([$id]);
$artwork = $stmt->fetch();

if (!$artwork) {
    redirect('/admin/artworks.php');
}

// Update artwork
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $price = floatval($_POST['price']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($title === '' || $description === '' || $category === '' || $price <= 0) {
        $error = "Please fill all required fields correctly.";
    } else {
        $imageName = $artwork['image_path'];

        // If new image uploaded
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === 0) {
            $newImage = uploadFile($_FILES['artwork_image'], '../uploads/artworks/');

            if ($newImage) {
                // Delete old image
                $oldImagePath = '../uploads/artworks/' . $artwork['image_path'];

                if (!empty($artwork['image_path']) && file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }

                $imageName = $newImage;
            } else {
                $error = "Image upload failed. Please upload JPG, JPEG, PNG or GIF under 5MB.";
            }
        }

        if (!isset($error)) {
            $stmt = $pdo->prepare("
                UPDATE artworks
                SET 
                    title = ?, 
                    description = ?, 
                    category = ?, 
                    price = ?, 
                    image_path = ?, 
                    is_available = ?
                WHERE id = ?
            ");

            if ($stmt->execute([$title, $description, $category, $price, $imageName, $is_available, $id])) {
                $_SESSION['success'] = "Artwork updated successfully.";
                redirect('/admin/artworks.php');
            } else {
                $error = "Failed to update artwork.";
            }
        }
    }
}
?>

<style>
    .edit-box {
        max-width: 850px;
        margin: 3rem auto;
        background: white;
        padding: 3rem;
        border: 3px double var(--vintage-gold);
        box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    }

    .current-image {
        text-align: center;
        margin-bottom: 2rem;
    }

    .current-image img {
        max-width: 330px;
        max-height: 270px;
        object-fit: cover;
        border: 2px solid var(--vintage-gold);
        padding: 5px;
        background: var(--vintage-paper);
    }

    .preview-box {
        margin-top: 1rem;
        text-align: center;
        display: none;
    }

    .preview-box img {
        max-width: 260px;
        max-height: 230px;
        object-fit: cover;
        border: 2px solid var(--vintage-gold);
        padding: 5px;
        background: var(--vintage-paper);
    }

    .check-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 1rem 0 1.5rem;
        color: var(--vintage-brown);
        font-weight: 600;
    }
</style>

<div class="container">
    <div class="edit-box">

        <h1 style="text-align:center; margin-bottom: 0.5rem;">
            <i class="fas fa-edit" style="color: var(--vintage-gold);"></i>
            Edit Artwork
        </h1>

        <p style="text-align:center; color: var(--text-muted); margin-bottom: 2rem;">
            Update artwork name, description, price, category or photo.
        </p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="current-image">
            <p style="color: var(--text-muted);">Current Photo</p>

            <img 
                src="<?php echo SITE_URL; ?>/uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                alt="<?php echo htmlspecialchars($artwork['title']); ?>"
            >
        </div>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Artwork Title / Name *</label>
                <input 
                    type="text" 
                    name="title" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($artwork['title']); ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" class="form-control" required><?php echo htmlspecialchars($artwork['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category" class="form-control" required>
                    <option value="acrylic" <?php echo $artwork['category'] == 'acrylic' ? 'selected' : ''; ?>>
                        Acrylic Painting
                    </option>

                    <option value="pencil" <?php echo $artwork['category'] == 'pencil' ? 'selected' : ''; ?>>
                        Pencil Sketch
                    </option>

                    <option value="oil" <?php echo $artwork['category'] == 'oil' ? 'selected' : ''; ?>>
                        Oil Painting
                    </option>

                    <option value="watercolor" <?php echo $artwork['category'] == 'watercolor' ? 'selected' : ''; ?>>
                        Watercolor
                    </option>

                    <option value="digital" <?php echo $artwork['category'] == 'digital' ? 'selected' : ''; ?>>
                        Digital Art
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Price *</label>
                <input 
                    type="number" 
                    name="price" 
                    class="form-control" 
                    step="0.01" 
                    min="1" 
                    value="<?php echo htmlspecialchars($artwork['price']); ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label>Change Photo</label>
                <input 
                    type="file" 
                    name="artwork_image" 
                    id="artwork_image" 
                    class="form-control" 
                    accept="image/*"
                >

                <small style="color: var(--text-muted);">
                    Leave empty if you do not want to change current photo.
                </small>

                <div class="preview-box" id="previewBox">
                    <p style="color: var(--text-muted); margin-top: 1rem;">New Photo Preview</p>
                    <img id="previewImage" src="" alt="Preview">
                </div>
            </div>

            <label class="check-row">
                <input 
                    type="checkbox" 
                    name="is_available" 
                    <?php echo $artwork['is_available'] ? 'checked' : ''; ?>
                >
                Show this artwork in gallery
            </label>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem;">
                <i class="fas fa-save"></i> Update Artwork
            </button>

            <div style="text-align:center; margin-top:1.5rem;">
                <a href="<?php echo SITE_URL; ?>/admin/artworks.php" class="btn btn-outline btn-sm">
                    Back to Manage Artworks
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