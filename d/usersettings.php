<?php
// Start session and include database connection
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Adjust this to your actual DB connection script

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You need to log in to access this page.";
    header("Refresh: 3; url=login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT name, email, phone, verification_status, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User data not found!";
    exit();
}

// Handle form submission for updating user settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = trim($_POST['password']);
    $hashed_password = $new_password ? password_hash($new_password, PASSWORD_DEFAULT) : null;

    // Update query
    $updateQuery = $hashed_password
        ? "UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?"
        : "UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?";

    $updateStmt = $conn->prepare($updateQuery);
    if ($hashed_password) {
        $updateStmt->bind_param("ssssi", $name, $email, $phone, $hashed_password, $user_id);
    } else {
        $updateStmt->bind_param("sssi", $name, $email, $phone, $user_id);
    }

    if ($updateStmt->execute()) {
        echo "<p>Settings updated successfully!</p>";
        header("Refresh: 3; url=usersettings.php");
    } else {
        echo "<p>Failed to update settings. Please try again later.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>User Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            background: #4caf50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #45a049;
        }

        .message {
            text-align: center;
            margin-top: 20px;
        }

        #hamburger i {
            color: #333;
        }

        .info-section {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .info-section p {
            margin-bottom: 5px;
        }

        .message {
            width: 96%;
            margin: auto;
            text-align: center;
            margin-top: 20px;
            background-color: #fbfbfb;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .message p {
            font-size: 18px;
            color: red;
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo">
                <h3>Hisab Kitab</h3>
            </div>
            <div class="hamburger" id="hamburger">
                <a href="index.php"><i class="fa-solid fa-home"></i></a>
            </div>
        </nav>
    </header>
    <div class="container">
        <h2>User Settings</h2>
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter new password (optional)">

            <button type="submit">Update Settings</button>
        </form>

        <!-- <div class="info-section">
            <h3>Additional Information</h3>
            <p><strong>Verification Status:</strong> <?= htmlspecialchars($user['verification_status']) ?></p>
            <p><strong>Account Created At:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
        </div> -->
    </div>
    <br><br>
    <div class="message">
        <p>More features coming soon! 🚀</p>
        <p>We are continuously improving to bring you a better experience. Stay tuned for updates!</p>
    </div>
</body>

</html>