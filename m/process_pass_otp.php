<?php
include('../config.php');

// Start with checking if email and OTP are passed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['otp'])) {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Validate the OTP from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // OTP is valid; allow password update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Check if passwords match
            if ($new_password === $confirm_password) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $update_stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL WHERE email = ?");
                $update_stmt->bind_param("ss", $hashed_password, $email);

                if ($update_stmt->execute()) {
                    $message = "Password updated successfully.";
                    header("Location: success.html");
                    // header("sleep:3;url=signin.php");
                    exit();
                } else {
                    $error = "Failed to update password. Please try again.";
                }

                $update_stmt->close();
            } else {
                $error = "Passwords do not match.";
            }
        }
    } else {
        $error = "Invalid OTP.";
        $invalidOtp = true;  // Flag to indicate invalid OTP
    }

    $stmt->close();
} else {
    $error = "Invalid request.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

        .container {
            width: 90%;
            max-width: 400px;
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            background-color: #fbfbfb;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-control {
            width: 96%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
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

        .error {
            color: #dc3545;
            margin-bottom: 10px;
        }

        .success {
            color: #28a745;
            margin-bottom: 10px;
        }

        .toggle-password {
            cursor: pointer;
            font-size: 14px;
            color: #007bff;
            float: right;
        }

        .toggle-password:hover {
            text-decoration: underline;
        }

        .password-check {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }

        .password-check.mismatch {
            color: #dc3545;
        }

        .password-check.match {
            color: #28a745;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="title">Update Password</div>

        <?php if (isset($error)) { ?>
            <div class="error"> <?php echo $error; ?> </div>
        <?php } ?>

        <?php if (isset($message)) { ?>
            <div class="success"> <?php echo $message; ?> </div>
        <?php } ?>

        <!-- Only show the form if the OTP is valid -->
        <?php if (!isset($invalidOtp)): ?>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp); ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <span class="toggle-password" onclick="togglePassword('new_password')">Show</span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">Show</span>
                    <div id="password-check" class="password-check">Passwords do not match</div>
                </div>

                <button type="submit" class="submit-btn">Update Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Password toggle visibility function
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
            } else {
                field.type = "password";
            }
        }

        // Handle OTP invalidation
        <?php if (isset($invalidOtp) && $invalidOtp): ?>
            // Hide the form after 3 seconds and redirect
            setTimeout(function() {
                document.querySelector('.container').innerHTML = '<h3>Invalid OTP. You will be redirected shortly...</h3>';
                setTimeout(function() {
                    window.location.href = 'passverification.php?email=<?php echo urlencode($email); ?>';
                }, 1000);
            }, 1500);
        <?php endif; ?>

        // Password match check
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordCheck = document.getElementById('password-check');

        confirmPassword.addEventListener('input', () => {
            if (newPassword.value === confirmPassword.value) {
                passwordCheck.textContent = "Passwords match";
                passwordCheck.classList.remove('mismatch');
                passwordCheck.classList.add('match');
            } else {
                passwordCheck.textContent = "Passwords do not match";
                passwordCheck.classList.remove('match');
                passwordCheck.classList.add('mismatch');
            }
        });
    </script>
</body>

</html>