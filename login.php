<?php
session_start();
if (isset($_SESSION['logged-in'])) {
    header('Location: index.php');
    exit();
}

$username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';
$checked = isset($_COOKIE['remember_username']) ? 'checked' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
        .jumbotron{
            max-width:400px;
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
        .alert {
            max-width:400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="jumbotron text-center">
            <h2>Plan-it</h2>
        </div>
        
        <div class="container">&nbsp</div>

    <div class="card-header">
        <h5><b>Login</b></h5>
    </div>
    <form method="post" action="loginValidation.php">
        <div class="form-group">
            <label for="login">Username:</label>
            <input type="text" id="login" name="login" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="remember">
                <input type="checkbox" id="remember" name="remember" value="1" <?php echo $checked; ?>> Remember Me
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Log in</button>
        <hr>
        <a href="register.php" class="btn btn-secondary btn-register">Register</a>
    </form>
    
    <div class="container">&nbsp</div>
    <?php
    if (isset($_SESSION['loginError'])) {
        echo '<div class="alert alert-danger" role="alert">' . $_SESSION['loginError'] . '</div>';
        unset($_SESSION['loginError']);
    }
    ?>
</body>
</html>
