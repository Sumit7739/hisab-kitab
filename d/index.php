<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerName = trim($_POST['customerName']);
    $customerPhone = trim($_POST['customerPhone']); // Get phone input
    $userId = $_SESSION['user_id'];

    if (!empty($customerName)) {
        try {
            // Start transaction
            $conn->autocommit(false);

            // Insert into chats table (with phone number and user_id)
            $insertChatSQL = "INSERT INTO chats (chat_name, phone_number, creator_user_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertChatSQL);
            $stmt->bind_param("ssi", $customerName, $customerPhone, $userId);
            $stmt->execute();
            $chatId = $conn->insert_id;

            // Determine connection_type (based on user role or other logic)
            $getUserRoleSQL = "SELECT name, role FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($getUserRoleSQL);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $name = $row['name'];
            $userRole = $result->fetch_assoc()['role'];

            // Adjust connectionType based on user role
            if ($userRole === 'admin') {
                $connectionType = 'admin-user'; // Correct value for admin
            } else {
                $connectionType = 'user-user'; // Correct value for user
            }

            // Insert into connections table
            $insertConnectionSQL = "INSERT INTO connections (user_id_1, connection_type, chat_id, connection_status) 
                        VALUES (?, ?, ?, 'pending')";
            $stmt = $conn->prepare($insertConnectionSQL);
            $stmt->bind_param("isi", $userId, $connectionType, $chatId);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            $conn->autocommit(true);

            // Redirect to chat page on success
            header("Location: chat_page.php?chat_id=" . $chatId);
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $conn->autocommit(true);
            $errorMessage = "Error: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Name is required.";
    }
}

// Fetch all chats for the logged-in user (based on user_id)
$chats = [];
try {
    // Fetch chats where the logged-in user is either the creator or connected to the chat
    $fetchChatsSQL = "
        SELECT 
            c.chat_id, 
            c.chat_name, 
            c.phone_number, 
            c.created_at, 
            IFNULL(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) AS total_debit, 
            IFNULL(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) AS total_credit
        FROM 
            chats c
        LEFT JOIN 
            transactions t ON c.chat_id = t.chat_id
        LEFT JOIN 
            connections con ON c.chat_id = con.chat_id
        WHERE 
            c.creator_user_id = ? 
            OR con.user_id_1 = ? 
            OR con.user_id_2 = ?
        GROUP BY 
            c.chat_id
    ";
    $stmt = $conn->prepare($fetchChatsSQL);
    $stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']); // Bind user_id for creator and connection
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $chats[] = $row; // Store each chat in the $chats array
        }
    } else {
        // Add debug message in case of error
        $errorMessage = "Database query failed: " . $conn->error;
    }
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
}

if (empty($chats)) {
    $errorMessage = "No chats found for the logged-in user.";  // Debug message when no chats are found
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<style>

</style>

<body>
    <header>
        <nav>
            <div class="logo">Hisab-Kitab</div>
            <div class="hamburger" id="hamburger">
                <i class="fa fa-bars"></i>
            </div>
            <ul class="menu" id="menu">
                <li><a href="index.html"><i class="fa fa-user" id="active"></i>&nbsp; Parties</a></li>
                <li><a href="logout.php">LogOut</a></li>
            </ul>
        </nav>
    </header>
    <section>
        <!-- 
        <div class="box1">
            <div class="credit"></div>
            <div class="debit"></div>
        </div> -->
        <div class="box2">
            <input type="text" id="searchInput" placeholder="Search transactions...">
            <button id="searchButton">Search</button>
            <input type="date" id="dateInput">
        </div>
        <hr>

        <!-- Displaying the data in box3 for each chat -->
        <?php if (count($chats) > 0): ?>
            <?php foreach ($chats as $chat): ?>
                <a href="chat_page.php?chat_id=<?php echo $chat['chat_id']; ?>" class="box-link">
                    <div class="box3">
                        <div class="user">
                            <!-- Optional profile picture (currently commented out) -->
                            <!-- <div class="pf"></div> -->
                            <div class="info">
                                <p class="name"><?php echo $chat['chat_name']; ?></p> <!-- Display chat name as username -->
                                <p class="time"><?php echo date('Y-m-d H:i:s', strtotime($chat['created_at'])); ?></p> <!-- Display chat creation time -->
                            </div>
                        </div>
                        <div class="bal"
                            style="color: <?php
                                            $balance = $chat['total_credit'] - $chat['total_debit'];
                                            echo $balance < 0 ? 'red' : ($balance > 0 ? 'green' : 'yellow');
                                            ?>">
                            ₹ <?php echo $balance; ?> <!-- Display balance with dynamic color -->
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No chats available. <?php echo isset($errorMessage) ? $errorMessage : ''; ?></p>
        <?php endif; ?>


        <div class="box4">
            <button type="button" class="btn">+ ADD Customer</button>
        </div>
        <!-- Add Customer Modal -->
        <div id="addCustomerModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2>Add Customer</h2>
                <?php if (isset($errorMessage)): ?>
                    <div class="error"><?php echo $errorMessage; ?></div>
                <?php endif; ?>
                <form id="addCustomerForm" method="POST" action="">
                    <label for="customerName">Name:</label>
                    <input type="text" id="customerName" name="customerName" placeholder="Enter customer name" required>
                    <label for="customerPhone">Phone:</label>
                    <input type="text" id="customerPhone" name="customerPhone" placeholder="Enter customer phone">
                    <button type="submit">Add Customer</button>
                </form>
            </div>
        </div>
    </section>
    <script>
        const menu = document.getElementById('menu');
        const hamburger = document.getElementById('hamburger');

        hamburger.addEventListener('click', (event) => {
            event.stopPropagation();
            menu.classList.toggle('show');
            hamburger.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (!hamburger.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.remove('show');
                hamburger.classList.remove('active');
            }
        });
        // Get modal and related elements
        const addCustomerModal = document.getElementById('addCustomerModal');
        const closeModal = document.getElementById('closeModal');
        const addCustomerButton = document.querySelector('.btn');

        // Open modal when the "Add Customer" button is clicked
        addCustomerButton.addEventListener('click', () => {
            addCustomerModal.style.display = 'block';
        });

        // Close modal when the "X" button is clicked
        closeModal.addEventListener('click', () => {
            addCustomerModal.style.display = 'none';
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === addCustomerModal) {
                addCustomerModal.style.display = 'none';
            }
        });
    </script>
</body>

</html>