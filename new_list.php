<?php
// Server-side validation fallback (catches cases where JS is disabled)
$error_title = '';
$error_creditor = '';
$old_title = '';
$old_creditor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_content'])) {
    $old_title    = trim($_POST['title'] ?? '');
    $old_creditor = trim($_POST['creditor'] ?? '');

    if ($old_title === '') {
        $error_title = 'Debt list title is required.';
    }
    if ($old_creditor === '') {
        $error_creditor = 'Creditor is required.';
    }

    // Only forward to list_content.php if both fields are valid
    if ($error_title === '' && $error_creditor === '') {
        // Pass the values along via POST by re-submitting to list_content.php
        // We do this with a self-submitting hidden form so values aren't lost
        ?>
        <!DOCTYPE html>
        <html>
        <body>
            <form id="forward" action="list_content.php" method="post">
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
    <title>Add New List</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
    <style>
        /* Inline error messages under each field */
        .field-error {
            color: #c0392b;
            font-size: 0.85rem;
            margin-top: 4px;
            display: none;          /* hidden by default; JS or PHP shows them */
        }
        .field-error.visible {
            display: block;
        }

        /* Red border on invalid fields */
        input.invalid {
            border-color: #c0392b !important;
            outline-color: #c0392b !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Debt List</h1>
        <div id="messageModal" class="modal">
            <div class="modal-content" id="modalContent">
                <span id="closeModal" class="close-modal">&times;</span>
                <p id="modalMessage"></p>
                <div id="countdown" class="countdown" style="display:none;"></div>
            </div>
        </div>

        <form id="taskForm" action="new_list.php" method="post" novalidate>

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
                <button type="button" class="btn-cancel" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn-submit">Proceed to List Content</button>
            </div>
        </form>
    </div>

    <script>
        const form        = document.getElementById('taskForm');
        const titleInput  = document.getElementById('title');
        const credInput   = document.getElementById('creditor');
        const errorTitle  = document.getElementById('error-title');
        const errorCred   = document.getElementById('error-creditor');

        // Validate a single field and toggle its error state
        function validateField(input, errorEl) {
            const empty = input.value.trim() === '';
            input.classList.toggle('invalid', empty);
            errorEl.classList.toggle('visible', empty);
            return !empty;   // returns true when valid
        }

        // Clear error as soon as the user starts typing
        titleInput.addEventListener('input', () => validateField(titleInput, errorTitle));
        credInput.addEventListener('input',  () => validateField(credInput,  errorCred));

        // Intercept submit — only allow it through if both fields are filled
        form.addEventListener('submit', function (e) {
            const titleOk = validateField(titleInput, errorTitle);
            const credOk  = validateField(credInput,  errorCred);

            if (!titleOk || !credOk) {
                e.preventDefault();   // block navigation to list_content.php

                // Scroll the first error into view
                const firstInvalid = form.querySelector('.invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    </script>
</body>
</html>