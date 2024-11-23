<!-- https://www.php.net/manual/en/language.basic-syntax.comments.php -->
<!-- This file is mostly copied from listing.php -->

<?php include_once("header.php") ?>
<?php require("utilities.php") ?>
<?php require_once("database.php"); ?>

<?php
// Get info from the URL:
$item_id = $_GET['item_id'];
// https://stackoverflow.com/questions/1283327/how-to-get-url-of-current-page-in-php
$current_url = $_SERVER['REQUEST_URI'];

//use item_id to make a query to the database.
$searchQuery = "SELECT Auctions.*, MAX(Bids.bidPrice) as currentPrice, COUNT(Bids.bidID) as numberBids, Auctions.endTime < CURRENT_TIMESTAMP() AS Finished
                FROM Auctions
                LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
                WHERE Auctions.auctionID = $item_id";

$searchQuery .= " GROUP BY Auctions.auctionID";

//Auctions that have ended may pull a different set of data,
  //       like whether the auction ended in a sale or was cancelled due
  //       to lack of high-enough bids.
$auctionQuery = mysqli_query($connection, $searchQuery);
if ($auctionQuery && $auction = mysqli_fetch_assoc($auctionQuery)) {
    $title = $auction['auctionTitle'];
    $description = $auction['auctionDescription'];
    $current_price = $auction['currentPrice'] ?? $auction['startingPrice'];
    $num_bids = $auction['numberBids'];
    $end_time = new DateTime($auction['endTime']);
    $Finished = $auction['Finished'];
    $imageID = $auction['imageID'];
    
    if (isset($imageID)) {
      $imageDataQuery = "SELECT imageFile FROM Images WHERE imageID='$imageID'";
      $imageDataResult = mysqli_query($connection, $imageDataQuery) or die("Error making select userData query".mysql_error());

      if ($imageDataResult && $imageDataRow = mysqli_fetch_assoc($imageDataResult)) {
        $imageData = base64_encode($imageDataRow['imageFile']);
    } else {
        $imageData = NULL;
    }
    }
    else {
      $imageData = NULL;
    }

} else {
    echo "<div class='alert alert-danger'>Error accessing auction details.</div>";
    include_once("footer.php");
    exit();
}

  // Calculate time to auction end:
  $now = new DateTime();
  
  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }

//check if user is logged in and watching the item
$has_session = true;
$watching = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $buyer_id = $_SESSION['buyerID'] ?? null;

    if ($buyer_id) {
        $get_watchlist = "SELECT * FROM watchlist WHERE auctionID = '$item_id' AND buyerID = $buyer_id";
        $watchlistResult = mysqli_query($connection, $get_watchlist);

        $watching = $watchlistResult && mysqli_num_rows($watchlistResult) > 0;
    }
}
?>

<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo($title); ?></h2>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  
  <div class="col-sm-8"> <!-- Left col with item info -->

  <div class="itemDescription">
    <?php 
    if ($imageData): ?>
        <img src="data:image/jpeg;base64,<?php echo $imageData; ?>" alt="Auction Image" style="max-width: 100%; height: auto;">
    <?php else: ?>
        <p>No image available for this auction.</p>
    <?php endif; ?>

    <p><?php echo($description); ?></p>
</div>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>

  <?php  //Auctions that have ended may pull a different set of data,
  //       like whether the auction ended in a sale or was cancelled due
  //       to lack of high-enough bids   **add ability to cancel and view expired auctions***
 if ($Finished): ?>
    This auction ended on <?php echo(date_format($end_time, 'j M H:i')) ?>
    <?php if ($auction['status'] === 'completed'): ?>
      Winning bid: £<?php echo(number_format($current_price, 2)); ?>
    <?php elseif ($auction['status'] === 'unsuccessful'): ?>
        The reserve price was not met.
    <?php elseif ($auction['status'] === 'cancelled'): ?>
        The auction was cancelled.
    <?php endif; ?>
<?php else: ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 2)) ?></p>
    <h4>Bid History</h4>
    <?php
        $query = "SELECT username, bidPrice FROM Users, Bids WHERE Bids.auctionID = $item_id AND Bids.buyerID = Users.userID ORDER BY Bids.bidID DESC";
        $result = mysqli_query($connection, $query) or die("Error making query to database.");
        while ($row = $result->fetch_assoc()) {
            $username= $row['username'];
            $bid_price = $row['bidPrice'];
            // the line below is modified fron the line for current bid in the starter code version of listing.php
            echo "<p>$username: £$bid_price</p>";
        }
    ?>
<?php endif ?>

  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->



<?php include_once("footer.php")?>
