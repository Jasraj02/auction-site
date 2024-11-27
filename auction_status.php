<?php
    include 'database.php';
    include 'notification.php';

    // Update auction statuses (overnight cron job)
    $statusQueryNoBids = "UPDATE auctions a
    LEFT JOIN (
        SELECT auctionID, COUNT(BidID) AS bidCount
        FROM bids
        GROUP BY auctionID
    ) b ON a.auctionID = b.auctionID
    SET a.auctionStatusID = 4
    WHERE a.endTime < NOW() 
      AND (b.bidCount IS NULL OR b.bidCount = 0);";

    $statusQueryRest = "UPDATE Auctions
            SET auctionStatusID = CASE WHEN currentPrice >= reservePrice THEN 2 ELSE 3 END
            WHERE endTime < NOW() AND auctionStatusID NOT IN (2, 3, 4)";

    if (($connection->query($statusQueryNoBids) === TRUE) && ($connection->query($statusQueryRest) === TRUE)) {
        echo "Auction statuses updated successfully.";
    } else {
        echo "Error updating auction statuses: " . $connection->error;
    }

    // Send out correspondent notifications (or ones that should have been sent)
    $notificationQuery = "SELECT auctionID, auctionStatusID, expiryNotificationsSent FROM auctions WHERE auctionStatusID != 1 AND NOT expiryNotificationsSent";
    $notificationQueryResult = mysqli_query($connection, $notificationQuery);   
    
    if (mysqli_num_rows($notificationQueryResult) == 0) {
        echo "No auctions need notifications.\n";
    } else {
        echo "Found " . mysqli_num_rows($notificationQueryResult) . " auctions to notify.\n";
    }

    $auctionData = array();

    while ($row = mysqli_fetch_assoc($notificationQueryResult)) {
        $auctionData[] = $row;
    }
    foreach ($auctionData as $auction) {
        echo "Processing auctionID: " . $auction['auctionID'] . "\n";
        $auctionID = (int) $auction['auctionID'];        
        $notificationSuccessful = false;

        if ($auction['auctionStatusID'] == 2) {
            echo "Sending completed notification for auctionID: $auctionID\n";            
            $notificationSuccessful = emailCompleted($auctionID);
        }
        elseif ($auction['auctionStatusID'] == 3) {
            echo "Sending unsuccessful notification for auctionID: $auctionID\n";
            $notificationSuccessful = emailUnsuccessful($auctionID);
        }
        elseif ($auction['auctionStatusID'] == 4) {
            echo "Sending no-bids notification for auctionID: $auctionID\n";
            $notificationSuccessful = emailNoBids($auctionID);
        }        
        if ($notificationSuccessful) {
            $updateQuery = "UPDATE auctions SET expiryNotificationsSent = 1 WHERE auctionID = $auctionID";
            $updateQueryResult = mysqli_query($connection, $updateQuery);
            if (!$updateQueryResult) {
                echo "Error updating expiryNotificationsSent for auctionID: $auctionID - " . mysqli_error($connection) . "\n";
            } else {
                echo "Updated expiryNotificationsSent for auctionID: $auctionID\n";
            }
        } else {
            echo "Notification not sent for auctionID: $auctionID\n";
        }
    }    
    // FYI: MAMP uses PHP version 8.2.20, so to test scripts in the Terminal, use /Applications/MAMP/bin/php/php8.2.20/bin/php file_name.php
    $connection->close();
?>