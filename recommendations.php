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
      
  echo '<pre>';
    var_dump($recommendations);
  echo '</pre>';



  // TODO: Loop through results and print them out as list items.
  
?>

