<?php include 'database.php'; ?>
<?php
// TODO: Extract $_POST variables, check they're OK, and attempt to make a bid.
// Notify user of success/failure and redirect/give navigation options.
$bid_price = $_POST["bid"];
$item_id = $_POST["item_id"];
$user_email = $_POST['user_email'];
$previous_url = $_POST['previous_url'];
$query = "SELECT * FROM Auctions WHERE auctionID = $item_id";
$result = mysqli_query($connection, $query) or die("Error making query to database.");
// https://www.w3schools.com/php/php_mysql_select.asp
while ($row = $result->fetch_assoc()) {
    // [Yan TODO]: Exception Handling
    $current_price = $row["currentPrice"];
}
if ($bid_price > $current_price) {
    echo "Success.";
    // https://www.w3schools.com/mysql/mysql_update.asp
    $query = "UPDATE Auctions SET currentPrice = $bid_price WHERE auctionID = $item_id";
    mysqli_query($connection, $query) or die("Error making query to database.");
    $query = "SELECT userID FROM Users WHERE email = '$user_email'";
    $result = mysqli_query($connection, $query) or die("Error making query to database.");
    while ($row = $result->fetch_assoc()) {
        // [Yan TODO]: Exception handling 
        $user_id = $row['userID'];
    }
    $query = "INSERT INTO Bids (buyerID, auctionID, bidPrice) VALUES ($user_id, $item_id, $bid_price)";
    mysqli_query($connection, $query) or die("Error making query to database.");
} else {
    echo "Invalid bid. Please enter a value greater than the current price.";
}
mysqli_close($connection);
echo "<br>Redirecting to previous page.";

// Project Tutorial 3 PHP and MySQL, slide 31
// https://stackoverflow.com/questions/6119451/page-redirect-after-certain-time-php
header("refresh: 5; url=$previous_url");
?>