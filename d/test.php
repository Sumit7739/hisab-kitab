<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['chat_id']) || !is_numeric($_GET['chat_id'])) {
    die("Invalid chat ID.");
}

$chatId = $_GET['chat_id'];
$userId = $_SESSION['user_id'];
$errorMessage = "";

// Fetch chat details (chat_name and phone_number) from the database
$fetchChatSQL = "SELECT chat_name, phone_number FROM chats WHERE chat_id = ?";
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

// Fetch all transactions for this chat
$fetchTransactionsSQL = "SELECT * FROM transactions WHERE chat_id = ? ORDER BY transaction_date DESC";
$stmt = $conn->prepare($fetchTransactionsSQL);
$stmt->bind_param("i", $chatId);
$stmt->execute();
$transactionsResult = $stmt->get_result();

// Handle new transaction submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transactionType = $_POST['transactionType'];
    $amount = $_POST['amount'];
    $description = isset($_POST['description']) ? $_POST['description'] : "";

    // Basic validation
    if (empty($amount) || !is_numeric($amount)) {
        $errorMessage = "Please enter a valid amount.";
    } else {
        try {
            // Insert transaction into the database
            $insertTransactionSQL = "INSERT INTO transactions (chat_id, user_id, transaction_type, amount, description, transaction_date) 
                                     VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertTransactionSQL);
            $stmt->bind_param("iisds", $chatId, $userId, $transactionType, $amount, $description);
            $stmt->execute();

            // Redirect to the same page to refresh transaction list
            header("Location: chat_page.php?chat_id=$chatId");
            exit();
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Page</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <h2>Chat with <?php echo htmlspecialchars($chatName); ?> (Phone: <?php echo htmlspecialchars($customerPhone); ?>)</h2>

        <!-- Display error message -->
        <?php if (!empty($errorMessage)): ?>
            <div class="error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <!-- Transaction List -->
        <h3>Transactions:</h3>
        <?php if ($transactionsResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Transaction Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Transaction Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $transactionsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['transaction_type']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No transactions found for this chat.</p>
        <?php endif; ?>

        <!-- Form to add a new transaction -->
        <h3>Add New Transaction:</h3>
        <form method="POST" action="">
            <label for="transactionType">Transaction Type:</label>
            <select id="transactionType" name="transactionType" required>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
            </select>
            <br><br>
            <label for="amount">Amount:</label>
            <input type="text" id="amount" name="amount" required>
            <br><br>
            <label for="description">Description (Optional):</label>
            <input type="text" id="description" name="description">
            <br><br>
            <button type="submit">Add Transaction</button>
        </form>
    </div>
</body>

</html>