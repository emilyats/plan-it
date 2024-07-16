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

if (!isset($_SESSION['id'])) {
    echo "User ID is not set in the session.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['id'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $employees = $_POST['employees'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO projects (user_id, name, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $status, $start_date, $end_date);

        if ($stmt->execute()) {
            $project_id = $stmt->insert_id;

            $assign_stmt = $conn->prepare("INSERT INTO project_employees (project_id, employee_id) VALUES (?, ?)");
            foreach ($employees as $employee_id) {
                $assign_stmt->bind_param("ii", $project_id, $employee_id);
                $assign_stmt->execute();
            }

            $conn->commit();
            header('Location: home.php');
            exit();
        } else {
            throw new Exception($stmt->error);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $stmt->close();
    if (isset($assign_stmt)) {
        $assign_stmt->close();
    }
}

$employees_result = $conn->query("SELECT * FROM users WHERE role='Employee'");
$conn->close();
?>
