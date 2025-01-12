<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Include database connection
    include('../config.php');

    // Remove the remember_me token from the database
    $sql = "UPDATE users SET remember_token = NULL WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Check if the update was successful
    if ($stmt->affected_rows > 0) {
        // Optionally, you can log out the user, e.g., destroying cookies, etc.
    }

    $stmt->close();
    $conn->close();
}

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../index.html");
exit();
?>
