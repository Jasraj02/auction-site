<?php    
    $connection = mysqli_connect('localhost', 'auctionUser', 'COMP0178GROUP22', 'auctionSite');     
    if ($connection -> connect_errno) {
        echo "Failed to connect to MySQL: " . $connection -> connect_error;
        exit();
    }    
?>
