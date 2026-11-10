<?php
$pageTitle = 'My Profile';
require_once '../includes/header.php';

checkAuth();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error   = '';
$success = '';

// ---- Handle form submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Profile picture upload ---
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

        $file = $_FILES['profile_image'];

        // Validate size (2 MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            $error = "Image must be under 2 MB.";
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $error = "Only JPG, PNG, WEBP or GIF images are allowed.";
            } else {
                $uploadDir = '../uploads/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Delete old profile image if exists
                if (!empty($user['profile_image'])) {
                    $oldFile = $uploadDir . $user['profile_image'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $filename = uniqid() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $stmt2 = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt2->execute([$filename, $_SESSION['user_id']]);
                    $_SESSION['profile_image'] = $filename;
                    $user['profile_image'] = $filename;
                    $success = "Profile picture updated successfully!";
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }
    }

    // --- Remove profile picture ---
    if (isset($_POST['remove_photo'])) {
        if (!empty($user['profile_image'])) {
            $oldFile = '../uploads/profiles/' . $user['profile_image'];
            if (file_exists($oldFile)) unlink($oldFile);
        }
        $stmt2 = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
        $stmt2->execute([$_SESSION['user_id']]);
        $_SESSION['profile_image'] = null;
        $user['profile_image'] = null;
        $success = "Profile picture removed.";
    }

    // --- Update name / phone ---
    if (isset($_POST['update_info'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone     = sanitize($_POST['phone']);

        if (empty($full_name)) {
            $error = "Name cannot be empty.";
        } else {
            $stmt2 = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            if ($stmt2->execute([$full_name, $phone, $_SESSION['user_id']])) {
                $_SESSION['name'] = $full_name;
                $user['full_name'] = $full_name;
                $user['phone']     = $phone;
                $success = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}

$profileImageUrl = !empty($user['profile_image'])
    ? SITE_URL . '/uploads/profiles/' . $user['profile_image']
    : null;
?>

<style>
/* =============================================
   PROFILE PAGE
============================================= */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

.profile-wrapper {
    min-height: 80vh;
    background: linear-gradient(135deg, #f5f2e9 0%, #faf8f3 50%, #f0ebe0 100%);
    padding: 3rem 1rem;
}

.profile-container {
    max-width: 780px;
    margin: 0 auto;
    animation: fadeInUp 0.5s ease;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-page-title {
    font-size: 1.9rem;
    color: var(--vintage-brown);
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 0.2rem;
}
.profile-page-title i { color: var(--vintage-gold); }

/* ---- Cards ---- */
.profile-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(44,36,27,0.10);
}
.profile-card-header {
    background: linear-gradient(135deg, var(--vintage-brown), #3e2723);
    padding: 1.3rem 2rem;
    color: white;
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    letter-spacing: 0.03em;
}
.profile-card-header i { color: var(--vintage-gold); }
.profile-card-body { padding: 2rem; }

/* ---- Avatar Section ---- */
.avatar-section {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.avatar-preview {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    border: 4px solid var(--vintage-gold);
    background: var(--vintage-gold);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.8rem;
    color: white;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 4px 20px rgba(201,169,89,0.3);
}
.avatar-preview img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.avatar-preview .avatar-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.2s;
    cursor: pointer; border-radius: 50%;
    font-size: 1.4rem; color: white;
}
.avatar-preview:hover .avatar-overlay { opacity: 1; }

.avatar-actions { flex: 1; min-width: 200px; }
.avatar-actions h4 {
    color: var(--vintage-brown);
    margin-bottom: 0.4rem;
    font-size: 1.05rem;
}
.avatar-actions p {
    font-size: 0.83rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.upload-btn-wrap { position: relative; display: inline-block; }
.upload-btn-wrap input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%;
}
.upload-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: linear-gradient(135deg, var(--vintage-brown), #3e2723);
    color: white; padding: 0.6rem 1.3rem; border-radius: 8px;
    font-size: 0.88rem; font-weight: 700; cursor: pointer;
    transition: all 0.2s; border: none;
}
.upload-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(44,36,27,0.25); }

.remove-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: none; border: 1.5px solid #e0d9cd;
    color: var(--text-muted); padding: 0.6rem 1.1rem; border-radius: 8px;
    font-size: 0.85rem; cursor: pointer; margin-left: 0.6rem;
    transition: all 0.2s;
}
.remove-btn:hover { border-color: #e53935; color: #e53935; }

/* ---- Info form ---- */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.2rem;
}
.info-grid .full-width { grid-column: 1 / -1; }

.form-group-p label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--vintage-brown);
    margin-bottom: 0.4rem;
}
.form-group-p input {
    width: 100%;
    border: 2px solid #e8e3d8;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    font-family: 'Lato', sans-serif;
    color: var(--vintage-dark);
    background: #fdfcf9;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group-p input:focus {
    outline: none;
    border-color: var(--vintage-gold);
    box-shadow: 0 0 0 3px rgba(201,169,89,0.15);
    background: white;
}
.form-group-p input:disabled {
    background: #f5f2e9;
    color: #aaa;
    cursor: not-allowed;
}

.save-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: linear-gradient(135deg, var(--vintage-brown), #3e2723);
    color: white; padding: 0.75rem 2rem; border-radius: 10px;
    font-size: 0.95rem; font-weight: 700; cursor: pointer;
    border: none; margin-top: 1.2rem;
    transition: all 0.25s;
}
.save-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(44,36,27,0.25); }

/* ---- Alert ---- */
.alert-p {
    padding: 0.9rem 1.2rem;
    border-radius: 10px;
    font-size: 0.9rem;
    display: flex; align-items: center; gap: 0.6rem;
    margin-bottom: 1rem;
}
.alert-p.success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; }
.alert-p.error   { background: #fdecea; border: 1px solid #f5c6c6; color: #b71c1c; }

/* ---- Back link ---- */
.back-link {
    display: inline-flex; align-items: center; gap: 0.5rem;
    color: var(--vintage-brown); font-size: 0.88rem; font-weight: 700;
    text-decoration: none; margin-bottom: 0.5rem;
}
.back-link:hover { color: var(--vintage-gold); }

@media (max-width: 600px) {
    .info-grid { grid-template-columns: 1fr; }
    .avatar-section { flex-direction: column; align-items: flex-start; gap: 1.2rem; }
    .profile-card-body { padding: 1.4rem; }
}
</style>

<div class="profile-wrapper">
<div class="profile-container">

    <div>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1 class="profile-page-title">
            <i class="fas fa-user-circle"></i> My Profile
        </h1>
    </div>

    <?php if ($success): ?>
    <div class="alert-p success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-p error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ===== PROFILE PICTURE CARD ===== -->
    <div class="profile-card">
        <div class="profile-card-header">
            <i class="fas fa-camera"></i> Profile Picture
        </div>
        <div class="profile-card-body">
            <div class="avatar-section">

                <!-- Preview -->
                <div class="avatar-preview" id="avatarPreview">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?php echo $profileImageUrl; ?>" alt="Profile" id="avatarImg">
                    <?php else: ?>
                        <i class="fas fa-user" id="avatarIcon"></i>
                    <?php endif; ?>
                    <div class="avatar-overlay" onclick="document.getElementById('picInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <!-- Actions -->
                <div class="avatar-actions">
                    <h4>Upload a new photo</h4>
                    <p>JPG, PNG, WEBP or GIF · Max 2 MB<br>
                       Square images look best (e.g. 400×400 px)</p>

                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-btn-wrap">
                            <input type="file" name="profile_image" id="picInput"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewAndSubmit(this)">
                            <span class="upload-btn">
                                <i class="fas fa-upload"></i> Choose Photo
                            </span>
                        </div>
                    </form>

                    <?php if (!empty($user['profile_image'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="remove_photo" value="1">
                        <button type="submit" class="remove-btn"
                                onclick="return confirm('Remove your profile picture?')">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PERSONAL INFO CARD ===== -->
    <div class="profile-card">
        <div class="profile-card-header">
            <i class="fas fa-id-card"></i> Personal Information
        </div>
        <div class="profile-card-body">
            <form method="POST">
                <input type="hidden" name="update_info" value="1">
                <div class="info-grid">
                    <div class="form-group-p">
                        <label>Full Name <span style="color:#e53935">*</span></label>
                        <input type="text" name="full_name"
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group-p">
                        <label>Phone Number</label>
                        <input type="tel" name="phone"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               placeholder="e.g. 077 123 4567">
                    </div>
                    <div class="form-group-p full-width">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

</div><!-- /profile-container -->
</div><!-- /profile-wrapper -->

<script>
// Live preview before submit
function previewAndSubmit(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        let img = document.getElementById('avatarImg');
        const icon = document.getElementById('avatarIcon');
        if (!img) {
            img = document.createElement('img');
            img.id = 'avatarImg';
            if (icon) icon.remove();
            preview.insertBefore(img, preview.firstChild);
        }
        img.src = e.target.result;
        // Auto-submit
        document.getElementById('uploadForm').submit();
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php require_once '../includes/footer.php'; ?>
