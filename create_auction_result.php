<?php include_once("header.php")?>

<div class="container my-5">

<?php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // first get all the form results
    $auctionTitle = trim($_POST["auctionTitle"] ?? "");
    $auctionDetails = trim($_POST["auctionDetails"] ?? "");
    $category = $_POST["auctionCategory"] ?? "";
    $startingPrice = $_POST["auctionStartPrice"] ?? 0;
    $reservePrice = ($_POST["auctionReservePrice"] ?? NULL);
    $endTime = $_POST["auctionEndDate"] ?? "";

    $sellerName = $_SESSION["username"];

    $errorAuction = [];

    // error checks for auction title
    $genericTitles = ["Item", "Auction", "Product", "For Sale"];
    if (empty($auctionTitle)) {
        $errorAuction[] = "Auction title required";
    } elseif (strlen($auctionTitle) > 50) {
        $errorAuction[] = "Auction title cannot be longer than 50 characters.";
    } elseif (strlen($auctionTitle) < 5) {
        $errorAuction[] = "Auction title must be longer than 5 characters.";
    } elseif (in_array($auctionTitle,$genericTitles)) {
        $errorAuction[] = "Auction title must not be generic.";
    } elseif (strip_tags($auctionTitle) !== $auctionTitle) {
        $errorAuction[] = "Invalid characters present in Auction Title.";
    }

    // error checks for auction detail box 
    if (strlen($auctionDetails) > 100) {
        $errorAuction[] = "Auction description cannot be longer than 100 characters.";
    } elseif (strip_tags($auctionDetails) !== $auctionDetails) {
        $errorAuction[] = "Auction description contains invalid characters.";
    }

    // error checks for Category
    if (empty($category)) {
        $errorAuction[] = "Auction category must be chosen.";
    }

    // error checks for Starting Price
    if (empty($startingPrice)) {
        $errorAuction[] = "Starting price must be given.";
    } elseif (!is_numeric($startingPrice)) {
        $errorAuction[] = "Starting price must be a number.";
    } 
    $startingPrice = (float)$startingPrice ;
    if ($startingPrice < 0) {
        $errorAuction[] = "Starting price must be a postive number.";
    } elseif (decimalPlaces($startingPrice) > 2) {
        $errorAuction[] = "Starting price must have a maximum of 2 decimal places.";
    }

    // error checks for Reserve Price
    if (!($reservePrice == NULL)) {
        // run error checking only if reserve price is given
        if (!is_numeric($reservePrice)) {
            $errorAuction[] = "Reserve price must be a number.";
        }
        $reservePrice = (float)$reservePrice ;
        if ($reservePrice <= 0) {
            $errorAuction[] = "Reserve price must be a postive number above zero.";
        } elseif ($reservePrice < $startingPrice) {
            $errorAuction[] = "Reserve price must be larger than Starting price.";
        } elseif (decimalPlaces($reservePrice) > 2) {
            $errorAuction[] = "Reserve price must have a maximum of 2 decimal places.";
        }
    } else {
        $reservePrice = $startingPrice;
    }

    // error checks for End Time 
    if (empty($endTime)) {
        $errorAuction[] = "End date for the auction must be given.";
    } 
    $oldEndTime = $endTime;
    $endTime = str_replace("T"," ", $endTime);

    if (time() >= strtotime($endTime)) {
        $errorAuction[] = "End date must be in the future.";
    }

    // convert category to categoryID 
    if ($category == "art") {
        $categoryID = 1;
    } elseif ($category == "electronics") {
        $categoryID = 2;
    } elseif ($category == "fashion") {
        $categoryID = 3;
    } elseif ($category == "health") {
        $categoryID = 4;
    } elseif ($category == "home") {
        $categoryID = 5;
    } elseif ($category == "lifestyle") {
        $categoryID = 6;
    } elseif ($category == "media") {
        $categoryID = 7;
    } elseif ($category == "others") {
        $categoryID = 8;
    } elseif ($category == "vehicles") {
        $categoryID = 9;
    } elseif ($category == "workplace") {
        $categoryID = 10;
    } else {
        $errorAuction[] = "Invalid category selected.";
    }

    // set auctionStatusID to 1 (ongoing)
    $auctionStatusID = 1;
    
    // perform error checks for Image (check image name for correct fileformat)
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
        $imageFileName = $_FILES['imageFile']['name'];
        $imageLocation = $_FILES['imageFile']['tmp_name'];
        $imageSize = $_FILES['imageFile']['size'];
        $imageType = $_FILES['imageFile']['type'];
        
        // make sure file type is correct (only want jpeg, jpg or png)
        $allowedTypes = ['image/jpeg', 'image/png','image/jpg'];
        if (!in_array($imageType, $allowedTypes)) {
            $errorAuction[] = "Invalid image format. Only JPG and PNG are allowed.";
        }
        // ensure image is not too large 
        if ($imageSize >= 16777215) {
            $errorAuction[] = "File uploaded is too large. Upper limit is 16 Mb.";
        }

        $imageData = file_get_contents($imageLocation);

    } else if (empty($_FILES['imageFile']['name'])) {
        $imageFileName = NULL;
        $imageData = NULL;
    } else {
        $errorAuction[] = "An unknown error occurred.";
    }


    // save all variables inputted so far into the session
    $userAuctionInputs = [];
    $userAuctionInputs["title"] = htmlspecialchars($auctionTitle);
    $userAuctionInputs["details"] = htmlspecialchars($auctionDetails);
    $userAuctionInputs["category"] = htmlspecialchars($category);
    $userAuctionInputs["startingPrice"] = $startingPrice;
    $userAuctionInputs["reservePrice"] = $reservePrice;
    $userAuctionInputs["endTime"] = $oldEndTime;

    // SAVE IMAGE TO MEDIUMBLOB FORMAT SO THAT IT CAN BE PUT INTO THE DATABASE

    // check and see if any errors are present
    if (count($errorAuction) > 0) {
        $_SESSION["auctionErrors"] = $errorAuction;
        $_SESSION["auctionInputs"] = $userAuctionInputs;
        // RUN BELOW LINE WHEN AUCTION FAILS (BEST TO LINK BACK TO CREATE AUCTIONS PAGE)
        header('Location: create_auction.php');
        exit;
    }
    else {
        $_SESSION["auctionErrors"] = [];
    }
}

