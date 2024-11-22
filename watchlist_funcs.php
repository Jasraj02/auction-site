<?php require_once("database.php");?>

<?php
session_start();
$loggedIn = $_SESSION['logged_in'];
$userID = $_SESSION['userID'];

if (!isset($_POST['functionname']) || !isset($_POST['arguments'])) {
  return;
}


// Extract arguments from the POST variables:
$auctionID = (int)$_POST['arguments'][0];

// get the sellerID for the auction
$auctionSellerIDQuery = "SELECT sellerID FROM Auctions WHERE auctionID  = '$auctionID'";
$auctionSellerIDResult = mysqli_query($connection, $auctionSellerIDQuery) or die("Error making select sellerID query".mysql_error());
$auctionSellerIDRow = mysqli_fetch_array($auctionSellerIDResult);
$auctionSellerID = (int)$auctionSellerIDRow["sellerID"];

if ($_POST['functionname'] == "add_to_watchlist") {
  // TODO: Update database and return success/failure.

  // NOTIFICATIONS ARE NOT IMPLEMENTED SO THEY'RE OFF BY STANDARD
  $notification = 0;

  if ($loggedIn && ($auctionSellerID == $userID)) {
    $res = "This is your auction";
  } else if ($loggedIn) {
    $watchAddQuery = "INSERT INTO Watchlists (buyerID,auctionID,notificationEnabled) VALUES ($userID,$auctionID,$notification)";
    mysqli_query($connection, $watchAddQuery) or die("Error creating the INSERT Watchlist query".mysql_error($connection));
    $res = "success";
  } else {
    $res = "You must be logged in to add to Watchlist";
  }
  
}
else if ($_POST['functionname'] == "remove_from_watchlist") {
  // TODO: Update database and return success/failure.
  $watchRemoveQuery = "DELETE FROM Watchlists WHERE buyerID = '$userID' AND auctionID = '$auctionID'";
  mysqli_query($connection, $watchRemoveQuery) or die("Error creating the INSERT Watchlist query".mysql_error($connection));
  $res = "success";
}

// Note: Echoing from this PHP function will return the value as a string.
// If multiple echo's in this file exist, they will concatenate together,
// so be careful. You can also return JSON objects (in string form) using
// echo json_encode($res).
echo $res;

?>