<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Require a list_id
$list_id = isset($_GET['list_id']) ? (int) $_GET['list_id'] : 0;
if ($list_id === 0) {
    header("Location: index.php");
    exit();
}

// Fetch the list — make sure it belongs to this user
$stmt = $conn->prepare("SELECT list_id, title, creditor FROM debt_list WHERE list_id = ? AND user_id = ?");
$stmt->bind_param("ii", $list_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$list = $result->fetch_assoc();
$stmt->close();

if (!$list) {
    header("Location: index.php");
    exit();
}

$error_title    = '';
$error_creditor = '';
$old_title      = $list['title'];
$old_creditor   = $list['creditor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_content'])) {
    $old_title    = trim($_POST['title']    ?? '');
    $old_creditor = trim($_POST['creditor'] ?? '');

    if ($old_title === '') {
        $error_title = 'Debt list title is required.';
    }
    if ($old_creditor === '') {
        $error_creditor = 'Creditor is required.';
    }

    if ($error_title === '' && $error_creditor === '') {
        // Forward to edit_content.php via hidden self-submit form
        ?>
        <!DOCTYPE html>
        <html>
        <body>
            <form id="forward" action="edit_content.php" method="post">
                <input type="hidden" name="list_id"            value="<?php echo $list_id; ?>">
                <input type="hidden" name="title"              value="<?php echo htmlspecialchars($old_title); ?>">
                <input type="hidden" name="creditor"           value="<?php echo htmlspecialchars($old_creditor); ?>">
                <input type="hidden" name="proceed_to_content" value="1">
            </form>
            <script>document.getElementById('forward').submit();</script>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Debt List</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
    <style>
        .field-error {
            color: #c0392b;
            font-size: 0.85rem;
            margin-top: 4px;
            display: none;
        }
        .field-error.visible {
            display: block;
        }
        input.invalid {
            border-color: #c0392b !important;
            outline-color: #c0392b !important;
        }
        .edit-badge {
            display: inline-block;
            background: #fa6909;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <span class="edit-badge">Editing List</span>
        <h1>Edit Debt List</h1>

        <form id="taskForm" action="edit_list.php?list_id=<?php echo $list_id; ?>" method="post" novalidate>

            <div class="form-group">
                <label for="title">Debt List Title *</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Enter debt list title"
                    value="<?php echo htmlspecialchars($old_title); ?>"
                    <?php if ($error_title): ?>class="invalid"<?php endif; ?>
                >
                <span
                    class="field-error<?php echo $error_title ? ' visible' : ''; ?>"
                    id="error-title"
                ><?php echo $error_title ?: 'Debt list title is required.'; ?></span>
            </div>

            <div class="form-group">
                <label for="creditor">Creditor *</label>
                <input
                    type="text"
                    id="creditor"
                    name="creditor"
                    placeholder="Enter creditor information"
                    value="<?php echo htmlspecialchars($old_creditor); ?>"
                    <?php if ($error_creditor): ?>class="invalid"<?php endif; ?>
                >
                <span
                    class="field-error<?php echo $error_creditor ? ' visible' : ''; ?>"
                    id="error-creditor"
                ><?php echo $error_creditor ?: 'Creditor is required.'; ?></span>
            </div>

            <input type="hidden" name="proceed_to_content" value="1">

            <div class="button-group">
                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
                <button type="submit" class="btn-submit">Proceed to Edit Contents</button>
            </div>
        </form>
    </div>

    <script>
        const form       = document.getElementById('taskForm');
        const titleInput = document.getElementById('title');
        const credInput  = document.getElementById('creditor');
        const errorTitle = document.getElementById('error-title');
        const errorCred  = document.getElementById('error-creditor');

        function validateField(input, errorEl) {
            const empty = input.value.trim() === '';
            input.classList.toggle('invalid', empty);
            errorEl.classList.toggle('visible', empty);
            return !empty;
        }

        titleInput.addEventListener('input', () => validateField(titleInput, errorTitle));
        credInput.addEventListener('input',  () => validateField(credInput,  errorCred));

        form.addEventListener('submit', function (e) {
            const titleOk = validateField(titleInput, errorTitle);
            const credOk  = validateField(credInput,  errorCred);
            if (!titleOk || !credOk) {
                e.preventDefault();
                const firstInvalid = form.querySelector('.invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    </script>
</body>
</html>