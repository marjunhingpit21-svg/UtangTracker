
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
                <img src="Img/OrangeLogo.png" alt="Listahan Logo" class="logo">
            </div>
            <header class="header">Listahan</header>
            <header class="header1">Login Here!</header>

            <div class="loading" id="loading">
                <span class="spinner"></span>Verifying...
            </div>
            <div class="message" id="messageBox" style="display: none;">
                <?php if (!empty($message)): ?>
                    <p class="<?php echo strpos($message, 'errormsg') !== false ? 'errormsg' : 'successmsg'; ?>">
                        <?php echo strip_tags($message, '<div>'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <form action="" method="post" id="loginForm">
                <div class="field input">
                    <label for="Username"><i class="fa fa-user" style="font-size:32px;"></i></label>
                    <input type="text" name="Username" id="Username" placeholder="username" required>
                </div>
                <div class="field input">
                    <label for="Password"><i class="fa fa-unlock-alt" id="passwordIcon" style="font-size:32px;"></i></label>
                    <input type="password" name="Password" id="Password" placeholder="password" required>
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