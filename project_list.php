<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$projects = $conn->query("SELECT * FROM projects");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Project List</title>
</head>
<body>
    <h2>Project List</h2>
    <a href="new_project.php">Create New Project</a>
    <table>
        <tr>
            <th>Project Name</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($project = $projects->fetch_assoc()) : ?>
            <tr>
                <td><?php echo $project['name']; ?></td>
                <td><?php echo $project['status']; ?></td>
                <td>
                    <a href="view_project.php?id=<?php echo $project['id']; ?>">View</a>
                    <a href="edit_project.php?id=<?php echo $project['id']; ?>">Edit</a>
                    <a href="delete_project.php?id=<?php echo $project['id']; ?>">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
