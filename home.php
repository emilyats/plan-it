<?php
session_start();
if (!isset($_SESSION['logged-in'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['logged-in']) && $_SESSION['role'] === 'Admin') {
    header('Location: admin.php');
    exit();
}

if (!isset($_SESSION['system'])) {
    $_SESSION['system'] = array(
        'site_name' => 'Plan-it',
    );
}

include 'db_connect.php';

$role = $_SESSION['role'];
$userId = $_SESSION['id'];

$where = "";
$where2 = "";
if ($role == 'Project Manager') {
    $where = " WHERE user_id = '$userId' ";
    $where2 = " WHERE tu.user_id = '$userId' ";
} elseif ($role == 'Employee') {
    $where = " WHERE project_id IN (SELECT project_id FROM project_employees WHERE employee_id = '$userId') ";
    $where2 = " WHERE tu.user_id = '$userId' ";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $_SESSION['system']['site_name']; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="script.js"></script>
    <style>
        .table th, .table td {
            white-space: nowrap;
            text-align: center;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mt-4">
                <div class="card-body">
                    <h2>Welcome to <?php echo $_SESSION['system']['site_name']; ?>, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
                    <p>Role: <?php echo htmlspecialchars($role); ?></p>
                    <?php if ($role == 'Project Manager'): ?>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#newProjectModal">New Project</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#reportModal">Generate Project Report</button>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-7">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h5><b>Project Progress</b></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 table-hover">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Project Name</th>
                                <th class="text-center">Progress</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            $qry = $conn->query("SELECT * FROM projects $where ORDER BY name ASC");
                            while ($row = $qry->fetch_assoc()):
                                $prog = 0;
                                $tprog = $conn->query("SELECT * FROM tasks WHERE project_id = {$row['project_id']}")->num_rows;
                                $cprog = $conn->query("SELECT * FROM tasks WHERE project_id = {$row['project_id']} AND status = 'Completed'")->num_rows;
                                $prog = $tprog > 0 ? ($cprog / $tprog) * 100 : 0;
                                $prog = $prog > 0 ? number_format($prog, 2) : $prog;
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $prog; ?>%" aria-valuenow="<?php echo $prog; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small><?php echo $prog; ?>% Complete</small>
                                    </td>
                                    <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo $row['status']; ?></span></td>
                                    <td class="action-buttons">
                                    <a class="btn btn-primary btn-sm" href="view_project.php?project_id=<?php echo $row['project_id']; ?>"><i class="fas fa-folder"></i> View Tasks</a>
                                    <?php if ($role == 'Project Manager'): ?>
                                        <button class="btn btn-secondary btn-sm edit-project-btn" data-toggle="modal" data-target="#editProjectModal" data-id="<?php echo $row['project_id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-status="<?php echo $row['status']; ?>" data-start_date="<?php echo $row['start_date']; ?>" data-end_date="<?php echo $row['end_date']; ?>" data-user_ids="<?php echo implode(',', array_column($conn->query("SELECT employee_id FROM project_employees WHERE project_id = {$row['project_id']}")->fetch_all(MYSQLI_ASSOC), 'employee_id')); ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="btn btn-danger btn-sm delete-project-btn" data-toggle="modal" data-target="#deleteProjectModal" data-id="<?php echo $row['project_id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                                    <?php endif; ?>
                                </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-md-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h5><b>Your Progress</b></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table m-0 table-hover">
                        <colgroup>
                            <col width="5%">
                            <col width="35%">
                            <col width="30%">
                            <col width="30%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Username</th>
                            <th class="text-center">Task Name</th>
                            <th class="text-center">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT tu.*, t.*, u.username AS name FROM task_users tu
                            INNER JOIN tasks t ON tu.task_id = t.task_id
                            INNER JOIN users u ON tu.user_id = u.id
                            $where2 ORDER BY u.username ASC");
                        while ($row = $qry->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars(ucwords($row['name'])); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>

     <div class="col-md-14">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h5><b>Team Progress</b></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0 table-hover">
                            <colgroup>
                                <col width="5%">
                                <col width="30%">
                                <col width="30%">
                                <col width="20%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Username</th>
                                <th class="text-center">Task Name</th>
                                <th class="text-center">Task Status</th>
                                <th class="text-center">Project Name</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            $qry = $conn->query("
                                SELECT u.username, tu.*, t.*, p.name AS project_name
                                FROM task_users tu
                                INNER JOIN tasks t ON tu.task_id = t.task_id
                                INNER JOIN projects p ON t.project_id = p.project_id
                                INNER JOIN users u ON tu.user_id = u.id
                                ORDER BY u.username ASC
                            ");
                            while ($row = $qry->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars(ucwords($row['username'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" action="generate_report.php">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportModalLabel">Generate Project Report</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="projectSelect">Select Project</label>
                                <select class="form-control" id="projectSelect" name="project_id" required>
                                    <?php
                                    $qry = $conn->query("SELECT project_id, name FROM projects ORDER BY name ASC");
                                    while ($row = $qry->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo $row['project_id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>


<div class="modal fade" id="newProjectModal" tabindex="-1" role="dialog" aria-labelledby="newProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="new_project.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="newProjectModalLabel">New Project</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="projectName">Project Name</label>
                        <input type="text" class="form-control" id="projectName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="projectStatus">Status</label>
                        <select class="form-control" id="projectStatus" name="status" required>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="projectStartDate">Start Date</label>
                        <input type="date" class="form-control" id="projectStartDate" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="projectEndDate">End Date</label>
                        <input type="date" class="form-control" id="projectEndDate" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="projectUsers">Assign Users</label>
                        <select multiple class="form-control" id="projectUsers" name="employees[]">
                            <?php
                            $qry = $conn->query("SELECT id, username FROM users WHERE role != 'Admin' ORDER BY username ASC");
                            while ($row = $qry->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="editProjectModal" tabindex="-1" role="dialog" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="edit_project.php">
                <input type="hidden" id="editProjectId" name="project_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editProjectName">Project Name</label>
                        <input type="text" class="form-control" id="editProjectName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editProjectStatus">Status</label>
                        <select class="form-control" id="editProjectStatus" name="status" required>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editProjectStartDate">Start Date</label>
                        <input type="date" class="form-control" id="editProjectStartDate" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="editProjectEndDate">End Date</label>
                        <input type="date" class="form-control" id="editProjectEndDate" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="editProjectUsers">Assign Users</label>
                        <select multiple class="form-control" id="editProjectUsers" name="employees[]">
                            <?php
                            $qry = $conn->query("SELECT id, username FROM users WHERE role != 'Admin' ORDER BY username ASC");
                            while ($row = $qry->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="delete_project.php">
                <input type="hidden" id="deleteProjectId" name="project_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProjectModalLabel">Delete Project</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this project?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('.edit-project-btn').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var status = $(this).data('status');
            var start_date = $(this).data('start_date');
            var end_date = $(this).data('end_date');
            var user_ids = $(this).data('user_ids');

            $('#editProjectId').val(id);
            $('#editProjectName').val(name);
            $('#editProjectStatus').val(status);
            $('#editProjectStartDate').val(start_date);
            $('#editProjectEndDate').val(end_date);

            $('#editProjectUsers').val([]);
            if (user_ids) {
                $('#editProjectUsers').val(user_ids.split(','));
            }
        });

        $('.delete-project-btn').on('click', function() {
            var id = $(this).data('id');
            $('#deleteProjectId').val(id);
        });
    });
</script>
</body>
</html>
