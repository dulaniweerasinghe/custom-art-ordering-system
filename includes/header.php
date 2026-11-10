<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Old+Standard+TT:ital,wght@0,400;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stripe -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <style>
        :root {
            --vintage-cream: #f5f2e9;
            --vintage-paper: #faf8f3;
            --vintage-brown: #5d4037;
            --vintage-dark: #2c241b;
            --vintage-gold: #c9a959;
            --vintage-gold-light: #d4af37;
            --vintage-olive: #6b7c5c;
            --text-muted: #8c7b65;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            background-color: var(--vintage-paper);
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23c9a959' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            color: var(--vintage-dark);
            line-height: 1.6;
        }
        
        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            color: var(--vintage-brown);
        }
        
        .vintage-border {
            border: 2px solid var(--vintage-gold);
            position: relative;
        }
        
        .vintage-border::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            right: 5px;
            bottom: 5px;
            border: 1px solid var(--vintage-gold);
            pointer-events: none;
        }
        
        /* Navigation */
        .navbar {
            background: linear-gradient(to bottom, var(--vintage-cream), var(--vintage-paper));
            border-bottom: 3px double var(--vintage-gold);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(44, 36, 27, 0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--vintage-brown);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            color: var(--vintage-gold);
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--vintage-dark);
            font-weight: 600;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-links a:hover {
            color: var(--vintage-gold);
        }
        
        /* Nav avatar */
        .nav-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--vintage-gold);
            border: 2px solid var(--vintage-gold);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem; color: white;
            overflow: hidden; vertical-align: middle;
            margin-right: 0.4rem;
            flex-shrink: 0;
        }
        .nav-avatar img { width:100%; height:100%; object-fit:cover; }
        .nav-user-link {
            display: inline-flex !important;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--vintage-gold);
            transition: width 0.3s;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            cursor: pointer;
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border-radius: 3px;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: var(--vintage-brown);
            color: var(--vintage-cream);
            border: 2px solid var(--vintage-brown);
        }
        
        .btn-primary:hover {
            background: transparent;
            color: var(--vintage-brown);
        }
        
        .btn-gold {
            background: var(--vintage-gold);
            color: var(--vintage-dark);
            border: 2px solid var(--vintage-gold);
        }
        
        .btn-gold:hover {
            background: transparent;
            color: var(--vintage-gold);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--vintage-brown);
            border: 2px solid var(--vintage-brown);
        }
        
        .btn-outline:hover {
            background: var(--vintage-brown);
            color: var(--vintage-cream);
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--vintage-brown);
            font-family: 'Playfair Display', serif;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            background: var(--vintage-paper);
            font-family: 'Lato', sans-serif;
            transition: border-color 0.3s;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--vintage-gold);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235d4037' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            padding-right: 2.5rem;
        }
        
        /* Footer */
        .footer {
            background: var(--vintage-dark);
            color: var(--vintage-cream);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
            border-top: 4px solid var(--vintage-gold);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h3 {
            color: var(--vintage-gold);
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(201, 169, 89, 0.3);
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>" class="logo">
                <i class="fas fa-palette"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
            
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>/gallery.php">Gallery</a></li>
                <?php if(isLoggedIn()): ?>
                    <?php if(isAdmin()): ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="nav-user-link">
                                <span class="nav-avatar">
                                    <?php if(!empty($_SESSION['profile_image'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-user" style="font-size:0.8rem;"></i>
                                    <?php endif; ?>
                                </span>
                                <?php echo htmlspecialchars($_SESSION['name']); ?>
                            </a>
                        </li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/dashboard.php">My Orders</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline btn-sm">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <main>