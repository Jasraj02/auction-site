<?php include_once 'header.php'; ?>
<?php include_once 'database.php'; ?>
<?php include 'notification.php'; ?>
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
    $end_time = $row["endTime"]; 
}

//check if user is logged in
$loggedIn = false;
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $loggedIn = true;
}

if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == 'seller') {
    echo '<div class="alert alert-danger">Sellers can not place bids.</div>';
    header("refresh: 2; url=$previous_url");
    exit();
}

// prevent a user not logged in from placing a bid
if (!$loggedIn) {
    echo '<div class="alert alert-danger">Must be logged in to place a bid.</div>';
    header("refresh: 2; url=$previous_url");
    exit();
}

// prevent a user from bidding against her own bid
$lastBidderQuery = "SELECT buyerID FROM bids WHERE auctionID = $item_id ORDER BY bidPrice DESC LIMIT 1";
$lastBidderResult = mysqli_query($connection, $lastBidderQuery);
while ($row = mysqli_fetch_assoc($lastBidderResult)) {
    $lastBidderID = $row['buyerID'];
}
if ($user_id == $lastBidderID) {
    echo '<div class="alert alert-danger">Cannot bid against own bid. Redirecting.</div>';
    header("refresh: 2; url=$previous_url");
    exit();
}

if ($bid_price > $current_price) {    
    $current_time = new DateTime();  
    $auction_end_time = new DateTime($end_time);      
    $time_diff = $auction_end_time->diff($current_time);
    $minutes_left = $time_diff->i + ($time_diff->h * 60);  
    
    // Last minute bidding within 10 minutes
    if ($minutes_left <= 10) {        
        $auction_end_time->modify('+10 minutes');
        $new_end_time = $auction_end_time->format('Y-m-d H:i:s');                
        $update_query = "UPDATE Auctions SET endTime = '$new_end_time' WHERE auctionID = $item_id";
        mysqli_query($connection, $update_query) or die("Error updating auction end time.");
    }

    echo "Success.";
    // https://www.w3schoolsgit .com/mysql/mysql_update.asp

    $query = "UPDATE Auctions SET currentPrice = $bid_price WHERE auctionID = $item_id";
    mysqli_query($connection, $query) or die("Error making query to database.");
    $query = "INSERT INTO Bids (buyerID, auctionID, bidPrice) VALUES ($user_id, $item_id, $bid_price)";
    mysqli_query($connection, $query) or die("Error making query to database.");
    header("refresh: 2; url=$previous_url");  
} else {
    echo "Invalid bid. Please enter a value greater than the current price.";
}

// Notify user who has been outbid (if she is watching auction)
$auctionTitleQuery = "SELECT auctionTitle FROM auctions WHERE auctionID = $item_id";
$auctionTitleResult = mysqli_query($connection, $auctionTitleQuery);
while ($row = mysqli_fetch_assoc($auctionTitleResult)) {
    $auctionTitle = $row['auctionTitle'];
}
$priorBidderQuery = "SELECT b.buyerID, u.username FROM bids b LEFT JOIN users u ON u.userID = b.buyerID WHERE auctionID = $item_id ORDER BY bidprice DESC LIMIT 1 OFFSET 1";
$priorBidderResult = mysqli_query($connection, $priorBidderQuery);
while ($row = mysqli_fetch_assoc($priorBidderResult)) {
    $priorBidderID = $row['buyerID'];
    $priorBidderUsername = $row['username'];
}

if (isset($priorBidderID) && !($priorBidderID == $user_id)) {
    emailOutbid($item_id, $auctionTitle, $priorBidderID, $priorBidderUsername, $bid_price);
}


mysqli_close($connection);
echo "<br>Redirecting to previous page.";

// Project Tutorial 3 PHP and MySQL, slide 31
// https://stackoverflow.com/questions/6119451/page-redirect-after-certain-time-php
header("refresh: 2; url=$previous_url");
?>