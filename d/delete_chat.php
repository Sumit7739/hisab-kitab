<?php
session_start();

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
include '../config.php'; // Adjust the path to your database configuration file

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if the chat_id is provided in the POST request
if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chat ID is required']);
    exit();
}

$chat_id = intval($_POST['chat_id']); // Sanitize the chat_id input

// Check if chat_id exists in the database
$checkQuery = "SELECT chat_id FROM chats WHERE chat_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Chat ID not found']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete related transactions
    $deleteTransactions = "DELETE FROM transactions WHERE chat_id = ?";
    $stmt = $conn->prepare($deleteTransactions);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();

    // Delete related connections
    $deleteConnections = "DELETE FROM connections WHERE chat_id = ?";
    $stmt = $conn->prepare($deleteConnections);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();

    // Delete the chat itself
    $deleteChats = "DELETE FROM chats WHERE chat_id = ?";
    $stmt = $conn->prepare($deleteChats);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Chat and related data deleted successfully']);
} catch (Exception $e) {
    // Roll back the transaction if something goes wrong
    $conn->rollback();

    echo json_encode(['success' => false, 'message' => 'Failed to delete chat: ' . $e->getMessage()]);
}

exit();
