<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$task_id = $_GET['id'];
$task = $conn->query("SELECT * FROM tasks WHERE id = $task_id")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $description, $priority, $due_date, $task_id);

    if ($stmt->execute()) {
        header("Location: manage_task.php?project_id=" . $task['project_id']);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Task</title>
</head>
<body>
    <h2>Edit Task</h2>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=$task_id"; ?>">
        Task Title: <input type="text" name="title" value="<?php echo $task['title']; ?>" required><br><br>
        Description: <textarea name="description" required><?php echo $task['description']; ?></textarea><br><br>
        Priority: 
        <select name="priority">
            <option value="Low" <?php if ($task['priority'] == 'Low') echo 'selected'; ?>>Low</option>
            <option value="Medium" <?php if ($task['priority'] == 'Medium') echo 'selected'; ?>>Medium</option>
            <option value="High" <?php if ($task['priority'] == 'High') echo 'selected'; ?>>High</option>
        </select><br><br>
        Due Date: <input type="date" name="due_date" value="<?php echo $task['due_date']; ?>" required><br><br>
        <input type="submit" value="Update Task">
    </form>
</body>
</html>
