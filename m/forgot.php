<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userEmail = $_POST['email']; // Retrieve the entered email

    include('../config.php');

    // Check if the email is present in the database and has verification status 1
    $sql = "SELECT * FROM users WHERE email = ? AND verification_status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // User email found and verification status is 1

        // Generate a random 6-digit OTP
        $otp = mt_rand(100000, 999999);

        // Update the database with the new OTP
        $updateSql = "UPDATE users SET otp = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $otp, $userEmail);
        if ($updateStmt->execute()) {
            // OTP updated successfully

            // Send the OTP to the entered email
            $mail = new PHPMailer();

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->Username = 'srisinhasumit10@gmail.com'; // Your Gmail email address
            $mail->Password = 'ggtbuofjfdmqcohr'; // Your Gmail password

            $mail->setFrom('no-reply@hisab-kitab.com', 'Hisab-Kitab'); // Sender email and name
            $mail->addAddress($userEmail); // Recipient's email

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Hisab-Kitab Account Password'; // Subject line

            $body = "
<html>
<head>
  <title>Reset Your Hisab-Kitab Account Password</title>
</head>
<body>
  <p>Hi,</p>
  <p>We received a request to reset your password for your Hisab-Kitab account. Use the following One-Time Password (OTP) to reset your password:</p>
  <p style='font-weight: bold; font-size: 1.2em;'>" . $otp . "</p>
  <p>This OTP is valid for 5 minutes.</p>
  <p>If you did not request this, you can safely ignore this email. No changes will be made to your account.</p>
  <p>Thank you,<br />The Hisab-Kitab Team</p>
</body>
</html>";

            $mail->Body = $body;

            if ($mail->send()) {
                // OTP sent successfully
                $_SESSION['email'] = $userEmail; // Store the email in the session for further verification
                header('Location: passverification.php?email=' . $userEmail); // Redirect to OTP verification page
                exit();
            } else {
                $error = 'Error sending email: ' . $mail->ErrorInfo;
            }
        } else {
            $error = 'Error updating OTP: ' . $conn->error;
        }

        // Close the update statement
        $updateStmt->close();
    } else {
        // User not found or verification status is not 1
        $error = "Email not verified or user does not exist.";
    }

    // Close database connections and statements
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
            height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            display: flex;
            justify-content: center;
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
            width: 100%;
            text-align: center;
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
            /* text-align: left; */
            /* background-color: #fbfbfb; */
            /* padding: 10px; */
            /* border-radius: 10px; */
            /* box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); */
        }

        /* .form-group label {
            font-size: 14px;
            margin-bottom: 5px;
            margin-left: 10px;
            display: block;
            color: #555;
        } */

        .form-group input {
            width: 90%;
            padding: 15px;
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 10px;
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
            position: absolute;
            bottom: 0;
            margin-bottom: 10px;
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

        .error-msg {
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
        <a href="#" class="signin">Forgot Password</a>
    </div>
    <?php if (isset($error)) : ?>
        <p class="error-msg">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>
    <div class="container">
        <div class="title">
            Reset your password
            <p>Enter your email address to receive a 6-digit OTP for password reset.</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <!-- <label for="email">Email Address</label> -->
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <button type="submit" class="submit-btn">Send OTP</button>
        </form>

        <p><a href="signin.php">Back to Sign In</a></p>
    </div>

    <div class="footer">
        &copy; 2025 Hisab-Kitab. All Rights Reserved.
    </div>
</body>

</html>