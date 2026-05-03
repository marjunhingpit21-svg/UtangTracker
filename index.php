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
    <link href="Css/index.css" rel="stylesheet">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Debt Tracker</title>
    <link href="Css/index.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <!-- Header Navigation -->
    <div class="navbar">
        <div class="nav-container">
            <div class="logo-area">
                <div class="logo">
                    <img src="img/OrangeLogo.png" alt="Listahan Logo">
                </div>
                <span class="app-title">Listahan</span>
            </div>
            <div class="nav-center">
                <!-- <h1>Debt Tracker</h1> -->
            </div>
            <div class="nav-right">
                <button class="btn-nav" onclick="openModal()">+ Create Debt List</button>
                <div class="profile-icon" title="Profile">
                    <i class="fas fa-user-circle"></i>
                </div>
                <button class="btn-nav2 btn-logout" onclick="logout()"><i class="fas fa-sign-out"></i></button>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h1>💰 Debt Tracker</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Debt List Form -->
        <div class="form-section">
            <h2>📋 Create New Debt List</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>User (optional)</label>
                        <select name="user_id">
                            <option value="">No User (Guest)</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>List Title *</label>
                        <input type="text" name="title" required placeholder="e.g., Personal Loans">
                    </div>
                    <div class="form-group">
                        <label>Creditor *</label>
                        <input type="text" name="creditor" required placeholder="e.g., John Doe">
                    </div>
                </div>
                <button type="submit" name="add_list">Create Debt List</button>
            </form>
        </div>
        
        <!-- Display All Debt Lists -->
        <?php if (empty($debt_lists)): ?>
            <div class="debt-list">
                <div class="no-data">
                    <p>No debt lists yet. Create your first debt list above! 📝</p>
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
                                <button type="submit" name="delete_list" class="btn-small btn-delete"><i class="fas fa-trash"></i></button>
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
                                                    <button type="submit" name="delete_debt" class="btn-small btn-delete2"><i class="fas fa-trash"></i></button>
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
                    
                    <!-- Add Item to List Form -->
                    <div class="add-item-form">
                        <h4>➕ Add Debt Item to "<?php echo htmlspecialchars($list['title']); ?>"</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="list_id" value="<?php echo $list['list_id']; ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Debt Name *</label>
                                    <input type="text" name="content_name" required placeholder="e.g., iPhone Loan">
                                </div>
                                <div class="form-group">
                                    <label>Additional Info</label>
                                    <textarea name="additional_info" rows="2" placeholder="Any notes..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Amount Owed *</label>
                                    <input type="number" step="0.01" name="money_owed" required placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Deadline</label>
                                    <input type="datetime-local" name="deadline">
                                </div>
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="debt_status" required>
                                        <option value="unpaid">Unpaid</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="add_debt">Add Debt Item</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal for Create Debt List -->
    <div id="debtListModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Debt List</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="" id="createListForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>User (optional)</label>
                        <select name="user_id">
                            <option value="">No User (Guest)</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>List Title *</label>
                        <input type="text" name="title" required placeholder="e.g., Personal Loans">
                    </div>
                    <div class="form-group">
                        <label>Creditor *</label>
                        <input type="text" name="creditor" required placeholder="e.g., John Doe">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_list">Create List</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openModal() {
            document.getElementById('debtListModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('debtListModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('debtListModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Logout function (to be implemented later)
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // Add your logout logic here later
                window.location.href = 'index.php';
            }
        }
        
        // Auto-hide message after 3 seconds
        setTimeout(function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>