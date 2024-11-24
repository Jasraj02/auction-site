<?php include_once("header.php"); ?>
<?php require("utilities.php"); ?>
<?php require_once("database.php"); ?>

<div class="container">

    <h2 class="my-3">My Watchlist</h2>

    <?php
    // Ensure the user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo '<div class="alert alert-danger">You must be logged in to view your watchlist. Redirecting to browse...</div>';
        header("refresh:2;url=index.php");
        include_once("footer.php");
        exit();
    }

    $username = $_SESSION['username'] ?? 'Unknown User';
    $userRole = $_SESSION['account_type'] ?? 'Unknown Role';

    // Ensure the user has buyer privileges
    if ($userRole !== 'buyer' && $userRole !== 'both') {
        echo '<div class="alert alert-warning">You do not have the necessary privileges to view this page. Only buyers can view their watchlist.</div>';
        include_once("footer.php");
        exit();
    }

    // Retrieve user ID based on username
    $userIDQuery = "SELECT userID FROM users WHERE username = '$username'";
    $userIDResult = mysqli_query($connection, $userIDQuery);
    if ($userIDResult && $userIDRow = mysqli_fetch_assoc($userIDResult)) {
        $userID = $userIDRow['userID'];
    } else {
        echo '<div class="alert alert-danger">Error retrieving user details. Please try again later.</div>';
        include_once("footer.php");
        exit();
    }

    // Sorting and pagination setup
    $ordering = $_GET['order_by'] ?? 'popularityByBids';
    $resultsPerPage = 10;
    $curr_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($curr_page - 1) * $resultsPerPage;

    // Base query for retrieving listings
    $searchQuery = "
    SELECT Auctions.auctionID, Auctions.auctionTitle, Auctions.auctionDescription, 
           Auctions.startingPrice, Auctions.endTime, Auctions.startTime AS dateAdded,
           COALESCE(MAX(Bids.bidPrice), Auctions.startingPrice) AS currentPrice, 
           COUNT(Bids.bidID) AS numberBids,
           (SELECT COUNT(*) FROM UserViews WHERE UserViews.auctionID = Auctions.auctionID) AS userViews
    FROM Auctions
    LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
    INNER JOIN Watchlists ON Watchlists.auctionID = Auctions.auctionID
    WHERE Watchlists.buyerID = $userID
    GROUP BY Auctions.auctionID";

    // Sorting logic
    switch ($ordering) {
        case 'priceLowToHigh':
            $searchQuery .= " ORDER BY currentPrice ASC, Auctions.auctionTitle ASC";
            break;
        case 'priceHighToLow':
            $searchQuery .= " ORDER BY currentPrice DESC, Auctions.auctionTitle ASC";
            break;
        case 'popularityByBids':
            $searchQuery .= " ORDER BY numberBids DESC, Auctions.auctionTitle ASC";
            break;
        case 'popularityByViews':
            $searchQuery .= " ORDER BY userViews DESC, Auctions.auctionTitle ASC";
            break;   
        case 'date':
            $searchQuery .= " ORDER BY Auctions.endTime ASC, Auctions.auctionTitle ASC";
            break;
        case 'dateAdded':
            $searchQuery .= " ORDER BY Auctions.startTime DESC, Auctions.auctionTitle ASC";
            break;
        case 'popularityByBids2':
            $searchQuery .= " ORDER BY count ASC, Auctions.auctionTitle ASC";
            break;
        case 'popularityByUserViews2':
            $searchQuery .= " ORDER BY userViews ASC, Auctions.auctionTitle ASC";
            break;
            }

    // Pagination logic
    $searchQuery .= " LIMIT $resultsPerPage OFFSET $offset";

    $searchResult = mysqli_query($connection, $searchQuery);

    // Total listings for pagination
    $paginationQuery = "
    SELECT COUNT(DISTINCT Auctions.auctionID) AS totalListings
    FROM Auctions
    INNER JOIN Watchlists ON Watchlists.auctionID = Auctions.auctionID
    WHERE Watchlists.buyerID = $userID";
    $paginationResult = mysqli_query($connection, $paginationQuery);
    $totalListings = mysqli_fetch_assoc($paginationResult)['totalListings'];
    $max_page = ceil($totalListings / $resultsPerPage);
    ?>

    <div class="mb-3">
        <label for="order_by" class="form-label">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by" onchange="location = this.value;">
            <option value="mybids.php?order_by=popularityByBids" <?= ($ordering === 'popularityByBids') ? 'selected' : '' ?>>Most popular: bids</option>
            <option value="mybids.php?order_by=popularityByViews" <?= ($ordering === 'popularityByViews') ? 'selected' : '' ?>>Most popular: views</option>
            <option value="mybids.php?order_by=popularityByBids2" <?= ($ordering === 'popularityByBids2') ? 'selected' : '' ?>>Least popular: bids</option>
            <option value="mybids.php?order_by=popularityByViews2" <?= ($ordering === 'popularityByViews2') ? 'selected' : '' ?>>Least popular: views</option>
            <option value="mywatchlist.php?order_by=priceHighToLow" <?= ($ordering === 'priceHighToLow') ? 'selected' : '' ?>>Price: highest first</option>
            <option value="mywatchlist.php?order_by=priceLowToHigh" <?= ($ordering === 'priceLowToHigh') ? 'selected' : '' ?>>Price: lowest first</option>
            <option value="mywatchlist.php?order_by=date" <?= ($ordering === 'date') ? 'selected' : '' ?>>Time: ending soonest</option>
            <option value="mywatchlist.php?order_by=dateAdded" <?= ($ordering === 'dateAdded') ? 'selected' : '' ?>>Time: newly listed</option>
        </select>
    </div>

    <?php
    // Display listings
    if ($searchResult && mysqli_num_rows($searchResult) > 0) {
        echo '<ul class="list-group">';
        while ($row = mysqli_fetch_assoc($searchResult)) {
            $currentPrice = $row['currentPrice'];
            $endDate = new DateTime($row['endTime']);
            $userViews = $row['userViews'];
            $dateAdded = $row['dateAdded'];

            // Function from utilities.php to display each listing
            print_listing_li2(
                $row['auctionID'],
                $row['auctionTitle'],
                substr($row['auctionDescription'], 0, 200) . '...',
                $currentPrice,
                $row['numberBids'],
                $endDate,
                $userViews,
                $dateAdded
            );
        }
        echo '</ul>';
    } else {
        echo '<div class="alert alert-info">You have no items on your watchlist.</div>';
    }
    ?>

    <nav aria-label="Search results pages" class="mt-5">
        <ul class="pagination justify-content-center">
            <?php
            $querystring = "";
            foreach ($_GET as $key => $value) {
                if ($key != "page") {
                    $querystring .= "$key=$value&amp;";
                }
            }

            if ($curr_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="mywatchlist.php?' . $querystring . 'page=' . ($curr_page - 1) . '"><i class="fa fa-arrow-left"></i></a></li>';
            }
            for ($i = 1; $i <= $max_page; $i++) {
                if ($i == $curr_page) {
                    echo '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
                } else {
                    echo '<li class="page-item"><a class="page-link" href="mywatchlist.php?' . $querystring . 'page=' . $i . '">' . $i . '</a></li>';
                }
            }
            if ($curr_page < $max_page) {
                echo '<li class="page-item"><a class="page-link" href="mywatchlist.php?' . $querystring . 'page=' . ($curr_page + 1) . '"><i class="fa fa-arrow-right"></i></a></li>';
            }
            ?>
        </ul>
    </nav>

</div>

<?php include_once("footer.php"); ?>
