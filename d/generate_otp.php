<?php
session_start();
include '../config.php';

// Initialize the response array
$response = ['success' => false, 'message' => '', 'otp' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the connection_id
    if (isset($_POST['connection_id']) && is_numeric($_POST['connection_id'])) {
        $connectionId = intval($_POST['connection_id']);

        try {
            // Fetch the associated chat_id (or other identifier)
            $chatQuery = "SELECT chat_id FROM connections WHERE connection_id = ?";
            $chatStmt = $conn->prepare($chatQuery);
            $chatStmt->bind_param("i", $connectionId);
            $chatStmt->execute();
            $chatResult = $chatStmt->get_result();

            if ($chatResult->num_rows > 0) {
                $chatData = $chatResult->fetch_assoc();
                $chatId = $chatData['chat_id'];

                // Generate an 8-digit OTP using a combination of chat_id and random number
                $shortId = substr(md5($chatId), 0, 4); // Shortened identifier (first 4 characters of hash)
                $randomOtp = random_int(1000, 9999);  // Generate a 4-digit random number
                $otp = $shortId . $randomOtp;         // Combine short_id and random OTP (8 digits total)

                // Insert or update the OTP in the database
                $insertOtpSQL = "
                    INSERT INTO connections (connection_id, otp, otp_status)
                    VALUES (?, ?, 'OTP Generated')
                    ON DUPLICATE KEY UPDATE
                    otp = VALUES(otp),
                    otp_status = VALUES(otp_status)
                ";

                $stmt = $conn->prepare($insertOtpSQL);
                $stmt->bind_param("is", $connectionId, $otp);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['otp'] = $otp;
                } else {
                    $response['message'] = "Failed to insert or update OTP.";
                }
            } else {
                $response['message'] = "Chat ID not found for the given connection ID.";
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
