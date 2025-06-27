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

$message = null; // Initialize message variable

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
        $message = ['text' => 'Settings updated successfully!', 'type' => 'success'];
        // Re-fetch user data to display updated values
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        }
    } else {
        $message = ['text' => 'Failed to update settings. Please try again.', 'type' => 'error'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>User Settings</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        :root {
            --md-primary-color: #85dfe7;
            /* Deep Purple */
            --md-primary-dark: rgb(16, 107, 115);
            --md-accent-color: #85dfe7;
            /* Teal */
            --md-background-color: #f5f5f5;
            /* Lighter grey for background */
            --md-surface-color: #ffffff;
            /* White for cards/containers */
            --md-text-color: #212121;
            /* Dark grey for primary text */
            --md-light-text-color: #757575;
            /* Medium grey for secondary text */
            --md-shadow-1: 0 2px 4px rgba(0, 0, 0, 0.1);
            --md-shadow-2: 0 4px 8px rgba(0, 0, 0, 0.15);
            --md-shadow-3: 0 4px 8px rgba(0, 0, 0, 0.25);
            --md-border-radius: 8px;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--md-background-color);
            margin: 0;
            padding: 0;
            color: var(--md-text-color);
            line-height: 1.6;
        }

        header {
            background-color: var(--md-primary-color);
            color: black;
            padding: 15px 20px;
            box-shadow: var(--md-shadow-1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            /* Adjust header width */
            margin: 0 auto;
        }

        .logo h3 {
            margin: 0;
            font-size: 1.1em;
            font-weight: 500;
        }

        .hamburger a {
            color: white;
            /* Home icon color */
            font-size: 1.4em;
            transition: color 0.3s ease;
        }

        .hamburger a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* --- */
        .container {
            max-width: 500px;
            width: 95%;
            /* Slightly narrower for focus */
            margin: 20px auto;
            /* Reduced margin for a snugger fit */
            background: var(--md-surface-color);
            padding: 30px;
            /* More padding */
            border-radius: var(--md-border-radius);
            box-shadow: var(--md-shadow-2);
            /* More pronounced shadow */
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            /* More space below heading */
            color: var(--md-primary-dark);
            /* Using a darker shade of primary for heading */
            font-weight: 500;
            /* Slightly bolder */
            font-size: 1.8em;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            /* Spacing between form elements */
        }

        label {
            font-weight: 500;
            /* Medium font-weight for labels */
            color: var(--md-light-text-color);
            font-size: 0.9em;
            /* Slightly smaller labels */
            margin-bottom: -5px;
            /* Pull labels closer to inputs */
            display: block;
            /* Ensures labels take full width */
        }

        input {
            padding: 12px 15px;
            /* More padding for inputs */
            border: 1px solid #e0e0e0;
            /* Lighter border */
            border-radius: 4px;
            /* Slightly less rounded */
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            /* Smooth transitions */
            color: var(--md-text-color);
        }

        input:focus {
            border-color: var(--md-primary-color);
            /* Primary color border on focus */
            box-shadow: 0 0 0 3px rgba(98, 0, 238, 0.1);
            /* Subtle glow on focus */
            outline: none;
            /* Remove default outline */
        }

        button {
            margin-top: 25px;
            /* More space above button */
            padding: 12px 20px;
            /* More padding for a bigger button */
            background: #fff;
            color: #000;
            /* White text */
            border: none;
            border-radius: 24px;
            /* Slightly less rounded */
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 500;
            letter-spacing: 0.5px;
            /* Slight letter spacing */
            text-transform: uppercase;
            /* Uppercase text for buttons */
            box-shadow: var(--md-shadow-3);
            /* Subtle shadow for button */
            transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.1s ease;
            /* Smooth transitions */
        }

        button:hover {
            background: #ccc;
            /* Darker primary on hover */
            box-shadow: var(--md-shadow-2);
            /* More pronounced shadow on hover */
        }

        button:active {
            transform: translateY(1px);
            /* Slight press effect */
        }

        .message {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95em;
            color: var(--md-light-text-color);
        }

        .dock2 {
            margin: 0 auto;
            justify-content: center;
            align-items: center;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            position: absolute;
            bottom: 0;
            background: rgb(255, 255, 255);
            padding: 0px;
            border: 1px solid #ddd;
            border-radius: 30px;
            box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
        }

        .dock2 ul {
            list-style: none;
            padding: 0;
            margin: 0 auto;
            display: flex;

            justify-content: space-around;

            align-items: center;
        }

        .dock2 ul li {
            margin: 10px;
        }

        .dock2 ul li a {
            color: rgb(0, 0, 0);
            text-decoration: none;
        }

        .dock2 .menu i {
            /* background-color: rgb(48, 48, 48); */
            font-size: 18px;
        }

        .dock2 .menu .active2 {
            background-color: rgb(122, 122, 122);
            padding: 15px;
            border-radius: 50%;
            color: #fff;
        }

        /* Theme Switcher Toggle */
        .theme-switch-wrapper {
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            padding: 20px;
            width: 90%;
            border-radius: 20px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .theme-switch-wrapper em {
            margin-left: 10px;
            font-size: 1rem;
        }

        .theme-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: var(--md-primary-color);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* Dark Theme */
        body.dark-theme {
            background: #1a1a1a;
            color: #f0f0f0;
        }

        body.dark-theme header {
            background-color: #2c2c2c;
        }

        body.dark-theme .logo h3 {
            color: #f0f0f0;
        }

        body.dark-theme .hamburger a {
            color: #f0f0f0;
        }

        body.dark-theme .container {
            background: #2c2c2c;
        }

        body.dark-theme h2 {
            color: var(--md-accent-color);
        }

        body.dark-theme label {
            color: #bbb;
        }

        body.dark-theme input {
            background-color: #3a3a3a;
            border-color: #555;
            color: #f0f0f0;
        }

        body.dark-theme button {
            background: #3a3a3a;
            color: #f0f0f0;
        }

        body.dark-theme button:hover {
            background: #4a4a4a;
        }

        body.dark-theme .message {
            color: #bbb;
        }

        body.dark-theme .dock2 {
            background: #2c2c2c;
            border-color: #444;
        }

        body.dark-theme .dock2 ul li a {
            color: #f0f0f0;
        }

        body.dark-theme .dock2 .menu .active2 {
            background-color: #555;
        }

        body.dark-theme .dock2 .menu i {
            color: #f0f0f0;
        }

        body.dark-theme .theme-switch-wrapper {
            background-color: #2c2c2c;
        }

        body.dark-theme .theme-switch-wrapper em {
            color: #f0f0f0;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: var(--md-border-radius);
            color: #fff;
            font-size: 1em;
            z-index: 1000;
            box-shadow: var(--md-shadow-3);
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: translateY(-20px);
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success {
            background-color: #28a745;
        }

        .toast.error {
            background-color: #dc3545;
        }

        body.dark-theme .toast.success { background-color: #3aa868; }
        body.dark-theme .toast.error { background-color: #e57373; }
    </style>
</head>

<body>
    <div id="toast-notification" class="toast"></div>
    <!-- <header>
        <nav>
            <div class="logo">
                <h3>Hisab-Kitab</h3>
            </div>
        </nav>
    </header> -->
    <div class="container">
        <h2>User Settings</h2>
        <form method="POST">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

            <label for="password">New Password</label>
            <input type="password" id="password" name="password" placeholder="Enter new password (optional)">

            <button type="submit">Update Settings</button>
        </form>
    </div>

    <!-- add a toggle here to on or off dark mode -->
    <div class="theme-switch-wrapper">
        <label class="theme-switch" for="checkbox">
            <input type="checkbox" id="checkbox" />
            <div class="slider round"></div>
        </label>
        <em id="theme-toggle-text">Enable Dark Mode!</em>
    </div>

    <br>

    <div class="dock2">
        <ul class="menu" id="menu">
            <li><a href="dashboard.php"><i class="fa fa-home " id="active"></i></a></li>
            <!-- <li><a href="clients.html"><i class="fa fa-users"></i> Clients</a></li> -->
            <li><a href="index.php"><i class="fa fa-exchange "></i> </a></li>
            <li><a href="usersettings.php"><i class="fa fa-cog active2"></i> </a></li>
            <li><a href="notification.html"><i class="fa fa-bell"></i> </a></li>
            <li><a href="logout.php" class="btn-logout"><i class="fa fa-sign-out"></i> </a></li>
        </ul>
    </div>

    <script>
        <?php if ($message) : ?>
            (function() {
                const toast = document.getElementById('toast-notification');
                toast.textContent = '<?= addslashes($message['text']) ?>';
                toast.classList.add('<?= $message['type'] ?>', 'show');

                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000); // Hide after 3 seconds
            })();
        <?php endif; ?>

        const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
        const themeToggleText = document.getElementById('theme-toggle-text');

        function switchTheme(e) {
            if (e.target.checked) {
                document.body.classList.add('dark-theme');
                localStorage.setItem('theme', 'dark');
                themeToggleText.textContent = 'Enable Light Mode!';
            } else {
                document.body.classList.remove('dark-theme');
                localStorage.setItem('theme', 'light');
                themeToggleText.textContent = 'Enable Dark Mode!';
            }
        }

        toggleSwitch.addEventListener('change', switchTheme, false);

        // Apply theme on initial load
        function applyTheme() {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-theme');
                toggleSwitch.checked = true;
                themeToggleText.textContent = 'Enable Light Mode!';
            } else {
                document.body.classList.remove('dark-theme');
                toggleSwitch.checked = false;
                themeToggleText.textContent = 'Enable Dark Mode!';
            }
        }
        applyTheme();
    </script>
</body>

</html>