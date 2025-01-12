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
    echo "Chat ID not found!";
    header("Refresh: 3; url=index.php");
    exit();
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
            c.creator_user_id,
            con.connection_id,
            con.permission,
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

function getCreatorUserDetails($conn, $user_id)
{
    $usersQuery = "SELECT name, phone FROM users WHERE user_id =?";
    $stmt = $conn->prepare($usersQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

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
    echo "No chat data found!";
    header("Refresh: 3; url=index.php");
    exit();
}

// Fetch creator user details using user_id
$creatorUserData = getCreatorUserDetails($conn, $chatData['creator_user_id']);

// If no creator user data is found, set a default message
if (!$creatorUserData) {
    $creatorUserData = [
        'name' => 'Unknown',
        'phone' => 'N/A'
    ];
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

    // Verify if the logged-in user is the creator of the chat
    $checkCreatorQuery = "SELECT creator_user_id FROM chats WHERE chat_id = ?";
    $stmt = $conn->prepare($checkCreatorQuery);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Chat not found!']);
        exit();
    }

    $row = $result->fetch_assoc();

    // Check if the logged-in user is the creator of the chat
    if ($row['creator_user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only the chat creator can edit the chat details.']);
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
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Popup buttons */
        .popup-buttons {
            margin-top: 20px;
        }

        .popup-buttons button {
            /* margin: 0 10px; */
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

        <div class="spacer"></div>
        <br>
        <br>
        <br>

        <!-- Form for Name, Phone, Email -->
        <h2 style="justify-content: center; align-items: center;text-align: center;">Chat Settings</h2>
        <div class="spacer"></div>
        <form id="settingsForm" action="settings.php?chat_id=<?php echo $chat_id; ?>" method="POST">

            <div id="messageBox" class="message-box" style="display:none;">
                <p id="messageText"></p>
            </div>
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
        <br><br>
        <div class="creator-user-details">
            <h2 style="justify-content: center; align-items: center;text-align: center;">Creator User Details</h2>
            <label for="username">Name</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($creatorUserData['name'] ?? '-- No Creator --'); ?>" readonly>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($creatorUserData['phone'] ?? 'N/A'); ?>" readonly>
        </div>
        <br><br>
        <hr>
        <div class="spacer"></div>
        <!-- connected user details -->
        <div class="connected-user-details">
            <h2 style="justify-content: center; align-items: center;text-align: center;">Connected User Details</h2>
            <br>
            <label for="username">Name:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($connectedUserData['name'] ?? '-- No Connection --'); ?>" readonly>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($connectedUserData['phone'] ?? 'N/A'); ?>" readonly>
        </div>
        <div class="spacer"></div>
        <hr>

        <hr><br>
        <div class="spacer"></div>
        <div class="permission-toggle">
            <span>Grant Permission To Add Entries:</span>
            <div class="toggle-switch" data-connection-id="<?php echo $chatData['connection_id']; ?>" data-permission="<?php echo $chatData['permission']; ?>">
                <div class="slider"></div>
            </div>
        </div>
        <br><br>
        <p style="font-size: 1.2rem; color: #555; margin-top: 5px; justify-content: center; align-items: center;text-align: center;">When the toggle is on, the connected user can add transactions.</p>
        <br><br>
        <hr>

        <div class="spacer"></div>
        <!-- OTP Section -->
        <h2 style="justify-content: center; align-items: center;text-align: center;">Generate and Fetch OTP</h2>
        <div class="spacer"></div>
        <p style="justify-content: center; align-items: center;text-align: center;">Generate and share the OTP with the user to connect securely and enable transaction management.</p>

        <br>

        <div class="form-group">
            <button id="generateOtpBtn" disabled>Generate OTP</button> <!-- Button is disabled by default -->
        </div>
        <br>
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
            <!-- <button id="fetchOtpBtn">Get Status</button>
            <button id="stopFetchBtn">Stop Status</button> -->
        </div>
        <hr>
        <hr>
        <div class="spacer"></div>
        <div class="btn" data-chat-id="<?php echo $chat_id; ?>">
            <h3>Danger Zone</h3>

            <!-- remove user code -->
            <!-- <div class="spacer"></div>
            <p>Remove Connected User</p>
            <br>
            <p>This will remove the connected user from this chat. They will no longer be able to access or view transactions.</p> -->

            <!-- <div class="spacer"></div>
            <button type="button" class="rembtn" id="removeUserBtn"><i class="fa-solid fa-trash"></i> Remove User</button> -->
            <!-- <br>
            <br><br>
            <hr> -->
            <!-- remove user code needs more work -->

            <br>
            <p>Deleting a chat is permanent. Please be sure before you do it.</p>
            <div class="spacer"></div>
            <button type="button" class="delbtn"><i class="fa-solid fa-trash"></i> Delete Chat</button>
        </div>

        <!-- <div id="removeUserPopup" class="popup-overlay">
            <div class="popup-content">
                <h3>Confirm Removal</h3>
                <p>Are you sure you want to remove this user from the chat?</p>
                <button id="confirmRemove" style="margin: 10px; margin-top:40px; padding: 10px 20px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer; width: 300px">Yes, Remove</button>
                <button id="cancelRemove" style="margin: 10px; padding: 10px 20px; background-color: grey; color: white; border: none; border-radius: 5px; cursor: pointer; width: 300px">Cancel</button>
            </div>
        </div> -->

        <!-- Popup Structure -->
        <div id="deletePopup" class="popup-overlay">
            <div class="popup-content">
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete this chat?</p>
                <button id="confirmDelete" style="margin-top:40px; padding: 10px 20px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer; width: 250px">Yes, Delete</button>
                <button id="cancelDelete" style="padding: 10px 20px; background-color: grey; color: white; border: none; border-radius: 5px; cursor: pointer; width: 250px">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            const connectionId = <?php echo json_encode($chatData['connection_id']); ?>;
            let chatIdToDelete = null;

            // Function to handle displaying messages
            function showMessage(message, type) {
                const bgColor = type === "success" ? "#28a745" : "#dc3545";
                $("#messageBox").css({
                    "background-color": bgColor,
                    "color": "#fff",
                    "padding": "10px"
                }).fadeIn();
                $("#messageText").text(message);

                setTimeout(function() {
                    $("#messageBox").fadeOut();
                    if (type === 'success') location.reload();
                }, 3000);

                setTimeout(function() {
                    $("#messageBox").fadeOut();
                    if (type === 'error') location.reload();
                }, 3000);
            }

            // Function to check connection status and enable OTP button
            function checkConnectionStatus() {
                if (!connectionId) {
                    alert('No connection ID found.');
                    return;
                }

                $.ajax({
                    url: 'fetch_otp_status.php',
                    type: 'GET',
                    data: {
                        connection_id: connectionId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#otp').val(response.otp);
                            $('#otp_status').val(response.otp_status);
                            $('#connection_status').val(response.connection_status);

                            // Enable button based on conditions
                            $('#generateOtpBtn').prop('disabled', !(response.connection_status === 'pending' && response.otp_status !== 'OTP Generated'));
                        } else {
                            $('#connection_status').val('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        $('#connection_status').val('An error occurred while fetching connection status.');
                    }
                });
            }

            // Function to generate OTP
            function generateOtp() {
                if (!connectionId) {
                    alert('No connection ID found.');
                    return;
                }

                $.ajax({
                    url: 'generate_otp.php',
                    type: 'POST',
                    data: {
                        connection_id: connectionId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#otp').val(response.otp);
                            $('#otp_status').val('OTP Generated');
                            $('#generateOtpBtn').prop('disabled', true);
                        } else {
                            $('#otp_status').val('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        $('#otp_status').val('An error occurred while generating OTP.');
                    }
                });
            }

            // Function to handle settings form submission
            $('#settingsForm').submit(function(event) {
                event.preventDefault();

                $.ajax({
                    url: 'settings.php?chat_id=<?php echo $chat_id; ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.message, 'success');
                        } else {
                            showMessage(response.message, 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred while updating settings.', 'error');
                    }
                });
            });

            // Function to handle chat deletion
            $(".delbtn").on("click", function() {
                chatIdToDelete = $(this).closest(".btn").data("chat-id");
                $("#deletePopup").fadeIn();
            });

            // Close delete popup
            $("#cancelDelete, #deletePopup").on("click", function(e) {
                if (e.target.id === "deletePopup" || e.target.id === "cancelDelete") {
                    $("#deletePopup").fadeOut();
                }
            });

            // Confirm delete chat
            $("#confirmDelete").on("click", function() {
                if (chatIdToDelete) {
                    $.ajax({
                        url: "delete_chat.php",
                        type: "POST",
                        data: {
                            chat_id: chatIdToDelete
                        },
                        success: function(response) {
                            try {
                                let result = JSON.parse(response);
                                if (result.success) {
                                    showMessage("Chat deleted successfully!", 'success');
                                    $(`.btn[data-chat-id="${chatIdToDelete}"]`).remove();
                                    // add reidrection to index.php page after 3 seconds
                                    setTimeout(() => {
                                        window.location.href = 'index.php';
                                    }, 3000);
                                } else {
                                    showMessage(result.message || "Failed to delete chat.", 'error');
                                }
                            } catch (e) {
                                showMessage("Invalid response from the server. Please try again later.", 'error');
                            }
                        },
                        error: function(xhr) {
                            showMessage('Error - ' + xhr.status + ': ' + xhr.statusText, 'error');
                        }
                    });
                }
                $("#deletePopup").fadeOut();
            });

            // Handle permission toggle
            $('.toggle-switch').each(function() {
                const $toggle = $(this);
                if ($toggle.data('permission') === 1) $toggle.addClass('active');
            });

            $('.toggle-switch').on('click', function() {
                const $toggle = $(this);
                const connectionId = $toggle.data('connection-id');
                const newPermission = $toggle.hasClass('active') ? 0 : 1;

                $toggle.toggleClass('active');

                $.ajax({
                    url: 'update_permission.php',
                    method: 'POST',
                    data: {
                        connection_id: connectionId,
                        permission: newPermission,
                        user_id: <?php echo $_SESSION['user_id']; ?>
                    },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            showMessage("Permission updated successfully!", 'success');
                        } else {
                            showMessage("Error: " + res.message, 'error');
                        }
                    },
                    error: function() {
                        showMessage('You do not have permission to update permissions. Please contact the administrator.', 'error');
                    }
                });
            });

            // Call connection status check initially and at intervals
            checkConnectionStatus();
            // setInterval(checkConnectionStatus, 5000); // Uncomment to check every 5 seconds

            // Handle OTP generation
            $('#generateOtpBtn').click(generateOtp);
        });
    </script>
</body>

</html>