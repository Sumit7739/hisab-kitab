<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../index.html");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch logged-in user's details (including phone number)
$sqlUserDetails = "SELECT name, phone FROM users WHERE user_id = ?";
$stmtUserDetails = $conn->prepare($sqlUserDetails);
$stmtUserDetails->bind_param("i", $user_id);
$stmtUserDetails->execute();
$resultUserDetails = $stmtUserDetails->get_result();

if ($resultUserDetails->num_rows > 0) {
    $userDetails = $resultUserDetails->fetch_assoc();
    $username = $userDetails['name'];
    $userPhoneNumber = $userDetails['phone'];
} else {
    echo "Error fetching user details.";
    exit();
}

// Handle chat creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerName = trim($_POST['customerName']);
    $customerPhone = trim($_POST['customerPhone']); // Get phone input
    $userId = $_SESSION['user_id'];

    if (!empty($customerName)) {
        try {
            // Start transaction
            $conn->autocommit(false);

            // Insert into chats table
            $insertChatSQL = "INSERT INTO chats (chat_name, phone_number, creator_user_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertChatSQL);
            $stmt->bind_param("ssi", $customerName, $customerPhone, $userId);
            $stmt->execute();
            $chatId = $conn->insert_id;

            // Determine connection_type (based on user role or other logic)
            $getUserRoleSQL = "SELECT role FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($getUserRoleSQL);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userRole = $result->fetch_assoc()['role'];

            $connectionType = ($userRole === 'admin') ? 'admin-user' : 'user-user';

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

// Fetch all chats for the logged-in user
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
        ORDER BY 
            c.created_at DESC
    ";
    $stmt = $conn->prepare($fetchChatsSQL);
    $stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $chats[] = $row;
    }

    // Fetch chats where phone_number matches the logged-in user's phone number
    $fetchChatsByPhoneSQL = "
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
        WHERE 
            c.phone_number = ?
        GROUP BY 
            c.chat_id 
        ORDER BY 
            c.created_at DESC
    ";
    $stmt = $conn->prepare($fetchChatsByPhoneSQL);
    $stmt->bind_param("s", $userPhoneNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $chats[] = $row;
    }

    // Deduplicate chats
    $chats = array_map('unserialize', array_unique(array_map('serialize', $chats)));
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
}

if (empty($chats)) {
    $errorMessage = "No chats found.";
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
    .logo {
        margin-top: 5px;
        width: 250px;
    }

    .logo p {
        font-size: 16px;
    }

    .errormsg {
        width: 90%;
        margin: 0 auto;
        text-align: center;
        color: #ff0000;
        font-size: 16px;
        font-weight: 600;
        background-color: aliceblue;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
    }

    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .popup {
        background: #fff;
        width: 90%;
        max-width: 450px;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        text-align: center;
        position: relative;
    }

    .popup h2 {
        margin: 0;
        font-size: 24px;
        color: #333;
    }

    .popup p {
        font-size: 16px;
        color: #555;
        margin: 10px 0;
        line-height: 1.5;
    }

    .highlight {
        font-weight: bold;
        color: #4caf50;
    }

    .amount {
        font-weight: bold;
        color: #f44336;
    }

    .popup button {
        background: #4caf50;
        color: #fff;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 16px;
        cursor: not-allowed;
        margin-top: 15px;
        opacity: 0.5;
        transition: opacity 0.3s ease;
    }

    .popup button.enabled {
        cursor: pointer;
        opacity: 1;
    }

    .popup button:hover {
        background: #45a049;
    }
</style>

<body>
    <header>
        <nav>
            <div class="logo">
                <p>Welcome, <?php echo htmlspecialchars($username); ?></p>
            </div>
            <div class="hamburger" id="hamburger">
                <i class="fa fa-bars"></i>
            </div>
            <ul class="menu" id="menu">
                <li><a href="index.html"><i class="fa fa-user" id="active"></i>&nbsp; Parties</a></li>
                <li><a href="usersettings.php"><i class="fa fa-cog"></i>&nbsp; Settings</a></li>
                <li><a href="comingsoon.html"><i class="fa fa-bell"></i>&nbsp; Notifications </a></li>
                <li><a href="logout.php" class="btn-logout"><i class="fa fa-sign-out"></i>&nbsp; Logout</a></li>
            </ul>
        </nav>
    </header>
    <section>

        <div class="loguser">

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
        <div class="box2">
            <input type="text" id="searchInput" placeholder="Search transactions...">
            <button type="button" id="searchButton">Search</button>
            <input type="date" id="dateInput">
        </div>
        <hr>

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
                                                        $balance = ($chat['total_credit'] ?? 0) - ($chat['total_debit'] ?? 0);
                                                        echo $balance < 0 ? 'red' : ($balance > 0 ? 'green' : '#ffce1b');
                                                        ?>">
                            ₹ <?php echo $balance; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="errormsg">Create a new Hisab-Kitab.</p>
            <?php endif; ?>
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

        <div class="popup-overlay" id="popup-overlay">
            <div class="popup">
                <h2>🎉 Welcome to <span class="highlight">Hisab-Kitab!</span></h2>
                <p>
                    We're excited to have you on board! Here's the deal: <br> You can enjoy <span class="highlight">Full
                        Access</span> to all features of our app for the next <span class="highlight">7 days</span>
                    <strong>completely FREE!</strong> 🎁
                </p>
                <p>
                    After your free trial ends, you'll only need to pay <span class="amount">₹50/month</span> to continue
                    enjoying all the benefits.
                </p>
                <p>
                    Use this opportunity to explore the app and experience its powerful features. We're confident you'll
                    love it! 💖
                </p>
                <button id="continue-btn" disabled>Got It! Let's Get Started 🚀</button>
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

        // Add event listener to each chat box to redirect to chat page
        document.querySelectorAll('.box3').forEach(box => {
            box.addEventListener('click', function(event) {

                // Redirect to chat page
                const chatId = box.id.split('_')[1];
                window.location.href = `chat_page.php?chat_id=${chatId}`;
            });
        });

        // Function to check if the popup should be shown
        function shouldShowPopup() {
            const lastShown = localStorage.getItem('popupShownAt');
            const currentTime = new Date().getTime();

            // Show the popup if it's the first time or 24 hours have passed
            return !lastShown || currentTime - lastShown > 24 * 60 * 60 * 1000;
        }

        // Function to mark the popup as shown with the current timestamp
        function markPopupAsShown() {
            const currentTime = new Date().getTime();
            localStorage.setItem('popupShownAt', currentTime);
        }

        // Show the popup and handle button disabling
        function showPopup() {
            const popupOverlay = document.getElementById('popup-overlay');
            const continueButton = document.getElementById('continue-btn');

            popupOverlay.style.display = 'flex';

            // Disable the "Got It!" button for 7 seconds
            continueButton.disabled = true; // Initially disable the button
            setTimeout(() => {
                continueButton.classList.add('enabled');
                continueButton.disabled = false; // Enable the button after 7 seconds
            }, 8000);

            // Handle "Got It!" button click
            continueButton.addEventListener('click', () => {
                popupOverlay.style.display = 'none';
                markPopupAsShown(); // Mark the popup as shown
            });
        }

        // Check and show the popup if needed
        if (shouldShowPopup()) {
            showPopup();
        }
    </script>
</body>

</html>