<?php
// Database configuration
$host = 'localhost';
$dbname = 'listahan';
$username = 'root'; // Change this to your MySQL username
$password = 'root'; // Change this to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';

// Add new debt list
if (isset($_POST['add_list'])) {
    $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    $title = $_POST['title'];
    $creditor = $_POST['creditor'];
    $created_at = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO debt_list (user_id, title, creditor, created_at) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$user_id, $title, $creditor, $created_at])) {
        $message = "Debt list added successfully!";
        $messageType = "success";
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error adding debt list.";
        $messageType = "error";
    }
}

// Add new debt item
if (isset($_POST['add_debt'])) {
    $list_id = $_POST['list_id'];
    $content_name = $_POST['content_name'];
    $additional_info = $_POST['additional_info'];
    $money_owed = $_POST['money_owed'];
    $deadline = $_POST['deadline'] ?: null;
    $debt_status = $_POST['debt_status'];
    
    $sql = "INSERT INTO list_content (list_id, content_name, additional_info, money_owed, deadline, debt_status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$list_id, $content_name, $additional_info, $money_owed, $deadline, $debt_status])) {
        $message = "Debt item added successfully!";
        $messageType = "success";
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error adding debt item.";
        $messageType = "error";
    }
}

// Update debt status
if (isset($_POST['update_status'])) {
    $content_id = $_POST['content_id'];
    $new_status = $_POST['new_status'];
    
    $sql = "UPDATE list_content SET debt_status = ? WHERE content_id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$new_status, $content_id])) {
        $message = "Debt status updated!";
        $messageType = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error updating status.";
        $messageType = "error";
    }
}

// Delete debt item
if (isset($_POST['delete_debt'])) {
    $content_id = $_POST['content_id'];
    
    $sql = "DELETE FROM list_content WHERE content_id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$content_id])) {
        $message = "Debt item deleted!";
        $messageType = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error deleting debt item.";
        $messageType = "error";
    }
}

// Delete debt list
if (isset($_POST['delete_list'])) {
    $list_id = $_POST['list_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First delete all content in the list
        $sql1 = "DELETE FROM list_content WHERE list_id = ?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$list_id]);
        
        // Then delete the list
        $sql2 = "DELETE FROM debt_list WHERE list_id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$list_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $message = "Debt list deleted!";
        $messageType = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $message = "Error deleting debt list.";
        $messageType = "error";
    }
}

// Get all debt lists
$sql = "SELECT * FROM debt_list ORDER BY created_at DESC";
$result = $pdo->query($sql);
$debt_lists = $result->fetchAll(PDO::FETCH_ASSOC);

// Get all debt items and attach them to their respective lists
foreach ($debt_lists as $key => $list) {
    $sql = "SELECT * FROM list_content WHERE list_id = ? ORDER BY deadline ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$list['list_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to the list
    $debt_lists[$key]['items'] = $items;
    
    // Calculate total owed for the list
    $total = 0;
    foreach ($items as $item) {
        if ($item['debt_status'] == 'unpaid') {
            $total += $item['money_owed'];
        }
    }
    $debt_lists[$key]['total_owed'] = $total;
}

// Get users for dropdown
$users = $pdo->query("SELECT user_id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="Css/index.css" rel="stylesheet">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Debt Tracker</title>
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
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Display All Debt Lists -->
        <?php if (empty($debt_lists)): ?>
            <div class="debt-list">
                <div class="no-data">
                    <p>No debt lists yet. Click "Create Debt List" to get started!</p>
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
                        <h4>Add Debt Item to "<?php echo htmlspecialchars($list['title']); ?>"</h4>
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