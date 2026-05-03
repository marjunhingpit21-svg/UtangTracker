<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


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

$item_query = "SELECT lc.content_id, lc.list_id, lc.content_name, lc.money_owed, lc.deadline, lc.debt_status " .
              "FROM list_content lc " .
              "JOIN debt_list dl ON lc.list_id = dl.list_id " .
              "WHERE dl.user_id = ? " .
              "ORDER BY lc.list_id, lc.content_id";
$item_stmt = $conn->prepare($item_query);
$item_stmt->bind_param("i", $user_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$list_items = [];
while ($row = $item_result->fetch_assoc()) {
    $list_items[$row['list_id']][] = $row;
}
$item_stmt->close();

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
                <img src="img/circular-logo.png" alt="Listahan Logo" class="logo">
                <span class="logo-text">Listahan</span>
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

        <div class="filters">
            <div class="task-filters">
                <button class="filter-btn active" data-filter="all">
                    <span class="material-symbols-outlined">view_list</span>
                    All Lists
                    <span class="filter-count">0</span>
                </button>
                <button class="filter-btn" data-filter="completed">
                    <span class="material-symbols-outlined">task_alt</span>
                    Completed
                    <span class="filter-count">0</span>
                </button>
                <button class="filter-btn" data-filter="pending">
                    <span class="material-symbols-outlined">pending_actions</span>
                    Pending
                    <span class="filter-count">0</span>
                </button>
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
                <div class="debt-list" data-list-id="<?php echo $list['list_id']; ?>">
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
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="task-panel-overlay" class="task-panel-overlay"></div>
    <aside id="task-panel" class="task-panel" aria-hidden="true">
        <div class="panel-header">
            <div>
                <h3 id="task-panel-title">List Details</h3>
                <p id="task-panel-subtitle" class="panel-subtitle"></p>
            </div>
            <button type="button" class="close-panel-btn" id="closeTaskPanel" aria-label="Close panel">&times;</button>
        </div>
        <div class="panel-content">
            <div id="task-details-content"></div>
        </div>
    </aside>

    <script>
        window.listItems = <?php echo json_encode($list_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>
    <script src="js/index.js"></script>
</body>
</html>