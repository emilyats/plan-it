<?php
session_start();

if (!isset($_SESSION['logged-in']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['system'])) {
    $_SESSION['system'] = array(
        'site_name' => 'Plan-it',
    );
}

require_once "db_connect.php";


if (isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['delete_project_id'])) {
    $delete_project_id = $_POST['delete_project_id'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $delete_project_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['delete_task_id'])) {
    $delete_task_id = $_POST['delete_task_id'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
    $stmt->bind_param("i", $delete_task_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['edit_user_id'])) {
    $edit_user_id = $_POST['edit_user_id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $edit_user_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['edit_project_id'])) {
    $edit_project_id = $_POST['edit_project_id'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $assigned_to = $_POST['assigned_to'];

    $stmt = $conn->prepare("DELETE FROM project_employees WHERE project_id = ?");
    $stmt->bind_param("i", $edit_project_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE projects SET name = ?, status = ?, start_date = ?, end_date = ? WHERE project_id = ?");
    $stmt->bind_param("ssssi", $name, $status, $start_date, $end_date, $edit_project_id);
    $stmt->execute();

    foreach ($assigned_to as $user_id) {
        $stmt = $conn->prepare("INSERT INTO project_employees (project_id, employee_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $edit_project_id, $user_id);
        $stmt->execute();
    }
    $stmt->close();
}

if (isset($_POST['edit_task_id'])) {
    $edit_task_id = $_POST['edit_task_id'];
    $task_name = $_POST['task_name'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE tasks SET task_name = ?, status = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $task_name, $status, $due_date, $edit_task_id);
    $stmt->execute();
    $stmt->close();

    if (isset($_POST['assigned_to'])) {
        $assigned_to = $_POST['assigned_to'];

        $stmt = $conn->prepare("DELETE FROM task_users WHERE task_id = ?");
        $stmt->bind_param("i", $edit_task_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO task_users (task_id, user_id) VALUES (?, ?)");
        foreach ($assigned_to as $user_id) {
            $stmt->bind_param("ii", $edit_task_id, $user_id);
            $stmt->execute();
        }
        $stmt->close();
    }
}

    $sql_users = "SELECT * FROM users WHERE role != 'Admin'";
    $result_users = $conn->query($sql_users);

    $sql_projects = "SELECT projects.project_id, projects.name, projects.status, projects.start_date, projects.end_date,
                            GROUP_CONCAT(users.username SEPARATOR ', ') AS assigned_users 
                    FROM projects 
                    LEFT JOIN project_employees ON projects.project_id = project_employees.project_id 
                    LEFT JOIN users ON project_employees.employee_id = users.id 
                    GROUP BY projects.project_id";
    $result_projects = $conn->query($sql_projects);

    $sql_tasks = "SELECT tasks.task_id, tasks.title, tasks.status, tasks.due_date, GROUP_CONCAT(users.username SEPARATOR ', ') AS assigned_users 
                FROM tasks 
                LEFT JOIN task_users ON tasks.task_id = task_users.task_id 
                LEFT JOIN users ON task_users.user_id = users.id 
                GROUP BY tasks.task_id";
    $result_tasks = $conn->query($sql_tasks);
    ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="jumbotron text-center">
            <h2>Admin Dashboard</h2>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h2>Welcome to <?php echo $_SESSION['system']['site_name']; ?>, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <hr>
        
        <div class="card-header">
            <h5><b>Manage Users</b></h5>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td>
                    <button onclick="editUser('<?php echo htmlspecialchars($row['id']); ?>', '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['role']); ?>')" class="btn btn-primary" data-toggle="modal" data-target="#editUserModal">Edit</button>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" id="edit_user_id" name="edit_user_id">
                            <div class="form-group">
                                <label for="edit_username">Username:</label>
                                <input type="text" id="edit_username" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_role">Role:</label>
                                <select id="edit_role" name="role" class="form-control">
                                    <option value="Admin">Admin</option>
                                    <option value="Project Manager">Project Manager</option>
                                    <option value="Employee">Employee</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <hr>

        <div class="card-header">
                    <h5><b>Manage Projects</b></h5>
                </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Assigned Users</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_projects->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['project_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['assigned_users']); ?></td>
                    <td>
                        <form method="post" action="" style="display:inline;">
                        <button onclick="editProject('<?php echo htmlspecialchars($row['project_id']); ?>', '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars($row['status']); ?>', '<?php echo htmlspecialchars($row['start_date']); ?>', '<?php echo htmlspecialchars($row['end_date']); ?>')" class="btn btn-primary" data-toggle="modal" data-target="#editProjectModal">Edit</button>
                            <input type="hidden" name="delete_project_id" value="<?php echo htmlspecialchars($row['project_id']); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="modal fade" id="editProjectModal" tabindex="-1" role="dialog" aria-labelledby="editProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" id="edit_project_id" name="edit_project_id">
                            <div class="form-group">
                                <label for="edit_name">Name:</label>
                                <input type="text" id="edit_name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_status">Status:</label>
                                <select id="edit_status" name="status" class="form-control">
                                    <option value="Not Started">Not Started</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_start_date">Start Date:</label>
                                <input type="date" id="edit_start_date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_end_date">Due Date:</label>
                                <input type="date" id="edit_due_date" name="due_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_assigned_to">Assign Users:</label>
                                <select id="edit_assigned_to" name="assigned_to[]" class="form-control" multiple>
                                    <?php
                                    $result_users->data_seek(0); // Reset result set pointer
                                    while ($user = $result_users->fetch_assoc()) {
                                        echo "<option value=\"" . htmlspecialchars($user['id']) . "\">" . htmlspecialchars($user['username']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Project</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <hr>

        <div class="card-header">
                    <h5><b>Manage Tasks</b></h5>
                </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Task Name</th>
                    <th>Status</th>
                    <th>Assigned Users</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_tasks->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['task_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']);?></td>
                    <td><?php echo htmlspecialchars($row['assigned_users']); ?></td>
                    <td>
                    <button onclick="editTask('<?php echo htmlspecialchars($row['task_id']); ?>', '<?php echo htmlspecialchars($row['title']); ?>', '<?php echo htmlspecialchars($row['status']); ?>')" class="btn btn-primary" data-toggle="modal" data-target="#editTaskModal">Edit</button>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_task_id" value="<?php echo htmlspecialchars($row['task_id']); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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
                                <label for="edit_task_name">Task Name:</label>
                                <input type="text" id="edit_task_name" name="task_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_task_status">Status:</label>
                                <select id="edit_task_status" name="status" class="form-control">
                                    <option value="Not Started">Not Started</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_task_end_date">End Date:</label>
                                <input type="date" id="edit_task_end_date" name="end_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="edit_task_assigned_to">Assign Users:</label>
                                <select id="edit_task_assigned_to" name="assigned_to[]" class="form-control" multiple>
                                    <?php
                                    $result_users->data_seek(0); // Reset result set pointer
                                    while ($user = $result_users->fetch_assoc()) {
                                        echo "<option value=\"" . htmlspecialchars($user['id']) . "\">" . htmlspecialchars($user['username']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function editUser(id, username, role) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role').value = role;
    }

    function editProject(id, name, status, start_date, end_date) {
        document.getElementById('edit_project_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_start_date').value = start_date;
        document.getElementById('edit_end_date').value = end_date;

        <?php
        $result_users->data_seek(0);
        while ($user = $result_users->fetch_assoc()) {
            echo "document.getElementById('edit_assigned_to').options[" . $user['id'] . "].selected = true;";
        }
        ?>
    }

    function editTask(id, task_name, status, due_date, assigned_users) {
        document.getElementById('edit_task_id').value = task_id;
        document.getElementById('edit_task_title').value = title;
        document.getElementById('edit_task_status').value = status;
        document.getElementById('edit_task_due_date').value = due_date;

        const select = document.getElementById('edit_task_assigned_to');
        for (let option of select.options) {
            option.selected = assigned_users.includes(option.value);
        }

        $('#editTaskModal').modal('show');
    }
    </script>
</body>
</html>
