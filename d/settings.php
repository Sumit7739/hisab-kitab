<?php

session_start();

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration
include '../config.php'; // Update the path to your database configuration

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$user_id = $_SESSION['user_id']; // Get user_id from session
$chat_id = isset($_GET['chat_id']) ? $_GET['chat_id'] : null; // Get chat_id from the query parameter

// If no chat_id is passed, show an error and exit
if (!$chat_id) {
    die("Chat ID not found!");
}

// Function to fetch chat details and connection ID
function getChatDetailsAndConnectionId($conn, $chat_id)
{
    // Query to join chats and connections table and fetch connection_id and user_id_2
    $chatQuery = "
        SELECT 
            c.chat_name, 
            c.phone_number, 
            c.email, 
            con.connection_id,
            con.user_id_2
        FROM 
            chats c
        JOIN 
            connections con ON c.chat_id = con.chat_id
        WHERE 
            c.chat_id = ?
    ";

    $stmt = $conn->prepare($chatQuery);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to fetch connected user details from the users table
function getConnectedUserDetails($conn, $user_id_2)
{
    $userQuery = "SELECT name, phone FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id_2);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fetch chat details and connection ID
$chatData = getChatDetailsAndConnectionId($conn, $chat_id);

// If no chat data is found, show an error and exit
if (!$chatData) {
    die("No chat data found!");
}

// Fetch connected user details using user_id_2
$connectedUserData = getConnectedUserDetails($conn, $chatData['user_id_2']);

// If no connected user data is found, set a default message
if (!$connectedUserData) {
    $connectedUserData = [
        'user_name' => 'Unknown',
        'phone_number' => 'N/A'
    ];
}

// Handle form submission to update chat details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_id = $_POST['chat_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Validate Name (only if provided)
    if (!empty($name) && strlen($name) < 3) {
        echo json_encode(['success' => false, 'message' => 'Name must be at least 3 characters long.']);
        exit();
    }

    // Validate Phone (only if provided)
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number must be a valid 10-digit number.']);
        exit();
    }

    // Validate Email (only if provided)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format!']);
        exit();
    }

    // Update the chat details in the database
    $updateQuery = "UPDATE chats SET chat_name = ?, phone_number = ?, email = ? WHERE chat_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssi", $name, $phone, $email, $chat_id);

    if ($stmt->execute()) {
        // Success response
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully!']);
    } else {
        // Error response
        echo json_encode(['success' => false, 'message' => 'Error updating settings: ' . $conn->error]);
    }
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Settings</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="chatpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* General Page Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fbfbfb;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        h2,
        h3 {
            margin-bottom: 10px;
            color: #333;
        }

        /* Form Styling */
        form {
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 18px;
            color: #555;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            height: 50px;
            padding: 15px;
            font-size: 18px;
            border: 1px solid #ddd;
            border-radius: 20px;
            box-sizing: border-box;
            outline: none;
            transition: border 0.3s ease, box-shadow 0.3s ease;
            box-shadow: inset 0 3px 4px rgba(0, 0, 0, 0.3);
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Buttons */
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* OTP Section Styling */
        h3 {
            color: #333;
        }

        #otp,
        #otp_status,
        #connection_status {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            border: 1px solid #ddd;
            border-radius: 14px;
            margin-top: 10px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }

        #otp:read-only,
        #otp_status:read-only,
        #connection_status:read-only {
            background-color: #e9ecef;
        }

        #otp_status,
        #connection_status {
            font-size: 18px;
            font-weight: bold;
            color: #555;
        }

        /* Spacer between sections */
        .spacer {
            margin-bottom: 30px;
        }

        /* Button container */
        button#stopFetchBtn {
            background-color: #dc3545;
        }

        button#stopFetchBtn:hover {
            background-color: #c82333;
        }

        /* Make sure input fields are aligned properly */
        input[type="text"],
        input[type="email"],
        button {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        form div {
            margin-bottom: 20px;
        }

        .message-box {
            text-align: center;
            padding: 3px;
            margin-top: 5px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: inline-block;
            width: 100%;
            max-width: 400px;
            font-size: 16px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            /* background-color: #f9f9f9; */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            display: block;
        }


        .userinfo p {
            font-size: 22px;
        }


        /* Disabled button style */
        #generateOtpBtn:disabled {
            background-color: #888;
            /* Dark gray for disabled state */
            color: #ccc;
            /* Light gray text */
            cursor: not-allowed;
            /* Change cursor to indicate it's disabled */
        }

        .delbtn {
            width: 100%;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
        }

        .delbtn:hover {
            background-color: #d32f2f;
        }

        .btn h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #d32f2f;
        }

        .btn p {
            font-size: 18px;
            color: #d32f2f;
        }

        /* Popup overlay */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
        }

        /* Popup content */
        .popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        /* Popup buttons */
        .popup-buttons {
            margin-top: 20px;
        }

        .popup-buttons button {
            margin: 0 10px;
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .confirm-btn {
            background: #d9534f;
            color: #fff;
        }

        .cancel-btn {
            background: #6c757d;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="nav">
        <a href="chat_page.php?chat_id=<?php echo $chat_id; ?>"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="userinfo">
            <p class="username">Settings</p>
        </div>
        <a href="#" id="gearIcon">
            <i class="fa-solid fa-gear"></i></a>
    </div>
    <div class="container">

        <!-- Heading -->
        <!-- <h1>Edit Settings</h1> -->
        <!-- Message Section (Success/Error) -->
        <div id="messageBox" class="message-box" style="display:none;">
            <p id="messageText"></p>
        </div>

        <!-- Form for Name, Phone, Email -->
        <h2>Chat Settings</h2>
        <div class="spacer"></div>
        <form id="settingsForm" action="settings.php?chat_id=<?php echo $chat_id; ?>" method="POST">
            <input type="hidden" name="chat_id" value="<?php echo $chat_id; ?>">

            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($chatData['chat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($chatData['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($chatData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>


            <div class="form-group">
                <button type="submit">Update Settings</button>
            </div>
        </form>
        <hr>
        <div class="spacer"></div>
        <!-- connected user details -->
        <div class="connected-user-details">
            <h2>Connected User Details</h2>
            <br>
            <label for="username">Name:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($connectedUserData['name'] ?? '-- No Connection --'); ?>" readonly>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($connectedUserData['phone'] ?? 'N/A'); ?>" readonly>
        </div>
        <div class="spacer"></div>
        <hr>
        <div class="spacer"></div>
        <!-- OTP Section -->
        <h3>Generate and Fetch OTP</h3>
        <div class="spacer"></div>
        <p>Share the OTP to Connect other User</p>
        <br>

        <div class="form-group">
            <button id="generateOtpBtn" disabled>Generate OTP</button> <!-- Button is disabled by default -->
        </div>
        <div class="form-group">
            <label for="otp">Generated OTP:</label>
            <input type="text" id="otp" readonly>
        </div>

        <div class="form-group">
            <label for="otp_status">OTP Status:</label>
            <input type="text" id="otp_status" readonly>
        </div>

        <div class="form-group">
            <label for="connection_status">Connection Status:</label>
            <input type="text" id="connection_status" readonly>
        </div>

        <div class="form-group">
            <button id="fetchOtpBtn">Get Status</button>
            <!-- <button id="stopFetchBtn">Stop Status</button> -->
        </div>
        <hr>
        <div class="spacer"></div>
        <div class="btn" data-chat-id="<?php echo $chat_id; ?>">
            <h3>Danger Zone</h3>
            <p>Once you delete a chat, there is no going back. Please be certain.</p>
            <div class="spacer"></div>
            <button type="button" class="delbtn">Delete Chat</button>
        </div>


        <!-- Popup Structure -->
        <div id="deletePopup" class="popup-overlay">
            <div class="popup-content">
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete this chat?</p>
                <button id="confirmDelete" style="margin: 10px; padding: 10px 20px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer;">Yes, Delete</button>
                <button id="cancelDelete" style="margin: 10px; padding: 10px 20px; background-color: grey; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
            </div>
        </div>


    </div>

    <script>
        $(document).ready(function() {

            // Get the connection_id from PHP and pass it to JavaScript
            const connectionId = <?php echo json_encode($chatData['connection_id']); ?>;

            // Function to check connection status and enable the OTP generation button
            function checkConnectionStatus() {
                if (!connectionId) {
                    alert('No connection ID found.');
                    return;
                }

                // AJAX request to fetch OTP and connection status
                $.ajax({
                    url: 'fetch_otp_status.php', // PHP file that handles OTP status fetching
                    type: 'GET',
                    data: {
                        connection_id: connectionId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#otp').val(response.otp); // Display fetched OTP
                            $('#otp_status').val(response.otp_status); // Display OTP status
                            $('#connection_status').val(response.connection_status); // Display connection status

                            // Enable the Generate OTP button only if connection_status is 'pending' and OTP is not generated
                            if (response.connection_status === 'pending' && response.otp_status !== 'OTP Generated') {
                                $('#generateOtpBtn').prop('disabled', false); // Enable the button
                            } else {
                                $('#generateOtpBtn').prop('disabled', true); // Disable the button if OTP is generated or connection status is not 'pending'
                            }
                        } else {
                            $('#connection_status').val('Error: ' + response.message); // Display error in connection status
                        }
                    },
                    error: function() {
                        $('#connection_status').val('An error occurred while fetching connection status.');
                    }
                });
            }

            // Call the function to check connection status on page load
            checkConnectionStatus();

            // Generate OTP button click handler
            $('#generateOtpBtn').click(function() {
                if (!connectionId) {
                    alert('No connection ID found.');
                    return;
                }

                // AJAX request to generate OTP
                $.ajax({
                    url: 'generate_otp.php', // PHP file that handles OTP generation
                    type: 'POST',
                    data: {
                        connection_id: connectionId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#otp').val(response.otp); // Display the generated OTP
                            $('#otp_status').val('OTP Generated'); // Update OTP status
                            $('#generateOtpBtn').prop('disabled', true); // Disable the button after OTP is generated
                        } else {
                            $('#otp_status').val('Error: ' + response.message); // Display error in OTP status
                        }
                    },
                    error: function() {
                        $('#otp_status').val('An error occurred while generating OTP.');
                    }
                });
            });

            // Set the interval to fetch OTP status every 5 seconds (5000 milliseconds)
            // setInterval(checkConnectionStatus, 5000);

            $('#settingsForm').submit(function(event) {
                event.preventDefault(); // Prevent the default form submission

                var formData = $(this).serialize(); // Serialize form data

                $.ajax({
                    url: 'settings.php?chat_id=<?php echo $chat_id; ?>', // PHP script handling form submission
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        $('#messageBox').show();
                        if (response.success) {
                            $('#messageText').text(response.message); // Display success message
                            $('#messageBox').addClass('success');
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            $('#messageText').text(response.message); // Display error message
                            $('#messageBox').addClass('error');
                        }
                    },
                    error: function() {
                        $('#messageText').text('An error occurred while updating settings.');
                        $('#messageBox').show().addClass('error');
                    }
                });
            });


            let chatIdToDelete = null;

            $(document).ready(function() {
                // Open the popup
                $(".delbtn").on("click", function() {
                    chatIdToDelete = $(this).closest(".btn").data("chat-id"); // Get chat ID from the closest .btn
                    $("#deletePopup").fadeIn(); // Show the popup
                });

                // Close the popup on cancel button
                $("#cancelDelete").on("click", function() {
                    $("#deletePopup").fadeOut(); // Hide the popup
                });

                // Close the popup on clicking outside the box
                $("#deletePopup").on("click", function(e) {
                    if (e.target.id === "deletePopup") {
                        $("#deletePopup").fadeOut(); // Hide the popup
                    }
                });

                // Handle the confirm delete button
                $("#confirmDelete").on("click", function() {
                    if (chatIdToDelete) {
                        console.log("Chat ID to delete:", chatIdToDelete); // Debugging log
                        // Proceed with AJAX or further logic
                    } else {
                        alert("No chat ID selected.");
                    }
                    $("#deletePopup").fadeOut();
                });
            });
            // Handle the confirm delete button
            $("#confirmDelete").on("click", function() {
                if (chatIdToDelete) {
                    $.ajax({
                        url: "delete_chat.php",
                        type: "POST",
                        data: {
                            chat_id: chatIdToDelete
                        }, // Pass the chat ID to the server
                        success: function(response) {
                            try {
                                let result = JSON.parse(response);

                                if (result.success) {
                                    alert("Chat deleted successfully!");
                                    $(`.btn[data-chat-id="${chatIdToDelete}"]`).remove();
                                    // add redirection to index.php page
                                    location.href = "index.php";
                                } else {
                                    alert(result.message || "Failed to delete chat.");
                                }
                            } catch (e) {
                                console.error("Invalid JSON response", response);
                                alert("An unexpected error occurred.");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX error:", error);
                            alert("Failed to delete chat. Please try again later.");
                        },
                    });
                }
                $("#deletePopup").fadeOut();
            });

            // Close the popup on cancel or background click
            $("#cancelDelete, #deletePopup").on("click", function(e) {
                if (e.target.id === "cancelDelete" || e.target.id === "deletePopup") {
                    $("#deletePopup").fadeOut();
                }
            });
        });
    </script>
    <!-- dfads trn = 32, chat id =  11-->
</body>

</html>