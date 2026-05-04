<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$list_id  = 0;
$title    = '';
$creditor = '';
$error    = '';
$existing_items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['proceed_to_content'])) {
        // Coming from edit_list.php — just show the form pre-filled
        $list_id  = (int) ($_POST['list_id']  ?? 0);
        $title    = trim($_POST['title']    ?? '');
        $creditor = trim($_POST['creditor'] ?? '');

        if ($list_id === 0 || $title === '' || $creditor === '') {
            header("Location: index.php");
            exit();
        }

        // Verify ownership
        $chk = $conn->prepare("SELECT list_id FROM debt_list WHERE list_id = ? AND user_id = ?");
        $chk->bind_param("ii", $list_id, $user_id);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            header("Location: index.php");
            exit();
        }
        $chk->close();

        // Load existing content items for pre-fill
        $ic = $conn->prepare("SELECT content_id, content_name, money_owed, deadline, debt_status FROM list_content WHERE list_id = ? ORDER BY content_id");
        $ic->bind_param("i", $list_id);
        $ic->execute();
        $ir = $ic->get_result();
        while ($row = $ir->fetch_assoc()) {
            $existing_items[] = $row;
        }
        $ic->close();

    } elseif (isset($_POST['save_list'])) {
        // Final save
        $list_id  = (int) ($_POST['list_id']  ?? 0);
        $title    = trim($_POST['title']    ?? '');
        $creditor = trim($_POST['creditor'] ?? '');

        $content_ids      = $_POST['content_id']      ?? [];   // '' for new rows
        $content_names    = $_POST['content_name']    ?? [];
        $money_oweds      = $_POST['money_owed']      ?? [];
        $deadline_options = $_POST['deadline_option'] ?? [];
        $deadlines        = $_POST['deadline']        ?? [];
        $statuses         = $_POST['debt_status']     ?? [];
        $delete_ids       = $_POST['delete_content_ids'] ?? [];   // ids to delete

        $row_count = count($content_names);

        // Verify ownership
        $chk = $conn->prepare("SELECT list_id FROM debt_list WHERE list_id = ? AND user_id = ?");
        $chk->bind_param("ii", $list_id, $user_id);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            header("Location: index.php");
            exit();
        }
        $chk->close();

        // Validate
        if ($title === '' || $creditor === '') {
            $error = 'Please fill in all required fields.';
        } elseif ($row_count === 0) {
            $error = 'Please add at least one content item.';
        } else {
            for ($i = 0; $i < $row_count; $i++) {
                $cn  = trim($content_names[$i] ?? '');
                $mo  = trim($money_oweds[$i]   ?? '');
                $opt = $deadline_options[$i]   ?? 'set';
                $dl  = trim($deadlines[$i]     ?? '');
                if ($cn === '' || $mo === '') { $error = 'Please fill in all content titles and amounts.'; break; }
                if (!is_numeric($mo) || $mo < 0) { $error = 'Please enter a valid amount owed for each item.'; break; }
                if ($opt === 'set' && $dl === '') { $error = 'Please set a deadline or select "No deadline" for item ' . ($i + 1) . '.'; break; }
            }
        }

        if ($error === '') {
            // 1. Update the parent list
            $upd = $conn->prepare("UPDATE debt_list SET title = ?, creditor = ? WHERE list_id = ? AND user_id = ?");
            $upd->bind_param("ssii", $title, $creditor, $list_id, $user_id);
            $upd->execute();
            $upd->close();

            // 2. Delete rows marked for removal
            foreach ($delete_ids as $did) {
                $did = (int) $did;
                if ($did > 0) {
                    $del = $conn->prepare("DELETE FROM list_content WHERE content_id = ? AND list_id = ?");
                    $del->bind_param("ii", $did, $list_id);
                    $del->execute();
                    $del->close();
                }
            }

            // 3. Update existing rows / insert new rows
            for ($i = 0; $i < $row_count; $i++) {
                $cid = (int) ($content_ids[$i] ?? 0);
                $cn  = trim($content_names[$i]);
                $mo  = (float) trim($money_oweds[$i]);
                $opt = $deadline_options[$i] ?? 'set';
                $dl  = trim($deadlines[$i]  ?? '');
                $st  = ($statuses[$i] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid';
                $dl_val = ($opt === 'none') ? null : $dl;

                if ($cid > 0) {
                    // Existing row — update
                    if ($dl_val === null) {
                        $s = $conn->prepare("UPDATE list_content SET content_name=?, money_owed=?, deadline=NULL, debt_status=? WHERE content_id=? AND list_id=?");
                        $s->bind_param("sdsii", $cn, $mo, $st, $cid, $list_id);
                    } else {
                        $s = $conn->prepare("UPDATE list_content SET content_name=?, money_owed=?, deadline=?, debt_status=? WHERE content_id=? AND list_id=?");
                        $s->bind_param("sdssii", $cn, $mo, $dl_val, $st, $cid, $list_id);
                    }
                    $s->execute();
                    $s->close();
                } else {
                    // New row — insert
                    if ($dl_val === null) {
                        $s = $conn->prepare("INSERT INTO list_content (list_id, content_name, money_owed, deadline, debt_status) VALUES (?, ?, ?, NULL, 'unpaid')");
                        $s->bind_param("isd", $list_id, $cn, $mo);
                    } else {
                        $s = $conn->prepare("INSERT INTO list_content (list_id, content_name, money_owed, deadline, debt_status) VALUES (?, ?, ?, ?, 'unpaid')");
                        $s->bind_param("isds", $list_id, $cn, $mo, $dl_val);
                    }
                    $s->execute();
                    $s->close();
                }
            }

            header("Location: index.php");
            exit();
        }

        // Re-load existing items for re-display on error
        $ic = $conn->prepare("SELECT content_id, content_name, money_owed, deadline, debt_status FROM list_content WHERE list_id = ? ORDER BY content_id");
        $ic->bind_param("i", $list_id);
        $ic->execute();
        $ir = $ic->get_result();
        while ($row = $ir->fetch_assoc()) {
            $existing_items[] = $row;
        }
        $ic->close();
    }

} else {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit List Contents</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
    <style>
        .content-divider {
            border: none;
            border-top: 2px solid #fa6909;
            margin: 28px 0;
            opacity: 0.55;
        }
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
        .btn-remove-block {
            background: none;
            border: 1.5px solid #e0c9b0;
            border-radius: 6px;
            color: #c0392b;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            cursor: pointer;
            flex: 0;
            transition: background 0.2s, border-color 0.2s;
        }
        .btn-remove-block:hover {
            background: #fdecea;
            border-color: #c0392b;
        }
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
        .error-message {
            color: #c0392b;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .status-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        .status-row label {
            font-weight: normal;
            margin-bottom: 0;
        }
        .existing-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
            padding: 2px 8px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
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
        <h1>Edit List Contents</h1>
        <p style="color:var(--color-text-placeholder);font-size:13px;margin-bottom:20px;">
            <strong><?php echo htmlspecialchars($title); ?></strong> &mdash; <?php echo htmlspecialchars($creditor); ?>
        </p>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Hidden input to carry deleted IDs -->
        <input type="hidden" id="delete-ids-input" name="delete_content_ids" value="">

        <form id="taskForm" action="edit_content.php" method="post">
            <input type="hidden" name="list_id"   value="<?php echo $list_id; ?>">
            <input type="hidden" name="title"      value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="creditor"   value="<?php echo htmlspecialchars($creditor); ?>">
            <input type="hidden" name="save_list"  value="1">
            <input type="hidden" id="delete-ids"   name="delete_content_ids" value="">

            <div id="content-blocks"></div>

            <button type="button" id="add-content-btn">+ Add More Contents</button>

            <div class="button-group">
                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        const container  = document.getElementById('content-blocks');
        const addBtn     = document.getElementById('add-content-btn');
        const deleteIdsInput = document.getElementById('delete-ids');
        const minDate    = '<?php echo date('Y-m-d\TH:i'); ?>';
        let   blockCount = 0;
        let   deletedIds = [];

        // Existing items passed from PHP
        const existingItems = <?php echo json_encode(array_values($existing_items), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        function buildBlock(index, prefill = null) {
            const isFirst = index === 0 && container.children.length === 0;

            const wrapper = document.createElement('div');
            wrapper.className = 'content-block';
            wrapper.dataset.index = index;

            // Divider
            if (container.children.length > 0) {
                const hr = document.createElement('hr');
                hr.className = 'content-divider';
                wrapper.appendChild(hr);
            }

            // Header
            const header = document.createElement('div');
            header.className = 'content-block-header';

            const label = document.createElement('span');
            label.className = 'content-block-label';
            label.textContent = `Item ${container.children.length + 1}`;
            header.appendChild(label);

            // Show "existing" badge for pre-filled rows
            if (prefill?.content_id) {
                const badge = document.createElement('span');
                badge.className = 'existing-badge';
                badge.textContent = 'Existing';
                header.appendChild(badge);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-block';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', () => removeBlock(wrapper, prefill?.content_id));
            header.appendChild(removeBtn);

            wrapper.appendChild(header);

            // Hidden content_id ('' for new rows)
            const cidInput = document.createElement('input');
            cidInput.type  = 'hidden';
            cidInput.name  = 'content_id[]';
            cidInput.value = prefill?.content_id ?? '';
            wrapper.appendChild(cidInput);

            // Content Title
            wrapper.appendChild(makeFormGroup(
                `content_name_${index}`,
                'Content Title *',
                `<input type="text"
                        id="content_name_${index}"
                        name="content_name[]"
                        placeholder="Enter content title"
                        value="${escHtml(prefill?.content_name ?? '')}"
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
                        value="${escHtml(prefill?.money_owed ?? '')}"
                        required
                        step="0.01"
                        min="0">`
            ));

            // Deadline
            const hasDeadline = prefill?.deadline && prefill.deadline !== '0000-00-00 00:00:00';
            // Format for datetime-local input (YYYY-MM-DDTHH:MM)
            let deadlineVal = '';
            if (hasDeadline) {
                // PHP gives "YYYY-MM-DD HH:MM:SS" — convert to "YYYY-MM-DDTHH:MM"
                deadlineVal = prefill.deadline.replace(' ', 'T').substring(0, 16);
            }

            const deadlineGroup = document.createElement('div');
            deadlineGroup.className = 'form-group';
            deadlineGroup.innerHTML = `
                <label>Deadline</label>
                <div class="deadline-radios">
                    <label>
                        <input type="radio" name="deadline_option[${index}]" value="set" ${!prefill || hasDeadline ? 'checked' : ''}>
                        Set a deadline
                    </label>
                    <label>
                        <input type="radio" name="deadline_option[${index}]" value="none" ${prefill && !hasDeadline ? 'checked' : ''}>
                        <strong>No deadline</strong>
                    </label>
                </div>
                <input type="datetime-local"
                       id="deadline_${index}"
                       name="deadline[]"
                       min="${minDate}"
                       value="${deadlineVal}"
                       style="display:${prefill && !hasDeadline ? 'none' : 'block'};">
            `;
            wrapper.appendChild(deadlineGroup);

            const radios        = deadlineGroup.querySelectorAll('input[type="radio"]');
            const deadlineInput = deadlineGroup.querySelector('input[type="datetime-local"]');

            function syncDeadline() {
                const noDeadline = deadlineGroup.querySelector('input[value="none"]').checked;
                deadlineInput.style.display = noDeadline ? 'none' : 'block';
                deadlineInput.required = !noDeadline;
                if (noDeadline) deadlineInput.value = '';
            }
            radios.forEach(r => r.addEventListener('change', syncDeadline));
            syncDeadline();

            // Status (only show for existing items)
            if (prefill?.content_id) {
                const statusGroup = document.createElement('div');
                statusGroup.className = 'form-group';
                const isPaid = prefill.debt_status === 'paid';
                statusGroup.innerHTML = `
                    <label>Status</label>
                    <div class="status-row">
                        <label>
                            <input type="radio" name="debt_status[${index}]" value="unpaid" ${!isPaid ? 'checked' : ''}>
                            Unpaid
                        </label>
                        <label>
                            <input type="radio" name="debt_status[${index}]" value="paid" ${isPaid ? 'checked' : ''}>
                            Paid
                        </label>
                    </div>
                `;
                wrapper.appendChild(statusGroup);
            } else {
                // New rows always start as unpaid — hidden input
                const stInput = document.createElement('input');
                stInput.type  = 'hidden';
                stInput.name  = 'debt_status[]';
                stInput.value = 'unpaid';
                wrapper.appendChild(stInput);
            }

            return wrapper;
        }

        function makeFormGroup(id, labelText, inputHTML) {
            const group = document.createElement('div');
            group.className = 'form-group';
            group.innerHTML = `<label for="${id}">${labelText}</label>${inputHTML}`;
            return group;
        }

        function removeBlock(blockEl, contentId) {
            if (contentId) {
                deletedIds.push(contentId);
                deleteIdsInput.value = deletedIds.join(',');
            }
            blockEl.remove();
            renumberBlocks();
        }

        function renumberBlocks() {
            const blocks = container.querySelectorAll('.content-block');
            blocks.forEach((block, i) => {
                const lbl = block.querySelector('.content-block-label');
                if (lbl) lbl.textContent = `Item ${i + 1}`;
            });
        }

        function addBlock(prefill = null) {
            const block = buildBlock(blockCount, prefill);
            container.appendChild(block);
            blockCount++;
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        addBtn.addEventListener('click', () => addBlock(null));

        // Pre-fill existing items on load
        if (existingItems.length > 0) {
            existingItems.forEach(item => addBlock(item));
        } else {
            addBlock(null); // start with one empty block if list has no items
        }
    </script>
</body>
</html>