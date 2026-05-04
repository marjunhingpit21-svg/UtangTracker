<?php
require 'db.php';
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Must be a POST request with the correct action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'delete_account' || ($_POST['confirm'] ?? '') !== 'DELETE') {
    header("Location: profile.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

// ── 1. Validate password ─────────────────────────────────────────────────────

if (empty($password)) {
    header("Location: profile.php?delete_error=password_required");
    exit();
}

$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: profile.php?delete_error=wrong_password");
    exit();
}

// ── 2. Delete all user data inside a transaction ─────────────────────────────
// Deletion order respects FK constraints:
//   list_content → debt_list → users

$conn->begin_transaction();

try {
    // Delete list_content rows that belong to this user's lists
    $stmt = $conn->prepare("
        DELETE lc FROM list_content lc
        INNER JOIN debt_list dl ON lc.list_id = dl.list_id
        WHERE dl.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete the user's debt lists
    $stmt = $conn->prepare("DELETE FROM debt_list WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete the user account
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // Redirect back with a generic error — don't expose exception details
    header("Location: profile.php?delete_error=server_error");
    exit();
}

// ── 3. Destroy the session and redirect ──────────────────────────────────────

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Send to login with a farewell message
header("Location: login.php?account_deleted=1");
exit();
?>