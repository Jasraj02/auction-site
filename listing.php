<?php include_once("header.php") ?>
<?php require("utilities.php") ?>
<?php require_once("database.php"); ?>

<?php
// Get info from the URL:
$item_id = $_GET['item_id'];
// https://stackoverflow.com/questions/1283327/how-to-get-url-of-current-page-in-php
$current_url = $_SERVER['REQUEST_URI'];

//initialise view count
$viewCount = 0;

//track unique views if the user is logged in and is a buyer
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SESSION['account_type'] !== 'seller') {
  if (isset($_SESSION['userID'])) { // Check if userID is set
    $user_id = $_SESSION['userID'];

    $sellerQuery = "SELECT sellerID FROM Auctions WHERE auctionID = $item_id";
    $sellerResult = mysqli_query($connection, $sellerQuery);

      if ($sellerResult && $sellerRow = mysqli_fetch_assoc($sellerResult)) {
          $seller_id = $sellerRow['sellerID'];

      //check is user has already viewed the auction
      //only add view if user is not the seller
        if ($user_id !== $seller_id) {
          $viewCheckQuery = "SELECT * FROM UserViews WHERE auctionID = $item_id AND userID = $user_id";
          $viewResult = mysqli_query($connection, $viewCheckQuery);
            
          if (mysqli_num_rows($viewResult) === 0) {
              //add new view
              $insertViewQuery = "INSERT INTO UserViews (userID, auctionID, viewTime) VALUES ($user_id, $item_id, NOW())";
              mysqli_query($connection, $insertViewQuery) or die("Error inserting view record: " . mysqli_error($connection));
        }
      }
    }
  }
}
//use item_id to make a query to the database.
$searchQuery = "
    SELECT Auctions.*, 
           MAX(Bids.bidPrice) AS currentPrice, 
           COUNT(Bids.bidID) AS numberBids, 
           Auctions.endTime < CURRENT_TIMESTAMP() AS Finished,
           Auctions.startTime AS dateAdded,
           (SELECT COUNT(*) FROM UserViews WHERE auctionID = $item_id) AS viewCount
    FROM Auctions
    LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
    WHERE Auctions.auctionID = $item_id
    GROUP BY Auctions.auctionID";

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
    $sellerID = $auction['sellerID'];
    $viewCount = $auction['viewCount'];
    $dateAdded = $auction['dateAdded']; 
       
    if (isset($imageID)) {
      $imageDataQuery = "SELECT imageFile FROM Images WHERE imageID='$imageID'";
      $imageDataResult = mysqli_query($connection, $imageDataQuery) or die("Error making select userData query".mysqli_error($connection));

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
$has_session = false;
$watching = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $buyer_id = $_SESSION['userID'] ?? null;
    $has_session = true;
    $accountType = $_SESSION['account_type'];
    if ($buyer_id) {
      $get_watchlist = "SELECT * FROM Watchlists WHERE auctionID = '$item_id' AND buyerID = $buyer_id";
      $watchlistResult = mysqli_query($connection, $get_watchlist);
      $watching = $watchlistResult && (mysqli_num_rows($watchlistResult) > 0);
    }
}

// disable bidding for the auction creator 
// disable watchlists for sellers
if ($has_session && ($sellerID == $buyer_id)) {
  $disabled = "disabled";
} 
// else if ($accountType == 'seller') {
//   $disabled = "";
// }
else {
  $disabled = "";
}

?>

<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo($title); ?></h2>
    <p>Views: <?php echo $viewCount; ?></p> <!-- Display view count -->
    <p>Date Added: <?php echo date_format(new DateTime($dateAdded), 'j M Y H:i'); ?></p> <!-- Display date added -->
  </div>
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php 

  if (!$Finished): ?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()" >Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
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
    <p> Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 2)) ?></p>
    
    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
        <!-- Hidden fields -->
        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">  
        <?php if (isset($_SESSION['userID'])): ?>
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['userID']; ?>">    
        <?php endif; ?>
        <input type="hidden" name="previous_url" value="<?php echo $current_url; ?>">
        <!-- Bid amount input -->
        <input type="number" name="bid" class="form-control" id="bid" <?php echo($disabled);?> >
      </div>
      <button type="submit" class="btn btn-primary form-control" <?php echo($disabled);?>>Place bid</button>
    </form><br>
    
    <?php 
    $suggestedPrice = suggestedPriceIncrease($item_id,$connection); 
    $suggestedPrice = $suggestedPrice * ((float)$current_price) / 100;
    $suggestedPrice = $suggestedPrice + ((float)$current_price);
    $suggestedPrice = number_format($suggestedPrice, 2);
    ?>
    <p class="form-control">Suggested bid: £<?php echo($suggestedPrice); ?></p>
    
<!-- 

<?php include_once("footer.php")?>

    <h4>Bid History</h4>
    <?php
        $query = "SELECT username, bidPrice FROM Users, Bids WHERE Bids.auctionID = $item_id AND Bids.buyerID = Users.userID ORDER BY Bids.bidID DESC";
        $result = mysqli_query($connection, $query) or die("Error making query to database.");

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) { 
                $username = htmlspecialchars($row['username']);
                $bid_price = htmlspecialchars($row['bidPrice']);
                echo "<p>$username: £$bid_price</p>";
            }
          } else {
            // No bid history
            echo "<p>N/A</p>";
        }
        ?>
<?php endif ?>
    </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->

<?php include_once("footer.php")?>

<script> 

function addToWatchlist(button) {
  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var failureMessage = objT;
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.innerHTML = '<div class="alert alert-danger">Add to watch failed: ' + failureMessage + '</div>';
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func

function removeFromWatchlist(button) {
  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var failureMessage = objT;
          var mydiv = document.getElementById("watch_watching");
          mydiv.innerHTML = '<div class="alert alert-danger">Watch removal failed: ' + failureMessage + '</div>';
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func
</script>