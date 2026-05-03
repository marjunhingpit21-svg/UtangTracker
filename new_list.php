<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New List</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
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

        <form id="taskForm">
            <div class="form-group">
                <label for="title">Debt List Title *</label>
                <input type="text" id="title" name="title" placeholder="Enter debt list title" required>
            </div>

            <div class="form-group">
                <label for="creditor">Creditor *</label>
                <input type="text" id="creditor" name="creditor" placeholder="Enter creditor information" required>
            </div>

            <div class="button-group">
                <button type="button" class="btn-cancel" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn-submit">Proceed to List Content</button>
            </div>
        </form>
        
    </div>

</body>
</html>