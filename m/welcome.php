<?php
session_start(); // Start the session

if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header('Location: signin.php');
    exit();
}

// Include database connection
include('../config.php');

// Fetch user ID from session
$userID = $_SESSION['user_id'];

// Prepare SQL query to fetch user details
$sql = "SELECT name, role FROM users WHERE user_id = '$userID'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $role = $row['role']; // Fetch user role
} else {
    $name = "User"; // Default name
    $role = "user"; // Default role if not found
}

// Close the database connection
$conn->close();

// Determine redirection based on role
$redirectPage = (strtolower($role) === 'admin') ? '../d/dashboard.php' : '../d/index.php';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
            padding-top: 50px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333333;
            font-size: 28px;
            margin-bottom: 20px;
        }

        p {
            color: #555555;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Welcome <?php echo htmlspecialchars($name); ?></h1>
        <p>We are setting things up for you. Please wait a few seconds.</p>
        <div class="countdown" id="countdown">Redirecting...</div>
        <div class="loader"></div>
    </div>

    <script>
        // Countdown timer for redirection
        var countdownElement = document.getElementById('countdown');
        var countdown = 1;

        var timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = 'Redirecting in ' + countdown + ' seconds...';
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '<?php echo $redirectPage; ?>';
            }
        }, 1000);
    </script>
</body>

</html>