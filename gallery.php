<?php
$pageTitle = 'Artwork Gallery';
require_once 'includes/header.php';

// Fetch artworks
$stmt = $pdo->query("SELECT * FROM artworks WHERE is_available = 1 ORDER BY created_at DESC");
$artworks = $stmt->fetchAll();

// Prepare artwork data for JavaScript modal
$artworksForJs = [];

foreach ($artworks as $art) {
    $priceText = function_exists('formatPrice') 
        ? formatPrice($art['price']) 
        : 'Rs. ' . number_format($art['price'], 2);

    $artworksForJs[$art['id']] = [
        'id' => (int)$art['id'],
        'title' => $art['title'],
        'description' => $art['description'],
        'category' => ucfirst($art['category']),
        'price' => $priceText,
        'image' => SITE_URL . '/uploads/artworks/' . $art['image_path'],
        'orderUrl' => SITE_URL . '/customer/place_order.php?artwork_id=' . $art['id'],
        'loginUrl' => SITE_URL . '/login.php'
    ];
}
?>

<style>
    .gallery-header {
        text-align: center;
        padding: 4rem 0;
        background: linear-gradient(to bottom, var(--vintage-cream), var(--vintage-paper));
        margin-bottom: 3rem;
        border-bottom: 2px solid var(--vintage-gold);
    }

    .gallery-header h1 {
        font-size: 3rem;
        margin-bottom: 0.5rem;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 2.5rem;
    }

    .artwork-card {
        background: white;
        border: 2px solid var(--vintage-gold);
        overflow: hidden;
        transition: all 0.3s;
        position: relative;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(44, 36, 27, 0.08);
    }

    .artwork-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 40px rgba(44, 36, 27, 0.2);
    }

    .artwork-image-wrap {
        position: relative;
        overflow: hidden;
    }

    .artwork-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        display: block;
        transition: transform 0.4s ease;
    }

    .artwork-card:hover .artwork-image {
        transform: scale(1.05);
    }

    .view-overlay {
        position: absolute;
        inset: 0;
        background: rgba(44, 36, 27, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: 0.3s;
        color: white;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .artwork-card:hover .view-overlay {
        opacity: 1;
    }

    .view-overlay span {
        background: var(--vintage-gold);
        color: var(--vintage-dark);
        padding: 0.7rem 1.2rem;
        border-radius: 3px;
        border: 1px solid var(--vintage-cream);
    }

    .artwork-info {
        padding: 1.5rem;
    }

    .artwork-category {
        color: var(--vintage-gold);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    .artwork-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--vintage-brown);
    }

    .artwork-price {
        font-size: 1.25rem;
        color: var(--vintage-olive);
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .artwork-description {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .tap-text {
        color: var(--vintage-gold);
        font-size: 0.85rem;
        font-weight: 700;
        margin-top: 0.8rem;
    }

    /* Modal */
    .art-modal {
        position: fixed;
        inset: 0;
        display: none;
        z-index: 9999;
    }

    .art-modal.show {
        display: block;
    }

    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(20, 15, 10, 0.78);
        backdrop-filter: blur(3px);
    }

    .art-modal-content {
        position: relative;
        width: min(1100px, 92%);
        max-height: 90vh;
        overflow-y: auto;
        background: var(--vintage-paper);
        border: 3px double var(--vintage-gold);
        margin: 4vh auto;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        animation: modalPop 0.25s ease;
    }

    @keyframes modalPop {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 2px solid var(--vintage-gold);
        background: var(--vintage-brown);
        color: var(--vintage-cream);
        cursor: pointer;
        font-size: 1.2rem;
        z-index: 2;
    }

    .modal-close:hover {
        background: var(--vintage-gold);
        color: var(--vintage-dark);
    }

    .modal-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 0;
    }

    .modal-image-area {
        background: #1d1712;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        min-height: 500px;
    }

    .modal-image-area img {
        max-width: 100%;
        max-height: 78vh;
        object-fit: contain;
        border: 2px solid var(--vintage-gold);
        background: white;
    }

    .modal-details {
        padding: 3rem 2rem 2rem;
        background: white;
    }

    .modal-category {
        color: var(--vintage-gold);
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 0.6rem;
    }

    .modal-title {
        font-size: 2rem;
        color: var(--vintage-brown);
        margin-bottom: 1rem;
    }

    .modal-price {
        font-size: 1.5rem;
        color: var(--vintage-olive);
        font-weight: bold;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(201, 169, 89, 0.4);
    }

    .modal-description {
        color: var(--vintage-dark);
        line-height: 1.8;
        margin-bottom: 2rem;
        white-space: pre-line;
    }

    .modal-actions {
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .empty-gallery {
        text-align: center;
        padding: 5rem 1rem;
        background: white;
        border: 2px solid var(--vintage-gold);
    }

    @media (max-width: 850px) {
        .gallery-header h1 {
            font-size: 2.3rem;
        }

        .gallery-grid {
            grid-template-columns: 1fr;
        }

        .modal-grid {
            grid-template-columns: 1fr;
        }

        .modal-image-area {
            min-height: auto;
        }

        .modal-image-area img {
            max-height: 55vh;
        }

        .modal-details {
            padding: 2rem 1.5rem;
        }

        .modal-title {
            font-size: 1.7rem;
        }
    }
</style>

<div class="gallery-header">
    <h1> Portfolio</h1>
    <p style="color: var(--text-muted); font-size: 1.2rem;">
        Touch or click any artwork to view full image and details
    </p>
</div>

<div class="container">
    <?php if (count($artworks) > 0): ?>
        <div class="gallery-grid">
            <?php foreach ($artworks as $artwork): ?>
                <div 
                    class="artwork-card"
                    data-art-id="<?php echo $artwork['id']; ?>"
                    tabindex="0"
                    role="button"
                    aria-label="View details for <?php echo htmlspecialchars($artwork['title']); ?>"
                >
                    <div class="artwork-image-wrap">
                        <img 
                            src="<?php echo SITE_URL; ?>/uploads/artworks/<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                            alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                            class="artwork-image"
                        >

                        <div class="view-overlay">
                            <span>
                                <i class="fas fa-search-plus"></i> View Details
                            </span>
                        </div>
                    </div>

                    <div class="artwork-info">
                        <div class="artwork-category">
                            <?php echo ucfirst(htmlspecialchars($artwork['category'])); ?>
                        </div>

                        <h3 class="artwork-title">
                            <?php echo htmlspecialchars($artwork['title']); ?>
                        </h3>

                        <div class="artwork-price">
                            <?php echo function_exists('formatPrice') ? formatPrice($artwork['price']) : 'Rs. ' . number_format($artwork['price'], 2); ?>
                        </div>

                        <p class="artwork-description">
                            <?php echo htmlspecialchars(substr($artwork['description'], 0, 120)); ?>...
                        </p>

                        <?php if (isLoggedIn() && !isAdmin()): ?>
                            <a 
                                href="<?php echo SITE_URL; ?>/customer/place_order.php?artwork_id=<?php echo $artwork['id']; ?>" 
                                class="btn btn-primary"
                                style="width: 100%; text-align: center;"
                            >
                                Order Similar
                            </a>
                        <?php elseif (!isLoggedIn()): ?>
                            <a 
                                href="<?php echo SITE_URL; ?>/login.php" 
                                class="btn btn-outline"
                                style="width: 100%; text-align: center;"
                            >
                                Login to Order
                            </a>
                        <?php endif; ?>

                        <div class="tap-text">
                            <i class="fas fa-hand-pointer"></i> Tap artwork to view full details
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-gallery">
            <i class="fas fa-palette" style="font-size: 4rem; color: var(--vintage-gold); margin-bottom: 1.5rem;"></i>
            <h3>No Artworks Available</h3>
            <p style="color: var(--text-muted);">Check back soon for new additions to our gallery.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Artwork Details Modal -->
<div id="artModal" class="art-modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-modal></div>

    <div class="art-modal-content">
        <button class="modal-close" type="button" data-close-modal>
            <i class="fas fa-times"></i>
        </button>

        <div class="modal-grid">
            <div class="modal-image-area">
                <img id="modalImage" src="" alt="">
            </div>

            <div class="modal-details">
                <div id="modalCategory" class="modal-category"></div>
                <h2 id="modalTitle" class="modal-title"></h2>
                <div id="modalPrice" class="modal-price"></div>
                <div id="modalDescription" class="modal-description"></div>

                <div class="modal-actions">
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <a id="modalOrderBtn" href="#" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Order Similar
                        </a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a id="modalLoginBtn" href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Order
                        </a>
                    <?php elseif (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/artworks.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Manage Artworks
                        </a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-outline" data-close-modal>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const artworks = <?php echo json_encode($artworksForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const modal = document.getElementById('artModal');
    const modalImage = document.getElementById('modalImage');
    const modalCategory = document.getElementById('modalCategory');
    const modalTitle = document.getElementById('modalTitle');
    const modalPrice = document.getElementById('modalPrice');
    const modalDescription = document.getElementById('modalDescription');

    const modalOrderBtn = document.getElementById('modalOrderBtn');
    const modalLoginBtn = document.getElementById('modalLoginBtn');

    function openArtModal(artId) {
        const art = artworks[artId];

        if (!art) {
            return;
        }

        modalImage.src = art.image;
        modalImage.alt = art.title;

        modalCategory.textContent = art.category;
        modalTitle.textContent = art.title;
        modalPrice.textContent = art.price;
        modalDescription.textContent = art.description;

        if (modalOrderBtn) {
            modalOrderBtn.href = art.orderUrl;
        }

        if (modalLoginBtn) {
            modalLoginBtn.href = art.loginUrl;
        }

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeArtModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        // Clear image after closing
        setTimeout(() => {
            modalImage.src = '';
        }, 200);
    }

    document.querySelectorAll('.artwork-card').forEach(card => {
        card.addEventListener('click', function(event) {
            // If user clicked order/login button, do not open modal
            if (event.target.closest('a, button')) {
                return;
            }

            openArtModal(this.dataset.artId);
        });

        // Keyboard accessibility
        card.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openArtModal(this.dataset.artId);
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', closeArtModal);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeArtModal();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>