<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Validate chat_id
if (!isset($_GET['chat_id']) || !is_numeric($_GET['chat_id'])) {
    die("Invalid chat ID.");
}

$chatId = $_GET['chat_id'];
$userId = $_SESSION['user_id'];
$errorMessage = "";

// Check if the user is part of the chat and the connection is active
$checkConnectionSQL = "SELECT * FROM connections WHERE chat_id = ? AND (user_id_1 = ? OR user_id_2 = ?) AND connection_status = 'completed'";
$stmt = $conn->prepare($checkConnectionSQL);
$stmt->bind_param("iii", $chatId, $userId, $userId);
$stmt->execute();
$connectionResult = $stmt->get_result();

// Check if the logged-in user is the creator of the chat
$checkCreatorSQL = "SELECT creator_user_id FROM chats WHERE chat_id = ?";
$stmt = $conn->prepare($checkCreatorSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$creatorResult = $stmt->get_result();

if ($creatorResult->num_rows == 0) {
    die("Chat not found.");
}

$creatorData = $creatorResult->fetch_assoc();
$creatorUserId = $creatorData['creator_user_id'];

// Ensure the user is either connected or the creator
if ($connectionResult->num_rows == 0 && $creatorUserId != $userId) {
    die("You are not authorized to access this chat. Please ensure you are connected or are the creator.");
}

// Fetch chat details (chat_name and phone_number)
$fetchChatSQL = "SELECT chat_name, phone_number, email FROM chats WHERE chat_id = ?";
$stmt = $conn->prepare($fetchChatSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$chatResult = $stmt->get_result();

if ($chatResult->num_rows == 0) {
    die("Chat not found.");
}

$chatData = $chatResult->fetch_assoc();
$chatName = $chatData['chat_name'];
$customerPhone = $chatData['phone_number'];
$customerEmail = $chatData['email'];

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
    // Ensure payment_date is a valid date or use today's date
    $paymentDate = isset($_POST['payment_date']) && !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

    // Basic validation
    if (empty($amount) || !is_numeric($amount)) {
        $errorMessage = "Please enter a valid amount.";
    } else {
        // Additional validation for payment_date
        if (!preg_match('/\d{4}-\d{2}-\d{2}/', $paymentDate)) {
            $errorMessage = "Invalid date format. Please select a valid date.";
        } else {
            try {
                // Insert transaction into the database
                $insertTransactionSQL = "INSERT INTO transactions (chat_id, user_id, transaction_type, amount, description, transaction_date, payment_date) 
                                         VALUES (?, ?, ?, ?, ?, NOW(), ?)";
                $stmt = $conn->prepare($insertTransactionSQL);
                $stmt->bind_param("iisdss", $chatId, $userId, $transactionType, $amount, $description, $paymentDate);
                $stmt->execute();

                // Redirect to refresh the transaction list
                header("Location: chat_page.php?chat_id=$chatId");
                exit();
            } catch (Exception $e) {
                $errorMessage = "Error: " . $e->getMessage();
            }
        }
    }
}

// Initialize an array to store transactions for HTML output
$transactionList = [];

if ($transactionsResult->num_rows > 0) {
    // Loop through each transaction to calculate balance and prepare HTML output
    while ($transaction = $transactionsResult->fetch_assoc()) {
        // Calculate the running balance
        if ($transaction['transaction_type'] == 'debit') {
            $totalDebit += (float) $transaction['amount'];
            $balance -= (float) $transaction['amount'];  // Debit reduces balance
        } else if ($transaction['transaction_type'] == 'credit') {
            $totalCredit += (float) $transaction['amount'];
            $balance += (float) $transaction['amount'];  // Credit increases balance
        }

        // Add transaction details to the list for later display
        $transactionList[] = [
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
// Reverse the transaction list to show the transactions in the order they were added
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
    <style>

    </style>
</head>

<body>
    <div class="nav">
        <a href="index.php"><i class="fa-solid fa-arrow-left"></i></a>
         <!-- <a href="index.php">back</a> -->
        <div class="userinfo">
            <p class="username"><?php echo htmlspecialchars($chatName); ?></p>
        </div>
        <a href="settings.php?chat_id=<?php echo $chatId; ?>" id="gearIcon">
            <i class="fa-solid fa-gear"></i></a>
    </div>
    <section>
        <!-- Balance message -->
        <div class="balance-message">
            <p>Debit: ₹<?php echo number_format($totalDebit, 2); ?></p>
            <p>Credit: ₹<?php echo number_format($totalCredit, 2); ?></p>
            <?php if ($balance > 0): ?>
                <p>You will Get ₹ <?php echo number_format($balance, 2); ?>.</p>
            <?php elseif ($balance < 0): ?>
                <p>You will give ₹ <?php echo number_format(abs($balance), 2); ?>.</p>
            <?php else: ?>
                <p>Balance: ₹0.00</p>
            <?php endif; ?>
        </div>

        <div class="transaction">
            <?php if (isset($transactionList[0]['error'])): ?>
                <p class="errormsg"><?php echo $transactionList[0]['error']; ?></p>
            <?php else: ?>
                <?php foreach ($transactionList as $transaction): ?>
                    <div class="box <?php echo $transaction['transaction_type'] == 'debit' ? 'debit' : 'credit'; ?>">
                        <div class="transaction-info">
                            <p><?php echo $transaction['description']; ?></p>
                            <!-- <p class="balance">Running Balance: Rs <?php echo $transaction['balance']; ?></p> -->
                            <p class="time"><?php echo $transaction['payment_date']; ?>
                            </p>
                        </div>
                        <div class="transaction-status transaction-info">
                            <p class="amount">₹ <?php echo $transaction['amount']; ?></p>
                            <p class="time">Bal: ₹ <?php echo $transaction['balance']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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


        <!-- Bottom Buttons -->
        <div class="addTransactions">
            <button type="button" id="debit-button" class="debits">You Gave ₹</button>
            <button type="button" id="credit-button" class="credits">You Got ₹</button>
        </div>
    </section>

    <script src="script.js"></script>
</body>

</html>