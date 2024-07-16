<?php
require_once "db_connect.php";

if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    $stmt = $conn->prepare("SELECT user_id FROM task_users WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_ids = [];
    while ($row = $result->fetch_assoc()) {
        $user_ids[] = $row['user_id'];
    }

    echo json_encode($user_ids);

    $stmt->close();
}

$conn->close();
?>
