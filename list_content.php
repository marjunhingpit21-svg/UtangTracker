<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Task</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"/>
    <link href="Css/new_list.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Add List Contents</h1>
        <div id="messageModal" class="modal">
            <div class="modal-content" id="modalContent">
                <span id="closeModal" class="close-modal">&times;</span>
                <p id="modalMessage"></p>
                <div id="countdown" class="countdown" style="display:none;"></div>
            </div>
        </div>

        <form id="taskForm">
            <div class="form-group">
                <label for="title">Content Title *</label>
                <input type="text" id="title" name="title" placeholder="Enter content title" required>
            </div>

            <div class="form-group">
                <label for="money_owed">Money Owed *</label>
                <input type="number" id="money_owed" name="money_owed" placeholder="Enter amount owed" required step="0.01" min="0">
            </div>

            <div class="form-group">
                <label>Deadline</label>
                
                <div style="margin-bottom: 10px; display: flex; gap: 20px; flex-wrap: wrap;">
                    <label style="cursor: pointer; font-weight: normal;">
                        <input type="radio" name="deadline_option" value="set" checked>
                        Set a deadline
                    </label>
                    <label style="cursor: pointer; font-weight: normal;">
                        <input type="radio" name="deadline_option" value="none">
                        <strong>No deadline</strong>
                    </label>
                </div>

                <input type="datetime-local" 
                    id="deadline" 
                    name="deadline"
                    required 
                    min="<?php echo date('Y-m-d\TH:i'); ?>"
                    style="display: block;">
            </div>

            <button type="button" id="add-subtask">+ Add More Contents</button>

            <div class="button-group">
                <button type="button" class="btn-cancel" onclick="window.history.back()">Back</button>
                <button type="submit" class="btn-submit">Create List</button>
            </div>
        </form>
        
    </div>
</body>
</html>