<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
  header('Content-Type: application/json');
  echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
  exit();
}

include('../config.php');

if ($conn->connect_error) {
  header('Content-Type: application/json');
  echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
  exit();
}

// Check if selection is already completed
$user_id = $_SESSION['user_id'];
$checkStmt = $conn->prepare("SELECT name, selection_completed FROM users WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$row = $result->fetch_assoc();
$checkStmt->close();

if ($row['selection_completed'] == 1) {
  header('Location: welcome.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  if (isset($_POST['role']) && $_POST['role'] === 'user') {
    // Update for regular user
    $updateSql = "UPDATE users SET role = 'user', selection_completed = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
      echo json_encode(["status" => "success", "message" => "User role selected successfully."]);
    } else {
      echo json_encode(["status" => "error", "message" => "Failed to update user role."]);
    }
    $stmt->close();
    $conn->close();
    exit();
  }

  if (isset($_POST['agent_code'])) {
    $agent_code = trim($_POST['agent_code']);

    if (!preg_match('/^\d{7}[A-Za-z]$/', $agent_code)) {
      echo json_encode(["status" => "error", "message" => "Invalid agent code format. Must be 7 digits followed by 1 letter."]);
      exit();
    }

    // Check if agent code already exists in the system
    $checkCodeStmt = $conn->prepare("SELECT user_id FROM users WHERE agentcode = ?");
    $checkCodeStmt->bind_param("s", $agent_code);
    $checkCodeStmt->execute();
    $codeResult = $checkCodeStmt->get_result();

    if ($codeResult->num_rows > 0) {
      $checkCodeStmt->close();
      echo json_encode(["status" => "error", "message" => "This agent code is already taken by another user."]);
      exit();
    }
    $checkCodeStmt->close();

    // Check if this user already has an agent code
    $checkAgentStmt = $conn->prepare("SELECT agentcode FROM users WHERE user_id = ?");
    $checkAgentStmt->bind_param("i", $user_id);
    $checkAgentStmt->execute();
    $agentResult = $checkAgentStmt->get_result();
    $agentRow = $agentResult->fetch_assoc();
    $checkAgentStmt->close();

    if ($agentRow['agentcode'] !== null) {
      echo json_encode(["status" => "error", "message" => "You already have an agent code assigned."]);
      exit();
    }

    // Update for agent
    $updateSql = "UPDATE users SET agentcode = ?, role = 'admin', selection_completed = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $agent_code, $user_id);

    if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Agent code updated successfully. Your role is now Admin."]);
      } else {
        echo json_encode(["status" => "error", "message" => "Failed to update agent code."]);
      }
    } else {
      echo json_encode(["status" => "error", "message" => "Error updating agent code: " . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to Hisab Kitab</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      margin: 0;
    }

    .welcome-container {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 350px;
    }

    .welcome-container h1 {
      margin-bottom: 25px;
      color: #333;
    }

    .role-buttons {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .agent-code-input {
      margin: 20px 0;
      display: none;
    }

    .agent-code-input input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 5px;
      margin-bottom: 10px;
    }

    .confirm-buttons {
      display: none;
      justify-content: center;
      align-items: center;
    }

    button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin: 5px;
      transition: background 0.3s;
    }

    .user-btn {
      background: #4caf50;
      color: white;
    }

    .agent-btn {
      background: #2196f3;
      color: white;
    }

    .submit-btn {
      background: #e74c3c;
      color: white;
    }

    .cancel-btn {
      background: #34495e;
      color: white;
    }
  </style>
</head>

<body>
  <div class="welcome-container">
    <h1>Welcome <br>
      <span id="userName">"<?php echo htmlspecialchars($row['name']); ?>"</span>
      <br> to Hisab Kitab
    </h1>
    <div class="role-buttons" id="roleButtons">
      <button class="user-btn" onclick="selectRole('user')">I am a User</button>
      <button class="agent-btn" onclick="selectRole('agent')">I am an Agent</button>
    </div>
    <div class="agent-code-input" id="agentCodeInput">
      <input type="text" id="agentCode" placeholder="Enter Agent Code" maxlength="8" />
    </div>
    <div class="confirm-buttons" id="confirmButtons">
      <button class="submit-btn" onclick="submitRole()">Submit</button>
      <button class="cancel-btn" onclick="cancelSelection()">Cancel</button>
    </div>
  </div>

  <script>
    function showToast(message, type) {
      Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "center",
        backgroundColor: type === "success" ? "#4CAF50" : "#e74c3c",
      }).showToast();
    }

    let selectedRole = "";

    function selectRole(role) {
      selectedRole = role;
      document.getElementById("roleButtons").style.display = "none";
      if (role === "agent") {
        document.getElementById("agentCodeInput").style.display = "block";
        document.getElementById("confirmButtons").style.display = "flex";
      } else {
        submitRole();
      }
    }

    function submitRole() {
      if (selectedRole === "agent") {
        const agentCode = document.getElementById("agentCode").value.trim();
        const agentCodePattern = /^\d{7}[A-Za-z]$/;

        if (!agentCode) {
          showToast("Please enter your agent code.", "error");
          return;
        }

        if (!agentCodePattern.test(agentCode)) {
          showToast("Invalid agent code format. Must be 7 digits followed by 1 letter.", "error");
          return;
        }

        fetch(window.location.href, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `agent_code=${encodeURIComponent(agentCode)}`
          })
          .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
          })
          .then(data => {
            showToast(data.message, data.status);
            if (data.status === "success") {
              setTimeout(() => window.location.href = "welcome.php", 2000);
            }
          })
          .catch(error => showToast("An error occurred: " + error.message, "error"));
      } else if (selectedRole === "user") {
        fetch(window.location.href, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "role=user"
          })
          .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
          })
          .then(data => {
            showToast(data.message, data.status);
            if (data.status === "success") {
              setTimeout(() => window.location.href = "welcome.php", 2000);
            }
          })
          .catch(error => showToast("An error occurred: " + error.message, "error"));
      }
    }

    function cancelSelection() {
      selectedRole = "";
      document.getElementById("roleButtons").style.display = "flex";
      document.getElementById("agentCodeInput").style.display = "none";
      document.getElementById("confirmButtons").style.display = "none";
    }
  </script>
</body>

</html>