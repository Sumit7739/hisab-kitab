<?php
session_start();

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include '../config.php'; // Update the path as needed

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">User not logged in!</div>';
    echo '<script>setTimeout(function(){ window.location.href = "/index.html"; }, 3000);</script>'; // Redirect after 3 seconds
    exit();
}

$user_id = $_SESSION['user_id']; // Logged-in user's ID

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? null; // OTP entered by the user

    // Validate OTP input
    if (!$otp) {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">OTP is required!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
        exit();
    }

    // Query to fetch connection based on the OTP provided
    $fetchOtpQuery = "SELECT otp, otp_status, chat_id, connection_id, connection_status FROM connections WHERE otp = ?";
    $stmt = $conn->prepare($fetchOtpQuery);
    $stmt->bind_param("s", $otp); // Using 's' since OTP is a string
    $stmt->execute();
    $result = $stmt->get_result();
    $connectionData = $result->fetch_assoc();

    if (!$connectionData) {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Invalid OTP!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
        exit();
    }

    // Extract connection details
    $dbOtp = $connectionData['otp'];
    $chatId = $connectionData['chat_id'];
    $otpStatus = $connectionData['otp_status'];
    $connectionId = $connectionData['connection_id'];
    $connectionStatus = $connectionData['connection_status'];

    // Ensure OTP is valid and not already completed
    if ($otp !== $dbOtp) {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Invalid OTP!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
        exit();
    }

    // Ensure connection is not already completed
    if ($connectionStatus === 'completed') {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Connection already completed!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
        exit();
    }

    // Update the connection status, set OTP to null, and assign user_id_2
    $updateQuery = "UPDATE connections SET connection_status = 'completed', otp = NULL, otp_status = NULL, user_id_2 = ? WHERE connection_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $user_id, $connectionId);

    if ($stmt->execute()) {
        echo '<div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Connection successfully completed!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
    } else {
        echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Error updating connection: ' . $conn->error . '</div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
    }
    exit();
} else {
    echo '<div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; font-size: 42px; text-align: center; width: 50%; margin-left: auto; margin-right: auto;">Invalid request method!</div>';
    echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 3000);</script>'; // Redirect after 3 seconds
    exit();
}
?>
