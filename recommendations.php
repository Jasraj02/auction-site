<?php include_once("header.php")?>
<?php require("utilities.php")?>
<?php require("recommendation_utilities.php")?>

<div class="container">

<h2 class="my-3">Recommendations for you</h2>

<?php
  // This page is for showing a buyer recommended items based on their bid 
  // history. It will be pretty similar to browse.php, except there is no 
  // search bar. This can be started after browse.php is working with a database.
  // Feel free to extract out useful functions from browse.php and put them in
  // the shared "utilities.php" where they can be shared by multiple files.
  
  
  // TODO: Check user's credentials (cookie/session). 
  require 'database.php';  

  if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $loggedIn = true;
    $username = $_SESSION['username'];
    $userID = $_SESSION['userID'];
    $accountType = $_SESSION['account_type'];
  } else {
    $loggedIn = false;
    $accountType = 'seller';
  }    
  // TODO: Perform a query to pull up auctions they might be interested in.       
  $checkBidsFinalResult = getBidCount($connection, $userID);
  $checkBuyerPreferencesFinalResult = checkBuyerPreferences($connection, $userID);
    
  if ($checkBidsFinalResult > 1) {    
    runMainQuery($connection, $mainQuery, $recommendations);           
    if (count($recommendations) < $totalRecommendations) {
      $remainingRecommendations = $totalRecommendations - count($recommendations);      
      if ($checkBuyerPreferencesFinalResult > 1) {               
        runFallbackQueryOne($connection, $fallbackOneQuery, $recommendations);        
        if (count($recommendations) < $totalRecommendations) {
          $remainingRecommendations = $totalRecommendations - count($recommendations);          
          runFallbackQueryTwo($connection, $fallbackTwoQuery, $recommendations);          
        }
      } else {        
        runFallbackQueryTwo($connection, $fallbackTwoQuery, $recommendations);        
      }
    }
  } else {    
    if ($checkBuyerPreferencesFinalResult > 1) {      
      runFallbackQueryOne($connection, $fallbackOneQuery, $recommendations);      
      if (count($recommendations) < $totalRecommendations) {
        $remainingRecommendations = $totalRecommendations - count($recommendations);        
        runFallbackQueryTwo($connection, $fallbackTwoQuery, $recommendations);        
      }
    } else {      
      runFallbackQueryTwo($connection, $fallbackTwoQuery, $recommendations);      
    }
  }
  /*
  echo '<pre>';
    var_dump($recommendations);
  echo '</pre>';
  */
  ?>
  
  
  <?php
  // TODO: Loop through results and print them out as list items.
  $pageLimit = 10;
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  $totalPages = ceil(count($recommendations) / $pageLimit);
  $startIndex = ($page - 1) * $pageLimit;
  $pagedRecommendations = array_slice($recommendations, $startIndex, $pageLimit);
  

  if (isset($pagedRecommendations) && !empty($pagedRecommendations)) {
      $auctionIds = implode(",", array_map('intval', $pagedRecommendations));

      $searchQuery = "SELECT Auctions.*, Auctions.auctionTitle, MAX(Bids.bidPrice) as currentPrice, COUNT(Bids.bidID) as numberBids
                      FROM Auctions
                      LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
                      WHERE Auctions.auctionID IN ($auctionIds) AND Auctions.endTime > CURRENT_TIMESTAMP()
                      GROUP BY Auctions.auctionID
                      ORDER BY FIELD(Auctions.auctionID, $auctionIds)";

      $searchResult = mysqli_query($connection, $searchQuery);

      if (!$searchResult) {
          echo "<div class='alert alert-danger'>Error executing query: " . mysqli_error($connection) . "</div>";
      } elseif (mysqli_num_rows($searchResult) == 0) {
          echo '<div class="alert alert-info">No auction results match the recommended auction IDs.</div>';
      } else {
          echo '<ul class="list-group">';
          while ($row = mysqli_fetch_assoc($searchResult)) {
              $currentPrice = $row['currentPrice'] ?? $row['startingPrice'];
              $endDate = new DateTime($row['endTime']);

              print_listing_li($row['auctionID'], $row['auctionTitle'], substr($row['auctionDescription'], 0, 200) . '...', $currentPrice, $row['numberBids'], $endDate);
          }
          echo '</ul>';
      }
  } else {
      echo '<div class="alert alert-warning">No recommended auctions available.</div>';
  }
  if ($totalPages > 1) {
    echo '<nav aria-label="Page navigation" class="mt-3">';
    echo '<ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $page ? 'active' : '';
        echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
    }
    echo '</ul>';
    echo '</nav>';
  }
  ?>

<?php include_once("footer.php") ?>



  
  


