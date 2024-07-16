<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectId = $_POST['project_id'];


    $projectResult = $conn->query("SELECT * FROM projects WHERE project_id = '$projectId'");
    $project = $projectResult->fetch_assoc();


    $totalTasksResult = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE project_id = '$projectId'");
    $totalTasks = $totalTasksResult->fetch_assoc()['total'];

    $completedTasksResult = $conn->query("SELECT COUNT(*) as completed FROM tasks WHERE project_id = '$projectId' AND status = 'Completed'");
    $completedTasks = $completedTasksResult->fetch_assoc()['completed'];


    $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
    $progress = number_format($progress, 2);

    $tasksResult = $conn->query("SELECT t.*, GROUP_CONCAT(u.username SEPARATOR ', ') AS assigned_users 
                                FROM tasks t 
                                LEFT JOIN task_users tu ON t.task_id = tu.task_id 
                                LEFT JOIN users u ON tu.user_id = u.id 
                                WHERE t.project_id = '$projectId'
                                GROUP BY t.task_id");

    $tasks = [];
    while ($task = $tasksResult->fetch_assoc()) {
        $tasks[] = $task;
    }

    $reportContent = "Project Report for " . $project['name'] . "\n";
    $reportContent .= "----------------------------------------\n";
    $reportContent .= "Project Name: " . $project['name'] . "\n";
    $reportContent .= "Start Date: " . $project['start_date'] . "\n";
    $reportContent .= "End Date: " . $project['end_date'] . "\n";
    $reportContent .= "Status: " . $project['status'] . "\n";
    $reportContent .= "Progress: " . $progress . "%\n";
    $reportContent .= "----------------------------------------\n\n";
    
    $reportContent .="\n";
    $reportContent .= "----------------------------------------\n";
    $reportContent .= "Tasks:\n";
    $reportContent .= "----------------------------------------\n";
    foreach ($tasks as $task) {
        $reportContent .= "Task: " . $task['title'] . "\n";
        $reportContent .= "Assigned to: " . ($task['assigned_users'] ? $task['assigned_users'] : "Not Assigned") . "\n";
        $reportContent .= "Status: " . $task['status'] . "\n";
        $reportContent .= "----------------------------------------\n";
    }


    $fileName = 'Project_Report_' . $project['name'] . '.txt';
    file_put_contents($fileName, $reportContent);


    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($fileName));
    readfile($fileName);


    unlink($fileName);
}
?>
