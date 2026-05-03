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
        // Update status
        $stmt = $conn->prepare("UPDATE list_content SET debt_status = ? WHERE content_id = ?");
        $stmt->bind_param("si", $new_status, $content_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_debt'])) {
        $content_id = $_POST['content_id'];
        // Delete item
        $stmt = $conn->prepare("DELETE FROM list_content WHERE content_id = ?");
        $stmt->bind_param("i", $content_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_list'])) {
        $list_id = $_POST['list_id'];
        // Delete list and its items
        $stmt = $conn->prepare("DELETE FROM list_content WHERE list_id = ?");
        $stmt->bind_param("i", $list_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM debt_list WHERE list_id = ?");
        $stmt->bind_param("i", $list_id);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect to avoid resubmission
    header("Location: index.php");
    exit();
}

// Fetch debt lists for user
$query = "SELECT dl.list_id, dl.title, dl.creditor, dl.created_at FROM debt_list dl WHERE dl.user_id = ? ORDER BY dl.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$debt_lists = [];
while ($row = $result->fetch_assoc()) {
    $list_id = $row['list_id'];
    // Fetch items for this list
    $item_query = "SELECT content_id, content_name, additional_info, money_owed, deadline, debt_status FROM list_content WHERE list_id = ?";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param("i", $list_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $items = [];
    $total_owed = 0;
    while ($item = $item_result->fetch_assoc()) {
        $items[] = $item;
        if ($item['debt_status'] == 'unpaid') {
            $total_owed += $item['money_owed'];
        }
    }
    $row['items'] = $items;
    $row['total_owed'] = $total_owed;
    $debt_lists[] = $row;
    $item_stmt->close();
}
$stmt->close();

// Get user's name for the header
$name_query = "SELECT username FROM users WHERE user_id = ?";
$name_stmt = $conn->prepare($name_query);
$name_stmt->bind_param("i", $user_id);
$name_stmt->execute();
$name_result = $name_stmt->get_result();
if ($name_row = $name_result->fetch_assoc()) {
    $name = $name_row['username'];
}
$name_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debt Tracker</title>
    <link href="Css/index.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="container">
        <h1><?php echo isset($name) ? htmlspecialchars($name) . "'s Listahan" : "My Listahan"; ?></h1>
        
        <div class="nav-header">
            <div class="nav-links">
                <a href="index.php"><i class="fa fa-home xl"></i></a>
                <a href="profile.php"><i class="fa fa-user"></i></a>
                <a href="logout.php"><i class="fa fa-sign-out"></i></a>
            </div>

            <button class="add-list">
                    <a href="new_list.php"><i class="fa fa-plus"></i></a>
            </button>
        </div>

        
        <!-- Display All Debt Lists -->
        <?php if (empty($debt_lists)): ?>
            <div class="debt-list">
                <div class="no-data">
                    <p>No debt lists yet. Create a debt list first.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($debt_lists as $list): ?>
                <div class="debt-list">
                    <div class="list-header">
                        <div>
                            <h3><?php echo htmlspecialchars($list['title']); ?></h3>
                            <p>Creditor: <?php echo htmlspecialchars($list['creditor']); ?> | Created: <?php echo date('M d, Y', strtotime($list['created_at'])); ?></p>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div class="total-owed">
                                Total Unpaid: ₱<?php echo number_format($list['total_owed'], 2); ?>
                            </div>
                            <form method="POST" action="" class="inline-form" onsubmit="return confirm('Delete this entire debt list? This will also delete all items in it.');">
                                <input type="hidden" name="list_id" value="<?php echo $list['list_id']; ?>">
                                <button type="submit" name="delete_list" class="btn-small btn-delete">Delete List</button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (!empty($list['items'])): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Debt Item</th>
                                    <th>Additional Info</th>
                                    <th>Amount Owed</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list['items'] as $item): ?>
                                    <tr>
                                        <td data-label="Debt Item"><?php echo htmlspecialchars($item['content_name']); ?></td>
                                        <td data-label="Additional Info"><?php echo htmlspecialchars($item['additional_info']); ?></td>
                                        <td data-label="Amount Owed">₱<?php echo number_format($item['money_owed'], 2); ?></td>
                                        <td data-label="Deadline"><?php echo $item['deadline'] ? date('M d, Y', strtotime($item['deadline'])) : 'No deadline'; ?></td>
                                        <td data-label="Status">
                                            <span class="status-badge <?php echo $item['debt_status'] == 'paid' ? 'status-paid' : 'status-unpaid'; ?>">
                                                <?php echo ucfirst($item['debt_status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="action-buttons">
                                                <?php if ($item['debt_status'] == 'unpaid'): ?>
                                                    <form method="POST" action="" class="inline-form">
                                                        <input type="hidden" name="content_id" value="<?php echo $item['content_id']; ?>">
                                                        <input type="hidden" name="new_status" value="paid">
                                                        <button type="submit" name="update_status" class="btn-small">Mark Paid</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="" class="inline-form">
                                                        <input type="hidden" name="content_id" value="<?php echo $item['content_id']; ?>">
                                                        <input type="hidden" name="new_status" value="unpaid">
                                                        <button type="submit" name="update_status" class="btn-small">Mark Unpaid</button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" action="" class="inline-form" onsubmit="return confirm('Delete this debt item?');">
                                                    <input type="hidden" name="content_id" value="<?php echo $item['content_id']; ?>">
                                                    <button type="submit" name="delete_debt" class="btn-small btn-delete">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No debt items yet. Add your first debt item below!</p>
                        </div>
                    <?php endif; ?>
                    
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>