<?php
session_start();

if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

require_once "db_connect.php";

$project = null;
$result_tasks = null;
$users = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['edit_task'])) {
        $task_id = $_POST['edit_task_id'];
        $title = $_POST['edit_title'];
        $description = $_POST['edit_description'];
        $priority = $_POST['edit_priority'];
        $status = $_POST['edit_status'];
        $due_date = $_POST['edit_due_date'];
        $assigned_users = $_POST['edit_assigned_users'];

        $title = mysqli_real_escape_string($conn, $title);
        $description = mysqli_real_escape_string($conn, $description);

        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ? WHERE task_id = ?");
        $stmt->bind_param("sssssi", $title, $description, $priority, $status, $due_date, $task_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0 || $stmt->errno == 0) {
            $conn->query("DELETE FROM task_users WHERE task_id = $task_id");

            if (!empty($assigned_users)) {
                foreach ($assigned_users as $user_id) {
                    $stmt_task_users = $conn->prepare("INSERT INTO task_users (task_id, user_id) VALUES (?, ?)");
                    $stmt_task_users->bind_param("ii", $task_id, $user_id);
                    $stmt_task_users->execute();
                    $stmt_task_users->close();
                }
            }

            echo "<script>alert('Task updated successfully!');</script>";
            header("Refresh:0");
        } else {
            echo "Error updating task: " . $stmt->error;
        }

        $stmt->close();
    }

    if (isset($_POST['delete_task'])) {
        $delete_task_id = $_POST['delete_task_id'];

        $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
        $stmt->bind_param("i", $delete_task_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->query("DELETE FROM task_users WHERE task_id = $delete_task_id");
            echo "<script>alert('Task deleted successfully!');</script>";
            header("Refresh:0");
        } else {
            echo "Error deleting task: " . $stmt->error;
        }

        $stmt->close();
    }

    if (isset($_POST['create_task'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $priority = $_POST['priority'];
        $status = $_POST['status'];
        $due_date = $_POST['due_date'];
        $assigned_users = $_POST['assigned_users'];
        $project_id = $_POST['project_id'];

        $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description, priority, status, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $project_id, $title, $description, $priority, $status, $due_date);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $task_id = $stmt->insert_id;

            if (!empty($assigned_users)) {
                foreach ($assigned_users as $user_id) {
                    $stmt_task_users = $conn->prepare("INSERT INTO task_users (task_id, user_id) VALUES (?, ?)");
                    $stmt_task_users->bind_param("ii", $task_id, $user_id);
                    $stmt_task_users->execute();
                    $stmt_task_users->close();
                }
            }

            echo "<script>alert('Task created successfully!');</script>";
            header("Refresh:0");
        } else {
            echo "Error creating task: " . $stmt->error;
        }

        $stmt->close();
    }
}

