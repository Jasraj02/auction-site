<?php include_once("header.php") ?>
<?php require("utilities.php") ?>
<?php require_once("database.php"); ?>

<?php
// Get info from the URL:
$item_id = $_GET['item_id'];


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
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php 

  if (!$Finished): ?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <div class="itemDescription">
    <?php echo($description); ?>
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

    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
	    <input type="number" class="form-control" id="bid">
      </div>
      <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php endif ?>

  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->



<?php include_once("footer.php")?>


<script> 
// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist(button) {
  console.log("These print statements are helpful for debugging btw");

  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
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
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func
</script>