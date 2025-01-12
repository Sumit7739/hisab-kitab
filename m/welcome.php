<?php
session_start(); // Start the session

if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page or any other appropriate page
    header('Location: signin.php');
    exit();
}

// Include your database connection configuration
include('../config.php');

// Fetch the user's ID from the session
$userID = $_SESSION['user_id'];

// Prepare SQL query to fetch the user's name based on their ID
$sql = "SELECT name FROM users WHERE user_id = '$userID'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = $row['name'];
} else {
    $name = "User"; // Default name if user's name is not found
}

// Close the database connection
$conn->close();
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
        <h1>Welcome <?php echo $name; ?></h1>
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
                window.location.href = '../d/index.php';
            }
        }, 1000);
    </script>
</body>

</html>