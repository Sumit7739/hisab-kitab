<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    include '../config.php';

    $connectionId = intval($_POST['connection_id']); // Get the connection_id
    $permission = intval($_POST['permission']); // Ensure it's 0 or 1
    $userId = intval($_POST['user_id']); // Get the logged-in user's ID

    // Check if the user is the creator of the connection using user_id_1 in connections table
    $checkCreatorQuery = "SELECT user_id_1 FROM connections WHERE connection_id = ?";
    $stmt = $conn->prepare($checkCreatorQuery);
    $stmt->bind_param("i", $connectionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if the logged-in user is the creator (user_id_1)
        if ($row['user_id_1'] != $userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Only the chat creator can modify permissions.']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Connection not found.']);
        exit();
    }

    // Update the permission in the database
    $query = "UPDATE connections SET permission = ? WHERE connection_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $permission, $connectionId); // Use connection_id to find the row

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
