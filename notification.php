<?php
include 'database.php';
require_once __DIR__ . '/vendor/autoload.php'; 

// For testing simplicity, .env and .gitignore not used; to and from emails identical
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function emailOutbid($auctionID, $auctionTitle, $outbidID, $outbidUsername, $outbidPrice) {
    global $connection; 
    
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // Mail configuration        
    $mailUsernameTo = '2024comp0178group22@gmail.com'; // test "to" account: password = comp0178group222024
    $mailUsernameFrom = 'voxvulgaris2993@gmail.com'; // test "from" account
    $mailPassword = 'vozf uzpk xzof iiqb';
    $mail = new PHPMailer(true);

    $sent = true;

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  
        $mail->SMTPAuth = true;
        $mail->Username = $mailUsernameFrom;  
        $mail->Password = $mailPassword;  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($mailUsernameFrom, 'Auction App');
        $mail->addAddress($mailUsernameTo, $outbidUsername);

        $mail->isHTML(true);
        $mail->Subject = "Auction $auctionTitle: Outbid";
        $mail->Body = "Hello $outbidUsername,<br><br>On watched auction titled <b>$auctionTitle</b> you have been outbid at a bid price of $outbidPrice.";

        $mail->send();        
    } catch (Exception $e) {
        echo "Email to seller could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $sent = false;        
    }
    if ($sent) {
        $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) 
                           VALUES ('$outbidID', '$auctionID', 9, NOW())";
        mysqli_query($connection, $insertionQuery);
    }
}

function emailNoBids($auctionID) {
    global $connection;

    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $allNotificationsSent = true;

    // Fetch seller information
    $sellerQuery = "SELECT u.userID, u.username, u.email, a.auctionTitle 
                    FROM users u 
                    JOIN auctions a ON u.userID = a.sellerID 
                    WHERE a.auctionID = $auctionID";
    $sellerResult = mysqli_query($connection, $sellerQuery);

    // Mail configuration        
    $mailUsernameTo = '2024comp0178group22@gmail.com'; // test "to" account: password = comp0178group222024; this could be either "winnerEmail" or "sellerEmail" from above in practice
    $mailUsernameFrom = 'voxvulgaris2993@gmail.com'; // test "from" account
    $mailPassword = 'vozf uzpk xzof iiqb';
    $mail = new PHPMailer(true);

    // Notify the seller
    if ($sellerResult && $row = mysqli_fetch_assoc($sellerResult)) {
        $sellerID = $row['userID'];
        $sellerUsername = $row['username'];
        $sellerEmail = $row['email'];
        $auctionTitle = $row['auctionTitle'];

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsernameFrom;  
            $mail->Password = $mailPassword;  
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($mailUsernameFrom, 'Auction App');
            $mail->addAddress($mailUsernameTo, $sellerUsername);

            $mail->isHTML(true);
            $mail->Subject = "Auction $auctionTitle: No Bids Received";
            $mail->Body = "Hello $sellerUsername,<br><br>Your auction titled <b>$auctionTitle</b> has ended without receiving any bids.";

            $mail->send();

            $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) 
                               VALUES ('$sellerID', '$auctionID', 7, NOW())";
            mysqli_query($connection, $insertionQuery);
        } catch (Exception $e) {
            echo "Email to seller could not be sent. Mailer Error: {$mail->ErrorInfo}";
            $allNotificationsSent = false;
        }
    }

    // Fetch watchlist users
    $watchlistQuery = "SELECT u.userID, u.username 
                       FROM watchlists w 
                       LEFT JOIN users u ON u.userID = w.buyerID 
                       WHERE auctionID = $auctionID";
    $watchlistResult = mysqli_query($connection, $watchlistQuery);

    // Notify watchlist users
    if ($watchlistResult) {
        while ($row = mysqli_fetch_assoc($watchlistResult)) {
            $watchlistUserID = $row['userID'];
            $watchlistUsername = $row['username'];

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;
                $mail->Username = $mailUsernameFrom;  
                $mail->Password = $mailPassword;  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom($mailUsernameFrom, 'Auction App');
                $mail->addAddress($mailUsernameFrom, 'Watchlist User'); // Replace if dynamic email data available

                $mail->isHTML(true);
                $mail->Subject = "Auction $auctionTitle: No Bids Received";
                $mail->Body = "Hello $watchlistUsername,<br><br>The auction titled <b>$auctionTitle</b> that you were watching has ended without receiving any bids.";

                $mail->send();

                $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) 
                                   VALUES ('$watchlistUserID', '$auctionID', 8, NOW())";
                mysqli_query($connection, $insertionQuery);
            } catch (Exception $e) {
                echo "Email to watchlist user could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $allNotificationsSent = false;
            }
        }
    }

    return $allNotificationsSent;
}

