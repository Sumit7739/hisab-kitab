<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$error_message = ''; // Initialize error message variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // Added phone field
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Ensure role is set properly, default to 'user' if not set
    $role = isset($_POST['role']) ? $_POST['role'] : 'user'; // Default to 'user' if role is not provided

    include('../config.php');

    $token = bin2hex(random_bytes(16));
    // Check if the user already exists
    $sql = "SELECT * FROM users WHERE email = ? OR phone = ?"; // Check by email or phone
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User already exists
        $error_message = "User already exists with this email or phone number";
    } else {
        // Insert the new user into the database with role and access
        $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
        $stmt->execute();

        // Check if the user was successfully inserted
        if ($stmt->affected_rows > 0) {
            // User created successfully
            $_SESSION['id'] = $stmt->insert_id;
            $stmt->close();

            // Function to generate a random 6-digit OTP
            function generateOTP()
            {
                $otp = "";
                for ($i = 0; $i < 4; $i++) {
                    $otp .= mt_rand(0, 9);
                }
                return $otp;
            }

            // Retrieve the recipient email from the form
            $recipientEmail = $_POST['email'];

            // Generate OTP
            $otp = generateOTP();

            // Initialize PHPMailer
            $mail = new PHPMailer();

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->Username = 'srisinhasumit10@gmail.com'; // Your Gmail email address
            $mail->Password = 'ggtbuofjfdmqcohr'; // Your Gmail password

            // Sender and recipient
            $mail->setFrom('hisabkitab@mail.com', 'Hisab-Kitab'); // Sender email and name
            $mail->addAddress($recipientEmail); // Recipient email

            // Save the OTP in the database
            $sql = "UPDATE users SET otp = ? WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $otp, $recipientEmail);

            if ($stmt->execute()) {
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Hisab-Kitab Account'; // Personalized subject line

                $body = "
                <html>
                <head>
                  <title>Verify Your Hisab-Kitab Account</title>
                </head>
                <body>
                  <p>Hi there,</p>
                  <p>Thank you for creating an account with Hisab-Kitab! To complete your registration and ensure the security of your account, please verify your email address using the following One-Time Password (OTP):</p>
                  <p style='font-weight: bold;'>" . $otp . "</p>
                  <p>This code is valid for 5 minutes. Please enter it in the designated field on our website to complete your registration.</p>
                  <p>If you didn't request this verification, please ignore this email. Your account remains secure.</p>
                  <p>Thanks,<br />The Hisab-Kitab Team</p>
                </body>
                </html>";

                $mail->Body = $body;

                if ($mail->send()) {
                    // Redirect to OTP verification page
                    header('Location: verification.php?email=' . $recipientEmail);
                    exit();
                } else {
                    $error_message = 'Error sending email: ' . $mail->ErrorInfo;
                }
            } else {
                $error_message = 'Error updating OTP: ' . $stmt->error;
            }

            $stmt->close();
        } else {
            $error_message = "Failed to create user";
        }
    }

    $stmt->close(); // Close the statement
    $conn->close(); // Close the database connection
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
            color: #353535a9;
            padding: 12px 0;
        }

        .header .signup {
            background: #ffffff;
            color: #000;
            border: 1px solid #dddddd96;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 12px 0;
        }

        .container {
            width: 90%;
            max-width: 400px;
            text-align: center;
            /* margin-top: 20px; */
        }

        .title {
            font-size: 20px;
            font-weight: 500;
            /* margin-bottom: 10px; */
            text-align: left;
        }

        .title p {
            font-size: 16px;
            font-weight: 400;
            color: #424040;
            /* margin-bottom: 20px; */
            text-align: left;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
            /* background-color: #fbfbfb; */
            /* padding: 10px; */
            /* border-radius: 10px; */
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
        <a href="signin.php" class="signin">Sign In</a>
        <a href="signup.php" class="signup">Sign Up</a>
    </div>
    <?php if (!empty($error_message)): ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    <div class="title">
        <h2>Create Your Account</h2>
        <p>Sign up to get started</p>
    </div>
    <div class="container">
        <form id="signup-form" action="signup.php" method="POST">
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Enter your name" required>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Enter your password" required maxlength="16">
            </div>
            <div class="form-group">
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required maxlength="16">
            </div>
            <button type="submit" class="submit-btn">Sign Up</button>
        </form>
        <div class="footer">
            <p>By signing up, you agree to our <a href="termsofservice.html">terms of service</a> and <a href="privacypolicy.html">privacy policy</a>.</p>
        </div>
    </div>

    <script>
        // Function to validate password match in real-time
        document.getElementById('password').addEventListener('input', validatePasswords);
        document.getElementById('confirm-password').addEventListener('input', validatePasswords);

        function validatePasswords() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm-password").value;
            var submitButton = document.querySelector('.submit-btn');

            if (password !== confirmPassword) {
                // Disable the submit button if passwords don't match
                submitButton.disabled = true;
                submitButton.style.backgroundColor = "#ccc";
                submitButton.style.cursor = "not-allowed";
            } else {
                // Enable the submit button if passwords match
                submitButton.disabled = false;
                submitButton.style.backgroundColor = "#007bff";
                submitButton.style.cursor = "pointer";
            }
        }

        // Prevent form submission if passwords don't match
        document.getElementById('signup-form').addEventListener('submit', function(event) {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm-password").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                event.preventDefault(); // Prevent form submission
            }
        });
    </script>
</body>

</html>