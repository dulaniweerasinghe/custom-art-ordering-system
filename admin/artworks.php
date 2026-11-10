<?php
$pageTitle = 'Manage Artworks';
require_once '../includes/header.php';

checkAdmin();

// Delete artwork
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_artwork'])) {
    $id = intval($_POST['artwork_id']);

    $stmt = $pdo->prepare("SELECT image_path FROM artworks WHERE id = ?");
    $stmt->execute([$id]);
    $artwork = $stmt->fetch();

    if ($artwork) {
        $imagePath = '../uploads/artworks/' . $artwork['image_path'];

        if (!empty($artwork['image_path']) && file_exists($imagePath)) {
            unlink($imagePath);
        }

        $stmt = $pdo->prepare("DELETE FROM artworks WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Artwork deleted successfully.";
    }

    redirect('/admin/artworks.php');
}

// Show / Hide artwork
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_available'])) {
    $id = intval($_POST['artwork_id']);

    $stmt = $pdo->prepare("SELECT is_available FROM artworks WHERE id = ?");
    $stmt->execute([$id]);
    $artwork = $stmt->fetch();

    if ($artwork) {
        $newStatus = $artwork['is_available'] ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE artworks SET is_available = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $_SESSION['success'] = $newStatus ? "Artwork is now visible in gallery." : "Artwork hidden from gallery.";
    }

    redirect('/admin/artworks.php');
}

// Fetch artworks
$stmt = $pdo->query("SELECT * FROM artworks ORDER BY created_at DESC");
$artworks = $stmt->fetchAll();
?>

<style>
    .page-header {
        margin: 2rem 0;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--vintage-gold);
    }

    .artworks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .art-card {
        background: white;
        border: 2px solid var(--vintage-gold);
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(44, 36, 27, 0.08);
    }

    .art-card img {
        width: 100%;
        height: 230px;
        object-fit: cover;
        display: block;
    }

    .art-card-body {
        padding: 1.5rem;
    }

    .art-card-body h3 {
        margin-bottom: 0.5rem;
    }

    .art-meta {
        color: var(--text-muted);
        font-size: 0.92rem;
        margin-bottom: 1rem;
    }

    .badge {
        display: inline-block;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .badge-show {
        background: #d4edda;
        color: #155724;
    }

    .badge-hide {
        background: #f8d7da;
        color: #721c24;
    }

    .card-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .empty-box {
        background: white;
        border: 2px solid var(--vintage-gold);
        padding: 4rem 1rem;
        text-align: center;
        margin-top: 2rem;
    }
</style>

<div class="container">

    <div class="page-header">
        <h1>
            <i class="fas fa-images" style="color: var(--vintage-gold);"></i>
            Manage Artworks
        </h1>

        <p style="color: var(--text-muted);">
            Edit artwork name, description, price, photo and gallery visibility.
        </p>

        <div style="margin-top: 1rem;">
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <a href="<?php echo SITE_URL; ?>/admin/add_artwork.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Artwork
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($artworks) > 0): ?>
        <div class="artworks-grid">
            <?php foreach ($artworks as $art): ?>
                <div class="art-card">

                    <img 
                        src="<?php echo SITE_URL; ?>/uploads/artworks/<?php echo htmlspecialchars($art['image_path']); ?>" 
                        alt="<?php echo htmlspecialchars($art['title']); ?>"
                    >

                    <div class="art-card-body">
                        <h3><?php echo htmlspecialchars($art['title']); ?></h3>

                        <div class="art-meta">
                            Category: <?php echo ucfirst(htmlspecialchars($art['category'])); ?><br>
                            Price:
                            <?php 
                                if (function_exists('formatPrice')) {
                                    echo formatPrice($art['price']);
                                } else {
                                    echo 'Rs. ' . number_format($art['price'], 2);
                                }
                            ?>
                        </div>

                        <?php if ($art['is_available']): ?>
                            <span class="badge badge-show">Visible</span>
                        <?php else: ?>
                            <span class="badge badge-hide">Hidden</span>
                        <?php endif; ?>

                        <div class="card-actions">

                            <a 
                                href="<?php echo SITE_URL; ?>/admin/edit_artwork.php?id=<?php echo $art['id']; ?>" 
                                class="btn btn-primary btn-sm"
                            >
                                <i class="fas fa-edit"></i> Edit
                            </a>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                                <button type="submit" name="toggle_available" class="btn btn-outline btn-sm">
                                    <?php echo $art['is_available'] ? 'Hide' : 'Show'; ?>
                                </button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                                <button 
                                    type="submit" 
                                    name="delete_artwork" 
                                    class="btn btn-outline btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this artwork?');"
                                >
                                    Delete
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <i class="fas fa-image" style="font-size:4rem; color:var(--vintage-gold); margin-bottom:1rem;"></i>
            <h3>No artworks found</h3>
            <p style="color:var(--text-muted);">Start by adding your first artwork.</p>

            <a href="<?php echo SITE_URL; ?>/admin/add_artwork.php" class="btn btn-primary">
                Add Artwork
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>