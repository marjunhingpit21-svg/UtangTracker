<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['username'] ?? 'User';
$errorMessage = '';
$successMessage = '';
$isEditing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $formUsername = trim($_POST['username'] ?? '');
    $formEmail = trim($_POST['email'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $isEditing = true;

    if ($formUsername === '' || $formEmail === '') {
        $errorMessage = 'Username and email are required.';
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        $errorMessage = 'New password must be at least 8 characters long.';
    } elseif ($newPassword !== '' && empty($oldPassword)) {
        $errorMessage = 'Old password is required when changing password.';
    } else {
        // Check for duplicate username/email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->bind_param("ssi", $formUsername, $formEmail, $user_id);
        $stmt->execute();
        $duplicateResult = $stmt->get_result();

        if ($duplicateResult && $duplicateResult->num_rows > 0) {
            $errorMessage = 'That username or email is already in use.';
        }
        $stmt->close();
    }

    // Validate old password if changing password
    if (empty($errorMessage) && $newPassword !== '') {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($oldPassword, $userData['password'])) {
            $errorMessage = 'Old password is incorrect.';
        }
    }

    if (empty($errorMessage)) {
        $updatedAt = date('Y-m-d H:i:s');
        if ($newPassword !== '') {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $formUsername, $formEmail, $passwordHash, $updatedAt, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $formUsername, $formEmail, $updatedAt, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['username'] = $formUsername;
            $name = $formUsername;
            $successMessage = 'Profile updated successfully.';
            $isEditing = false;
        } else {
            $errorMessage = 'Unable to save changes. Please try again later.';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$formUsername = htmlspecialchars($_POST['username'] ?? $user['username']);
$formEmail = htmlspecialchars($_POST['email'] ?? $user['email']);
$isEditing = $isEditing || !empty($errorMessage);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($name); ?>'s Profile</title>
    <link href="Css/index.css" rel="stylesheet">
    <link href="Css/profile.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
</head>
<body>

    <!-- Mobile top nav -->
    <nav class="site-nav">
        <button class="mobile-menu-btn sidebar-toggle">
            <i class="fa fa-bars" style="color:white; padding:0;"></i>
        </button>
    </nav>

    <!-- Sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="img/circular-logo.png" alt="Listahan Logo" class="logo">
                <span class="logo-text" style="font-weight:bold;font-size:25px;">Listahan</span>
            </div>
            <button class="sidebar-toggle-btn sidebar-toggle" title="Collapse sidebar">
                <i class="fa fa-chevron-left" style="color: var(--color-text-placeholder); padding:0; font-size:0.85rem;"></i>
            </button>
        </div>

        <div class="sidebar-content">
            <ul class="menu-list">
                <li class="menu-items">
                    <a href="index.php" class="menu-link">
                        <i class="fa fa-home"></i>
                        <span class="menu-label">Home</span>
                    </a>
                </li>
                <li class="menu-items">
                    <a href="new_list.php" class="menu-link">
                        <i class="fa fa-plus-circle"></i>
                        <span class="menu-label">New List</span>
                    </a>
                </li>
                <li class="menu-items">
                    <a href="profile.php" class="menu-link active">
                        <i class="fa fa-user"></i>
                        <span class="menu-label">Profile</span>
                    </a>
                </li>
                <li class="menu-items">
                    <a href="logout.php" class="menu-link">
                        <i class="fa fa-sign-out"></i>
                        <span class="menu-label">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
     <div class="main-content">
        <div class="profile-header">
            <button class="mobile-menu-toggle sidebar-toggle">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1>My Profile</h1>
            <p>Manage your account information.</p>
        </div>


        
        <div class="profile-grid">
            <div class="profile-card overview-card<?php echo $isEditing ? ' editing' : ''; ?>">
               <div class="card-header">
                    <h2>Profile Overview</h2>
                    
                    <div class="header-actions">
                        <button type="submit" form="profileForm" class="save-btn" id="saveBtn">
                            <span class="material-symbols-outlined">save</span>
                            Save Changes
                        </button>
                        <button type="button" class="edit-btn" id="toggleEditBtn" aria-expanded="<?php echo $isEditing ? 'true' : 'false'; ?>">
                            <span class="material-symbols-outlined"><?php echo $isEditing ? 'close' : 'edit'; ?></span>
                            <?php echo $isEditing ? 'Cancel' : 'Edit'; ?>
                        </button>
                    </div>
                </div>

                <?php if (!empty($successMessage) || !empty($errorMessage)): ?>
                    <div class="profile-message <?php echo !empty($successMessage) ? 'success' : 'error'; ?>">
                        <p><?php echo htmlspecialchars($successMessage ?: $errorMessage); ?></p>
                    </div>
                <?php endif; ?>

                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    </div>
                </div>

                <div class="profile-view">
                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="material-symbols-outlined">email</span>
                            <div>
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="material-symbols-outlined">lock</span>
                            <div>
                                <label>Password</label>
                                <p>••••••••</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <div>
                                <label>Member Since</label>
                                <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="profileForm" class="profile-form profile-edit" method="post" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="is_edit" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo $formUsername; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $formEmail; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="old_password">Old Password</label>
                            <input type="password" id="old_password" name="old_password" required placeholder="Enter current password to save changes">
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="delete-account">
            <h3>Delete Account</h3>
            <p class="warning-text">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
            <button type="button" id="deleteAccountBtn">Delete Account</button>
        </div>

    </div>

    
    <script src="js/index.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileCard = document.querySelector('.profile-card');
        const toggleEditBtn = document.getElementById('toggleEditBtn');
        const profileForm = document.getElementById('profileForm');
        
        const currentUsernameElem = document.querySelector('.profile-info h3');
        const currentEmailElem = document.querySelector('.detail-item:first-child p');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const oldPasswordInput = document.getElementById('old_password');
        const newPasswordInput = document.getElementById('new_password');

        let originalUsername = '';
        let originalEmail = '';

        function populateFormWithCurrentData() {
            if (currentUsernameElem) {
                originalUsername = currentUsernameElem.textContent.trim();
                if (usernameInput) usernameInput.value = originalUsername;
            }
            if (currentEmailElem) {
                originalEmail = currentEmailElem.textContent.trim();
                if (emailInput) emailInput.value = originalEmail;
            }
            if (oldPasswordInput) oldPasswordInput.value = '';
            if (newPasswordInput) newPasswordInput.value = '';
        }

        function enterEditMode() {
            if (profileCard) profileCard.classList.add('editing');
            if (toggleEditBtn) {
                toggleEditBtn.innerHTML = '<span class="material-symbols-outlined">close</span> Cancel';
                toggleEditBtn.setAttribute('aria-expanded', 'true');
                toggleEditBtn.classList.add('cancel-mode');
            }
            populateFormWithCurrentData();
            if (usernameInput) usernameInput.focus();
        }

        function exitEditMode() {
            if (profileCard) profileCard.classList.remove('editing');
            if (toggleEditBtn) {
                toggleEditBtn.innerHTML = '<span class="material-symbols-outlined">edit</span> Edit';
                toggleEditBtn.setAttribute('aria-expanded', 'false');
                toggleEditBtn.classList.remove('cancel-mode');
            }
            const messageDiv = document.querySelector('.profile-message');
            if (messageDiv) messageDiv.style.display = 'none';
            if (newPasswordInput) newPasswordInput.value = '';
            if (oldPasswordInput) oldPasswordInput.value = '';
        }

        // === Edit button is now handled safely (no conflict with index.js) ===

        window.enterProfileEdit = enterEditMode;
        window.exitProfileEdit = exitEditMode;

        // Form submit loading
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const submitBtn = profileForm.querySelector('.save-btn');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px;display: inline-block;">hourglass_top</span> Saving...';
                    submitBtn.disabled = true;
                }
            });
        }

        if (newPasswordInput) {
            // You can keep or adjust validation as needed
            newPasswordInput.addEventListener('input', function() {
                if (this.value.length > 0 && this.value.length < 8) {
                    this.setCustomValidity('New password must be at least 8 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', function() {
                const confirmed = confirm('WARNING: This will permanently delete your account and ALL your data. This action cannot be undone.\n\nAre you absolutely sure you want to delete your account?');
                
                if (confirmed) {
                    const finalConfirm = confirm('LAST WARNING: All your lists and data will be lost forever.\n\nType "DELETE" to confirm account deletion.');
                    
                    if (finalConfirm) {
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST';
                        deleteForm.action = 'delete_account.php';
                        deleteForm.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'delete_account';
                        
                        const confirmInput = document.createElement('input');
                        confirmInput.type = 'hidden';
                        confirmInput.name = 'confirm';
                        confirmInput.value = 'DELETE';
                        
                        deleteForm.appendChild(actionInput);
                        deleteForm.appendChild(confirmInput);
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();
                    }
                }
            });
        }

        // Handle server-side validation errors
        const errorMessageElem = document.querySelector('.profile-message.error');
        if (errorMessageElem) {
            enterEditMode();
        }

        // Success message handling
        const successMessageElem = document.querySelector('.profile-message.success');
        if (successMessageElem) {
            setTimeout(function() {
                if (profileCard && profileCard.classList.contains('editing')) {
                    exitEditMode();
                }
                successMessageElem.style.transition = 'opacity 0.5s ease';
                successMessageElem.style.opacity = '0';
                setTimeout(function() {
                    successMessageElem.style.display = 'none';
                }, 500);
            }, 1500);
        }

        // Keyboard ESC support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && profileCard?.classList.contains('editing')) {
                exitEditMode();
            }
        });

        // Username length limit
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                const maxLength = 50;
                if (this.value.length > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                }
            });
        }

        // Email real-time validation
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                if (this.value !== '' && !emailRegex.test(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                    this.style.borderColor = '#f5b5b5';
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '';
                }
            });
        }
    });
</script>
</body>
</html>