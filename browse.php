<?php include_once("header.php"); ?>
<?php require("utilities.php"); ?>
<?php require_once("database.php"); ?>

<div class="container">

<h2 class="my-3">Browse listings</h2>

<div id="searchSpecs">
<!-- When this form is submitted, this PHP page is what processes it.
     Search/sort specs are passed to this page through parameters in the URL
     (GET method of passing data to a page). -->
<form method="get" action="browse.php">
  <div class="row">
    <div class="col-md-5 pr-0">
      <div class="form-group">
        <label for="keyword" class="sr-only">Search keyword:</label>
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-transparent pr-0 text-muted">
              <i class="fa fa-search"></i>
            </span>
          </div>
          <input type="text" class="form-control border-left-0" id="keyword" name="keyword" placeholder="Search for anything">
        </div>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-group">
        <label for="cat" class="sr-only">Search within:</label>
        <select class="form-control" id="cat" name="cat">
          <option value="all">All categories</option>
          <?php
          //create dropdown menu for categories
            $searchQuery = "SELECT categoryID, categoryType FROM Categories ORDER BY categoryType";
            $searchResult = mysqli_query($connection, $searchQuery);
            
            while ($category = mysqli_fetch_assoc($searchResult)) {
              $selected = '';
              if (isset($_GET['cat']) && $_GET['cat'] == $category['categoryID']) {
                  $selected = 'selected';
              }
              echo "<option value='" . $category['categoryID'] . "' $selected>" . 
                   htmlspecialchars($category['categoryType']) . "</option>";
            }
          ?>
        </select>
      </div>
    </div>
    <div class="col-md-3 pr-0">
      <div class="form-inline">
        <label class="mx-2" for="order_by">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by">
<?php
// allow users to choose sorting option and makes sure sorting option is maintained when page is refreshed
?>
          <option value="popularityByBids" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'popularityByBids') ? 'selected' : ''; ?>>
            Popularity: by bids
          </option> 
          <option value="priceHighToLow" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'priceHighToLow') ? 'selected' : ''; ?>>
            Price: highest first
          </option>         
          <option value="priceLowToHigh" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'priceLowToHigh') ? 'selected' : ''; ?>>
            Price: lowest first
          </option>
          <option value="date" <?php echo (isset($_GET['order_by']) && $_GET['order_by'] == 'date') ? 'selected' : ''; ?>>
            Time: ending soonest
          </option>
<?php
  //****need to add option to sort popularity by user views*****
  ?>
          </option>    
        </select>
      </div>
    </div>
    <div class="col-md-1 px-0">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </div>
</form>
</div>  <!-- end search specs bar -->


</div>
<?php

//default for keyword is empty
if (!isset($_GET['keyword'])) {
  $keyword = '';
} else {
  $keyword = $_GET['keyword'];
}

//default for categories is all
if (!isset($_GET['cat'])) {
  $category = 'all';
} else {
  $category = $_GET['cat'];
}

//if order_by value not specified, sort by popularity of bids
if (!isset($_GET['order_by'])) {
  $ordering = 'popularityByBids';
} else {
  $ordering = $_GET['order_by'];
}

if (!isset($_GET['page'])) {
  $curr_page = 1;
} else {
  $curr_page = $_GET['page'];
}

//query to retrieve data from the database
$searchQuery = "SELECT Auctions.*, Auctions.auctionTitle, MAX(Bids.bidPrice) as currentPrice, COUNT(Bids.bidID) as numberBids
                FROM Auctions
                LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
                WHERE Auctions.endTime>CURRENT_TIMESTAMP()";

if (!empty($keyword)) {
  $searchQuery .= " AND (Auctions.auctionTitleLIKE '%$keyword%')";
}
if ($category != 'all') {
  $searchQuery .= " AND Auctions.categoryID = '$category'";
}
$searchQuery .= " GROUP BY Auctions.auctionID";

