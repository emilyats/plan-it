<?php
include 'db_connect.php';

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$valid = true;
$username = $password = $confirm_password = $role = "";
$usernameErr = $passwordErr = $confirmPasswordErr = $roleErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST['username'])) {
        $usernameErr = "*Username is required";
        $valid = false;
    } else {
        $username = sanitizeInput($_POST['username']);
        if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9]*$/", $username)) {
            $usernameErr = "*Username should start with a letter or a number and cannot contain special characters";
            $valid = false;
        }
    }

    if (empty($_POST['password'])) {
        $passwordErr = "*Password is required";
        $valid = false;
    } else {
        $password = sanitizeInput($_POST['password']);
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
            $passwordErr = "*Password must be at least 8 characters long, include at least one uppercase letter, one lowercase letter, one number, and one special character";
            $valid = false;
        }
    }

    if (empty($_POST['confirm_password'])) {
        $confirmPasswordErr = "*Confirm password is required";
        $valid = false;
    } else {
        $confirm_password = sanitizeInput($_POST['confirm_password']);
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $confirmPasswordErr = "*Passwords do not match";
            $valid = false;
        }
    }

    if (empty($_POST['role'])) {
        $roleErr = "*Role is required";
        $valid = false;
    } else {
        $role = sanitizeInput($_POST['role']);
    }

    if ($valid) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            echo "User registered successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <style>
        body {
            padding: 20px;
        }
        form {
            padding-top:10px;
            max-width: 400px;
            margin: 0 auto;
        }
        .card-header{
            max-width:400px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-register {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card-header">
        <h5><b>Register</b></h5>
    </div>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mx-auto">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>" class="form-control" required>
            <span class="text-danger"><?php echo $usernameErr; ?></span>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <span class="text-danger"><?php echo $passwordErr; ?></span>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            <span class="text-danger"><?php echo $confirmPasswordErr; ?></span>
        </div>

        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role" class="form-control">
                <option value="">Select Role</option>
                <option value="Employee" <?php echo isset($role) && $role == 'Employee' ? 'selected' : ''; ?>>Employee</option>
                <option value="Project Manager" <?php echo isset($role) && $role == 'Project Manager' ? 'selected' : ''; ?>>Project Manager</option>
            </select>
            <span class="text-danger"><?php echo $roleErr; ?></span>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
    </form>
</body>
</html>
