<?php
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emailOrPhone = $_POST['email_or_phone'];  // New input for email or phone
    $enteredPassword = $_POST['password'];
    $rememberMe = isset($_POST['remember']);  // Remember me checkbox

    include('../config.php');

    // Query to check both email and phone
    $sql = "SELECT * FROM users WHERE email = ? OR phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedHashedPassword = $row['password'];
        // $verificationStatus = $row['verification_status'];

        // // Redirect to verification page if not verified
        // if ($verificationStatus == 0) {
        //     header('Location: verification.php?email=' . $row['email']);
        //     exit();
        // }

        // Verify the password
        if (password_verify($enteredPassword, $storedHashedPassword)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];

            // Handle Remember Me functionality
            if ($rememberMe) {
                $token = bin2hex(random_bytes(16)); // Generate a secure token
                $expiryTime = time() + (86400 * 30); // cookie for 30 days
                setcookie('remember_me', $token, $expiryTime, "/", "", true, true); // Secure, HttpOnly flag

                // Update the token and expiry time in the database
                $updateTokenSql = "UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateTokenSql);
                $updateStmt->bind_param("sii", $token, $expiryTime, $row['user_id']);
                $updateStmt->execute();
                $updateStmt->close();
            }

            $stmt->close();
            $conn->close();
            header("Location: welcome.php");
            exit();
        } else {
            $error_message = "Invalid password"; // Invalid password error
        }
    } else {
        $error_message = "Invalid email or phone number"; // Invalid email or phone error
    }

    $stmt->close();
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hisab-Kitab</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="header">
        <a href="#" class="signin">Sign In</a>
        <a href="signup.php" class="signup">Sign Up</a>
    </div>
    <?php if (!empty($error_message)): ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="title">
            Welcome Back,
            <p>Good to see you again</p>
        </div>
        <hr class="hr">
        <form method="POST" action="signin.php">
            <div class="form-group">
                <!-- Input to accept either email or phone -->
                <input type="text" id="email_or_phone" name="email_or_phone" placeholder="Enter your email or phone" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="checkbox-container">
                <label for="remember">Remember Me</label>
                <input type="checkbox" id="remember" name="remember">
            </div>
            <p>Forgot your password? <a href="forgot.php">Forgot Password</a></p>
            <br>
            <button type="submit" class="submit-btn">Sign In</button>
        </form>

    </div>
</body>

</html>