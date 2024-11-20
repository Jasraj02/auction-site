<?php session_start() ?>

<?php include_once("header.php")?>

<?php

// If user is not logged in or not a seller, they should not be able to use this page.
if (!isset($_SESSION['account_type']) || ($_SESSION['account_type'] == 'buyer')) {
  header('Location: browse.php');
}

// need to make sure prior User inputs are put back into the form
if (isset($_SESSION['auctionErrors'])) {
  $auctionErrors = $_SESSION['auctionErrors'];
  $previousInputs = $_SESSION["auctionInputs"];
}
else {
  $auctionErrors = [];
  $previousInputs = [];
}

// show all errors to user
function errorConstructor($errorList) {
  if (count($errorList) > 0) {
    $outputHTML = '<div class="alert alert-danger"> <ul>';
    foreach ($errorList as $indError) {
      $outputHTML = $outputHTML . '<li>' . $indError . '</li>';
    }
    $outputHTML = $outputHTML . '</ul></div>';
    echo($outputHTML);
  }
  else {
    return NULL;
  }
}

errorConstructor($auctionErrors);

?>

<div class="container">

<!-- Create auction form -->
<div style="max-width: 800px; margin: 10px auto">
  <h2 class="my-3">Create new auction</h2>
  <div class="card">
    <div class="card-body">
      <!-- Note: This form does not do any dynamic / client-side / 
      JavaScript-based validation of data. It only performs checking after 
      the form has been submitted, and only allows users to try once. You 
      can make this fancier using JavaScript to alert users of invalid data
      before they try to send it, but that kind of functionality should be
      extremely low-priority / only done after all database functions are
      complete. -->
      <form method="post" enctype="multipart/form-data" action="./create_auction_result.php">
        <div class="form-group row">
          <!-- added required to make sure you have to input something -->
          <label required for="auctionTitle" class="col-sm-2 col-form-label text-right">Title of auction</label>
          <div class="col-sm-10">
            <input type="text" name="auctionTitle" class="form-control" id="auctionTitle" placeholder="e.g. Black mountain bike" value ="<?php echo isset($previousInputs['title']) ? $previousInputs['title'] : '';?>">
            <small id="titleHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> A short description of the item you're selling, which will display in listings.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionDetails" class="col-sm-2 col-form-label text-right">Details</label>
          <div class="col-sm-10">
            <textarea class="form-control" name="auctionDetails" id="auctionDetails" rows="4"><?php echo isset($previousInputs['details']) ? $previousInputs['details'] : '';?></textarea>
            <small id="detailsHelp" class="form-text text-muted">Full details of the listing to help bidders decide if it's what they're looking for.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="imageFile" class="col-sm-2 col-form-label text-right">Image</label>
          <div class="col-sm-10">
            <input type="file" class="form-control" name="imageFile" id="imageFile"></input>
            <small id="detailsHelp" class="form-text text-muted">Image to show bidders what the item is.</small>
          </div>
        </div>
        <!-- could add some JS code here to support multiple images being uploaded 
        if doing this then also include in the POST request the number of images being uploaded --> 
        <div class="form-group row">
          <label for="auctionCategory" class="col-sm-2 col-form-label text-right">Category</label>
          <div class="col-sm-10">
            <select class="form-control" name="auctionCategory" id="auctionCategory">
              <!-- majority of the php code within each option tag was AI generated using OpenAI ChatGPT 4o-->
              <option value="choose" disabled <?= empty($previousInputs['category']) || $previousInputs['category'] === 'choose' ? 'selected' : ''; ?>>Choose...</option>
              <option value="art" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'art' ? 'selected' : ''; ?>>Art & Collectables</option>
              <option value="electronics" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
              <option value="fashion" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
              <option value="health" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'health' ? 'selected' : ''; ?>>Health & Beauty</option>
              <option value="home" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'home' ? 'selected' : ''; ?>>Home</option>
              <option value="lifestyle" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'lifestyle' ? 'selected' : ''; ?>>Lifestyle & Recreation</option>
              <option value="media" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'media' ? 'selected' : ''; ?>>Media</option>
              <option value="others" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'others' ? 'selected' : ''; ?>>Others</option>
              <option value="vehicles" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'vehicles' ? 'selected' : ''; ?>>Vehicles & Automotive</option>
              <option value="workplace" <?= isset($previousInputs['category']) && $previousInputs['category'] === 'workplace' ? 'selected' : ''; ?>>Workplace Supplies & Equipment</option>
            </select>
            </select>
            <small id="categoryHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Select a category for this item.</small>
          </div>
        </div>
        <div class="form-group row">
          <!-- added an input type of number AND required (to make sure you have to input something) -->
          <!-- could add more browser form validation to prevent negative numbers -->
          <label type="number" required for="auctionStartPrice" class="col-sm-2 col-form-label text-right">Starting price</label>
          <div class="col-sm-10">
	        <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" step= ".01" name="auctionStartPrice" class="form-control" id="auctionStartPrice" value ="<?php echo isset($previousInputs['startingPrice']) ? $previousInputs['startingPrice'] : '';?>">
            </div>
            <small id="startBidHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Initial bid amount.</small>
          </div>
        </div>
        <div class="form-group row">
          <!-- could add more form validation to prevent negative numbers -->
          <!-- added an input type of number -->
          <label type="number" for="auctionReservePrice" class="col-sm-2 col-form-label text-right">Reserve price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" step= ".01" name="auctionReservePrice" class="form-control" id="auctionReservePrice" value ="<?php echo isset($previousInputs['reservePrice']) ? $previousInputs['reservePrice'] : '';?>">
            </div>
            <small id="reservePriceHelp" class="form-text text-muted">Optional. Auctions that end below this price will not go through. This value is not displayed in the auction listing.</small>
          </div>
        </div>
        <div class="form-group row">
          <label for="auctionEndDate" class="col-sm-2 col-form-label text-right">End date</label>
          <div class="col-sm-10">
            <input type="datetime-local" name="auctionEndDate" class="form-control" id="auctionEndDate" value ="<?php echo isset($previousInputs['endTime']) ? $previousInputs['endTime'] : '';?>">
            <small id="endDateHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Day for the auction to end.</small>
          </div>
        </div>
        <button type="submit" class="btn btn-primary form-control">Create Auction</button>
      </form>
    </div>
  </div>
</div>

</div>


<?php include_once("footer.php")?>