//order results to match sorting option, if multiple items have same value order alphabetically
switch ($ordering) {
  case  'priceLowtoHigh':
  case 'priceLowToHigh':
    $searchQuery .= " ORDER BY COALESCE(MAX(Bids.bidPrice), Auctions.startingPrice) ASC, Auctions.auctionTitle ASC";
    break;
  case 'priceHighToLow':
    $searchQuery .= " ORDER BY COALESCE(MAX(Bids.bidPrice), Auctions.startingPrice) DESC, Auctions.auctionTitle ASC";
    break;
  case 'date':
    $searchQuery .= " ORDER BY Auctions.endTime ASC, Auctions.auctionTitle ASC";
    break;
  case 'popularityByBids':
    $searchQuery .= " ORDER BY numberBids DESC, Auctions.auctionTitle ASC";
    break;
}

//Pagination
//limit number of items per page
$resultsPerPage = 10;
$offset = ($curr_page - 1) * $resultsPerPage;
$searchQuery .= " LIMIT $resultsPerPage OFFSET $offset";

//calculate number of items that match criteria
$paginationQuery = "SELECT COUNT(DISTINCT Auctions.auctionID) AS numberRows
                    FROM Auctions
                    LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
                    WHERE Auctions.endTime>CURRENT_TIMESTAMP()";

if (!empty($keyword)) {
  $paginationQuery .= " AND (Auctions.auctionTitle LIKE '%$keyword%')";
}
if ($category != 'all') {
  $paginationQuery .= " AND Auctions.categoryID = '$category'";
}

//calculate number of pages
$resultsQuery = mysqli_query($connection, $paginationQuery);
$numResults = mysqli_fetch_assoc($resultsQuery);
$max_page = ceil($numResults['numberRows'] / $resultsPerPage);

$searchResult = mysqli_query($connection, $searchQuery);

?>

<div class="container mt-5">
<ul class="list-group">

<?php

//error messages
if (!$searchResult) {
  echo "<div class='alert alert-danger'>Error executing query: " . mysqli_error($connection) ;
} elseif (mysqli_num_rows($searchResult) == 0) {
  echo '<div class="alert alert-info">No auction results match your search criteria.';
} 

//while loop to print a list item for each auction listing retrieved from the query
else {
  while ($row = mysqli_fetch_assoc($searchResult)) {
    $currentPrice = $row['currentPrice'] ?? $row['startingPrice'];
    $endDate = new DateTime($row['endTime']);

    //function in utilities.php
    print_listing_li($row['auctionID'], $row['auctionTitle'], substr($row['auctionDescription'], 0, 200) . '...',
      $currentPrice, $row['numberBids'], $endDate); }
}
?>
</ul>

<nav aria-label="Search results pages" class="mt-5">
  <ul class="pagination justify-content-center">
  <?php

    // Copy any currently-set GET variables to the URL.
    $querystring = "";
    foreach ($_GET as $key => $value) {
      if ($key != "page") {
        $querystring .= "$key=$value&amp;";
      }
    }

    $high_page_boost = max(3 - $curr_page, 0);
    $low_page_boost = max(2 - ($max_page - $curr_page), 0);
    $low_page = max(1, $curr_page - 2 - $low_page_boost);
    $high_page = min($max_page, $curr_page + 2 + $high_page_boost);

    if ($curr_page != 1) {
      echo('<li class="page-item"><a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page - 1) . '"><i class="fa fa-arrow-left"></i></a></li>');
    }
    for ($i = $low_page; $i <= $high_page; $i++) {
      if ($i == $curr_page) {
        // Highlight the link 
        echo('
        <li class="page-item active">');
      } 
      else {
           // Non-highlighted link
        echo('
      <li class="page-item">');
      }

      // Do this in any case
      echo('
        <a class="page-link" href="browse.php?' . $querystring . 'page=' . $i . '">' . $i . '</a>
      </li>');
    }
  

    if ($curr_page != $max_page) {
      echo('
      <li class="page-item">
        <a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next">
          <span aria-hidden="true"><i class="fa fa-arrow-right"></i></span>
          <span class="sr-only">Next</span>
        </a>
      </li>');
    }
  ?>
  
    </ul>
  </nav>
  
  
  </div>
  
  
  
  <?php include_once("footer.php")?>
