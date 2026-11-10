<?php 
$pageTitle = 'Register';
require_once 'includes/header.php';

if(isLoggedIn()) {
    redirect('/customer/dashboard.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password]);
            
            $_SESSION['success'] = "Registration successful! Please login.";
            redirect('/login.php');
        } catch(PDOException $e) {
            if($e->getCode() == 23000) {
                $error = "Email already exists. Please use a different email.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="container">
    <div class="auth-container" style="max-width: 550px;">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Join our art community today</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" required value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
                <small style="color: var(--text-muted);">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 1rem;">
                Create Account
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            Already have an account? <a href="<?php echo SITE_URL; ?>/login.php" style="color: var(--vintage-gold); font-weight: 600;">Login here</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>