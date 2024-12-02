<?php 
    if (!isset($_SESSION['userID'])) {
        die("Error: User is not logged in.");
    }
    $userID = $_SESSION['userID'];

    $recommendations = [];
    $totalRecommendations = 25;
    $remainingRecommendations = 25;

    function getBidCount($connection, $userID) {
        $checkBidsQuery = "SELECT COUNT(buyerID) AS count FROM bids WHERE buyerID = $userID";
        $checkBidsResult = mysqli_query($connection, $checkBidsQuery);
        $checkBidsResultRow = mysqli_fetch_array($checkBidsResult);
        return $checkBidsResultRow['count'];
    }
    
    function checkBuyerPreferences($connection, $userID) {
        $checkBuyerPreferencesQuery = "SELECT COUNT(categoryID) AS count FROM preferences WHERE userID = $userID";
        $checkBuyerPreferencesResult = mysqli_query($connection, $checkBuyerPreferencesQuery);
        $checkBuyerPreferencesResultRow = mysqli_fetch_array($checkBuyerPreferencesResult);
        return $checkBuyerPreferencesResultRow['count'];
    }
    
    $mainQuery = 
    "WITH rankQuery AS (
        SELECT buyerID, COUNT(DISTINCT auctionID) AS buyerRank
        FROM bids
        WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = $userID)
        AND buyerID != $userID
        GROUP BY buyerID
    ),
    auctionsQuery AS (        
        SELECT DISTINCT auctionID
        FROM bids 
        WHERE buyerID IN (SELECT DISTINCT buyerID FROM bids WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = $userID) AND buyerID != $userID)        
        AND auctionID NOT IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = $userID)        
        AND auctionID NOT IN (SELECT auctionID FROM watchlists WHERE buyerID = $userID)
    ),
    categoriesQuery AS (        
        SELECT DISTINCT categoryID
        FROM auctions
        WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = $userID)
    )    
    SELECT a.auctionID, SUM(r.buyerRank) AS totalRank
    FROM auctionsQuery a    
    LEFT JOIN auctions auc ON a.auctionID = auc.auctionID
    LEFT JOIN categoriesQuery c ON auc.categoryID = c.categoryID    
    INNER JOIN bids b ON a.auctionID = b.auctionID 
    INNER JOIN rankQuery r ON b.buyerID = r.buyerID    
    WHERE auc.endTime > NOW()
    GROUP BY a.auctionID    
    ORDER BY CASE WHEN c.categoryID IS NOT NULL THEN (totalRank * 1.25) ELSE totalRank END DESC
    LIMIT $remainingRecommendations;";

    $fallbackOneQuery = "SELECT a.auctionID
        FROM auctions a
        WHERE a.categoryID IN (SELECT categoryID FROM buyerPreferences WHERE buyerID = $userID)
        AND a.endTime > NOW()
        ORDER BY (SELECT COUNT(*) FROM bids b WHERE a.auctionID = b.auctionID) DESC
        LIMIT $remainingRecommendations;";

    $fallbackTwoQuery = "SELECT a.auctionID
        FROM auctions a
        WHERE a.endTime > NOW()
        ORDER BY (SELECT COUNT(*) FROM bids b WHERE a.auctionID = b.auctionID) DESC
        LIMIT $remainingRecommendations";
    
    function runMainQuery($connection, $mainQuery, &$recommendations) {
        $mainResult = mysqli_query($connection, $mainQuery);
        while ($row = mysqli_fetch_assoc($mainResult)) {
            $recommendations[] = $row['auctionID'];
        }
    }

    function runFallbackQueryOne($connection, $fallbackOneQuery, &$recommendations) {
        $fallbackOneResult = mysqli_query($connection, $fallbackOneQuery);
        while ($row = mysqli_fetch_assoc($fallbackOneResult)) {
            $recommendations[] = $row['auctionID'];
        }
    }

    function runFallbackQueryTwo($connection, $fallbackTwoQuery, &$recommendations) {
        $fallbackTwoResult = mysqli_query($connection, $fallbackTwoQuery); 
        while ($row = mysqli_fetch_assoc($fallbackTwoResult)) {
          $recommendations[] = $row['auctionID'];
        }
    }            
?>