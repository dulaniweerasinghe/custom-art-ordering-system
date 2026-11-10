<?php 
$pageTitle = 'Home';
require_once 'includes/header.php'; 
?>

<style>
    .hero {
        background: linear-gradient(rgba(44, 36, 27, 0.6), rgba(44, 36, 27, 0.6)), url('https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: 85vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--vintage-cream);
        position: relative;
    }
    
    .hero::before {
        content: '';
        position: absolute;
        top: 30px;
        left: 30px;
        right: 30px;
        bottom: 30px;
        border: 2px solid var(--vintage-gold);
        pointer-events: none;
    }
    
    .hero-content h1 {
        font-size: 4rem;
        color: var(--vintage-cream);
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        font-family: 'Playfair Display', serif;
    }
    
    .hero-content p {
        font-size: 1.4rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        font-weight: 300;
    }
    
    .features {
        padding: 5rem 0;
        background: var(--vintage-cream);
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 3rem;
        margin-top: 3rem;
    }
    
    .feature-card {
        background: var(--vintage-paper);
        padding: 2.5rem;
        border: 1px solid var(--vintage-gold);
        text-align: center;
        transition: transform 0.3s;
        position: relative;
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        top: 5px;
        left: 5px;
        right: 5px;
        bottom: 5px;
        border: 1px solid rgba(201, 169, 89, 0.3);
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(44, 36, 27, 0.15);
    }
    
    .feature-icon {
        font-size: 3rem;
        color: var(--vintage-gold);
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        text-align: center;
        font-size: 2.8rem;
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
        left: 50%;
        transform: translateX(-50%);
        color: var(--vintage-brown);
    }
    
    .section-title::after {
        content: '';
        display: block;
        width: 80px;
        height: 3px;
        background: var(--vintage-gold);
        margin: 1rem auto;
    }
    
    .cta-section {
        background: var(--vintage-brown);
        color: var(--vintage-cream);
        padding: 5rem 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .cta-section::before {
        content: '“';
        position: absolute;
        top: -50px;
        left: 50px;
        font-size: 300px;
        color: rgba(201, 169, 89, 0.1);
        font-family: 'Playfair Display', serif;
        line-height: 1;
    }
    
    .cta-section h2 {
        color: var(--vintage-gold);
        font-size: 2.5rem;
        margin-bottom: 1.5rem;
    }
</style>

<section class="hero">
    <div class="hero-content">
        <h1>Custom Artwork Creations</h1>
        <p>Bespoke acrylic paintings and pencil sketches crafted with precision and passion. Transform your vision into timeless art.</p>
        <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-gold" style="font-size: 1.1rem; padding: 1rem 2.5rem; margin-right: 1rem;">View Gallery</a>
        <?php if(!isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-outline" style="font-size: 1.1rem; padding: 1rem 2.5rem; border-color: var(--vintage-gold); color: var(--vintage-gold);">Get Started</a>
        <?php endif; ?>
    </div>
</section>

<section class="features">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-paint-brush"></i></div>
                <h3>Custom Orders</h3>
                <p>Submit your requirements and reference images for personalized artwork created just for you.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-clock"></i></div>
                <h3>Track Progress</h3>
                <p>Monitor your order status in real-time from pending to completion with automated updates.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-comments"></i></div>
                <h3>Direct Communication</h3>
                <p>Chat directly with the artist to discuss details and ensure your vision is perfectly captured.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to Commission Your Artwork?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">Join hundreds of satisfied customers who have transformed their ideas into beautiful art pieces.</p>
        <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-gold" style="font-size: 1.1rem;">Browse Gallery Now</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>