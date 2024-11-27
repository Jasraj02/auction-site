<?php

// TODO: Extract $_POST variables, check they're OK, and attempt to login.
// Notify user of success/failure and redirect/give navigation options.

// For now, I will just set session variables and redirect.

require 'database.php'; 
require 'notification.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';    
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "Login details cannot be empty!";
        header("refresh:2;url=index.php");        
    }

    $loginQuery = "SELECT userID FROM users WHERE email = '$email' AND userPassword = '$password'";
    $loginResult = mysqli_query($connection, $loginQuery)
        or die('Error logging in: ' . mysqli_error($connection));

    if (mysqli_num_rows($loginResult) === 1) {
        $authQuery = "SELECT authenticationEnabled FROM users WHERE email = '$email'";
        $authResult = mysqli_query($connection, $authQuery);
        $authResultRow = mysqli_fetch_array($authResult);
        $authStatus = $authResultRow['authenticationEnabled'];
        $usernameQuery = "SELECT username FROM users WHERE email = '$email'";
        $usernameResult = mysqli_query($connection, $usernameQuery);
        $usernameResultRow = mysqli_fetch_array($usernameResult); 
        $userIDQuery = "SELECT userID FROM users WHERE email = '$email'";
        $userIDResult = mysqli_query($connection, $userIDQuery);
        $userIDResultRow = mysqli_fetch_array($userIDResult); 
        $accountTypeQuery = "SELECT userRole FROM users WHERE email = '$email'";
        $accountTypeResult = mysqli_query($connection, $accountTypeQuery);
        $accountTypeResultRow = mysqli_fetch_array($accountTypeResult);      
        $_SESSION['username'] = $usernameResultRow['username'];  
        $_SESSION['userID'] = $userIDResultRow['userID'];   
        $_SESSION['account_type'] = $accountTypeResultRow['userRole']; 
        if (!$authStatus) {
            $_SESSION['logged_in'] = true;
            echo('<div class="text-center">You are now logged in! You will be redirected shortly!</div>');            
            header("refresh:2;url=index.php");
        } else {
            $_SESSION['logged_in'] = false;
            echo('<div class="text-center">Redirection to 2FA</div>'); 
            emailAuthentication($_SESSION['userID']);           
            header("refresh:2;url=authenticate.php");
        }
    } else {
        if ($email && $password) {
            echo('<div class="text-center">Invalid email or password!</div>');        
            header("refresh:2;url=index.php");        
        }        
    }
}
?>