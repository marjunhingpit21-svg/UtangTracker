<?php
session_start();

require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $user = $result->fetch_assoc()) {
            # login does not work. password is hashed in the db. fix this
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Account with that username does not exist';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="Css/login.css" rel="stylesheet">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Login</title>
</head>
<body>
    <div class="parent">
        <div class="description">
            <!-- <h1>Mabuhay!</h1> -->
            <!-- <p class="tagline"> 
                Ever lent money and forgot about it? Listahan helps you keep tabs on debts and payments so you always know who owes what.
            </p>
            <div class="divider"></div>
            <p class="contactinfo"> 
                Contacts here:
            </p> -->
        </div>  

        <div class="loginarea">
            <div class="logo-container">
                <img src="img/OrangeLogo.png" alt="Listahan Logo" class="logo">
            </div>
            <header class="header">Listahan</header>
            <header class="header1">Login Here!</header>

            <div class="loading" id="loading">
                <span class="spinner"></span>Verifying...
            </div>
            <div class="message" id="messageBox" style="<?php echo !empty($error) ? 'display: block;' : 'display: none;'; ?>">
                <?php if (!empty($error)): ?>
                    <p class="errormsg">
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <form action="" method="post" id="loginForm">
                <div class="field input">
                    <input type="text" name="username" id="Username" placeholder="username" required>
                </div>
                <div class="field input">
                    <input type="password" name="password" id="Password" placeholder="password" required>
                </div>
                <div class="button">
                    <input type="submit" class="btn" name="Submit" value="Login">
                </div>
                <div class="registrationlink">
                    <p class="registrationl">No account yet? <a href="register.php">Register Here</a></p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>