function emailCompleted($auctionID) {    
    global $connection; 
    
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $allNotificationsSent = true;    

    $checkNotificationsQuery = "SELECT userID, notificationTypeID FROM notifications WHERE auctionID = $auctionID";
    $checkNotificationsResult = mysqli_query($connection, $checkNotificationsQuery);    
    
    $watchlistQuery = "SELECT u.userID, u.username FROM watchlists w LEFT JOIN users u ON u.userID = w.buyerID WHERE auctionid = $auctionID";
    $watchlistResult = mysqli_query($connection, $watchlistQuery);

    $notificationTypes = [];

    if ($checkNotificationsResult) {        
        while ($row = mysqli_fetch_assoc($checkNotificationsResult)) {
            $notificationTypes[] = $row['notificationTypeID'];
        }
    }

    $countOfThree = array_count_values($notificationTypes)[3] ?? 0;        

    if ($watchlistResult) {
        $countOfWatchlist = mysqli_num_rows($watchlistResult);
    } else {
        $countOfWatchlist = 0;
    }

    $containsOne = in_array(1, $notificationTypes);
    $containsTwo = in_array(2, $notificationTypes);        
    $containsThree = ($countOfWatchlist === $countOfThree);
    $atLeastOneNotTrue = (!$containsOne) || (!$containsTwo) || (!$containsThree);    

    if ($atLeastOneNotTrue) {    
        $winnerSellerQuery = 
        "SELECT         
            u.userID AS winnerID,
            u.username AS winnerUsername, 
            u.email AS winnerEmail, 
            s.userID AS sellerID,
            s.username AS sellerUsername,
            s.email AS sellerEmail, 
            a.auctionTitle, 
            a.currentPrice AS bidPrice, 
            a.endTime
        FROM auctions a
        LEFT JOIN bids b ON a.auctionID = b.auctionID AND b.bidPrice = a.currentPrice
        LEFT JOIN users u ON b.buyerID = u.userID
        LEFT JOIN users s ON a.sellerID = s.userID
        WHERE a.auctionID = $auctionID";

        $winnerSellerResult = mysqli_query($connection, $winnerSellerQuery);
    
        if ($winnerSellerResult && $row = $winnerSellerResult->fetch_assoc()) {        
            $winnerID = $row['winnerID'];
            $winnerUsername = $row['winnerUsername'];
            $winnerEmail    = $row['winnerEmail'];
            $sellerID = $row['sellerID'];
            $sellerUsername = $row['sellerUsername'];
            $sellerEmail = $row['sellerEmail'];
            $auctionTitle   = $row['auctionTitle'];
            $bidPrice       = $row['bidPrice'];
            $endTime        = $row['endTime'];
        } else {
            echo "No results found or query failed: " . $connection->error;
        }
                
        $mailUsernameTo = '2024comp0178group22@gmail.com'; // test "to" account: password = comp0178group222024; this could be either "winnerEmail" or "sellerEmail" from above in practice
        $mailUsernameFrom = 'voxvulgaris2993@gmail.com'; // test "from" account
        $mailPassword = 'vozf uzpk xzof iiqb';
        
        $mail = new PHPMailer(true);

        if (!$containsOne) {                      
            try {                            
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;
                $mail->Username = $mailUsernameFrom;  
                $mail->Password = $mailPassword;  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom($mailUsernameFrom, 'Auction App');
                $mail->addAddress($mailUsernameTo, 'Auction User');

                // Content
                $mail->isHTML(true);
                $mail->Subject = "$auctionTitle: winner";
                $mail->Body    = "Congratulations $winnerUsername, you won $sellerUsername's auction $auctionTitle with a bid of $bidPrice at $endTime.";

                // Send email
                $mail->send();
                echo 'Email sent successfully!';
                echo $winnerID;                

                // Insertion                
                $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$winnerID', '$auctionID', 1, NOW())";
                mysqli_query($connection, $insertionQuery);

            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $allNotificationsSent = false;
            }            
        }

        if (!$containsTwo) {    
            try {        
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;
                $mail->Username = $mailUsernameFrom;  
                $mail->Password = $mailPassword;  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom($mailUsernameFrom, 'Auction App');
                $mail->addAddress($mailUsernameTo, 'Auction User');

                // Content
                $mail->isHTML(true);
                $mail->Subject = "$auctionTitle: complete";
                $mail->Body    = "Congratulations $sellerUsername, $auctionTitle has been completed by $winnerUsername with a bid of $bidPrice at $endTime.";

                // Send email
                $mail->send();
                echo 'Email sent successfully!';

                // Insertion
                $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$sellerID', '$auctionID', 2, NOW())";
                mysqli_query($connection, $insertionQuery);

            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $allNotificationsSent = false;
            }
        }
        
        if (!$containsThree) {        
            if ($watchlistResult) {
                $watchlistUsers = [];
                while ($row = mysqli_fetch_assoc($watchlistResult)) {
                    $watchlistUsers[] = [
                        'userID' => $row['userID'],
                        'username' => $row['username']
                    ];
                }
                foreach ($watchlistUsers as $user) {
                    $watchlistUserID = $user['userID'];
                    $watchlistUsername = $user['username'];
        
                    // Check if this user has already received notification type 3
                    $checkNotificationQuery = "SELECT 1 FROM notifications WHERE userID = $watchlistUserID AND auctionID = $auctionID AND notificationTypeID = 3";
                    $checkNotificationResult = mysqli_query($connection, $checkNotificationQuery);

                    if (mysqli_num_rows($checkNotificationResult) == 0)  {
                        $watchlistUserID = $user['userID'];
                        $watchlistUsername = $user['username'];
                        try {        
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';  
                            $mail->SMTPAuth = true;
                            $mail->Username = $mailUsernameFrom;  
                            $mail->Password = $mailPassword;  
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;
                    
                            // Recipients
                            $mail->setFrom($mailUsernameFrom, 'Auction App');
                            $mail->addAddress($mailUsernameTo, 'Auction User');
                    
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = "Watching $auctionTitle: complete";
                            $mail->Body    = "$watchlistUsername, $sellerUsername's auction $auctionTitle has been completed by $winnerUsername with a bid of $bidPrice at $endTime.";
                    
                            // Send email
                            $mail->send();
                            echo 'Email sent successfully!';

                            // Insertion
                            $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$watchlistUserID', '$auctionID', 3, NOW())";
                            mysqli_query($connection, $insertionQuery);

                        } catch (Exception $e) {
                            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                            $allNotificationsSent = true;
                        }
                    }
                }
            } else {
                echo "Query failed: " . mysqli_error($connection);
            }
        }        
    }
    return $allNotificationsSent;
}
//emailCompleted(64);

