<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

// Validate chat_id
if (!isset($_GET['chat_id']) || !is_numeric($_GET['chat_id'])) {
    die("Invalid chat ID.");
}

$chatId = $_GET['chat_id'];
$userId = $_SESSION['user_id'];
$errorMessage = "";

// Fetch the logged-in user's phone number
$getUserPhoneSQL = "SELECT phone FROM users WHERE user_id = ?";
$stmt = $conn->prepare($getUserPhoneSQL);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows == 0) {
    die("User not found.");
}

$userData = $userResult->fetch_assoc();
$loggedInUserPhone = $userData['phone'];

// Fetch chat details
$fetchChatSQL = "SELECT creator_user_id, phone_number, chat_name, email FROM chats WHERE chat_id = ?";
$stmt = $conn->prepare($fetchChatSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$chatResult = $stmt->get_result();

if ($chatResult->num_rows == 0) {
    die("Chat not found.");
}

$chatData = $chatResult->fetch_assoc();
$creatorUserId = $chatData['creator_user_id'];
$chatPhoneNumber = $chatData['phone_number'];
$chatName = $chatData['chat_name'];
$customerEmail = $chatData['email'];

// Check if phone numbers match and update the connections table
if ($chatPhoneNumber == $loggedInUserPhone) {
    // Update the connection table if phone numbers match and set connection status to 'completed'
    $updateConnectionSQL = "UPDATE connections SET user_id_2 = ?, connection_status = 'completed' WHERE chat_id = ? AND user_id_1 = ?";
    $stmt = $conn->prepare($updateConnectionSQL);
    $stmt->bind_param("iii", $userId, $chatId, $creatorUserId);
    if ($stmt->execute()) {
        echo "Connection updated and status set to 'completed'.";
    } else {
        echo "Failed to update connection.";
    }
} else {
    // Handle the case where phone numbers do not match
    // echo "Phone number mismatch, unable to update connection.";
}

// Fetch the connection details to check if the user has permissions
$checkConnectionSQL = "SELECT * FROM connections WHERE chat_id = ? AND (user_id_1 = ? OR user_id_2 = ?) AND connection_status = 'completed'";
$stmt = $conn->prepare($checkConnectionSQL);
$stmt->bind_param("iii", $chatId, $userId, $userId);
$stmt->execute();
$connectionResult = $stmt->get_result();

if ($connectionResult->num_rows == 0 && $creatorUserId != $userId && $chatPhoneNumber != $loggedInUserPhone) {
    die("You are not authorized to access this chat. Please ensure you are connected, are the creator, or have a matching phone number.");
}

// Fetch connected user's details from the connections table
$fetchConnectedUserSQL = "
    SELECT u.user_id, u.name, u.phone, c.permission
    FROM connections c
    JOIN users u ON c.user_id_2 = u.user_id
    WHERE c.chat_id = ? AND c.connection_status = 'completed'";
$stmt = $conn->prepare($fetchConnectedUserSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$connectedUserResult = $stmt->get_result();

$connectedUserData = [];
$canAddTransaction = false;
if ($connectedUserResult->num_rows > 0) {
    $connectedUserData = $connectedUserResult->fetch_assoc();
    $canAddTransaction = ($connectedUserData['permission'] == 1 && ($userId == $connectedUserData['user_id'] || $userId == $creatorUserId));
} else {
    $connectedUserData = ['name' => '', 'phone_number' => 'N/A'];
}

// Fetch all transactions for this chat
$fetchTransactionsSQL = "SELECT * FROM transactions WHERE chat_id = ? ORDER BY transaction_date ASC";
$stmt = $conn->prepare($fetchTransactionsSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$transactionsResult = $stmt->get_result();

// Initialize balance variables
$totalDebit = 0;
$totalCredit = 0;
$balance = 0;

// Handle new transaction submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transactionType = $_POST['transactionType'];
    $amount = $_POST['amount'];
    $description = isset($_POST['description']) ? $_POST['description'] : "";
    $paymentDate = isset($_POST['payment_date']) && !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

    if (empty($amount) || !is_numeric($amount)) {
        $errorMessage = "Please enter a valid amount.";
    } elseif (!preg_match('/\d{4}-\d{2}-\d{2}/', $paymentDate)) {
        $errorMessage = "Invalid date format. Please select a valid date.";
    } else {
        try {
            $insertTransactionSQL = "INSERT INTO transactions (chat_id, user_id, transaction_type, amount, description, transaction_date, is_read, payment_date) 
                                     VALUES (?, ?, ?, ?, ?, NOW(), 0, ?)";
            $stmt = $conn->prepare($insertTransactionSQL);
            $stmt->bind_param("iisdss", $chatId, $userId, $transactionType, $amount, $description, $paymentDate);
            $stmt->execute();

            header("Location: chat_page.php?chat_id=$chatId");
            exit();
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
}

// Prepare transaction data for output
$transactionList = [];
if ($transactionsResult->num_rows > 0) {
    while ($transaction = $transactionsResult->fetch_assoc()) {
        if ($transaction['transaction_type'] == 'debit') {
            $totalDebit += (float) $transaction['amount'];
            $balance -= (float) $transaction['amount'];
        } else if ($transaction['transaction_type'] == 'credit') {
            $totalCredit += (float) $transaction['amount'];
            $balance += (float) $transaction['amount'];
        }

        $transactionList[] = [
            'transaction_id' => $transaction['transaction_id'],
            'description' => htmlspecialchars($transaction['description']),
            'payment_date' => htmlspecialchars($transaction['payment_date'] ?? $transaction['transaction_date']),
            'amount' => htmlspecialchars($transaction['amount']),
            'transaction_type' => $transaction['transaction_type'],
            'balance' => number_format($balance, 2)
        ];
    }
} else {
    $transactionList[] = ['error' => 'No transactions found. Add a new transaction.'];
}

$transactionList = array_reverse($transactionList);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hisab</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
    <link rel="stylesheet" href="chatpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Sync theme on load
        if (localStorage.getItem("theme") === "dark") {
            document.body.classList.add("dark-theme");
        }
    </script>
    <style>
        body {
            font-family: Poppins, sans-serif;
            margin: 0;
            padding: 0;
            background: rgba(255, 255, 255, 0.77);
        }

        .transaction {
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 185px;
            /* Limits the height to 80% of the viewport */
            overflow-y: auto;
            overflow-x: hidden;
            /* Enables vertical scrolling if content exceeds max-height */
            padding-right: 10px;
            /* Adds some padding to the right for scrollbar spacing */
            scrollbar-width: thin;
            /* Adjust scrollbar width for modern browsers */
            scrollbar-color: rgb(212, 18, 18);
            /* Customize scrollbar colors */
        }


        .transaction .box {
            width: 100%;
            /* background-color: #fff; */
            /* White background for transaction boxes */
            padding: 6px 10px;
            border-radius: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
            /* Subtle shadow for better visibility */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3px;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .hold-to-delete {
            /* position: absolute;
            top: 50%;
            left: 50%; */
            /* transform: translate(-50%, -50%); */
            padding: 10px;
            /* background-color: #f44336; */
            color: white;
            border-radius: 5px;
            cursor: pointer;
            opacity: 0;
            /* Hidden by default */
            transition: opacity 0.3s ease;
        }

        .hold-to-delete:hover {
            color: #e53935;
        }

        /* Show the "hold-to-delete" section after holding */
        .box:active .hold-to-delete {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        button {
            padding: 10px 20px;
            margin: 10px;
            cursor: pointer;
        }

        /* Prevent text selection */
        .hold-to-delete {
            user-select: none;
            /* Prevent text selection */
            -webkit-user-select: none;
            /* For Safari */
            -moz-user-select: none;
            /* For Firefox */
            -ms-user-select: none;
            /* For older IE/Edge */
        }

        /* Optional: To disable any drag actions */
        .hold-to-delete {
            -webkit-user-drag: none;
            /* Prevent drag */
            /* For modern browsers */
        }

        /* Disable text selection for the modal */
        #confirmationModal,
        #confirmationModal .modal-content {
            user-select: none;
            /* Standard for most browsers */
            -webkit-user-select: none;
            /* For Safari */
            -moz-user-select: none;
            /* For Firefox */
            -ms-user-select: none;
            /* For IE/Edge */
        }

        /* Buttons for the transactions */
        .addTransactions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .addTransactions button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .addTransactions button#debit-button {
            background-color: #f44336;
            /* Red for debit */
            color: white;
        }

        .addTransactions button#credit-button {
            background-color: #4CAF50;
            /* Green for credit */
            color: white;
        }

        .addTransactions button:hover {
            opacity: 0.9;
        }

        /* Bottom Buttons */
        .addTransactions {
            position: fixed;
            top: 70px;
            width: 100%;
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            background-color: #fff;
            padding: 5px 0;
        }

        .addTransactions button {
            width: 45%;
            padding: 15px 20px;
            /* background-color: #f4b400; */
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .addTransactions button.debits {
            background-color: #ff00009a;
        }

        .addTransactions button.credits {
            background-color: #0373109a;
        }

        /* Modal background */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Fixed position to cover the screen */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* Semi-transparent background */
            z-index: 9999;
            /* Ensure the modal is above other content */
            align-items: center;
            justify-content: center;
            overflow: auto;
            /* In case the content overflows */
        }


        /* Modal content */
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 300px;
            /* Fixed width for the modal */
            max-width: 90%;
            /* Responsive width */
            margin: 0 auto;
        }

        body.dark-theme .modal {
            background-color: #1a1a1a;
        }

        body.dark-theme .modal-content {
            background-color: #2c2c2c;
            color: #f0f0f0;
        }
        

        /* Modal text */
        .modal-content p {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }

        /* Buttons */
        #confirmDeleteBtn,
        #cancelDeleteBtn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        #confirmDeleteBtn:hover,
        #cancelDeleteBtn:hover {
            background-color: #0056b3;
        }

        /* Cancel button */
        #confirmDeleteBtn {
            background-color: #dc3545;
        }

        #confirmDeleteBtn:hover {
            background-color: #c82333;
        }

        /* For mobile responsiveness */
        @media (max-width: 480px) {
            .modal-content {
                width: 80%;
            }

            .modal-content p {
                font-size: 16px;
            }
        }

        .success-message {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #28a745;
            width: 90%;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 9999;
        }

        .success-message p {
            margin: 0;
        }

        /* Error message styling */
        .error-message {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #dc3545;
            width: 90%;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 9999;
        }

        .error-message p {
            margin: 0;
        }

        /* Optional: Add animation for appearing and disappearing */
        .success-message,
        .error-message {
            animation: fadeInOut 5s ease-in-out;
        }

        @keyframes fadeInOut {
            0% {
                opacity: 0;
            }

            20% {
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        /* add dark theme */
        body.dark-theme {
            background-color: #1a1a1a;
            color: #f0f0f0;
        }

        body.dark-theme .nav {
            background-color: #2c2c2c;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        body.dark-theme .nav a {
            color: #f0f0f0;
        }

        body.dark-theme .nav .username,
        body.dark-theme .nav .conUser {
            color: #f0f0f0;
        }

        body.dark-theme .addTransactions {
            background-color: #1a1a1a;
        }

        body.dark-theme .addTransactions button {
            color: #f0f0f0;
        }

        body.dark-theme .addTransactions button.debits {
            background-color: #8b0000;
        }

        body.dark-theme .addTransactions button.credits {
            background-color: #006400;
        }

        body.dark-theme .transaction .box {
            /* background-color: #2c2c2c; */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.5);
        }

        body.dark-theme .transaction .box p {
            color: #f0f0f0;
        }

        body.dark-theme .transaction .box .time {
            color: #bbb;
        }

        body.dark-theme .modal-content {
            background-color: #2c2c2c;
            color: #f0f0f0;
        }

        body.dark-theme .modal-content p {
            color: #f0f0f0;
        }

        body.dark-theme #confirmDeleteBtn {
            background-color: #c82333;
        }

        body.dark-theme #cancelDeleteBtn {
            background-color: #555;
            color: #f0f0f0;
        }

        body.dark-theme .popup .popup-content {
            background-color: #2c2c2c;
            color: #f0f0f0;
        }

        body.dark-theme .popup .popup-close {
            color: #f0f0f0;
        }

        body.dark-theme .popup .popup-content h2 {
            color: #f0f0f0;
        }

        body.dark-theme .popup .popup-content form {
            background-color: #2c2c2c;
        }

        body.dark-theme .popup .popup-content form label {
            color: #f0f0f0;
        }

        body.dark-theme .popup input,
        body.dark-theme .popup select,
        body.dark-theme .popup textarea {
            color:rgb(255, 255, 255);
            background-color: #2c2c2c;
            border: 1px solid #555;
            box-shadow: inset 0 3px 4px rgba(0, 0, 0, 0.3);
        }

        body.dark-theme .popup input[type="date"],
        body.dark-theme .popup input[type="number"]::placeholder,
        body.dark-theme .popup input[type="text"]::placeholder {
            color:rgb(255, 255, 255);
        }

        body.dark-theme .popup button {
            color: #f0f0f0;
        }

        body.dark-theme .popup button.debits {
            background-color: #8b0000;
        }

        body.dark-theme .popup button.credits {
            background-color: #006400;
        }

        body.dark-theme .success-message {
            background-color: #28a745;
        }

        body.dark-theme .error-message {
            background-color: #dc3545;
        }

        body.dark-theme .success-message p,
        body.dark-theme .error-message p {
            color: #f0f0f0;
        }

        body.dark-theme .hold-to-delete {
            background-color: #444;
            color: #f0f0f0;
        }

        body.dark-theme .hold-to-delete:hover {
            color: #e53935;
        }

        body.dark-theme .fa-gear {
            color: #f0f0f0;
        }

        
    </style>
