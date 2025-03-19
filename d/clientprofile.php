<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$client_id = $_GET['id'];

// Handle form submission for updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $phone_number = trim($_POST["phone_number"]) ?: null;
    $policy_number = trim($_POST["policy_number"]);
    $policy_type = trim($_POST["policy_type"]);
    $last_payment_date = !empty($_POST["last_payment_date"]) ? $_POST["last_payment_date"] : null;
    $next_due_date = !empty($_POST["next_due_date"]) ? $_POST["next_due_date"] : null;
    $notes = trim($_POST["notes"]) ?: null;

    $stmt = $conn->prepare("UPDATE clients SET name=?, phone_number=?, policy_number=?, policy_type=?, last_payment_date=?, next_due_date=?, notes=? WHERE id=?");
    $stmt->bind_param("sssssssi", $name, $phone_number, $policy_number, $policy_type, $last_payment_date, $next_due_date, $notes, $client_id);

    if ($stmt->execute()) {
        $success_message = "Client updated successfully!";
    } else {
        $error_message = "Error updating client: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch client data
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
        }

        .logo p {
            font-weight: 600;
            color: var(--dark);
        }

        .menu {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        .menu li a {
            color: var(--dark);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .menu li a:hover,
        .menu li a#active {
            background: var(--primary);
            color: white;
        }

        .hamburger {
            display: none;
        }

        .profile-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--light);
            padding-bottom: 15px;
        }

        .profile-header h2 {
            color: var(--dark);
            font-size: 24px;
            font-weight: 600;
        }

        .profile-info {
            display: grid;
            gap: 15px;
        }

        .info-field {
            display: flex;
            flex-direction: column;
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .info-field label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .info-field span,
        .info-field input,
        .info-field select,
        .info-field textarea {
            color: var(--secondary);
            font-size: 16px;
        }

        .info-field input,
        .info-field select,
        .info-field textarea {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px;
            background: white;
            width: 100%;
            display: none;
        }

        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
        }

        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .save-btn {
            background: var(--success);
            color: white;
            display: none;
        }

        .cancel-btn {
            background: var(--danger);
            color: white;
            display: none;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .message.show {
            opacity: 1;
        }

        .success {
            background: var(--success);
        }

        .error {
            background: var(--danger);
        }

        .back-button {
            font-size: 24px;
        }

        @media (max-width: 768px) {
            .menu {
                display: none;
            }

            .hamburger {
                display: block;
            }

            .menu.show {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background: white;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo">
                <p>Hisab Kitab</p>
            </div>
            <div class="hamburger" id="hamburger">
                <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
            </div>
            <!-- <ul class="menu" id="menu">
                <li><a href="dashboard.php"><i class="fa fa-home" id="active"></i> Dashboard</a></li>
                <li><a href="clients.html"><i class="fa fa-users"></i> Clients</a></li>
                <li><a href="index.php"><i class="fa fa-exchange"></i> Transactions</a></li>
                <li><a href="usersettings.php"><i class="fa fa-cog"></i> Settings</a></li>
                <li><a href="comingsoon.html"><i class="fa fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php" class="btn-logout"><i class="fa fa-sign-out"></i> Logout</a></li>
            </ul> -->
        </nav>
    </header>

    <div id="message" class="message <?php echo isset($success_message) ? 'success show' : (isset($error_message) ? 'error show' : ''); ?>">
        <?php echo isset($success_message) ? $success_message : (isset($error_message) ? $error_message : ''); ?>
    </div>

    <div class="profile-container">
        <form id="profileForm" method="POST">
            <div class="profile-header">
                <h2>Client Profile</h2>

            </div>
            <div class="profile-info">
                <div class="info-field">
                    <label>Name:</label>
                    <span><?php echo htmlspecialchars($client['name'] ?? ''); ?></span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>" required>
                </div>
                <div class="info-field">
                    <label>Phone Number:</label>
                    <span><?php echo htmlspecialchars($client['phone_number'] ?? 'Not provided'); ?></span>
                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($client['phone_number'] ?? ''); ?>">
                </div>
                <div class="info-field">
                    <label>Policy Number:</label>
                    <span><?php echo htmlspecialchars($client['policy_number'] ?? ''); ?></span>
                    <input type="text" name="policy_number" value="<?php echo htmlspecialchars($client['policy_number'] ?? ''); ?>" required>
                </div>
                <div class="info-field">
                    <label>Policy Type:</label>
                    <span><?php echo htmlspecialchars($client['policy_type'] ?? ''); ?></span>
                    <select name="policy_type" required>
                        <option value="quarterly" <?php echo ($client['policy_type'] === 'quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="half-yearly" <?php echo ($client['policy_type'] === 'half-yearly') ? 'selected' : ''; ?>>Half-Yearly</option>
                        <option value="yearly" <?php echo ($client['policy_type'] === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <div class="info-field">
                    <label>Last Payment Date:</label>
                    <span><?php echo htmlspecialchars($client['last_payment_date'] ?? 'Not set'); ?></span>
                    <input type="date" name="last_payment_date" value="<?php echo htmlspecialchars($client['last_payment_date'] ?? ''); ?>">
                </div>
                <div class="info-field">
                    <label>Next Due Date:</label>
                    <span><?php echo htmlspecialchars($client['last_payment_date'] ?? 'Not set'); ?></span>
                    <input type="date" name="next_due_date" value="<?php echo htmlspecialchars($client['next_due_date'] ?? ''); ?>">
                </div>
                <div class="info-field">
                    <label>Notes:</label>
                    <span><?php echo htmlspecialchars($client['notes'] ?? 'No notes'); ?></span>
                    <textarea name="notes"><?php echo htmlspecialchars($client['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="buttons">
                <button type="button" class="edit-btn">Edit</button>
                <button type="submit" class="save-btn">Save</button>
                <button type="button" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        const menu = document.getElementById("menu");
        const hamburger = document.getElementById("hamburger");
        const editBtn = document.querySelector('.edit-btn');
        const saveBtn = document.querySelector('.save-btn');
        const cancelBtn = document.querySelector('.cancel-btn');
        const profileForm = document.getElementById('profileForm');
        const infoFields = document.querySelectorAll('.info-field');
        const message = document.getElementById('message');

        hamburger.addEventListener("click", (event) => {
            event.stopPropagation();
            menu.classList.toggle("show");
            hamburger.classList.toggle("active");
        });

        document.addEventListener("click", (event) => {
            if (!hamburger.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.remove("show");
                hamburger.classList.remove("active");
            }
        });

        editBtn.addEventListener('click', () => {
            infoFields.forEach(field => {
                field.querySelector('span').style.display = 'none';
                field.querySelector('input, select, textarea').style.display = 'block';
            });
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        });

        cancelBtn.addEventListener('click', () => {
            infoFields.forEach(field => {
                field.querySelector('span').style.display = 'block';
                field.querySelector('input, select, textarea').style.display = 'none';
            });
            editBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            profileForm.reset();
        });

        profileForm.addEventListener('submit', (e) => {
            // Form submission handled by PHP above
        });

        // Hide message after 3 seconds
        if (message.classList.contains('show')) {
            setTimeout(() => {
                message.classList.remove('show');
            }, 3000);
        }
    </script>
</body>

</html>