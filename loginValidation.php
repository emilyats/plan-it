<?php
session_start();
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $rememberme = isset($_POST['remember']) ? $_POST['remember'] : 0;

    $sql = "SELECT * FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['logged-in'] = true;
                $_SESSION['user'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['id'] = $row['id'];
                unset($_SESSION['loginError']);

                if ($rememberme == '1') {
                    setcookie('remember_username', $login, time() + 3600 * 24 * 30);
                } else {
                    setcookie('remember_username', '', time() - 3600);
                }

                if ($row['role'] == 'Admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $_SESSION['loginError'] = '<span class="error-msg">Invalid password.</span>';
            }
        } else {
            $_SESSION['loginError'] = '<span class="error-msg">Invalid username.</span>';
        }
        $stmt->close();
    } else {
        $_SESSION['loginError'] = '<span class="error-msg">Database query failed.</span>';
    }
    $conn->close();
    header('Location: login.php');
    exit();
}
?>

