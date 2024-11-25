<?php include_once("header.php"); ?>
<?php require_once("utilities.php"); ?>
<?php require_once("database.php"); ?>

<div class="container">

<h2 class="my-3">Auction Analytics </h2>

<?php 

// If user is not logged in or not a seller, they should not be able to use this page.
if (!isset($_SESSION['account_type']) || ($_SESSION['account_type'] == 'buyer')) {
    header('Location: browse.php');
}

$sellerID = $_SESSION['userID'];


echo('<h4>Live Auctions</h4>');

$currentAuctionSum = 0;

// Following chunk displays all live auctions the seller has 
$liveAuctionsQuery = "SELECT auctionID, auctionTitle, startingPrice, endTime, currentPrice
                        FROM Auctions
                        WHERE sellerID = $sellerID
                        AND startTime <= NOW()
                        AND endTime > NOW()";
$liveAuctionsResult = mysqli_query($connection,$liveAuctionsQuery);
if ($liveAuctionsResult && mysqli_num_rows($liveAuctionsResult) > 0) {
    echo('<ul class="list-group">');
    // iterate through all auctions fetched by sql query
    while ($row = mysqli_fetch_assoc($liveAuctionsResult)) {
        $auctionID = $row['auctionID'];
        $auctionTitle = $row['auctionTitle'];
        $startingPrice = $row['startingPrice'];
        $endTime = $row['endTime'];
        $currentPrice = $row['currentPrice'];
        $currentAuctionSum = $currentAuctionSum + $currentPrice;

        $viewAmount = giveAuctionViews($auctionID,$connection);
        $bidAmount = giveAuctionBids($auctionID,$connection);
        $viewBidRatio = round($viewAmount / $bidAmount ,1);

        echo("<li class='list-group-item'>
                <strong><a href='listing.php?item_id=$auctionID'>$auctionTitle</a></strong>
                <br>Current Price: <strong>£$currentPrice</strong>
                <br>Ends On: <strong>$endTime</strong>
                <br>Views: <strong>$viewAmount</strong>
                <br>Bids: <strong>$bidAmount</strong>
                <br>View to Bid Ratio of <strong>$viewBidRatio</strong>
              </li>");
        
    }
    echo('Total Price of all running auctions:'.'<strong>'.'£'.$currentAuctionSum.'</strong>');
    echo '</ul><br><br>';
} else {
    echo('<div class="alert alert-info">You have no live auctions.</div>');
}

// Following chunk shows all the expired auctions a User has 
// this includes extras such as increase over reserve price
$expiredAuctionsQuery = "SELECT auctionID
                        FROM Auctions
                        WHERE sellerID = $sellerID
                        AND endTime <= NOW()";
$expiredAuctionsResult = mysqli_query($connection,$expiredAuctionsQuery);
echo '<h4>Expired Auctions</h4>';
if ($expiredAuctionsResult && mysqli_num_rows($expiredAuctionsResult) > 0) {
    echo('<ul class="list-group">');
    // iterate through all auctions fetched by sql query
    while ($row = mysqli_fetch_assoc($expiredAuctionsResult)) {

        $auctionID = $row['auctionID'];
        $aucDetails = giveAuctionDetails($auctionID,$connection);
        $auctionTitle = $aucDetails['auctionTitle'];
        $currentPrice = $aucDetails['currentPrice'];
        $startingPrice = $aucDetails['startingPrice'];
        $reservePrice = $aucDetails['reservePrice'];
        $priceIncrease = $currentPrice - $reservePrice;
        $endTime = $aucDetails['endTime'];

        echo("<li class='list-group-item'>
                <strong><a href='listing.php?item_id=$auctionID'>$auctionTitle</a></strong>
                <br>Starting Price: <strong>£$startingPrice</strong>
                <br>Final Price: <strong>£$currentPrice</strong>
                <br>Increase over Reserve Price: <strong>£$priceIncrease</strong>
                <br>Ended On: <strong>$endTime</strong><br>
              </li>");
    }
    echo '</ul><br>';
} else {
    echo('<div class="alert alert-info">You have no expired auctions.</div>');
}

// Following chunk will display the most/least popular live auctions
$mostPopularAuctionQuery = "SELECT UV.auctionID, COUNT(DISTINCT UV.userID) as distinctViews
                            FROM UserViews UV 
                            JOIN Auctions A ON UV.auctionID = A.auctionID
                            GROUP BY UV.auctionID 
                            ORDER BY distinctViews DESC
                            LIMIT 1;";

$leastPopularAuctionQuery = "SELECT UV.auctionID, COUNT(DISTINCT UV.userID) AS distinctViews
                            FROM UserViews UV
                            JOIN Auctions A ON UV.auctionID = A.auctionID
                            GROUP BY UV.auctionID
                            ORDER BY distinctViews ASC
                            LIMIT 1;";

$mostPopularAuctionResult = mysqli_query($connection,$mostPopularAuctionQuery);
$leastPopularAuctionResult = mysqli_query($connection,$leastPopularAuctionQuery);

// get auction details of the most/least popular auctions so that they can be displayed 
$mostPopularAuction = mysqli_fetch_assoc($mostPopularAuctionResult);
$leastPopularAuction = mysqli_fetch_assoc($leastPopularAuctionResult);

$mostPopularAuctionID = $mostPopularAuction['auctionID'];
$leastPopularAuctionID = $leastPopularAuction['auctionID'];

if ($mostPopularAuctionResult && mysqli_num_rows($mostPopularAuctionResult) > 0) {
    $aucDetails = giveAuctionDetails($mostPopularAuctionID,$connection);
    $auctionTitle = $aucDetails['auctionTitle'];
    $currentPrice = $aucDetails['currentPrice'];
    $endTime = $aucDetails['endTime'];
    $disViews = $mostPopularAuction['distinctViews'];

    echo('<br><h4>Most Viewed Auction</h4>');
    echo("<li class='list-group-item'>
                <strong><a href='listing.php?item_id=$mostPopularAuctionID'>$auctionTitle</a></strong>
                <br>Current Price: <strong>£$currentPrice</strong>
                <br>Ends On: <strong>$endTime</strong>
                <br>Distinct Views: <strong>$disViews</strong>
              </li>");
}

if ($leastPopularAuctionResult && mysqli_num_rows($leastPopularAuctionResult) > 0) {
    $aucDetails = giveAuctionDetails($leastPopularAuctionID,$connection);
    $auctionTitle = $aucDetails['auctionTitle'];
    $currentPrice = $aucDetails['currentPrice'];
    $endTime = $aucDetails['endTime'];
    $disViews = $leastPopularAuction['distinctViews'];

    echo('<br><h4>Least Viewed Auction</h4>');
    echo("<li class='list-group-item'>
                <strong><a href='listing.php?item_id=$mostPopularAuctionID'>$auctionTitle</a></strong>
                <br>Current Price: <strong>£$currentPrice</strong>
                <br>Ends On: <strong>$endTime</strong>
                <br>Distinct Views: <strong>$disViews</strong>
              </li>");
}


echo('<br><h4>Lifetime Analytics</h4>');
// Following chunk will display the lifetime analytics of the user 

// Find the number of distinct auctions the user has put up

$numberOfFinishedAuctionsQuery = "SELECT COUNT(*) AS total
                    FROM Auctions
                    WHERE sellerID = $sellerID
                    AND endTime <= NOW();";
$numberOfFinishedAuctionsResult = mysqli_query($connection, $numberOfFinishedAuctionsQuery);
$numberOfFinishedAuctionsRow = mysqli_fetch_assoc($numberOfFinishedAuctionsResult);

if (isset($numberOfFinishedAuctionsRow)) {
    $numberOfFinishedAuctions = $numberOfFinishedAuctionsRow['total'];
}
else {
    $numberOfFinishedAuctions = NULL;
}



if (!isset($numberOfFinishedAuctions)) {
    echo('An error occured whilst retrieving lifetime analytics');
} else if ($numberOfFinishedAuctions == 0) {
    echo('No auctions have placed and/or have finished.');
} else {
    echo("Total number of finished auctions: <strong>$numberOfFinishedAuctions</strong>. <br>");
    
    // find total revenue from all auctions 
    $totalRevenueQuery = "SELECT SUM(currentPrice) AS totalRevenue
                        FROM Auctions
                        WHERE sellerID = $sellerID
                        AND endTime <= NOW();";
    $totalRevenueResult = mysqli_query($connection, $totalRevenueQuery);
    $totalRevenueRow = mysqli_fetch_assoc($totalRevenueResult);
    $totalRevenue = round($totalRevenueRow['totalRevenue']);
    echo("Total Revenue from all Finished Listings: <strong>£$totalRevenue</strong><br>");

    // find total views from all finished auctions 
    $totalViewsQuery = " SELECT COUNT(UV.userID) AS totalViews
                        FROM Auctions A
                        JOIN UserViews UV ON A.auctionID = UV.auctionID
                        WHERE A.sellerID = $sellerID
                        AND A.endTime <= NOW();";
    
    $totalViewsResult = mysqli_query($connection, $totalViewsQuery);
    $totalViewsRow = mysqli_fetch_assoc($totalViewsResult);
    $totalViews = round($totalViewsRow['totalRevenue']);
    $avgViews = $totalViews / $numberOfFinishedAuctions;

    echo("Average views for Completed Listings : <strong>$avgViews</strong><br>");
}

?>


</div>

<?php include_once "footer.php" ?>