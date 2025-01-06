<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $enteredPassword = $_POST['password'];
    $rememberMe = isset($_POST['remember']);

    include('../config.php');

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedHashedPassword = $row['password'];
        $verificationStatus = $row['verification_status'];

        if ($verificationStatus == 0) {
            header('Location: verification.php?email=' . $row['email']);
            exit();
        }

        if (password_verify($enteredPassword, $storedHashedPassword)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];

            if ($rememberMe) {
                $token = bin2hex(random_bytes(16)); // Generate a secure token
                $expiryTime = time() + (86400 * 30); // cookie for 30 days
                setcookie('remember_me', $token, $expiryTime, "/", "", true, true); // Secure, HttpOnly flag

                // Store the token in the database
                $updateTokenSql = "UPDATE users SET remember_token = ? WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateTokenSql);
                $updateStmt->bind_param("si", $token, $row['user_id']);
                $updateStmt->execute();
                $updateStmt->close();
            }

            $stmt->close();
            $conn->close();
            header("Location: welcome.php");
            exit();
        } else {
            $error_message = "Invalid password"; // Set the error message
        }
    } else {
        $error_message = "Invalid email"; // Set the error message
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
            height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            max-width: 400px;
            margin-top: 20px;
            background: #fbfbfb;
            border: 1px solid #dddddd96;
            border-radius: 20px;
            padding: 5px;
        }

        .header a {
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            color: #007bff;
            width: 45%;
            text-align: center;
        }

        .header .signin {
            background: #ffffff;
            color: #000;
            border: 1px solid #dddddd96;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 12px 0;
        }

        .header .signup {
            color: #353535a9;
            padding: 12px 0;
        }

        .container {
            width: 90%;
            max-width: 400px;
            text-align: center;
            margin-top: 20px;
        }

        .title {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
            text-align: left;
        }

        .title p {
            font-size: 16px;
            font-weight: 400;
            color: #424040;
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
            /* background-color: #fbfbfb; */
            /* padding: 10px; */
            border-radius: 10px;
            /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); */
        }

        .form-group label {
            font-size: 14px;
            margin-bottom: 5px;
            margin-left: 10px;
            display: block;
            color: #555;
        }

        .form-group input {
            width: 90%;
            padding: 15px;
            font-size: 14px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            transition: border 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group input:focus {
            border-color: #007bff;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 25px;
            text-align: left;
        }

        .checkbox-container label {
            font-size: 16px;
            margin-left: 10px;
            color: #555;
        }

        .checkbox-container input {
            margin-left: 10px;
        }


        .submit-btn {
            width: 100%;
            padding: 12px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .submit-btn:hover {
            background: #0056b3;
            box-shadow: 0 4px 6px rgba(0, 123, 255, 0.4);
        }

        p {
            font-size: 14px;
            color: #555;
            text-align: left;
        }

        p a {
            color: #007bff;
            text-decoration: none;
        }

        p a:hover {
            color: #0056b3;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }

        .hr {
            border: 0.9px;
            height: 1px;
            background: #ddd;
            margin: 20px 0;
            width: 100%;
        }

        .error-message {
            color: red;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
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
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
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