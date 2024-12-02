<?php include_once("header.php"); ?>
<?php require_once("utilities.php"); ?>
<?php require_once("database.php"); ?>

<div class="container">

<h2 class="my-3">Auction Analytics</h2>

<?php 

// If user is not logged in or not a seller, they should not be able to use this page.
if (!isset($_SESSION['account_type']) || ($_SESSION['account_type'] == 'buyer')) {
    header('Location: browse.php');
}

$sellerID = $_SESSION['userID'];

// Live Auctions Section
echo('<h4>Live Auctions</h4>');
$currentAuctionSum = 0;

// Query to fetch live auctions the seller has
$liveAuctionsQuery = "SELECT auctionID, auctionTitle, startingPrice, endTime, currentPrice
                        FROM Auctions
                        WHERE sellerID = $sellerID
                        AND startTime <= NOW()
                        AND endTime > NOW()";
$liveAuctionsResult = mysqli_query($connection, $liveAuctionsQuery);

if ($liveAuctionsResult && mysqli_num_rows($liveAuctionsResult) > 0) {
    echo('<ul class="list-group">');
    // Iterate through all live auctions fetched by SQL query
    while ($row = mysqli_fetch_assoc($liveAuctionsResult)) {
        $auctionID = $row['auctionID'];
        $auctionTitle = $row['auctionTitle'];
        $startingPrice = $row['startingPrice'];
        $endTime = $row['endTime'];
        $currentPrice = $row['currentPrice'];
        $currentAuctionSum += $currentPrice;

        $viewAmount = giveAuctionViews($auctionID, $connection);
        $bidAmount = giveAuctionBids($auctionID, $connection);
        $viewBidRatio = ($bidAmount > 0) ? round($viewAmount / $bidAmount, 1) : 0;

        echo("<li class='list-group-item'>
                <strong><a href='listing.php?item_id=$auctionID'>$auctionTitle</a></strong>
                <br>Current Price: <strong>£$currentPrice</strong>
                <br>Ends On: <strong>$endTime</strong>
                <br>Views: <strong>$viewAmount</strong>
                <br>Bids: <strong>$bidAmount</strong>
                <br>View to Bid Ratio: <strong>$viewBidRatio</strong>
              </li>");
    }
    echo('Total Price of all running auctions: <strong>£' . $currentAuctionSum . '</strong>');
    echo '</ul><br><br>';
} else {
    echo('<div class="alert alert-info">You have no live auctions.</div>');
}

// Expired Auctions Section
echo '<h4>Expired Auctions</h4>';
$expiredAuctionsQuery = "SELECT auctionID
                        FROM Auctions
                        WHERE sellerID = $sellerID
                        AND endTime <= NOW()";
$expiredAuctionsResult = mysqli_query($connection, $expiredAuctionsQuery);

if ($expiredAuctionsResult && mysqli_num_rows($expiredAuctionsResult) > 0) {
    echo('<ul class="list-group">');
    // Iterate through all expired auctions fetched by SQL query
    while ($row = mysqli_fetch_assoc($expiredAuctionsResult)) {
        $auctionID = $row['auctionID'];
        $aucDetails = giveAuctionDetails($auctionID, $connection);
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
                <br>Ended On: <strong>$endTime</strong>
              </li>");
    }
    echo '</ul><br>';
} else {
    echo('<div class="alert alert-info">You have no expired auctions.</div>');
}

// Most and Least Popular Auctions Section
echo '<h4>Most and Least Popular Auctions</h4>';

$mostPopularAuctionQuery = "SELECT UV.auctionID, COUNT(DISTINCT UV.userID) AS distinctViews
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

$mostPopularAuctionResult = mysqli_query($connection, $mostPopularAuctionQuery);
$leastPopularAuctionResult = mysqli_query($connection, $leastPopularAuctionQuery);

// Most Popular Auction
if ($mostPopularAuctionResult && mysqli_num_rows($mostPopularAuctionResult) > 0) {
    $mostPopularAuction = mysqli_fetch_assoc($mostPopularAuctionResult);
    $mostPopularAuctionID = $mostPopularAuction['auctionID'];
    $aucDetails = giveAuctionDetails($mostPopularAuctionID, $connection);
    $auctionTitle = $aucDetails['auctionTitle'];
    $currentPrice = $aucDetails['currentPrice'];
    $endTime = $aucDetails['endTime'];
    $disViews = $mostPopularAuction['distinctViews'];

    echo("<li class='list-group-item'>
            <strong><a href='listing.php?item_id=$mostPopularAuctionID'>$auctionTitle</a></strong>
            <br>Current Price: <strong>£$currentPrice</strong>
            <br>Ends On: <strong>$endTime</strong>
            <br>Distinct Views: <strong>$disViews</strong>
          </li>");
} else {
    echo('<div class="alert alert-info">No data available for the most viewed auction.</div>');
}

// Least Popular Auction
if ($leastPopularAuctionResult && mysqli_num_rows($leastPopularAuctionResult) > 0) {
    $leastPopularAuction = mysqli_fetch_assoc($leastPopularAuctionResult);
    $leastPopularAuctionID = $leastPopularAuction['auctionID'];
    $aucDetails = giveAuctionDetails($leastPopularAuctionID, $connection);
    $auctionTitle = $aucDetails['auctionTitle'];
    $currentPrice = $aucDetails['currentPrice'];
    $endTime = $aucDetails['endTime'];
    $disViews = $leastPopularAuction['distinctViews'];

    echo("<li class='list-group-item'>
            <strong><a href='listing.php?item_id=$leastPopularAuctionID'>$auctionTitle</a></strong>
            <br>Current Price: <strong>£$currentPrice</strong>
            <br>Ends On: <strong>$endTime</strong>
            <br>Distinct Views: <strong>$disViews</strong>
          </li>");
} else {
    echo('<div class="alert alert-info">No data available for the least viewed auction.</div>');
}

// Lifetime Analytics Section
echo('<br><h4>Lifetime Analytics</h4>');

// Number of finished auctions
$numberOfFinishedAuctionsQuery = "SELECT COUNT(*) AS total
                    FROM Auctions
                    WHERE sellerID = $sellerID
                    AND endTime <= NOW();";
$numberOfFinishedAuctionsResult = mysqli_query($connection, $numberOfFinishedAuctionsQuery);
$numberOfFinishedAuctionsRow = mysqli_fetch_assoc($numberOfFinishedAuctionsResult);
$numberOfFinishedAuctions = isset($numberOfFinishedAuctionsRow) ? $numberOfFinishedAuctionsRow['total'] : NULL;

if (isset($numberOfFinishedAuctions)) {
    if ($numberOfFinishedAuctions == 0) {
        echo('No auctions have been placed and/or have finished.');
    } else {
        echo("Total number of finished auctions: <strong>$numberOfFinishedAuctions</strong>.<br>");
        
        // Total revenue from finished auctions
        $totalRevenueQuery = "SELECT SUM(currentPrice) AS totalRevenue
                            FROM Auctions
                            WHERE sellerID = $sellerID
                            AND endTime <= NOW();";
        $totalRevenueResult = mysqli_query($connection, $totalRevenueQuery);
        $totalRevenueRow = mysqli_fetch_assoc($totalRevenueResult);
        $totalRevenue = round($totalRevenueRow['totalRevenue']);
        echo("Total Revenue from all Finished Listings: <strong>£$totalRevenue</strong><br>");
        
        // Total views and average views for completed auctions
        $totalViewsQuery = "SELECT COUNT(UV.userID) AS totalViews
                            FROM Auctions A
                            JOIN UserViews UV ON A.auctionID = UV.auctionID
                            WHERE A.sellerID = $sellerID
                            AND A.endTime <= NOW();";
        $totalViewsResult = mysqli_query($connection, $totalViewsQuery);
        $totalViewsRow = mysqli_fetch_assoc($totalViewsResult);
        $totalViews = round($totalViewsRow['totalViews']);
        $avgViews = $totalViews / $numberOfFinishedAuctions;

        echo("Average views for Completed Listings: <strong>$avgViews</strong><br>");
    }
} else {
    echo('<div class="alert alert-info">No finished auctions found for analytics.</div>');
}

?>

</div> <!-- Close container -->
<?php include_once("footer.php"); ?>