<?php session_start() ?>;

<?php include_once("header.php")?>

<div class="container my-5">

<?php

// This function takes the form data and adds the new auction to the database.

/* TODO #1: Connect to MySQL database (perhaps by requiring a file that
            already does this). */

require_once("database.php");

/* TODO #2: Extract form data into variables. Because the form was a 'post'
            form, its data can be accessed via $POST['auctionTitle'], 
            $POST['auctionDetails'], etc. Perform checking on the data to
            make sure it can be inserted into the database. If there is an
            issue, give some semi-helpful feedback to user. */

// function to find the number of decimal places 
function decimalPlaces($numberGiven) {
    if (is_integer($numberGiven)) {
        $dp = 0;
    } else {
        $dp = strlen($numberGiven) - strrpos($numberGiven, '.') - 1;
    }
    return $dp;
}

// function to print any errors picked up (helps when debugging)
function errorPrinter($errorList) {
    if (count($errorList) === 0) {
        echo "No errors";
    } else {
        foreach($errorList as $indError) {
            echo "$indError";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // first get all the form results
    $auctionTitle = trim($_POST["auctionTitle"] ?? "");
    $auctionDetails = trim($_POST["auctionDetails"] ?? "");
    $category = $_POST["auctionCategory"] ?? "";
    $startingPrice = $_POST["auctionStartPrice"] ?? 0;
    $reservePrice = ($_POST["auctionReservePrice"] ?? NULL);
    $endTime = $_POST["auctionEndDate"] ?? "";

    $sellerName = $_SESSION["username"];

    $error = [];

    // error checks for auction title
    $genericTitles = ["Item", "Auction", "Product", "For Sale"];
    if (empty($auctionTitle)) {
        $error[] = "Auction title required";
    } elseif (strlen($auctionTitle) > 100) {
        $error[] = "Auction title cannot be longer than 100 characters.";
    } elseif (strlen($auctionTitle) < 5) {
        $error[] = "Auction title must be longer than 5 characters.";
    } elseif (in_array($auctionTitle,$genericTitles)) {
        $error[] = "Auction title must not be generic.";
    } elseif (strip_tags($auctionTitle) !== $auctionTitle) {
        $error[] = "Invalid characters present in Auction Title.";
    }

    // error checks for auction detail box 
    if (strlen($auctionDetails) > 250) {
        $error[] = "Auction description cannot be longer than 250 characters.";
    } elseif (strip_tags($auctionDetails) !== $auctionDetails) {
        $error[] = "Auction description contains invalid characters.";
    }

    // error checks for Category
    if (empty($category)) {
        $error[] = "Auction category must be chosen.";
    }

    // error checks for Starting Price
    if (empty($startingPrice)) {
        $error[] = "Starting price must be given.";
    } elseif (!is_numeric($startingPrice)) {
        $error[] = "Starting price must be a number.";
    } 
    $startingPrice = (float)$startingPrice ;
    if ($startingPrice < 0) {
        $error[] = "Starting price must be a postive number.";
    } elseif (decimalPlaces($startingPrice) > 2) {
        $error[] = "Starting price must have a maximum of 2 decimal places.";
    }

    // error checks for Reserve Price
    if (!($reservePrice == NULL)) {
        // run error checking only if reserve price is given
        if (!is_numeric($reservePrice)) {
            $error[] = "Reserve price must be a number.";
        }
        $reservePrice = (float)$reservePrice ;
        if ($reservePrice <= 0) {
            $error[] = "Reserve price must be a postive number.";
        } elseif ($reservePrice > $startingPrice) {
            $error[] = "Reserve price must be smaller than Starting price.";
        } elseif (decimalPlaces($reservePrice) > 2) {
            $error[] = "Reserve price must have a maximum of 2 decimal places.";
        }
    }

    // error checks for End Time 
    if (empty($endTime)) {
        $error[] = "End date for the auction must be given.";
    } 
    $endTime = str_replace("T"," ", $endTime);

    if (time() >= strtotime($endTime)) {
        $error[] = "End date must be in the future.";
    }
    
    if (count($error) > 0) {
        $_SESSION['errors'] = $error;
        header('Location: register.php');
        exit;
    }

    // CONVERT Category to CategoryID 

}


/* TODO #3: If everything looks good, make the appropriate call to insert
            data into the database. */

// create a SQL query
// make an auctionID
// do a lookup to get the sellerID
// need to add auctionTitle to the AuctionsTable

$sqlQuery = "INSERT INTO Auctions (sellerID,categoryID,auctionDescription,imageFileName,startingPrice,reservePrice,currentPrice,startTime,endTime)";
$sqlQuery = $sqlQuery . "VALUES () ";

    // $auctionTitle = trim($_POST["auctionTitle"] ?? "");
    // $auctionDetails = trim($_POST["auctionDetails"] ?? "");
    // $category = $_POST["auctionCategory"] ?? "";
    // $startingPrice = $_POST["auctionStartPrice"] ?? 0;
    // $reservePrice = ($_POST["auctionReservePrice"] ?? NULL);
    // $endTime = $_POST["auctionEndDate"] ?? "";


// If all is successful, let user know.

// MAKE SURE TO CHANGE THE FIX ME PART 
// may need to use a GET request to change the url to the auction URL page
// will need to make changes to listing.php
echo('<div class="text-center">Auction successfully created! <a href="FIXME">View your new listing.</a></div>');


?>

</div>


<?php include_once("footer.php")?>