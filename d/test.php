<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$client_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch chats for the logged-in user (pre-fetched)
$stmt = $conn->prepare("SELECT chat_id, chat_name FROM chats WHERE creator_user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chatsResult = $stmt->get_result();
$chats = [];
while ($row = $chatsResult->fetch_assoc()) {
    $chats[] = $row;
}
$stmt->close();

// Handle chat linking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_chat'])) {
    $chat_id = $_POST['chat_id'];
    $checkStmt = $conn->prepare("SELECT linked_chat_id FROM clients WHERE id = ?");
    $checkStmt->bind_param("i", $client_id);
    $checkStmt->execute();
    $existingChat = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existingChat['linked_chat_id'] !== null) {
        $error_message = "This client already has a linked chat.";
    } else {
        $stmt = $conn->prepare("UPDATE clients SET linked_chat_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $chat_id, $client_id);
        if ($stmt->execute()) {
            $success_message = "Chat linked successfully!";
        } else {
            $error_message = "Error linking chat: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch client data
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate transaction amount
$transaction_amount = 0;
if ($client['linked_chat_id']) {
    $stmt = $conn->prepare("
        SELECT transaction_type, amount 
        FROM transactions 
        WHERE chat_id = ?
    ");
    $stmt->bind_param("i", $client['linked_chat_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transaction_amount += ($row['transaction_type'] === 'credit') ? $row['amount'] : -$row['amount'];
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="clientprofile.css">
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            margin: 10px 0;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .modal-content button {
            background: var(--primary);
            color: white;
            cursor: pointer;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo">
                <p>Hisab Kitab</p>
            </div>
            <div class="hamburger" id="hamburger">
                <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
            </div>
        </nav>
    </header>

    <div id="message" class="message <?= isset($success_message) ? 'success show' : (isset($error_message) ? 'error show' : '') ?>">
        <?= isset($success_message) ? $success_message : (isset($error_message) ? $error_message : '') ?>
    </div>

    <div class="profile-container">
        <form id="profileForm" method="POST">
            <div class="profile-header">
                <h2>Client Profile</h2>
                <button id="linkChatBtn" <?= $client['linked_chat_id'] ? 'disabled' : '' ?>>
                    <i class="fa fa-link"></i> LINK
                </button>
            </div>
            <div class="profile-info">
                <!-- Name -->
                <div class="info-field">
                    <label>Name:</label>
                    <span><?= htmlspecialchars($client['name'] ?? '') ?></span>
                    <input type="text" name="name" value="<?= htmlspecialchars($client['name'] ?? '') ?>" required>
                </div>
                <!-- Transaction Amount (Read-Only) -->
                <div class="info-field">
                    <label>Transaction Amount:</label>
                    <span><?= number_format($transaction_amount, 2) ?></span>
                </div>
                <!-- Other fields (policy number, phone, etc.) go here -->
            </div>
            <div class="buttons">
                <button type="button" class="edit-btn">Edit</button>
                <button type="submit" class="save-btn">Save</button>
                <button type="button" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Modal for Chat Selection -->
    <div class="modal" id="chatModal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2>Select Chat</h2>
            <input type="text" id="searchChats" placeholder="Search chats..." />
            <select id="chatSelect" size="5"></select>
            <button id="confirmChat">Confirm</button>
        </div>
    </div>

</body>

</html>