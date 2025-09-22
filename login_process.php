<?php
session_start();
require '../database/db_connection.php';

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    
}

// made a change that is now working...


?>