<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = ''; // Initialize error message variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = isset($_POST['email']) ? $_POST['email'] : ''; // Check if email is provided, otherwise set as empty
    $phone = $_POST['phone']; // Added phone field
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Ensure role is set properly, default to 'user' if not set
    $role = isset($_POST['role']) ? $_POST['role'] : 'user'; // Default to 'user' if role is not provided

    include('../config.php');

    $token = bin2hex(random_bytes(16));

    // Check if the user already exists based on email or phone
    $sql = "SELECT * FROM users WHERE email = ? OR phone = ?";
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
            $_SESSION['user_id'] = $stmt->insert_id;
            $stmt->close();

            // Redirect to user dashboard or welcome page after successful registration
            header('Location: signin.php');
            exit();
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
    <link rel="stylesheet" href="signin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <input type="email" id="email" name="email" placeholder="Enter your email">
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