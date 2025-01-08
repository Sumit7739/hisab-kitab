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
    header("Location: login.php");
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
    // Query to join chats and connections table and fetch connection_id
    $chatQuery = "
        SELECT c.chat_name, c.phone_number, c.email, con.connection_id 
        FROM chats c
        JOIN connections con ON c.chat_id = con.chat_id
        WHERE c.chat_id = ?
    ";

    $stmt = $conn->prepare($chatQuery);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fetch chat details and connection ID
$chatData = getChatDetailsAndConnectionId($conn, $chat_id);

// If no chat data is found, show an error and exit
if (!$chatData) {
    die("No chat data found!");
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
            /* background-color: #f4f7f6; */
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #B4C5DB;
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
            font-size: 16px;
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


        .userinfo p{
           font-size: 22px;
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

        <!-- OTP Section -->
        <h3>Generate and Fetch OTP</h3>
        <p>Share OTP to Connect</p>

        <div class="form-group">
            <button id="generateOtpBtn">Generate OTP</button>
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

    </div>

    <script>
        $(document).ready(function() {
            // Get the connection_id from PHP and pass it to JavaScript
            const connectionId = <?php echo json_encode($chatData['connection_id']); ?>;

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
                        } else {
                            $('#otp_status').val('Error: ' + response.message); // Display error in OTP status
                        }
                    },
                    error: function() {
                        $('#otp_status').val('An error occurred while generating OTP.');
                    }
                });
            });

            // Function to fetch OTP status at intervals
            function fetchOtpStatus() {
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
                            $('#connection_status').val(response.connection_status); // Display fetched connection status
                        } else {
                            $('#connection_status').val('Error: ' + response.message); // Display error in connection status
                        }
                    },
                    error: function() {
                        $('#connection_status').val('An error occurred while fetching connection status.');
                    }
                });
            }

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

            // Set the interval to fetch OTP status every 5 seconds (5000 milliseconds)
            const fetchInterval = setInterval(fetchOtpStatus, 5000);

            // Optional: If you want to stop fetching on some event, you can use clearInterval
            // For example, stop after the user manually clicks a button or any other condition:
            // $('#stopFetchBtn').click(function () {
            //     clearInterval(fetchInterval); // Stops the interval
            // });
        });
    </script>

</body>

</html>