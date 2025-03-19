<?php
// Database Connection
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

// Check if user is admin (assuming you have a role column in your users table)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check Connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    $name = trim($_POST["name"]);
    $phone_number = trim($_POST["phone_number"]);
    $policy_number = trim($_POST["policy_number"]);
    $policy_type = trim($_POST["policy_type"]);
    $last_payment_date = !empty($_POST["last_payment_date"]) ? $_POST["last_payment_date"] : null;
    $next_due_date = !empty($_POST["next_due_date"]) ? $_POST["next_due_date"] : null;
    $notes = trim($_POST["notes"]);

    if (empty($name) || empty($policy_number) || empty($policy_type)) {
        echo json_encode(["status" => "error", "message" => "Name, Policy Number, and Policy Type are required!"]);
        exit();
    }

    if ($phone_number === "") {
        $phone_number = null;
    }

    $checkStmt = $conn->prepare("SELECT id FROM clients WHERE policy_number = ?");
    $checkStmt->bind_param("s", $policy_number);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Policy Number already exists!"]);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO clients (name, phone_number, policy_number, policy_type, last_payment_date, next_due_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $phone_number, $policy_number, $policy_type, $last_payment_date, $next_due_date, $notes);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Client added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding client: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// Fetch client data for display
$clients_result = $conn->query("SELECT id, name, policy_number FROM clients");
$clients = [];
while ($row = $clients_result->fetch_assoc()) {
    $clients[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        .search-bar {
            margin-top: 15px;
            /* border: 1px solid; */
        }

        #searchInput{
            width: 100%;
        }

        a {
            text-decoration: none;
            color: black;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            width: 100%;
            height: 50px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .dock-item .btn {
            margin-top: -20px;
        }

        #messageBox {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 9999;
            min-width: 300px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        #messageBox.show {
            opacity: 1;
        }

        .success {
            background-color: #28a745;
            color: white;
        }

        .error {
            background-color: #dc3545;
            color: white;
        }

        .client-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            padding: 0 0px;
            /* border: 1px solid; */
        }

        .client-card {
            display: flex;
            /* gap: 50px; */
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .client-card p {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo">
                <p>Welcome,</p>
            </div>
            <div class="hamburger" id="hamburger">
                <i class="fa fa-bars"></i>
            </div>
            <ul class="menu" id="menu">
                <li><a href="dashboard.php"><i class="fa fa-home" id="active"></i> Dashboard</a></li>
                <li><a href="clients.html"><i class="fa fa-users"></i> Clients</a></li>
                <li><a href="index.php"><i class="fa fa-exchange"></i> Transactions</a></li>
                <li><a href="usersettings.php"><i class="fa fa-cog"></i> Settings</a></li>
                <li><a href="comingsoon.html"><i class="fa fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php" class="btn-logout"><i class="fa fa-sign-out"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="messageBox"></div>

    <div class="container">
        <div class="stats">
            <div class="card"><i class="fa fa-users"></i> Total Clients:
                <?php
                $count_result = $conn->query("SELECT COUNT(*) as total FROM clients");
                $count = $count_result->fetch_assoc();
                echo $count['total'];
                ?>
            </div>
            <div class="dock">
                <div class="dock-item">
                    <button type="button" class="btn" id="addClientBtn">
                        <i class="fas fa-user-plus"></i> ADD+
                    </button>
                </div>
            </div>
        </div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by Name or Policy Number..." />
        </div>

        <div class="client-cards" id="clientCards">
            <?php foreach ($clients as $client): ?>
                <a href="clientprofile.php?id=<?php echo htmlspecialchars($client['id']); ?>" class="client-card">
                    <!-- <p><strong>ID:</strong> <?php echo htmlspecialchars($client['id']); ?></p> -->
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
                    <p><strong>Policy:</strong> <?php echo htmlspecialchars($client['policy_number']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="popup" id="clientPopup">
        <div class="popup-content">
            <span class="close" id="closePopup">×</span>
            <h2>Add New Client</h2>
            <form id="clientForm" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required />
                <label for="phone_number">Phone Number:</label>
                <input type="tel" id="phone_number" name="phone_number" />
                <label for="policy_number">Policy Number:</label>
                <input type="text" id="policy_number" name="policy_number" required maxlength="9"/>
                <label for="policy_type">Policy Type:</label>
                <select id="policy_type" name="policy_type" required>
                    <option value="quarterly">Quarterly</option>
                    <option value="half-yearly">Half-Yearly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <label for="last_payment_date">Last Payment Date:</label>
                <input type="date" id="last_payment_date" name="last_payment_date" />
                <label for="next_due_date">Next Due Date:</label>
                <input type="date" id="next_due_date" name="next_due_date" />
                <label for="notes">Notes:</label>
                <textarea id="notes" name="notes" rows="4"></textarea>
                <div>
                    <button type="submit" class="submit-btn">Add Client</button>
                    <button type="button" class="close-btn" id="cancelPopup">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store initial client data from PHP
        const clients = <?php echo json_encode($clients); ?>;

        const menu = document.getElementById("menu");
        const hamburger = document.getElementById("hamburger");
        const addClientBtn = document.getElementById("addClientBtn");
        const clientPopup = document.getElementById("clientPopup");
        const closePopup = document.getElementById("closePopup");
        const cancelPopup = document.getElementById("cancelPopup");
        const clientForm = document.getElementById("clientForm");
        const messageBox = document.getElementById("messageBox");
        const searchInput = document.getElementById("searchInput");
        const clientCards = document.getElementById("clientCards");

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

        addClientBtn.addEventListener("click", () => {
            clientPopup.style.display = "block";
        });

        closePopup.addEventListener("click", () => {
            clientPopup.style.display = "none";
            clientForm.reset();
        });

        cancelPopup.addEventListener("click", () => {
            clientPopup.style.display = "none";
            clientForm.reset();
        });

        clientPopup.addEventListener("click", (event) => {
            if (event.target === clientPopup) {
                clientPopup.style.display = "none";
                clientForm.reset();
            }
        });

        clientForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch("dashboard.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    messageBox.innerHTML = data.message;
                    messageBox.className = data.status;
                    messageBox.classList.add('show');
                    clientPopup.style.display = "none";
                    clientForm.reset();

                    setTimeout(() => {
                        messageBox.classList.remove('show');
                        if (data.status === "success") {
                            location.reload();
                        }
                    }, 3000);
                })
                .catch(error => {
                    console.error("Error:", error);
                    messageBox.innerHTML = "An error occurred. Please try again.";
                    messageBox.className = "error";
                    messageBox.classList.add('show');
                    setTimeout(() => {
                        messageBox.classList.remove('show');
                    }, 3000);
                });
        });

        // Search functionality
        searchInput.addEventListener("input", function(e) {
            const searchTerm = e.target.value.toLowerCase();
            clientCards.innerHTML = ""; // Clear current cards

            const filteredClients = clients.filter(client =>
                client.name.toLowerCase().includes(searchTerm) ||
                client.policy_number.toLowerCase().includes(searchTerm)
            );

            filteredClients.forEach(client => {
                const card = document.createElement("a");
                card.href = `clientprofile.php?id=${client.id}`;
                card.className = "client-card";
                card.innerHTML = `
                    <p><strong>Name:</strong> ${client.name}</p>
                    <p><strong>Policy:</strong> ${client.policy_number}</p>
                `;
                clientCards.appendChild(card);
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>