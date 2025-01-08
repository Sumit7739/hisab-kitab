<?php
session_start();
include '../config.php';

// Initialize the response array
$response = ['success' => false, 'message' => '', 'otp' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the connection_id
    if (isset($_POST['connection_id']) && is_numeric($_POST['connection_id'])) {
        $connectionId = intval($_POST['connection_id']);
        $otp = random_int(100000, 999999); // Generate a 6-digit OTP

        try {
            // Insert or update the OTP in the database
            $insertOtpSQL = "
                INSERT INTO connections (connection_id, otp, otp_status)
                VALUES (?, ?, 'otp_generated')
                ON DUPLICATE KEY UPDATE
                otp = VALUES(otp),
                otp_status = VALUES(otp_status)
            ";

            $stmt = $conn->prepare($insertOtpSQL);
            $stmt->bind_param("ii", $connectionId, $otp);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['otp'] = $otp;
            } else {
                $response['message'] = "Failed to insert or update OTP.";
            }
        } catch (Exception $e) {
            $response['message'] = "Error: " . $e->getMessage();
        }
    } else {
        $response['message'] = "Invalid connection ID.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