/* TODO #3: If everything looks good, make the appropriate call to insert
            data into the database. */


// find sellerID
$sellerIDQuery = "SELECT userID FROM Users WHERE username='$sellerName'";
$sellerIDResult = mysqli_query($connection, $sellerIDQuery) or die("Error making select sellerID query".mysql_error());
$sellerIDRow = mysqli_fetch_array($sellerIDResult);
$sellerID = (int)$sellerIDRow["userID"];

// convert start time and end time into correct format for database
$currentTime = time();
$currentTime = date('Y-m-d H:i:s',$currentTime);
$endTime = $endTime . ":00";

// prevent SQL injection 
$auctionTitle = mysqli_real_escape_string($connection, $auctionTitle);
$auctionDetails = mysqli_real_escape_string($connection, $auctionDetails);

// UPLOAD IMAGE FIRST AND GET THE imageID so that you can add it to the Auction table
// UPLOAD IMAGEDATA AND IMAGE FILENAME INTO Image table first, take imageID and place reference into the Auction table
// MAKE APPROPRIATE CHECKS TO SEE IF NO IMAGE HAS BEEN UPLOADED

// upload image to database
if (isset($imageFileName)){
    $imageFileName = mysqli_real_escape_string($connection, $imageFileName);
    $imageData = mysqli_real_escape_string($connection,$imageData);
    $imgSQLQuery = "INSERT INTO Images (imageFileName,imageFile) VALUES ('$imageFileName','$imageData')";
    mysqli_query($connection, $imgSQLQuery) or die("Error creating the INSERT Image query".mysql_error($connection));
    $imageID = mysqli_insert_id($connection);

    $sqlQuery = "INSERT INTO Auctions (auctionTitle,sellerID,categoryID,auctionDescription,startingPrice,reservePrice,currentPrice,startTime,endTime,imageID,auctionStatusID) VALUES ('$auctionTitle',$sellerID,$categoryID,'$auctionDetails',$startingPrice,$reservePrice,$startingPrice,'$currentTime','$endTime',$imageID, $auctionStatusID);";

} else {
    $sqlQuery = "INSERT INTO Auctions (auctionTitle,sellerID,categoryID,auctionDescription,startingPrice,reservePrice,currentPrice,startTime,endTime,auctionStatusID) VALUES ('$auctionTitle',$sellerID,$categoryID,'$auctionDetails',$startingPrice,$reservePrice,$startingPrice,'$currentTime','$endTime', $auctionStatusID);";
}

// do the upload to the database 
mysqli_query($connection, $sqlQuery) or die("Error creating the INSERT Auction query".mysql_error($connection));
mysqli_close($connection);

// clear user input and errors as upload is successful
$userAuctionInputs = [];
$_SESSION["auctionInputs"] = $userAuctionInputs;

// If all is successful, let user know.

// MAKE SURE TO CHANGE THE FIX ME PART AFTER COMPLETING THE LISTING PART
// may need to use a GET request to change the url to the auction URL page
// will need to make changes to listing.php
echo('<div class="text-center">Auction successfully created! <a href="mylistings.php">View all your listings.</a></div>');

?>

</div>


<?php include_once("footer.php")?>
