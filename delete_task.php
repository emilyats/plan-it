<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$task_id = $_GET['id'];
$project_id = $_GET['project_id'];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("DELETE FROM task_users WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete from task_users: " . $stmt->error);
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete task: " . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    header("Location: manage_task.php?project_id=$project_id");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
