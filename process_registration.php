<?php
// TODO: Extract $_POST variables, check they're OK, and attempt to create
// an account. Notify user of success/failure and redirect/give navigation 
// options.

require 'database.php'; 

session_start();

$errors = [];
$formData = [];
$preferredCategories = $_POST['preferredCategories'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountType = $_POST['accountType'] ?? '';
    $username = $_POST['username'] ?? '';    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeatPassword = $_POST['passwordConfirmation'] ?? '';
    
    $formData['password'] = htmlspecialchars('');
    $formData['repeatPassword'] = htmlspecialchars('');

    if (empty($accountType)) {
        $errors[] = "Account type required";    
    } else {
        $formData['accountType'] = htmlspecialchars($accountType);
    }

    if (empty($username)) {
        $errors[] = "Username required";
    } elseif (strlen($username) > 100) {
        $errors[] = "Username cannot exceed 100 characters";
    } else {
        $formData['username'] = htmlspecialchars($username);
    }

    if (empty($email)) {
        $errors[] = "Email required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email address is not valid";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters";
    }
    
    else {
        $formData['email'] = htmlspecialchars($email);
    }

    if (empty($password)) {
        $errors[] = "Password required";
    } elseif (strlen($password) > 100) {
        $errors[] = "Password cannot exceed 100 characters";
    }

    if (empty($repeatPassword)) {
        $errors[] = "Repeat password required";
    } elseif ($password != $repeatPassword) {
        $errors[] = "Repeat password doesn't match";
    }    

    $usernameQuery = "SELECT username FROM users WHERE username = '$username'";
    $usernameResult = mysqli_query($connection, $usernameQuery)
        or die('Error checking username: ' . mysqli_error($connection));
    if (mysqli_num_rows($usernameResult) >= 1) {
        $errors[] = "Username taken";
    }

    $emailQuery = "SELECT email FROM users WHERE email = '$email'";
    $emailResult = mysqli_query($connection, $emailQuery)
        or die('Error checking email: ' . mysqli_error($connection));
    if (mysqli_num_rows($emailResult) >= 1) {
        $errors[] = "Email taken";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['formData'] = $formData;
        header('Location: register.php');
        exit;
    }

    $query = "INSERT INTO users (userRole, username, email, userPassword) VALUES ('$accountType', '$username', '$email', '$password')";
    $result = mysqli_query($connection, $query)
        or die('Error making insert query: ' . mysqli_error($connection));
    if ($result) {
        $userID = mysqli_insert_id($connection);
        if($accountType === 'buyer') {
            $buyerQuery = "INSERT INTO buyers (buyerID) VALUES ($userID)";
            mysqli_query($connection, $buyerQuery)
                or die('Error inserting into buyers table: ' . mysqli_error($connection));
        } elseif ($accountType === 'seller') {
            $sellerQuery = "INSERT INTO sellers (sellerID) VALUES ($userID)";
            mysqli_query($connection, $sellerQuery)
                or die('Error inserting into sellers table: ' . mysqli_error($connection));
        } elseif ($accountType === 'both') {
            $buyerQuery = "INSERT INTO buyers (buyerID) VALUES ($userID)";
            $sellerQuery = "INSERT INTO sellers (sellerID) VALUES ($userID)";
            mysqli_query($connection, $buyerQuery)
                or die('Error inserting into buyers table: ' . mysqli_error($connection));
            mysqli_query($connection, $sellerQuery)
                or die('Error inserting into buyers table: ' . mysqli_error($connection));
        }

        foreach ($preferredCategories as $categoryID) {
            $categoryID = mysqli_real_escape_string($connection, $categoryID);
            $userCategoryQuery = "INSERT INTO preferences (userID, categoryID) VALUES ($userID, $categoryID)";
            mysqli_query($connection, $userCategoryQuery)
                or die('Error inserting user-category pair: ' . mysqli_error($connection));
        }

        $successMessage = 'Successfully created account!';
        $_SESSION['successMessage'] = $successMessage;
        header('Location: register.php');
        exit;
    }    
    mysqli_close($connection);    
}
?>