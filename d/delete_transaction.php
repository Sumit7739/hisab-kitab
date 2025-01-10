<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Get the JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate transaction ID
if (!isset($data['transaction_id']) || !is_numeric($data['transaction_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
    exit();
}

$transactionId = $data['transaction_id'];
$userId = $_SESSION['user_id'];

// Check if the transaction belongs to the user
$checkSQL = "SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?";
$stmt = $conn->prepare($checkSQL);
$stmt->bind_param("ii", $transactionId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this transaction.']);
    exit();
}

// Delete the transaction
$deleteSQL = "DELETE FROM transactions WHERE transaction_id = ?";
$stmt = $conn->prepare($deleteSQL);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: Unable to prepare delete statement.']);
    exit();
}

$stmt->bind_param("i", $transactionId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete the transaction. Please try again later.']);
}
