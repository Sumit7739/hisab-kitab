<?php
session_start();
include '../config.php';

$response = ['success' => false, 'message' => '', 'otp' => null, 'connection_status' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['connection_id']) && is_numeric($_GET['connection_id'])) {
        $connectionId = intval($_GET['connection_id']);

        try {
            $fetchOtpSQL = "SELECT otp, otp_status, connection_status FROM connections WHERE connection_id = ?";
            $stmt = $conn->prepare($fetchOtpSQL);
            $stmt->bind_param("i", $connectionId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $response['success'] = true;
                $response['otp'] = $row['otp'];
                $response['connection_status'] = $row['connection_status'];
            } else {
                $response['message'] = "No connection found for the given ID.";
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

header('Content-Type: application/json');
echo json_encode($response);
?>
