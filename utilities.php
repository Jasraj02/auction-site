<?php

// display_time_remaining:
// Helper function to help figure out what time to display
function display_time_remaining($interval) {

    if ($interval->days == 0 && $interval->h == 0) {
      // Less than one hour remaining: print mins + seconds:
      $time_remaining = $interval->format('%im %Ss');
    }
    else if ($interval->days == 0) {
      // Less than one day remaining: print hrs + mins:
      $time_remaining = $interval->format('%hh %im');
    }
    else {
      // At least one day remaining: print days + hrs:
      $time_remaining = $interval->format('%ad %hh');
    }

  return $time_remaining;

}

// print_listing_li:
// This function prints an HTML <li> element containing an auction listing
function print_listing_li($item_id, $title, $desc, $price, $num_bids, $end_time, $userViews)
{
  // Truncate long descriptions
  if (strlen($desc) > 250) {
    $desc_shortened = substr($desc, 0, 250) . '...';
  }
  else {
    $desc_shortened = $desc;
  }
  
  // Fix language of bid vs. bids
  if ($num_bids == 1) {
    $bid = ' bid';
  }
  else {
    $bid = ' bids';
  }
  
  // Calculate time to auction end
  $now = new DateTime();
  if ($now > $end_time) {
    $time_remaining = 'This auction has ended';
  }
  else {
    // Get interval:
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($time_to_end) . ' remaining';
  }
  
  //determine singular or plural for views
  $viewLabel = ($userViews == 1) ? 'view' : 'views';

  // Print HTML
  echo('
      <li class="list-group-item d-flex justify-content-between">
          <div class="p-2 mr-5"><h5><a href="listing.php?item_id=' . $item_id . '">' . $title . '</a></h5>' . $desc_shortened . '</div>
          <div class="text-center text-nowrap"><span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid . '<br/>' . $time_remaining . '<br/>
              <small class="text-muted">' . $userViews . ' ' . $viewLabel . '</small>
          </div>
      </li>
  ');
}

// modified based on print_listing_li
function print_bidding_li($item_id, $title, $desc, $price, $num_bids, $end_time, $userViews, $date_added)
{
    // Truncate long descriptions
    if (strlen($desc) > 250) {
        $desc_shortened = substr($desc, 0, 250) . '...';
    } else {
        $desc_shortened = $desc;
    }

    // Fix language of bid vs. bids
    $bid = ($num_bids == 1) ? ' bid' : ' bids';

    // Calculate time to auction end
    $now = new DateTime();
    $time_remaining = ($now > $end_time) 
        ? 'This auction has ended' 
        : display_time_remaining(date_diff($now, $end_time)) . ' remaining';
      
    // Format the added date
    $formatted_date_added = (new DateTime($date_added))->format('j M Y');

    // Determine singular/plural for views
    $viewLabel = ($userViews == 1) ? 'view' : 'views';


    // Print HTML
    echo('
        <li class="list-group-item d-flex justify-content-between">
            <div class="p-2 mr-5">
                <h5><a href="bid_listing.php?item_id=' . $item_id . '">' . $title . '</a></h5>
                <p>' . $desc_shortened . '</p>
                <small class="text-muted">Added on: ' . $formatted_date_added . '</small>
            </div>
            <div class="text-center text-nowrap">
                <span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid . '<br/>' . $time_remaining . '<br/>
                <small class="text-muted">' . $userViews . ' ' . $viewLabel . '</small>
            </div>
        </li>
    ');
  }

// print_listing_li:
// This function prints an HTML <li> element containing an auction listing
function print_listing_li2($item_id, $title, $desc, $price, $num_bids, $end_time, $userViews, $date_added)
{
  // Truncate long descriptions
  if (strlen($desc) > 250) {
    $desc_shortened = substr($desc, 0, 250) . '...';
  }
  else {
    $desc_shortened = $desc;
  }
  
  // Fix language of bid vs. bids
  if ($num_bids == 1) {
    $bid = ' bid';
  }
  else {
    $bid = ' bids';
  }
  
  // Calculate time to auction end
  $now = new DateTime();
  if ($now > $end_time) {
    $time_remaining = 'This auction has ended';
  }
  else {
    // Get interval:
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($time_to_end) . ' remaining';
  }
  
  // Format the added date
  $formatted_date_added = (new DateTime($date_added))->format('j M Y');

  // Determine singular or plural for views
  $viewLabel = ($userViews == 1) ? 'view' : 'views';

  // Print HTML
  echo('
      <li class="list-group-item d-flex justify-content-between">
          <div class="p-2 mr-5">
              <h5><a href="listing.php?item_id=' . $item_id . '">' . $title . '</a></h5>
              <p>' . $desc_shortened . '</p>
              <small class="text-muted">Added on: ' . $formatted_date_added . '</small>
          </div>
          <div class="text-center text-nowrap">
              <span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid . '<br/>' . $time_remaining . '<br/>
              <small class="text-muted">' . $userViews . ' ' . $viewLabel . '</small>
          </div>
      </li>
  ');
}



// function returns important details regarding an auction from the auctionID 
function giveAuctionDetails ($auctionID,$databaseConnection) {
  $detailsQuery = "SELECT auctionTitle, sellerID, categoryID, auctionDescription, imageID, 
                  startingPrice, reservePrice, currentPrice, startTime, endTime
                  FROM Auctions
                  WHERE auctionID = $auctionID";
  $detailsResult = mysqli_query($databaseConnection,$detailsQuery);
  $detailsRow = mysqli_fetch_assoc($detailsResult);
  if (isset($detailsRow)) {
      $auctionDetails = [
          'auctionTitle' => $detailsRow['auctionTitle'],
          'sellerID' => $detailsRow['sellerID'],
          'categoryID' => $detailsRow['categoryID'],
          'auctionDescription' => $detailsRow['auctionDescription'],
          'imageID' => $detailsRow['imageID'],
          'startingPrice' => $detailsRow['startingPrice'],
          'reservePrice' => $detailsRow['reservePrice'],
          'currentPrice' => $detailsRow['currentPrice'],
          'startTime' => $detailsRow['startTime'],
          'endTime' => $detailsRow['endTime']
      ];
  }
  else {
      $auctionDetails = NULL;
  }
  
  return $auctionDetails;
}

function giveAuctionViews($auctionID,$databaseConnection) {
  $auctionViewsQuery = "SELECT COUNT(userID) AS views
                        FROM UserViews
                        WHERE auctionID = $auctionID;";
  $auctionViewsResult = mysqli_query($databaseConnection,$auctionViewsQuery);
  $auctionViewRow = mysqli_fetch_assoc($auctionViewsResult);
  return $auctionViewRow['views'];
}


function giveAuctionBids($auctionID,$databaseConnection) {
  $auctionBidsQuery = "SELECT COUNT(bidID) AS bids
                        FROM Bids
                        WHERE auctionID = $auctionID;";
  $auctionBidsResult = mysqli_query($databaseConnection,$auctionBidsQuery);
  $auctionBidsRow = mysqli_fetch_assoc($auctionBidsResult);

  return $auctionBidsRow['bids'];

}

function giveBidPercentageIncrease($auctionID,$databaseConnection) {
  $bidIncreaseQuery = " SELECT (SELECT bidPrice 
                    FROM Bids 
                    WHERE auctionID = $auctionID 
                    ORDER BY bidPrice DESC 
                    LIMIT 1) AS latestBid,
                    (SELECT bidPrice 
                    FROM Bids 
                    WHERE auctionID = $auctionID 
                    ORDER BY bidPrice DESC 
                    LIMIT 1 OFFSET 1) AS previousBid";
  
  $bidIncreaseResult = mysqli_query($databaseConnection, $bidIncreaseQuery);

  if (isset($bidIncreaseResult)) {
    $bidIncreaseRow = mysqli_fetch_assoc($bidIncreaseResult);
    $latestBid = isset($bidIncreaseRow['latestBid']) ? $bidIncreaseRow['latestBid'] : NULL;
    $previousBid = isset($bidIncreaseRow['previousBid']) ? $bidIncreaseRow['previousBid'] : NULL;
    
    if (isset($latestBid)) {
      $latestBid = (float)$latestBid;
    }
    
    if (isset($previousBid)) {
      $previousBid = (float)$previousBid;
    }
    
    if (isset($latestBid) && isset($previousBid)) {
      $increasePercentage = ($latestBid - $previousBid)/($previousBid);
      return $increasePercentage;
    } else {
      $increasePercentage = 0;
      return $increasePercentage;
    }

    
  }
  else {
    return NULL;
  }

    
}


function suggestedPriceIncrease($auctionID,$databaseConnection) {

  $auctionDetails = giveAuctionDetails($auctionID, $databaseConnection);
  // find time left on auction
  $endTime = new DateTime($auctionDetails['endTime']);
  $currentTime = new DateTime();
  $minutesRemaining = ($currentTime < $endTime) 
  ? $currentTime->diff($endTime)->format('%a') * 1440 + $currentTime->diff($endTime)->format('%h') * 60 + $currentTime->diff($endTime)->format('%i')
  : 0;

  $minutesMultiplier = 1;
  $bidMultiplier = 1;
  $viewMultiplier = 1;
  $bidIncreaseMultiplier = 1;

  // apply an minuyes multiplier
  if ($minutesRemaining <= 5) {
    $minutesMultiplier = 1.25;
  } else if ($minutesRemaining <= 60) {
    $minutesMultiplier = 1.2;
  } elseif ($minutesRemaining <= 360) { 
    $minutesMultiplier = 1.15;
  } elseif ($minutesRemaining <= 720) { 
    $minutesMultiplier = 1.10;
  } elseif ($minutesRemaining <= 1440) {
    $minutesMultiplier = 1.05;
}

$viewCount = giveAuctionViews($auctionID, $databaseConnection);
$bidCount = giveAuctionBids($auctionID, $databaseConnection);

  // apply multiplier for bids
  if ($bidCount >= 20) {
      $bidMultiplier = 1.40;
  } elseif ($bidCount >= 15) {
      $bidMultiplier = 1.30;
  } elseif ($bidCount >= 10) {
      $bidMultiplier = 1.20;
  } elseif ($bidCount >= 5) {
      $bidMultiplier = 1.15;
  }

  // apply multiplier for views
  if ($viewCount >= 50) {
      $viewMultiplier = 1.40;
  } elseif ($viewCount >= 40) {
      $viewMultiplier = 1.30;
  } elseif ($viewCount >= 30) {
      $viewMultiplier = 1.20;
  } elseif ($viewCount >= 20) {
      $viewMultiplier = 1.10;
  } elseif ($viewCount >= 10) {
      $viewMultiplier = 1.05;
  }


$bidPercentageIncrease = giveBidPercentageIncrease($auctionID,$databaseConnection);

// apply multiplier for latest bid increase
if (isset($bidPercentageIncrease)) {
  if ($bidPercentageIncrease >= 30) {
      $bidIncreaseMultiplier = 1.5;
  } elseif ($bidPercentageIncrease >= 20) {
      $bidIncreaseMultiplier = 1.4;
  } elseif ($bidPercentageIncrease >= 15) {
      $bidIncreaseMultiplier = 1.2;
  } elseif ($bidPercentageIncrease >= 10) {
      $bidIncreaseMultiplier = 1.1;
  }
}


$basePercentIncrease = 5;
$suggestedPercentIncrease = $basePercentIncrease * $minutesMultiplier * $bidMultiplier * $viewMultiplier * $bidIncreaseMultiplier;

return $suggestedPercentIncrease;
}