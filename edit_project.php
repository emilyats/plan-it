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
    $project_id = $_POST['project_id'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $employees = $_POST['employees'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, start_date = ?, end_date = ? WHERE project_id = ?");
        $stmt->bind_param("ssssi", $name, $status, $start_date, $end_date, $project_id);

        if ($stmt->execute()) {
            $conn->query("DELETE FROM project_employees WHERE project_id = $project_id");

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

$conn->close();
?>
