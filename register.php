
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="Css/register.css" rel="stylesheet">
    <title>Sign Up</title>
</head>
<body>
    <div class="parent">
        <div class="description">
            <!-- <h1>Mabuhay!</h1>
            <p class="tagline"> 
                Ever lent money and forgot about it? Listahan helps you keep tabs on debts and payments so you always know who owes what.
            </p>
            <div class="divider"></div>
            <p class="contactinfo"> 
                Contacts here:
            </p>  -->
        </div>  

        <div class="loginarea">
             <div class="logo-container">
                <img src="Img/OrangeLogo.png" alt="Listahan Logo" class="logo">
            </div>
            <header class="header">Listahan</header>
            <header class="header1">Sign Up Here!</header>
            <form action="register-process.php" method="post" id="registerForm">
                <div class="loading" id="loading">
                    <span class="spinner"></span>Verifying...
                </div>
                <div class="message" id="messageBox" aria-live="polite"></div>
                <div class="field input">
                    <input type="text" name="Username" id="Username" placeholder="Username" required>
                </div>
                
                <div class="field input">
                    <input type="email" name="Email" id="Email" placeholder="Email Address" required>
                </div>
                <div class="field input">
                    <input type="password" name="Password" id="Password" placeholder="Password" required>
                </div>
                <div class="field input">
                    <input type="password" name="ConfirmPassword" id="ConfirmPassword" placeholder="Confirm Password" required>
                </div>

                <div class="button">
                    <input type="submit" class="btn" name="Submit" value="Sign Up">
                </div>
                <div class="loginlink">
                    <p class="registrationl">Already have an account? <a href="login.php">Login Here</a></p>
                </div>
            </form>
        </div>
    </div>

    
</body>
</html>