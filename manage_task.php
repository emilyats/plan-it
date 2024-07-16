<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$project_id = $_GET['project_id'];
$tasks = $conn->query("SELECT * FROM tasks WHERE project_id = $project_id");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description, priority, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $project_id, $title, $description, $priority, $due_date);

    if ($stmt->execute()) {
        header("Location: manage_task.php?project_id=$project_id");
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
    <title>Manage Tasks</title>
</head>
<body>
    <h2>Manage Tasks</h2>
    <a href="project_list.php">Back to Project List</a>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?project_id=$project_id"; ?>">
        Task Title: <input type="text" name="title" required><br><br>
        Description: <textarea name="description" required></textarea><br><br>
        Priority: 
        <select name="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select><br><br>
        Due Date: <input type="date" name="due_date" required><br><br>
        <input type="submit" value="Create Task">
    </form>
    <h3>Task List</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Priority</th>
            <th>Due Date</th>
            <th>Action</th>
        </tr>
        <?php while ($task = $tasks->fetch_assoc()) : ?>
            <tr>
                <td><?php echo $task['title']; ?></td>
                <td><?php echo $task['description']; ?></td>
                <td><?php echo $task['priority']; ?></td>
                <td><?php echo $task['due_date']; ?></td>
                <td>
                    <a href="edit_task.php?id=<?php echo $task['id']; ?>">Edit</a>
                    <a href="delete_task.php?id=<?php echo $task['id']; ?>&project_id=<?php echo $project_id; ?>">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
