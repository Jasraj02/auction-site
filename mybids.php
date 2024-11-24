<?php include_once("header.php")?>
<?php include 'database.php'; ?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">My Bids</h2>

<?php
  // This page is for showing a user the auctions they've bid on.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  // TODO: Check user's credentials (cookie/session).
  $user_id = $_SESSION['userID'];

  // Sorting and pagination setup
  $ordering = $_GET['order_by'] ?? 'popularityByBids';
  $resultsPerPage = 10;
  $curr_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  $offset = ($curr_page - 1) * $resultsPerPage; 
  
  // TODO: Perform a query to pull up the auctions they've bidded on.
  // lecture slides 1B SQL, slide 44
  $query = "SELECT Auctions.auctionID, auctionTitle, auctionDescription, currentPrice, endTime, COUNT(bidID) AS count, (SELECT COUNT(*) FROM UserViews WHERE UserViews.auctionID = Auctions.auctionID) AS userViews FROM Auctions, Bids WHERE Auctions.auctionID in (SELECT Bids.auctionID from Bids WHERE buyerID = $user_id AND Bids.auctionID = auctionID) AND Bids.auctionID = Auctions.auctionID GROUP BY Auctions.auctionID";
  $result = mysqli_query($connection, $query) or die("Error making query to database.");
  
    // Sorting logic
switch ($ordering) {
  case 'priceLowToHigh':
      $query .= " ORDER BY currentPrice ASC, Auctions.auctionTitle ASC";
      break;
  case 'priceHighToLow':
      $query .= " ORDER BY currentPrice DESC, Auctions.auctionTitle ASC";
      break;
  case 'popularityByBids':
      $query .= " ORDER BY count DESC, Auctions.auctionTitle ASC";
      break;
  case 'date':
      $query .= " ORDER BY Auctions.endTime ASC, Auctions.auctionTitle ASC";
      break;
  case 'popularityByViews':
      $query .= " ORDER BY userViews DESC, Auctions.auctionTitle ASC";
      break;
  default:
      $query .= " ORDER BY Auctions.endTime ASC, Auctions.auctionTitle ASC";
      break;
}

  // mostly from browse.php
  $results_per_page = 10;
  // https://www.w3schools.com/Php/func_mysqli_num_rows.asp
  $max_page = ceil(mysqli_num_rows($result) / $results_per_page);
  if (!isset($_GET['page'])) {
    $curr_page = 1;
  } else {
    $curr_page = $_GET['page'];
  }
  $offset = ($curr_page - 1) * $results_per_page;
  $query .= " LIMIT $results_per_page OFFSET $offset";
  $result = mysqli_query($connection, $query) or die("Error making query to database.");
  

  // Total listings for pagination
$paginationQuery = "
SELECT COUNT(DISTINCT Auctions.auctionID) AS totalListings
FROM Auctions
INNER JOIN Bids ON Auctions.auctionID = Bids.auctionID
WHERE Bids.buyerID = $user_id
";
$paginationResult = mysqli_query($connection, $paginationQuery);
$totalListings = mysqli_fetch_assoc($paginationResult)['totalListings'];
$max_page = ceil($totalListings / $resultsPerPage);
?>

<div class="mb-3">
<label for="order_by" class="form-label">Sort by:</label>
<select class="form-control" id="order_by" name="order_by" onchange="location = this.value;">
    <option value="mybids.php?order_by=popularityByBids" <?= ($ordering === 'popularityByBids') ? 'selected' : '' ?>>Popularity: by bids</option>
    <option value="mybids.php?order_by=popularityByViews" <?= ($ordering === 'popularityByViews') ? 'selected' : '' ?>>Popularity: by views</option>
    <option value="mybids.php?order_by=priceHighToLow" <?= ($ordering === 'priceHighToLow') ? 'selected' : '' ?>>Price: highest first</option>
    <option value="mybids.php?order_by=priceLowToHigh" <?= ($ordering === 'priceLowToHigh') ? 'selected' : '' ?>>Price: lowest first</option>
    <option value="mybids.php?order_by=date" <?= ($ordering === 'date') ? 'selected' : '' ?>>Time: ending soonest</option>
</select>
</div>

<?php
  // TODO: Loop through results and print them out as list items.
  while ($row = $result->fetch_assoc()) {
    // [Yan TODO]: Exception Handling
    $item_id = $row["auctionID"];
    $title = $row["auctionTitle"];
    $desc = $row["auctionDescription"];
    $price = $row["currentPrice"];
    $num_bids = $row["count"];
    $userViews = $row['userViews'];
    // the use of new DateTime in the following line is group member Raj Singh's suggestion. Before Raj's suggestion, the line was "$end_time = $row["endTime"];"
    $end_time = new DateTime($row["endTime"]);
    print_bidding_li($item_id, $title, $desc, $price, $num_bids, $end_time, $userViews);
}

?>

<!-- from browse.php -->
<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">
  <?php

    // Copy any currently-set GET variables to the URL.
    $querystring = "";
    foreach ($_GET as $key => $value) {
      if ($key != "page") {
        $querystring .= "$key=$value&amp;";
      }
    }

    $high_page_boost = max(3 - $curr_page, 0);
    $low_page_boost = max(2 - ($max_page - $curr_page), 0);
    $low_page = max(1, $curr_page - 2 - $low_page_boost);
    $high_page = min($max_page, $curr_page + 2 + $high_page_boost);

    if ($curr_page != 1) {
      echo('<li class="page-item"><a class="page-link" href="myBids.php?' . $querystring . 'page=' . ($curr_page - 1) . '"><i class="fa fa-arrow-left"></i></a></li>');
    }
    for ($i = $low_page; $i <= $high_page; $i++) {
      if ($i == $curr_page) {
        // Highlight the link 
        echo('
        <li class="page-item active">');
      } 
      else {
           // Non-highlighted link
        echo('
      <li class="page-item">');
      }

      // Do this in any case
      echo('
        <a class="page-link" href="myBids.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
      </li>');
    }
  

    if ($curr_page != $max_page) {
      echo('
      <li class="page-item">
        <a class="page-link" href="myBids.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
          <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
          <span class="sr-only">Next</span>
        </a>
      </li>');
    }
  ?>
  
    </ul>
  </nav>

<?php include_once("footer.php")?>