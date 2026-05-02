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
    <title>Debt Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .message.success {
            background-color: #4CAF50;
            color: white;
        }
        
        .message.error {
            background-color: #f44336;
            color: white;
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .form-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .debt-list {
            background: white;
            border-radius: 12px;
            margin-bottom: 30px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .list-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .list-header h3 {
            font-size: 1.3em;
        }
        
        .list-header p {
            margin-top: 5px;
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .total-owed {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-paid {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-unpaid {
            background-color: #f44336;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        .add-item-form {
            background: #f8f9fa;
            padding: 20px;
            margin-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .add-item-form h4 {
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .inline-form {
            display: inline;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            thead {
                display: none;
            }
            
            tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                border-bottom: 1px solid #eee;
            }
            
            td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 40%;
            }
        }
    </style>
</head>
<body>
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
                            <h3>📌 <?php echo htmlspecialchars($list['title']); ?></h3>
                            <p>Creditor: <?php echo htmlspecialchars($list['creditor']); ?> | Created: <?php echo date('M d, Y', strtotime($list['created_at'])); ?></p>
                            <p><small>List ID: <?php echo $list['list_id']; ?></small></p>
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
</body>
</html>