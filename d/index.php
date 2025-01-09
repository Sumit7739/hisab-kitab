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
            c.chat_id ORDER BY t.transaction_date DESC
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
    /* Box2 App-Style */
    .box2 {
        margin: 0;
        margin-top: 10px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-radius: 6px;
        background-color: #ffffff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .box2:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .box2 input[type="text"],
    .box2 input[type="date"],
    .box2 button {
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        border: none;
        border-radius: 20px;
        padding: 8px 14px;
        transition: background-color 0.3s ease, box-shadow 0.2s ease;
    }

    .box2 input[type="text"],
    .box2 input[type="date"] {
        width: 180px;
        background-color: #f0f0f0;
        color: #333;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .box2 input[type="text"]:focus,
    .box2 input[type="date"]:focus {
        background-color: #fff;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
        outline: none;
    }

    .box2 button {
        background-color: #6200ea;
        color: white;
        cursor: pointer;
        border-radius: 20px;
        padding: 8px 20px;
    }

    .box2 button:hover {
        background-color: #3700b3;
    }

    .box2 button:active {
        background-color: #03dac6;
    }

    .box2 input[type="text"],
    .box2 input[type="date"],
    .box2 button {
        border-radius: 20px;
    }

    /* Box3 App-Style */

    .wrapper {
        height: 77vh;
        /* Limits the height of the wrapper to 70% of the viewport height */
        overflow-y: auto;
        /* Enables vertical scrolling when the content exceeds the height */
        padding: 5px;
        /* Optional padding to give space around the content */
        box-sizing: border-box;
        /* Ensures padding is included in the height calculation */
    }

    .box3 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        margin: 10px 5px;
        padding: 10px;
        background-color: #fbfbfb;
        border-radius: 10px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
        font-family: Arial, sans-serif;
        cursor: pointer;
    }

    .user {
        display: flex;
        flex: 1;
        align-items: center;
    }

    .info {
        margin-left: 15px;
        max-width: 200px;
    }

    .info p {
        margin: 0;
        padding: 4px 0;
        color: #000;
        font-size: 14px;
    }

    .info p.name {
        font-weight: bold;
        font-size: 16px;
    }

    .info p.time {
        font-size: 12px;
        color: #333;
        margin-top: 5px;
    }

    /* Centering the balance amount */
    .bal {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        /* Center the balance amount */
        flex: 1;
    }

    /* Delete Icon at the end of the box */
    .delete-icon {
        cursor: pointer;
        padding: 5px;
        position: absolute;
        right: 10px;
        /* Position the icon at the end of the box */
    }

    .delete-icon img {
        width: 20px;
        height: 20px;
    }

    /* Popup Style */
    .popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        padding: 20px;
        color: white;
        border-radius: 10px;
        text-align: center;
        width: 250px;
    }

    .popup-content button {
        margin-top: 10px;
        padding: 10px 20px;
        background-color: #e74c3c;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 5px;
    }

    .popup-content button:hover {
        background-color: #c0392b;
    }

    /* Conditional color for balance */
    .bal.negative {
        color: red;
    }

    .bal.positive {
        color: green;
    }

    .bal.neutral {
        color: #ffce1b;
        /* Default color when balance is neutral */
    }

    .delete-icon {
        cursor: pointer;
        padding: 5px;
        position: absolute;
        right: 10px;
        /* Position the icon at the end of the box */
        font-size: 18px;
        /* Adjust the icon size */
    }

    .delete-icon i {
        color: #e74c3c;
        /* Red color for the trash icon */
    }

    .delete-icon i:hover {
        color: #c0392b;
        /* Darker red when hovering */
    }

    /* Dock Container */
    .dock {
        position: fixed;
        /* Keeps the dock at the bottom of the screen */
        bottom: 10px;
        /* Distance from the bottom */
        left: 50%;
        /* Centers the dock horizontally */
        width: 100%;
        transform: translateX(-50%);
        /* Offset to center properly */
        display: flex;
        justify-content: center;
        gap: 20px;
        /* Space between buttons */
        z-index: 1000;
        /* Ensures it stays on top of other content */
        background-color: rgba(255, 255, 255, 0.9);
        /* Optional semi-transparent background */
        border-radius: 5px;
        /* Rounded corners for the dock */
        padding: 10px 10px;
    }

    /* Style for dock items (buttons) */
    .dock-item {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Style for individual buttons */
    .dock-item .button,
    .dock-item .btn {
        padding: 12px 15px;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        border-radius: 30px;
        /* Rounded corners */
        margin-left: 10px;
        margin-right: 10px;
        border: none;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        width: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Styling for the 'Join Chat' Button */
    .dock-item .button {
        background-color: rgb(255, 255, 255);
        /* Blue color for the 'Join Chat' button */
        color: black;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    /* Styling for the '+ ADD Customer' Button */
    .dock-item .btn {
        background-color: rgb(255, 255, 255);
        /* Blue color for the 'Join Chat' button */
        color: black;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    /* Icons Styling */
    .dock-item .button i,
    .dock-item .btn i {
        margin-right: 8px;
        /* Space between icon and text */
    }

    /* Hover effect for buttons */
    .dock-item .button:hover,
    .dock-item .btn:hover {
        transform: scale(1.1);
        /* Slightly enlarges the button on hover */
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        /* Adds a subtle shadow */
    }

    /* Styling for button focus */
    .dock-item .button:focus,
    .dock-item .btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.6);
        /* Blue focus ring */
    }

    /* Adjust for smaller screens */
    @media (max-width: 768px) {
        .dock {
            bottom: 10px;
            /* Adjust bottom margin on smaller screens */
            gap: 10px;
            /* Decrease space between buttons */
        }

        .dock-item .button,
        .dock-item .btn {
            width: 120px;
            /* Slightly reduce button size */
            font-size: 14px;
        }
    }
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
                <li><a href="usersettings.php">Settings</a></li>
            </ul>
        </nav>
    </header>
    <section>

        <!-- <div class="box1">
            <div class="credit"></div>
            <div class="debit"></div>
        </div> -->
        <div class="box2">
            <input type="text" id="searchInput" placeholder="Search transactions...">
            <button type="button" id="searchButton">Search</button>
            <input type="date" id="dateInput">
        </div>
        <hr>

        <!-- Displaying the data in box3 for each chat -->
        <!-- Displaying the data in box3 for each chat -->
        <div class="wrapper">
            <?php if (count($chats) > 0): ?>
                <?php foreach ($chats as $chat): ?>
                    <div class="box3" id="chat_<?php echo $chat['chat_id']; ?>">
                        <div class="user">
                            <div class="info">
                                <!-- Moving the <a> tag around the chat_name -->
                                <a href="chat_page.php?chat_id=<?php echo $chat['chat_id']; ?>" class="name-link" style="text-decoration: none;">
                                    <p class="name"><?php echo $chat['chat_name']; ?></p>
                                </a>
                                <p class="time"><?php echo date('Y-m-d', strtotime($chat['created_at'])); ?></p>
                            </div>
                        </div>
                        <!-- Center the balance amount -->
                        <div class="bal" style="color: <?php
                                                        $balance = $chat['total_credit'] - $chat['total_debit'];
                                                        echo $balance < 0 ? 'red' : ($balance > 0 ? 'green' : '#ffce1b');
                                                        ?>">
                            ₹ <?php echo $balance; ?>
                        </div>
                        <!-- Delete icon at the end -->
                        <div class="delete-icon" onclick="openDeletePopup(<?php echo $chat['chat_id']; ?>)">
                            <i class="fas fa-trash-alt"></i> <!-- Font Awesome trash icon -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No chats available. <?php echo isset($errorMessage) ? $errorMessage : ''; ?></p>
            <?php endif; ?>
        </div>


        <!-- Delete Confirmation Popup -->
        <div id="deletePopup" class="popup" style="display:none;">
            <div class="popup-content">
                <p>Are you sure you want to delete this chat?</p>
                <button onclick="deleteChat()">Yes, Delete</button>
                <button onclick="closePopup()">Cancel</button>
            </div>
        </div>


        <?php $user_id = $_SESSION['user_id']; ?>
        <!-- Dock at the bottom -->
        <div class="dock">
            <!-- Join Chat Button -->
            <div class="dock-item">
                <button type="button" class="button" onclick="window.location.href='join.php?user_id=<?php echo $user_id; ?>';">
                    <i class="fas fa-comments"></i> Join Chat
                </button>
            </div>

            <!-- Add Customer Button -->
            <div class="dock-item">
                <button type="button" class="btn">
                    <i class="fas fa-user-plus"></i> + ADD
                </button>
            </div>
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

        document.getElementById('searchButton').addEventListener('click', function() {
            filterData();
        });

        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterData();
        });

        document.getElementById('dateInput').addEventListener('change', function() {
            filterData();
        });

        function filterData() {
            var searchQuery = document.getElementById('searchInput').value.toLowerCase();
            var selectedDate = document.getElementById('dateInput').value;

            // Get all the box3 elements
            var chats = document.querySelectorAll('.box3');

            // Loop through each chat box and apply filtering
            chats.forEach(function(chat) {
                var chatName = chat.querySelector('.name').textContent.toLowerCase(); // Chat name
                var chatDate = chat.querySelector('.time').textContent; // Chat creation date
                var isMatch = true;

                // Check if search query matches the chat name
                if (searchQuery && !chatName.includes(searchQuery)) {
                    isMatch = false;
                }

                // Check if selected date matches the chat creation date
                if (selectedDate && chatDate !== selectedDate) {
                    isMatch = false;
                }

                // Show or hide the chat box based on whether it matches the filters
                if (isMatch) {
                    chat.style.display = '';
                } else {
                    chat.style.display = 'none';
                }
            });
        }
        let currentChatId = null;

        // Open the delete confirmation popup
        function openDeletePopup(chatId) {
            currentChatId = chatId;
            document.getElementById('deletePopup').style.display = 'block';
        }

        // Close the delete confirmation popup
        function closePopup() {
            document.getElementById('deletePopup').style.display = 'none';
        }

        // Handle the deletion of the chat
        function deleteChat() {
            if (currentChatId) {
                // You can perform an AJAX request or redirect to a PHP script to delete the chat
                window.location.href = `delete_chat.php?chat_id=${currentChatId}`;
            }
        }

        // Add event listener to each chat box to redirect to chat page
        document.querySelectorAll('.box3').forEach(box => {
            box.addEventListener('click', function(event) {
                // If the click was on the delete icon, don't redirect
                if (event.target.closest('.delete-icon')) {
                    return;
                }

                // Redirect to chat page
                const chatId = box.id.split('_')[1];
                window.location.href = `chat_page.php?chat_id=${chatId}`;
            });
        });

        // Function to open delete confirmation popup
        function openDeletePopup(chatId) {
            // Open the popup logic
            document.getElementById('deletePopup').style.display = 'block';
            document.getElementById('deletePopup').setAttribute('data-chat-id', chatId);
        }
    </script>
</body>

</html>