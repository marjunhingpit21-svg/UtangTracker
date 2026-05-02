<?php
session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';

    if (!empty($username) && !empty($password) && !empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long';
            } elseif ($password !== $_POST['confirm_password']) {
                $error = 'Passwords do not match';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $created_at = date('Y-m-d H:i:s');
                $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("ssss", $username, $email, $hashedPassword, $created_at);
                if ($insertStmt->execute()) {
                    header("Location: login.php?message=Account created successfully. Please log in.");
                    exit();
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please enter all required fields';
    }
}


?>
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
           
            <form action="register.php" method="post" id="registerForm">
                <div class="loading" id="loading">
                    <span class="spinner"></span>Verifying...
                </div>
                <div class="message" id="messageBox" style="<?php echo !empty($error) ? 'display: block;' : 'display: none;'; ?>" aria-live="polite">
                    <?php if (!empty($error)): ?>
                        <p class="errormsg"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </div>
                <div class="field input">
                    <label for="Username">Username</label>
                    <input type="text" name="username" id="Username" required>
                </div>
                
                <div class="field input">
                    <label for="Email">Email Address</label>
                    <input type="email" name="email" id="Email" required>
                </div>
                <div class="field input">
                    <label for="Password">Password</label>
                    <input type="password" name="password" id="Password" placeholder="Password must be at least 8 characters long" required>
                </div>
                <div class="field input">
                    <label for="ConfirmPassword">Confirm Password</label>
                    <input type="password" name="confirm_password" id="ConfirmPassword" required>
                </div>
                

                <!-- Modal for Terms and Policy -->   
                <div id="termsModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
                    <div class="modal-content" style="text-align:justify; background-color:white; margin:5% auto; padding:20px; width:55%; max-width:760px; border-radius:20px; max-height:80vh; overflow-y:auto;">
                        <span class="close" onclick="closeTermsModal()" style="float:right; font-size:24px; cursor:pointer;">&times;</span>
                        <h2>Terms and Policy <span style="color:#FB7806;">(Mga Palatuntunin at Patakaran)</span></h2>
                        <p>
                            <strong>Terms of Service</strong><br>
                            By using Listahan, you agree to use the service responsibly and in compliance with all applicable laws. You are responsible for maintaining the confidentiality of your account and password.<br><br>
                            <strong style="color:#FB7806;">Tuntunin ng Serbisyo</strong><br>
                            <p1 style="color:#FB7806;">Sa paggamit mo ng Listahan, ikaw ay sumang-ayun na gamitin ito sa tama at sumusunod ka sa batas. Sa inyo na ang responsibilidad sa pagpapanatiling pribado ng iyong account at password.</p1><br><br>
                            <strong>Privacy Policy</strong><br>
                            We collect personal information such as your username, email address, and other details provided during registration to provide and improve our services. Your data is stored securely and will not be shared with third parties without your consent, except as required by law.<br><br>
                            <strong style="color:#FB7806;">Patakaran sa Pribado</strong><br>
                            <p2 style="color:#FB7806;">Komokulikta kami ng personal na impormasyon tulad ng iyong username, email address, at iba pang detalye na iyong ibinigay sa pag rehistro mo upang mapaganda namin ang aming serbisyo. Ang iyong detalye ay naka-imbak nang ligtas at hindi ito ipapabahagi sa iba pa na walang pahintulot, maliban kung kinakailangan ito ng batas.</p2><br><br>
                            <strong>Usage</strong><br>
                            You agree not to misuse the service, including but not limited to attempting to access unauthorized data or disrupting the service. Listahan reserves the right to terminate accounts for violations of these terms.<br><br>
                            <strong style="color:#FB7806;">Paggamit</strong><br>
                            <p3 style="color:#FB7806;">Ikaw ay sumang-ayun na hindi mo ito gagamitin sa kamalian. Ang Listahan ay may karapatang mag terminado ng mga account kung ang gumagamit nito ay hindi sumusunod sa mga patakaran.</p3><br><br>
                            Please review the full terms and conditions or contact support for more details.<br><br>
                            <span style="color:#FB7806;">Paki-usap po namin na basahin ng maigi ang tuntunin at kondisyon o maari niyong kontakin ang aming team para sa mga karagdagan na detalye.</span>
                        </p>
                        <button onclick="closeTermsModal()" style="padding:10px; background: linear-gradient(90deg, rgba(202, 68, 16, 1) 36%, rgba(251, 120, 6, 1) 71%); color:white; border:none; border-radius:5px; cursor:pointer;">Close</button>
                    </div>
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