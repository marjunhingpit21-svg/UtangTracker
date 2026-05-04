<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$title = '';
$creditor = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['proceed_to_content'])) {
        $title    = trim($_POST['title'] ?? '');
        $creditor = trim($_POST['creditor'] ?? '');

        if ($title === '' || $creditor === '') {
            $error = 'Please provide both a debt list title and creditor.';
        }
    } elseif (isset($_POST['create_list'])) {
        $title    = trim($_POST['title'] ?? '');
        $creditor = trim($_POST['creditor'] ?? '');

        // Collect all content rows sent from the form
        $content_names    = $_POST['content_name']    ?? [];
        $money_oweds      = $_POST['money_owed']      ?? [];
        $deadline_options = $_POST['deadline_option'] ?? [];
        $deadlines        = $_POST['deadline']        ?? [];

        $row_count = count($content_names);

        // --- Validate ---
        if ($title === '' || $creditor === '') {
            $error = 'Please fill in all required fields before creating the list.';
        } elseif ($row_count === 0) {
            $error = 'Please add at least one content item.';
        } else {
            for ($i = 0; $i < $row_count; $i++) {
                $cn  = trim($content_names[$i] ?? '');
                $mo  = trim($money_oweds[$i]   ?? '');
                $opt = $deadline_options[$i]   ?? 'set';
                $dl  = trim($deadlines[$i]     ?? '');

                if ($cn === '' || $mo === '') {
                    $error = 'Please fill in all content titles and amounts.';
                    break;
                }
                if (!is_numeric($mo) || $mo < 0) {
                    $error = 'Please enter a valid amount owed for each item.';
                    break;
                }
                if ($opt === 'set' && $dl === '') {
                    $error = 'Please set a deadline or select "No deadline" for item ' . ($i + 1) . '.';
                    break;
                }
            }
        }

        if ($error === '') {
            // Insert parent list
            $stmt = $conn->prepare("INSERT INTO debt_list (user_id, title, creditor, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $title, $creditor);
            $stmt->execute();
            $list_id = $conn->insert_id;
            $stmt->close();

            // Insert each content row
            for ($i = 0; $i < $row_count; $i++) {
                $cn  = trim($content_names[$i]);
                $mo  = trim($money_oweds[$i]);
                $opt = $deadline_options[$i] ?? 'set';
                $dl  = trim($deadlines[$i]  ?? '');

                if ($opt === 'none') {
                    $stmt = $conn->prepare("INSERT INTO list_content (list_id, content_name, money_owed, deadline, debt_status) VALUES (?, ?, ?, NULL, 'unpaid')");
                    $stmt->bind_param("isd", $list_id, $cn, $mo);
                } else {
                    $stmt = $conn->prepare("INSERT INTO list_content (list_id, content_name, money_owed, deadline, debt_status) VALUES (?, ?, ?, ?, 'unpaid')");
                    $stmt->bind_param("isds", $list_id, $cn, $mo, $dl);
                }
                $stmt->execute();
                $stmt->close();
            }

            header("Location: index.php");
            exit();
        }
    }
} else {
    header("Location: new_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add List Contents</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
    <style>
        /* ── Content block divider ─────────────────────────── */
        .content-divider {
            border: none;
            border-top: 2px solid #fa6909;
            margin: 28px 0;
            opacity: 0.55;
        }

        /* ── Content block header row ──────────────────────── */
        .content-block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .content-block-label {
            font-size: 13px;
            font-weight: 700;
            color: #fa6909;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* ── Remove button ─────────────────────────────────── */
        .btn-remove-block {
            background: none;
            border: 1.5px solid #e0c9b0;
            border-radius: 6px;
            color: #c0392b;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            cursor: pointer;
            flex: 0;                 /* don't stretch like the other buttons */
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-remove-block:hover {
            background: #fdecea;
            border-color: #c0392b;
        }

        /* ── Deadline radio row ────────────────────────────── */
        .deadline-radios {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .deadline-radios label {
            font-weight: normal;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── Add more button ───────────────────────────────── */
        #add-content-btn {
            background: var(--color-bg-secondary);
            color: var(--color-text-primary);
            border: 2px solid var(--color-border-hr);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 8px;
        }

        #add-content-btn:hover {
            border-color: var(--color-hover-primary);
            background: var(--color-hover-primary);
            color: white;
        }

        /* ── Error message ─────────────────────────────────── */
        .error-message {
            color: #c0392b;
            margin-bottom: 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add List Contents</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="taskForm" action="list_content.php" method="post">
            <input type="hidden" name="title"       value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="creditor"    value="<?php echo htmlspecialchars($creditor); ?>">
            <input type="hidden" name="create_list" value="1">

            <!-- Content blocks are injected here by JS -->
            <div id="content-blocks"></div>

            <button type="button" id="add-content-btn">+ Add More Contents</button>

            <div class="button-group">
                <button type="button" class="btn-cancel" onclick="window.history.back()">Back</button>
                <button type="submit" class="btn-submit">Create List</button>
            </div>
        </form>
    </div>

    <script>
        const container  = document.getElementById('content-blocks');
        const addBtn     = document.getElementById('add-content-btn');
        const minDate    = '<?php echo date('Y-m-d\TH:i'); ?>';
        let   blockCount = 0;

        function buildBlock(index) {
            const isFirst = index === 0;

            const wrapper = document.createElement('div');
            wrapper.className = 'content-block';
            wrapper.dataset.index = index;

            // Divider between blocks (not before the first)
            if (!isFirst) {
                const hr = document.createElement('hr');
                hr.className = 'content-divider';
                wrapper.appendChild(hr);
            }

            // Header row: label + remove button
            const header = document.createElement('div');
            header.className = 'content-block-header';

            const label = document.createElement('span');
            label.className = 'content-block-label';
            label.textContent = `Item ${index + 1}`;
            header.appendChild(label);

            if (!isFirst) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-remove-block';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => removeBlock(wrapper));
                header.appendChild(removeBtn);
            }

            wrapper.appendChild(header);

            // Content Title
            wrapper.appendChild(makeFormGroup(
                `content_name_${index}`,
                'Content Title *',
                `<input type="text"
                        id="content_name_${index}"
                        name="content_name[]"
                        placeholder="Enter content title"
                        required>`
            ));

            // Money Owed
            wrapper.appendChild(makeFormGroup(
                `money_owed_${index}`,
                'Money Owed *',
                `<input type="number"
                        id="money_owed_${index}"
                        name="money_owed[]"
                        placeholder="Enter amount owed"
                        required
                        step="0.01"
                        min="0">`
            ));

            // Deadline
            const deadlineGroup = document.createElement('div');
            deadlineGroup.className = 'form-group';
            deadlineGroup.innerHTML = `
                <label>Deadline</label>
                <div class="deadline-radios">
                    <label>
                        <input type="radio" name="deadline_option[${index}]" value="set" checked>
                        Set a deadline
                    </label>
                    <label>
                        <input type="radio" name="deadline_option[${index}]" value="none">
                        <strong>No deadline</strong>
                    </label>
                </div>
                <input type="datetime-local"
                       id="deadline_${index}"
                       name="deadline[]"
                       min="${minDate}"
                       style="display:block;">
            `;
            wrapper.appendChild(deadlineGroup);

            // Wire up the radio toggle for this block
            const radios       = deadlineGroup.querySelectorAll('input[type="radio"]');
            const deadlineInput = deadlineGroup.querySelector('input[type="datetime-local"]');

            function syncDeadline() {
                const noDeadline = deadlineGroup.querySelector('input[value="none"]').checked;
                // Do NOT disable — disabled inputs are excluded from POST,
                // which shifts indexes and causes wrong deadline matching.
                // Instead just hide/show and clear the value.
                deadlineInput.style.display = noDeadline ? 'none' : 'block';
                deadlineInput.required = !noDeadline;
                if (noDeadline) deadlineInput.value = '';
            }

            radios.forEach(r => r.addEventListener('change', syncDeadline));
            syncDeadline();

            return wrapper;
        }

        function makeFormGroup(id, labelText, inputHTML) {
            const group = document.createElement('div');
            group.className = 'form-group';
            group.innerHTML = `<label for="${id}">${labelText}</label>${inputHTML}`;
            return group;
        }

        function removeBlock(blockEl) {
            blockEl.remove();
            renumberBlocks();
        }

        function renumberBlocks() {
            const blocks = container.querySelectorAll('.content-block');
            blocks.forEach((block, i) => {
                block.querySelector('.content-block-label').textContent = `Item ${i + 1}`;
            });
        }

        function addBlock() {
            const block = buildBlock(blockCount);
            container.appendChild(block);
            blockCount++;
        }

        addBtn.addEventListener('click', addBlock);

        // Render the first block on page load
        addBlock();
    </script>
</body>
</html>