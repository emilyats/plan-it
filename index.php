<?php
session_start();
if(!isset($_SESSION['login_id']))
	    header('location:login.php');
include 'db_connect.php';
ob_start();

if (isset($_SESSION['logged-in'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin.php');
    } else {
        header('Location: home.php');
    }
    exit();
}
?>