if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result_project = $stmt->get_result();

    if ($result_project->num_rows > 0) {
        $project = $result_project->fetch_assoc();

        $title = $_GET['title'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $status = $_GET['status'] ?? '';
        $due_date = $_GET['due_date'] ?? '';
        $assigned_user = $_GET['assigned_user'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? '';

        $query = "SELECT tasks.*, GROUP_CONCAT(users.username SEPARATOR ', ') AS assigned_users 
        FROM tasks 
        LEFT JOIN task_users ON tasks.task_id = task_users.task_id 
        LEFT JOIN users ON task_users.user_id = users.id 
        WHERE tasks.project_id = $project_id";
    
    if (!empty($title)) {
        $query .= " AND tasks.title LIKE '%" . mysqli_real_escape_string($conn, $title) . "%'";
    }
    if (!empty($priority)) {
        $query .= " AND tasks.priority = '" . mysqli_real_escape_string($conn, $priority) . "'";
    }
    if (!empty($status)) {
        $query .= " AND tasks.status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }
    if (!empty($due_date)) {
        $query .= " AND tasks.due_date = '" . mysqli_real_escape_string($conn, $due_date) . "'";
    }
    if (!empty($search)) {
        $query .= " AND (tasks.title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                            OR tasks.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
    }
    if (!empty($assigned_user)) {
        $query .= " AND EXISTS (
            SELECT 1 
            FROM task_users 
            WHERE task_users.task_id = tasks.task_id 
            AND task_users.user_id = " . intval($assigned_user) . "
        )";
    }
    
    $query .= " GROUP BY tasks.task_id";
    
    if (!empty($sort_by)) {
        if ($sort_by === 'priority') {
            $query .= " ORDER BY FIELD(tasks.priority, 'High', 'Medium', 'Low')";
        } else {
            $query .= " ORDER BY " . mysqli_real_escape_string($conn, $sort_by);
        }
    } else {
        $query .= " ORDER BY FIELD(tasks.priority, 'High', 'Medium', 'Low')";
    }
    
    $result_tasks = $conn->query($query);
    
    } else {
        header('Location: home.php');
        exit();
    }

    $stmt->close();
} else {
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Project: <?php echo htmlspecialchars($project['name'] ?? ''); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .action-buttons {
            justify-content: center;
        }
        .action-buttons form {
            display: inline;
        }
        .filter-form-container {
            display: flex;
            justify-content: center;
            margin: 1em 0;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 1em;
        }
        .filter-form .form-group {
            margin-bottom: 0;
        }
        .form-control {
            width: auto;
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="jumbotron text-center">
            <h2><?php echo htmlspecialchars($project['name'] ?? ''); ?></h2>
        </div>
        <div class="filter-form-container">
            <form method="GET" class="filter-form" id="filter-form">
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">       
                <div class="form-group">
                    <label for="due_date" class="sr-only">Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-control" placeholder="Due Date" value="<?php echo htmlspecialchars($due_date); ?>">
                </div>
                
                <div class="form-group">
                    <label for="assigned_user" class="sr-only">Assigned User</label>
                    <select name="assigned_user" id="assigned_user" class="form-control">
                        <option value="">Select User</option>
                        <?php
                        $users_query = $conn->query("SELECT id, username FROM users WHERE username != 'admin'");
                        while ($user = $users_query->fetch_assoc()) {
                            echo '<option value="' . $user['id'] . '" ' . ($assigned_user == $user['id'] ? 'selected' : '') . '>';
                            echo htmlspecialchars($user['username']);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="sort_by" class="sr-only">Sort By</label>
                    <select name="sort_by" id="sort_by" class="form-control">
                        <option value="">Sort By</option>
                        <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="priority" <?php echo $sort_by == 'priority' ? 'selected' : ''; ?>>Priority</option>
                        <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                        <option value="due_date" <?php echo $sort_by == 'due_date' ? 'selected' : ''; ?>>Due Date</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
            </form>
        </div>
        <div class="card card-outline card-success">
                <div class="card-header">
                    <h5><b>Tasks</b></h5>
        </div>
        <div class="card-body p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Assigned Users</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_tasks): ?>
                    <?php while ($row = $result_tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['task_id']; ?></td>
                            <td><?php echo $row['title']; ?></td>
                            <td><?php echo $row['description']; ?></td>
                            <td><?php echo $row['priority']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['due_date']; ?></td>
                            <td><?php echo $row['assigned_users']; ?></td>
                            <td class="action-buttons">
                                <button 
                                    onclick="editTask(
                                        '<?php echo $row['task_id']; ?>', 
                                        '<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>', 
                                        '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>', 
                                        '<?php echo $row['priority']; ?>', 
                                        '<?php echo $row['status']; ?>', 
                                        '<?php echo $row['due_date']; ?>'
                                    )" 
                                    class="btn btn-primary btn-sm">Edit</button>
                                <form method="post" action="">
                                    <input type="hidden" name="delete_task_id" value="<?php echo $row['task_id']; ?>">
                                    <button type="submit" name="delete_task" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No tasks found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    <div class="container">
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createTaskModal">
            Create New Task
        </button>

        <a href="home.php" class="btn btn-secondary">Back to Home</a>
        <hr>
    </div>
    
    <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_task_id" name="edit_task_id">
                        <div class="form-group">
                            <label for="edit_title">Title</label>
                            <input type="text" id="edit_title" name="edit_title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea id="edit_description" name="edit_description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_priority">Priority</label>
                            <select id="edit_priority" name="edit_priority" class="form-control">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select id="edit_status" name="edit_status" class="form-control">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_due_date">Due Date</label>
                            <input type="date" id="edit_due_date" name="edit_due_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_assigned_users">Assign to (hold CTRL to choose multiple)</label>
                            <select class="custom-select" id="edit_assigned_users" name="edit_assigned_users[]" class="form-control" multiple>
                            <?php
                            $users_query = $conn->query("SELECT * FROM users WHERE username != 'admin'");
                            while ($user = $users_query->fetch_assoc()) {
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . '</option>';
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_task" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTaskModal" tabindex="-1" role="dialog" aria-labelledby="createTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTaskModalLabel">Create New Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="assigned_users">Assign to (hold CTRL to choose multiple)</label>
                            <select class="custom-select" id="assigned_users" name="assigned_users[]" class="form-control" multiple>
                            <?php
                            $users_query = $conn->query("SELECT * FROM users WHERE username != 'admin'");
                            while ($user = $users_query->fetch_assoc()) {
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . '</option>';
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="create_task" class="btn btn-success">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function clearFilters() {
    document.getElementById('filter-form').reset();

    document.querySelectorAll('#filter-form select').forEach(select => {
        select.selectedIndex = 0; // Select the first option
    });

    document.getElementById('filter-form').submit();
}

    function editTask(id, title, description, priority, status, due_date) {
        document.getElementById('edit_task_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_priority').value = priority;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_due_date').value = due_date;

        $.ajax({
            url: 'get_task_users.php',
            type: 'GET',
            data: { task_id: id },
            success: function(response) {
                $('#edit_assigned_users').val(JSON.parse(response));
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
            }
        });

        $('#editTaskModal').modal('show');
    }

    function showCreateTaskModal() {
        $('#createTaskModal').modal('show');
    }
    </script>
</body>
</html>

<?php
$conn->close();
?>
