<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$task_id = $_GET['id'];
$project_id = $_GET['project_id'];

$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$stmt->close();

header("Location: manage_task.php?project_id=$project_id");
exit();
?>
