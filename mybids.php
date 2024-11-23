<?php include_once("header.php")?>
<?php include 'database.php'; ?>
<?php require("utilities.php")?>

<div class="container">

<h2 class="my-3">My bids</h2>

<?php
  // This page is for showing a user the auctions they've bid on.
  // It will be pretty similar to browse.php, except there is no search bar.
  // This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  // TODO: Check user's credentials (cookie/session).
  $user_id = $_SESSION['userID'];
  
  // TODO: Perform a query to pull up the auctions they've bidded on.
  // lecture slides 1B SQL, slide 44
  $query = "SELECT Auctions.auctionID, auctionTitle, auctionDescription, currentPrice, endTime, COUNT(bidID) AS count FROM Auctions, Bids WHERE Auctions.auctionID in (SELECT Bids.auctionID from Bids WHERE buyerID = $user_id AND Bids.auctionID = auctionID) AND Bids.auctionID = Auctions.auctionID GROUP BY Auctions.auctionID";
  $result = mysqli_query($connection, $query) or die("Error making query to database.");
  
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
  
  // TODO: Loop through results and print them out as list items.
  while ($row = $result->fetch_assoc()) {
    // [Yan TODO]: Exception Handling
    $item_id = $row["auctionID"];
    $title = $row["auctionTitle"];
    $desc = $row["auctionDescription"];
    $price = $row["currentPrice"];
    $num_bids = $row["count"];
    // the use of new DateTime in the following line is group member Raj Singh's suggestion. Before Raj's suggestion, the line was "$end_time = $row["endTime"];"
    $end_time = new DateTime($row["endTime"]);
    print_bidding_li($item_id, $title, $desc, $price, $num_bids, $end_time);
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