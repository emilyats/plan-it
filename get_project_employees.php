<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

if ($_SESSION['role'] !== 'Project Manager') {
    header('Location: unauthorized.php');
    exit();
}

if (!isset($_GET['project_id'])) {
    echo "Project ID is not set.";
    exit();
}

$project_id = $_GET['project_id'];

$employees = [];
$result = $conn->query("SELECT employee_id FROM project_employees WHERE project_id = $project_id");

while ($row = $result->fetch_assoc()) {
    $employees[] = (int)$row['employee_id'];
}

echo json_encode($employees);

$conn->close();
?>
