<?php include_once 'header.php'; ?>
<?php include_once 'database.php'; ?>
<?php



// TODO: Extract $_POST variables, check they're OK, and attempt to make a bid.
// Notify user of success/failure and redirect/give navigation options.
$bid_price = $_POST["bid"];
$item_id = $_POST["item_id"];
$user_id = $_POST['user_id'];
$previous_url = $_POST['previous_url'];
$query = "SELECT * FROM Auctions WHERE auctionID = $item_id";
$result = mysqli_query($connection, $query) or die("Error making query to database.");
// https://www.w3schools.com/php/php_mysql_select.asp
while ($row = $result->fetch_assoc()) {
    // [Yan TODO]: Exception Handling
    $current_price = $row["currentPrice"];
}

//check if user is logged in
$loggedIn = false;
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $loggedIn = true;
}

// prevent a user not logged in from placing a bid
if (!$loggedIn) {
    echo '<div class="alert alert-danger">Must be logged in to place a bid.</div>';
    header("refresh: 2; url=$previous_url");
    exit();
}

if ($bid_price > $current_price) {
    echo "Success.";
    // https://www.w3schoolsgit .com/mysql/mysql_update.asp
    $query = "UPDATE Auctions SET currentPrice = $bid_price WHERE auctionID = $item_id";
    mysqli_query($connection, $query) or die("Error making query to database.");
    $query = "INSERT INTO Bids (buyerID, auctionID, bidPrice) VALUES ($user_id, $item_id, $bid_price)";
    mysqli_query($connection, $query) or die("Error making query to database.");
} else {
    echo "Invalid bid. Please enter a value greater than the current price.";
}
mysqli_close($connection);
echo "<br>Redirecting to previous page.";

// Project Tutorial 3 PHP and MySQL, slide 31
// https://stackoverflow.com/questions/6119451/page-redirect-after-certain-time-php
header("refresh: 2; url=$previous_url");
?>