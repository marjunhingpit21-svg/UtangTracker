<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $content_id = $_POST['content_id'];
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE list_content SET debt_status = ? WHERE content_id = ?");
        $stmt->bind_param("si", $new_status, $content_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_debt'])) {
        $content_id = $_POST['content_id'];
        $stmt = $conn->prepare("DELETE FROM list_content WHERE content_id = ?");
        $stmt->bind_param("i", $content_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_list'])) {
        $list_id = $_POST['list_id'];
        $stmt = $conn->prepare("DELETE FROM list_content WHERE list_id = ?");
        $stmt->bind_param("i", $list_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM debt_list WHERE list_id = ?");
        $stmt->bind_param("i", $list_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}

$query = "SELECT dl.list_id, dl.title, dl.creditor, dl.created_at, IFNULL(SUM(CASE WHEN lc.debt_status = 'unpaid' THEN lc.money_owed ELSE 0 END), 0) AS total_owed " .
         "FROM debt_list dl " .
         "LEFT JOIN list_content lc ON dl.list_id = lc.list_id " .
         "WHERE dl.user_id = ? " .
         "GROUP BY dl.list_id " .
         "ORDER BY dl.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$debt_lists = [];
while ($row = $result->fetch_assoc()) {
    $debt_lists[] = $row;
}
$stmt->close();


// Get username
$name_query = "SELECT username FROM users WHERE user_id = ?";
$name_stmt = $conn->prepare($name_query);
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
$name = ($name_row = $name_result->fetch_assoc()) ? $name_row['username'] : 'User';
$name_stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listahan – Debt Tracker</title>
    <link href="Css/index.css" rel="stylesheet">
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
                <div class="sidebar-logo-icon">₱</div>
                <span class="sidebar-logo-text">Listahan</span>
            </div>
            <button class="sidebar-toggle-btn sidebar-toggle" title="Collapse sidebar">
                <i class="fa fa-chevron-left" style="color: var(--color-text-placeholder); padding:0; font-size:0.85rem;"></i>
            </button>
        </div>

        <div class="sidebar-content">
            <ul class="menu-list">
                <li class="menu-items">
                    <a href="index.php" class="menu-link active">
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
                    <a href="profile.php" class="menu-link">
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

        <div class="sidebar-footer">
            <!-- space for future widgets -->
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">

        <div class="user-greet">
            <div class="greet-content">
                <div class="greet-text">
                <?php
                    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                    $hour = (int) $now->format('G');
                    $greeting = ($hour < 12) ? 'Morning' : (($hour < 17) ? 'Afternoon' : 'Evening');
                ?>
                <h1>Good <?php echo $greeting; ?>, <?php echo htmlspecialchars($name); ?>! 👋</h1>
                </div>
                <div class="greet-date">
                    <span class="material-symbols-outlined">calendar_today</span>
                    <span><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Debt Lists -->
        <?php if (empty($debt_lists)): ?>
            <div class="debt-list">
                <div class="no-data">
                    <p>No debt lists yet. Create your first debt list to get started.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($debt_lists as $list): ?>
                <div class="debt-list">
                    <div class="list-header">
                        <div>
                            <h3><?php echo htmlspecialchars($list['title']); ?></h3>
                            <p>
                                Creditor: <?php echo htmlspecialchars($list['creditor']); ?>
                                &nbsp;·&nbsp;
                                Created: <?php echo date('M d, Y', strtotime($list['created_at'])); ?>
                            </p>
                        </div>
                        <div class="list-header-right">
                            <div class="total-owed">
                                Unpaid: ₱<?php echo number_format($list['total_owed'], 2); ?>
                            </div>
                            <form method="POST" action="" class="inline-form"
                                onsubmit="return confirm('Delete this entire list and all its items?');">
                                <input type="hidden" name="list_id" value="<?php echo $list['list_id']; ?>">
                                <button type="submit" name="delete_list" class="btn-small btn-delete">
                                    <i class="fa fa-trash" style="padding:0; color:white; font-size:0.75rem;"></i>
                                    Delete List
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Sidebar collapse / expand
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        document.querySelectorAll('.sidebar-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const isMobile = window.innerWidth <= 1024;
                if (isMobile) {
                    sidebar.classList.toggle('mobile-active');
                    overlay.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-active');
            overlay.classList.remove('active');
        });
    </script>
</body>
</html>