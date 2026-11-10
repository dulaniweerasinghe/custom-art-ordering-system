
<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'art_ordering_system');

// Site Configuration
define('SITE_URL', 'http://localhost/sketchcore');
define('SITE_NAME', 'S K E T C H - C O R E');
define('ADMIN_EMAIL', 'dulaniweerasinghe2002@gmail.com');

// Stripe Configuration (Test keys - replace with live keys in production)
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51H...');
define('STRIPE_SECRET_KEY', 'sk_test_51H...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'dulaniweerasinghe2002@gmail.com');  // ← ඔබේ Gmail App Password ලබා දෙන්න
define('SMTP_PASS', 'rvpaainxdhrtqlgc');                 // ← Gmail App Password replace කරන්න
define('SMTP_PORT', 465);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Vintage Color Constants
define('VINTAGE_CREAM', '#f5f2e9');
define('VINTAGE_BROWN', '#5d4037');
define('VINTAGE_GOLD', '#c9a959');

// Create Database Connection
try {

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {

    die("Database Connection Failed: " . $e->getMessage());
}

// ========================================
// Helper Functions
// ========================================

// Currency Configuration
define('CURRENCY_CODE', 'lkr');      // For payment gateway
define('CURRENCY_SYMBOL', 'Rs.');    // Display symbol
define('CURRENCY_NAME', 'LKR');

// Format price as Sri Lankan Rupees
function formatPrice($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateOrderNumber()
{
    return 'ART-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Y');
}

function redirect($url)
{
    // Prevent headers already sent issue
    if (!headers_sent()) {

        header("Location: " . SITE_URL . $url);
        exit();

    } else {

        echo "<script>window.location.href='" . SITE_URL . $url . "';</script>";
        exit();
    }
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function checkAuth()
{
    if (!isLoggedIn()) {

        redirect('/login.php');
    }
}

function checkAdmin()
{
    checkAuth();

    if (!isAdmin()) {

        redirect('/customer/dashboard.php');
    }
}

function uploadFile($file, $directory = 'uploads/')
{
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {

        return false;
    }

    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {

        return false;
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) {

        return false;
    }

    // Create upload directory if not exists
    if (!file_exists($directory)) {

        mkdir($directory, 0755, true);
    }

    // Generate secure filename
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $filepath = $directory . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {

        return $filename;
    }

    return false;
}

function sendNotification($user_id, $title, $message, $order_id = null)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, order_id, title, message) 
        VALUES (?, ?, ?, ?)
    ");

    return $stmt->execute([
        $user_id,
        $order_id,
        $title,
        $message
    ]);
}

function sendEmail($to, $subject, $body)
{
    // Load PHPMailer
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Sender & Recipient
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(ADMIN_EMAIL, SITE_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (\Exception $e) {
        // Log error silently — don't break the payment flow
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>

