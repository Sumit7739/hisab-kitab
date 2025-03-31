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
    $next_premium = !empty($_POST["next_premium"]) ? $_POST["next_premium"] : null; // Updated column name
    $notes = trim($_POST["notes"]) ?: null;
    $opening_date = !empty($_POST["opening_date"]) ? $_POST["opening_date"] : null;
    $premium_amount = !empty($_POST["premium_amount"]) ? $_POST["premium_amount"] : null;
    $dob = !empty($_POST["dob"]) ? $_POST["dob"] : null;
    $sb = !empty($_POST["sb"]) ? $_POST["sb"] : null; // Survival Benefit
    $maturity_date = !empty($_POST["maturity_date"]) ? $_POST["maturity_date"] : null;

    // Handle table_no: Convert empty string to NULL
    $table_no = trim($_POST["table_no"]);
    $table_no = ($table_no === '') ? null : $table_no;

    $amount_paid = !empty($_POST["amount_paid"]) ? $_POST["amount_paid"] : null;

    // Prepare and execute the UPDATE query
    $stmt = $conn->prepare("
        UPDATE clients 
        SET name=?, phone_number=?, policy_number=?, policy_type=?, last_payment_date=?, next_premium=?, notes=?, 
            opening_date=?, premium_amount=?, dob=?, sb=?, maturity_date=?, table_no=?, amount_paid=? 
        WHERE id=?
    ");
    $stmt->bind_param(
        "ssssssssdsssssi", // 14 fields + 1 ID = 15 variables
        $name,
        $phone_number,
        $policy_number,
        $policy_type,
        $last_payment_date,
        $next_premium,
        $notes,
        $opening_date,
        $premium_amount,
        $dob,
        $sb,
        $maturity_date,
        $table_no,
        $amount_paid,
        $client_id // ID is the 15th variable
    );
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
    <link rel="stylesheet" href="clientprofile.css">
    <style>
        .hamburger i {
            font-size: 24px;
            color: #333;
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
                <!-- Name -->
                <div class="info-field">
                    <label>Name:</label>
                    <span><?php echo htmlspecialchars($client['name'] ?? ''); ?></span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>" required>
                </div>

                <!-- Phone Number -->
                <div class="info-field">
                    <label>Phone Number:</label>
                    <span><?php echo htmlspecialchars($client['phone_number'] ?? 'Not provided'); ?></span>
                    <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($client['phone_number'] ?? ''); ?>">
                </div>

                <!-- Policy Number -->
                <div class="info-field">
                    <label>Policy Number:</label>
                    <span><?php echo htmlspecialchars($client['policy_number'] ?? ''); ?></span>
                    <input type="text" name="policy_number" value="<?php echo htmlspecialchars($client['policy_number'] ?? ''); ?>" required>
                </div>

                <!-- Policy Type -->
                <div class="info-field">
                    <label>Policy Type:</label>
                    <span><?php echo htmlspecialchars($client['policy_type'] ?? ''); ?></span>
                    <select name="policy_type" required>
                        <option value="quarterly" <?php echo ($client['policy_type'] === 'quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="half-yearly" <?php echo ($client['policy_type'] === 'half-yearly') ? 'selected' : ''; ?>>Half-Yearly</option>
                        <option value="yearly" <?php echo ($client['policy_type'] === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>

                <!-- Opening Date -->
                <div class="info-field">
                    <label>Opening Date:</label>
                    <span><?php echo htmlspecialchars($client['opening_date'] ?? 'Not set'); ?></span>
                    <input type="date" name="opening_date" value="<?php echo htmlspecialchars($client['opening_date'] ?? ''); ?>">
                </div>

                <!-- Premium Amount -->
                <div class="info-field">
                    <label>Premium Amount:</label>
                    <span><?php echo htmlspecialchars($client['premium_amount'] ?? 'Not set'); ?></span>
                    <input type="number" step="0.01" name="premium_amount" value="<?php echo htmlspecialchars($client['premium_amount'] ?? ''); ?>">
                </div>

                <!-- Date of Birth -->
                <div class="info-field">
                    <label>Date of Birth:</label>
                    <span><?php echo htmlspecialchars($client['dob'] ?? 'Not set'); ?></span>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($client['dob'] ?? ''); ?>">
                </div>

                <!-- Survival Benefit (SB) -->
                <div class="info-field">
                    <label>Survival Benefit (SB):</label>
                    <span><?php echo htmlspecialchars($client['sb'] ?? 'Not set'); ?></span>
                    <input type="number" step="0.01" name="sb" value="<?php echo htmlspecialchars($client['sb'] ?? ''); ?>">
                </div>

                <!-- Maturity Date -->
                <div class="info-field">
                    <label>Maturity Date:</label>
                    <span><?php echo htmlspecialchars($client['maturity_date'] ?? 'Not set'); ?></span>
                    <input type="date" name="maturity_date" value="<?php echo htmlspecialchars($client['maturity_date'] ?? ''); ?>">
                </div>

                <!-- Next Premium Date -->
                <div class="info-field">
                    <label>Next Premium Date:</label>
                    <span><?php echo htmlspecialchars($client['next_premium'] ?? 'Not set'); ?></span>
                    <input type="date" name="next_premium" value="<?php echo htmlspecialchars($client['next_premium'] ?? ''); ?>">
                </div>

                <!-- Table Number -->
                <div class="info-field">
                    <label>Table Number:</label>
                    <span><?php echo htmlspecialchars($client['table_no'] ?? 'Not set'); ?></span>
                    <input type="text" name="table_no" value="<?php echo htmlspecialchars($client['table_no'] ?? ''); ?>">
                </div>

                <!-- Amount Paid -->
                <div class="info-field">
                    <label>Amount Paid:</label>
                    <span><?php echo htmlspecialchars($client['amount_paid'] ?? 'Not set'); ?></span>
                    <input type="number" step="0.01" name="amount_paid" value="<?php echo htmlspecialchars($client['amount_paid'] ?? ''); ?>">
                </div>

                <!-- Last Payment Date -->
                <div class="info-field">
                    <label>Last Payment Date:</label>
                    <span><?php echo htmlspecialchars($client['last_payment_date'] ?? 'Not set'); ?></span>
                    <input type="date" name="last_payment_date" value="<?php echo htmlspecialchars($client['last_payment_date'] ?? ''); ?>">
                </div>

                <!-- Notes -->
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
        const editBtn = document.querySelector('.edit-btn');
        const saveBtn = document.querySelector('.save-btn');
        const cancelBtn = document.querySelector('.cancel-btn');
        const profileForm = document.getElementById('profileForm');
        const infoFields = document.querySelectorAll('.info-field');
        const message = document.getElementById('message');

        // Edit button functionality
        editBtn.addEventListener('click', () => {
            infoFields.forEach(field => {
                field.querySelector('span').style.display = 'none';
                field.querySelector('input, select, textarea').style.display = 'block';
            });
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        });

        // Cancel button functionality
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

        // Hide message after 3 seconds
        if (message.classList.contains('show')) {
            setTimeout(() => {
                message.classList.remove('show');
            }, 3000);
        }
    </script>
</body>

</html>