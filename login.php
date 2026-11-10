<?php 
$pageTitle = 'Login';
require_once 'includes/header.php';

if(isLoggedIn()) {
    redirect(isAdmin() ? '/admin/dashboard.php' : '/customer/dashboard.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'];
        
        // Update last login (you can add last_login column if needed)
        
        redirect($user['role'] == 'admin' ? '/admin/dashboard.php' : '/customer/dashboard.php');
    } else {
        $error = "Invalid email or password";
    }
}
?>

<style>
    .auth-container {
        max-width: 450px;
        margin: 5rem auto;
        background: white;
        padding: 3.5rem;
        border: 3px double var(--vintage-gold);
        position: relative;
        box-shadow: 0 10px 40px rgba(44, 36, 27, 0.1);
    }
    
    .auth-container::before {
        content: '§';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--vintage-paper);
        width: 50px;
        height: 50px;
        border: 2px solid var(--vintage-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--vintage-gold);
        font-size: 1.8rem;
        font-family: 'Playfair Display', serif;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    
    .auth-header h1 {
        color: var(--vintage-brown);
        font-size: 2.2rem;
    }
</style>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Sign in to your account</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 1rem;">
                Sign In
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem; color: var(--text-muted);">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php" style="color: var(--vintage-gold); font-weight: 600;">Register here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>