</head>

<body>
    <div class="nav">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="userinfo">
            <p class="username"><?php echo htmlspecialchars($chatName); ?></p>
            <?php if (!empty($connectedUserData['name'])): ?>
                <p class="conUser"><?php echo htmlspecialchars($connectedUserData['name']); ?> is connected</p>
            <?php endif; ?>
        </div>
        <a href="settings.php?chat_id=<?php echo $chatId; ?>" id="gearIcon">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>
    <div class="addTransactions">
        <!-- Only show buttons if the user has permission -->
        <?php if ($canAddTransaction || $userId == $creatorUserId): ?>
            <button type="button" id="debit-button" class="debits">You Gave ₹</button>
            <button type="button" id="credit-button" class="credits">You Got ₹</button>
        <?php else: ?>
            <button type="button" class="debits" disabled>You Gave ₹</button>
            <button type="button" class="credits" disabled>You Got ₹</button>
        <?php endif; ?>
    </div>
    <section>
        <div class="transaction">
            <?php if (isset($transactionList[0]['error'])): ?>
                <p class="errormsg"><?php echo $transactionList[0]['error']; ?></p>
            <?php else: ?>
                <?php foreach ($transactionList as $transaction): ?>
                    <div class="box <?php echo $transaction['transaction_type'] == 'debit' ? 'debit' : 'credit'; ?>"
                        data-transaction-id="<?php echo $transaction['transaction_id']; ?>">

                        <div class="transaction-info">
                            <p><?php echo $transaction['description']; ?></p>
                            <p class="time"><?php echo $transaction['payment_date']; ?></p>
                        </div>
                        <!-- Hold-to-delete section in the center -->
                        <div class="hold-to-delete"
                            onmousedown="startDeleteTimer(this)"
                            onmouseup="clearDeleteTimer()"
                            onmouseleave="clearDeleteTimer()"
                            ontouchstart="startDeleteTimer(this)"
                            ontouchend="clearDeleteTimer()">
                            <p><i class="fa-solid fa-trash"> Delete</i></p>
                        </div>
                        <div class="transaction-status transaction-info">
                            <p class="amount">₹ <?php echo $transaction['amount']; ?></p>
                            <p class="time">Bal: ₹ <?php echo $transaction['balance']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="confirmationModal" class="modal">
            <div class="modal-content">
                <p>Are you sure you want to delete this transaction?</p>
                <button id="confirmDeleteBtn">Yes</button>
                <button id="cancelDeleteBtn">No</button>
            </div>
        </div>

        <!-- Debit Popup -->
        <div class="popup debit-popup hide">
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <h2>Enter Debit Transaction</h2>
                <form action="chat_page.php?chat_id=<?php echo $chatId; ?>" method="POST">
                    <input type="hidden" name="transactionType" value="Debit">

                    <label for="debit-amount">Amount (₹):</label>
                    <input type="number" id="debit-amount" name="amount" required placeholder="Enter amount">

                    <label for="debit-description">Description:</label>
                    <input type="text" id="debit-description" name="description" placeholder="Add a note (optional)">

                    <label for="debit-date">Date:</label>
                    <input type="date" id="debit-date" name="payment_date" max="<?php echo date('Y-m-d'); ?>" placeholder="Pick a date (default: today)">

                    <button type="submit" class="debits">Submit Debit</button>
                </form>
            </div>
        </div>

        <!-- Credit Popup -->
        <div class="popup credit-popup hide">
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <h2>Enter Credit Transaction</h2>
                <form action="chat_page.php?chat_id=<?php echo $chatId; ?>" method="POST">
                    <input type="hidden" name="transactionType" value="Credit">

                    <label for="credit-amount">Amount (₹):</label>
                    <input type="number" id="credit-amount" name="amount" required placeholder="Enter amount">

                    <label for="credit-description">Description:</label>
                    <input type="text" id="credit-description" name="description" placeholder="Add a note (optional)">

                    <label for="credit-date">Date:</label>
                    <input type="date" id="credit-date" name="payment_date" max="<?php echo date('Y-m-d'); ?>" placeholder="Pick a date (default: today)">

                    <button type="submit" class="credits">Submit Credit</button>
                </form>
            </div>
        </div>
    </section>

    <script src="script.js"></script>
    <script>
        // Apply saved theme preference
        if (localStorage.getItem("theme") === "dark") {
            document.body.classList.add("dark-theme");
            if (themeToggle) {
                themeToggle.classList.replace("fa-sun", "fa-moon");
            }
        }

        let deleteTimer;
        let currentTransactionId = null;

        function startDeleteTimer(element) {
            // Start a timer for 2 seconds to trigger delete
            deleteTimer = setTimeout(() => {
                currentTransactionId = element.closest('.box').getAttribute('data-transaction-id');
                showConfirmationModal();
            }, 500); // 1 second hold time for mobile
        }

        function clearDeleteTimer() {
            // Clear the timer if the user releases or moves the mouse away
            clearTimeout(deleteTimer);
        }

        function showConfirmationModal() {
            // Show the modal on mobile
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'flex';

            // Set up event listeners for the modal buttons
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                deleteTransaction(currentTransactionId);
                closeConfirmationModal();
            });

            document.getElementById('cancelDeleteBtn').addEventListener('click', closeConfirmationModal);
        }

        function closeConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'none';
        }

        function deleteTransaction(transactionId) {
            // Send a request to delete the transaction
            fetch('delete_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        displaySuccessMessage(data.message || "Transaction deleted successfully.");

                        // Delay the page reload by 3 seconds
                        setTimeout(() => {
                            location.reload(); // Reload the page to update the transaction list
                        }, 3000); // 3000ms = 3 seconds
                    } else {
                        // Show the error message from the server
                        displayErrorMessage(data.message || "Failed to delete the transaction. Please try again.");
                    }
                })
                .catch(error => {
                    displayErrorMessage("An error occurred while trying to delete the transaction. Please try again.");
                });
        }


        // Function to display the success message on the page
        function displaySuccessMessage(message) {
            // Create a div to display the success message
            const successDiv = document.createElement('div');
            successDiv.classList.add('success-message');
            successDiv.innerHTML = `<p>${message}</p>`;

            // Append the success message to the body or a specific container
            document.body.appendChild(successDiv);

            // Auto-remove the message after a few seconds
            setTimeout(() => {
                successDiv.remove();
            }, 5000); // Message disappears after 5 seconds
        }

        // Function to display the error message on the page
        function displayErrorMessage(message) {
            // Create a div to display the error message
            const errorDiv = document.createElement('div');
            errorDiv.classList.add('error-message');
            errorDiv.innerHTML = `<p>${message}</p>`;

            // Append the error message to the body or a specific container
            document.body.appendChild(errorDiv);

            // Auto-remove the message after a few seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000); // Message disappears after 5 seconds
        }
    </script>
</body>

</html>