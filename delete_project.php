<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['logged-in']) || $_SESSION['role'] !== 'Project Manager') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $conn->real_escape_string($_POST['project_id']);

    $conn->query("DELETE FROM task_users WHERE task_id IN (SELECT task_id FROM tasks WHERE project_id = '$project_id')");

    $conn->query("DELETE FROM tasks WHERE project_id = '$project_id'");

    $conn->query("DELETE FROM project_employees WHERE project_id = '$project_id'");

    $conn->query("DELETE FROM projects WHERE project_id = '$project_id'");

    header('Location: index.php');
    exit();
}
?>
