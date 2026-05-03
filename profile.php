<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['username'] ?? 'User';

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

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
                    <form action="#" class="search-form">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" name="query" placeholder="Search list..." required>
                    </form>
                </li>
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
            <p>Manage your account information and preferences.</p>
        </div>


        
        <div class="profile-grid">
            <div class="profile-card overview-card">
                <div class="card-header">
                    <h2>Profile Overview</h2>

                    <button type="button" class="edit-btn">
                        <span class="material-symbols-outlined">edit</span>
                        Edit
                    </button>
                </div>
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    </div>
                </div>
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
        </div>
    </div>

    
    <script src="js/index.js"></script>
</body>
</html>