function emailUnsuccessful($auctionID) {    
    global $connection; 
    
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $allNotificationsSent = true;    

    $checkNotificationsQuery = "SELECT userID, notificationTypeID FROM notifications WHERE auctionID = $auctionID";
    $checkNotificationsResult = mysqli_query($connection, $checkNotificationsQuery);
    
    $watchlistQuery = "SELECT u.userID, u.username FROM watchlists w LEFT JOIN users u ON u.userID = w.buyerID WHERE auctionid = $auctionID";
    $watchlistResult = mysqli_query($connection, $watchlistQuery);

    $notificationTypes = [];

    if ($checkNotificationsResult) {        
        while ($row = mysqli_fetch_assoc($checkNotificationsResult)) {
            $notificationTypes[] = $row['notificationTypeID'];
        }
    }

    $countOfSix = array_count_values($notificationTypes)[6] ?? 0;

    if ($watchlistResult) {
        $countOfWatchlist = mysqli_num_rows($watchlistResult);
    } else {
        $countOfWatchlist = 0;
    }

    $containsFour = in_array(4, $notificationTypes);
    $containsFive = in_array(5, $notificationTypes);        
    $containsSix = ($countOfWatchlist === $countOfSix);
    $atLeastOneNotTrue = (!$containsFour) || (!$containsFive) || (!$containsSix);

    if ($atLeastOneNotTrue) {
        $buyerSellerQuery = 
        "SELECT
            u.userID AS buyerID,         
            u.username AS buyerUsername, 
            u.email AS buyerEmail,
            s.userID AS sellerID, 
            s.username AS sellerUsername,
            s.email AS sellerEmail, 
            a.auctionTitle, 
            a.currentPrice AS bidPrice,
            a.reservePrice AS reservePrice, 
            a.endTime
        FROM auctions a
        LEFT JOIN bids b ON a.auctionID = b.auctionID AND b.bidPrice = a.currentPrice
        LEFT JOIN users u ON b.buyerID = u.userID
        LEFT JOIN users s ON a.sellerID = s.userID
        WHERE a.auctionID = $auctionID";

        $buyerSellerResult = mysqli_query($connection, $buyerSellerQuery);

        if ($buyerSellerResult && $row = $buyerSellerResult->fetch_assoc()) {        
            $buyerID = $row['buyerID'];
            $buyerUsername = $row['buyerUsername'];
            $buyerEmail    = $row['buyerEmail'];
            $sellerID = $row['sellerID'];
            $sellerUsername = $row['sellerUsername'];
            $sellerEmail = $row['sellerEmail'];
            $auctionTitle   = $row['auctionTitle'];
            $bidPrice       = $row['bidPrice'];
            $reservePrice = $row['reservePrice'];
            $endTime        = $row['endTime'];
        } else {
            echo "No results found or query failed: " . $connection->error;
        }
                
        $mailUsernameTo = '2024comp0178group22@gmail.com'; // test "to" account: password = comp0178group222024; this could be either "winnerEmail" or "sellerEmail" from above in practice
        $mailUsernameFrom = 'voxvulgaris2993@gmail.com'; // test "from" account
        $mailPassword = 'vozf uzpk xzof iiqb';

        $mail = new PHPMailer(true);

        if(!$containsFour) {
            try {        
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;
                $mail->Username = $mailUsernameFrom;  
                $mail->Password = $mailPassword;  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom($mailUsernameFrom, 'Auction App');
                $mail->addAddress($mailUsernameTo, 'Auction User');

                // Content        
                $mail->isHTML(true);
                $mail->Subject = "$auctionTitle: unsuccessful";
                $mail->Body    = "$buyerUsername, despite being the highest bidder for $sellerUsername's auction $auctionTitle, you have not won upon expiry at $endTime due to your bid of $bidPrice falling below the reserve price of $reservePrice.";

                // Send email
                $mail->send();
                echo 'Email sent successfully!';

                // Insertion
                $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$buyerID', '$auctionID', 4, NOW())";
                mysqli_query($connection, $insertionQuery);

            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $allNotificationsSent = false;
            }
        }

        if(!$containsFive) {
            try {        
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;
                $mail->Username = $mailUsernameFrom;  
                $mail->Password = $mailPassword;  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom($mailUsernameFrom, 'Auction App');
                $mail->addAddress($mailUsernameTo, 'Auction User');

                // Content        
                $mail->isHTML(true);
                $mail->Subject = "$auctionTitle: unsuccessful";
                $mail->Body    = "$sellerUsername, $auctionTitle unsuccessful upon expiry at $endTime: highest bid of $bidPrice below reserve price of $reservePrice.";

                // Send email
                $mail->send();
                echo 'Email sent successfully!';

                // Insertion
                $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$sellerID', '$auctionID', 5, NOW())";
                mysqli_query($connection, $insertionQuery);

            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                $allNotificationsSent = false;
            }
        }
        
        if (!$containsSix) {
            if ($watchlistResult) {
                $watchlistUsers = [];
                while ($row = mysqli_fetch_assoc($watchlistResult)) {
                    $watchlistUsers[] = [
                        'userID' => $row['userID'],
                        'username' => $row['username']
                    ];
                }
                foreach ($watchlistUsers as $user) {
                    $watchlistUserID = $user['userID'];
                    $watchlistUsername = $user['username'];
        
                    // Check if this user has already received notification type 3
                    $checkNotificationQuery = "SELECT 1 FROM notifications WHERE userID = $watchlistUserID AND auctionID = $auctionID AND notificationTypeID = 6";
                    $checkNotificationResult = mysqli_query($connection, $checkNotificationQuery);

                    if (mysqli_num_rows($checkNotificationResult) == 0) {
                        $watchlistUsername = $row['username'];
                        try {        
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';  
                            $mail->SMTPAuth = true;
                            $mail->Username = $mailUsernameFrom;  
                            $mail->Password = $mailPassword;  
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;
                    
                            // Recipients
                            $mail->setFrom($mailUsernameFrom, 'Auction App');
                            $mail->addAddress($mailUsernameTo, 'Auction User');
                    
                            // Content                
                            $mail->isHTML(true);
                            $mail->Subject = "Watching $auctionTitle: unsuccessful";
                            $mail->Body    = "$watchlistUsername, $sellerUsername's auction $auctionTitle is unsuccessful upon expiry at $endTime: highest bid of $bidPrice below the reserve price of $reservePrice.";
                    
                            // Send email
                            $mail->send();
                            echo 'Email sent successfully!';

                            // Insertion
                            $insertionQuery = "INSERT INTO notifications (userID, auctionID, notificationTypeID, sentAt) VALUES ('$watchlistUserID', '$auctionID', 6, NOW())";
                            mysqli_query($connection, $insertionQuery);                            
                            
                        } catch (Exception $e) {
                            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                            $allNotificationsSent = false;
                        }
                    }
                }
            } else {
                echo "Query failed: " . mysqli_error($connection);
            }
        }        
    }
}
?>