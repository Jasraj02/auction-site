<?php
session_start();
require 'database.php'; 

if (!isset($_SESSION['userID'])) {
    echo "Session expired. Redirecting to login...";
    header("refresh:2;url=index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authCode = $_POST['authCode'] ?? '';

    if (empty($authCode)) {
        echo "Authentication code cannot be empty!";
        header("refresh:2;url=authenticate.php");
        exit;
    }
    
    $userID = $_SESSION['userID']; 
    $checkQuery = "SELECT * FROM authenticationCodes 
                   WHERE userID = '$userID' AND authenticationCode = '$authCode' 
                   AND createdAt >= NOW() - INTERVAL 5 MINUTE";
    $checkResult = mysqli_query($connection, $checkQuery);

    if ($checkResult && mysqli_num_rows($checkResult) === 1) {        
        $_SESSION['logged_in'] = true;
        
        $deleteQuery = "DELETE FROM authenticationCodes WHERE userID = '$userID'";
        mysqli_query($connection, $deleteQuery);

        echo "Authentication successful. Redirecting...";
        header("refresh:2;url=index.php");
        exit;
    } else {
        echo "Invalid or expired code. Redirecting to login.";
        session_unset();
        session_destroy();
        header("refresh:2;url=index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<body>    
    <form action="authenticate.php" method="POST">
        <label for="authCode">Enter the 8-digit code:</label>
        <input type="text" id="authCode" name="authCode" required minlength="8" maxlength="8" pattern="\d{8}">
        <button type="submit">Submit</button>
    </form>
</body>
</html>