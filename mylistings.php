<?php include_once("header.php"); ?>
<?php require("utilities.php"); ?>
<?php require_once("database.php"); ?>

<div class="container">

    <h2 class="my-3">My Listings</h2>

    <?php
    // Ensure the user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo '<div class="alert alert-danger">You must be logged in to view your listings. Redirecting to the login page...</div>';
        header("refresh:2;url=index.php");
        include_once("footer.php");
        exit();
    }

    $username = $_SESSION['username'] ?? 'Unknown User';
    $userRole = $_SESSION['account_type'] ?? 'Unknown Role';

    // Ensure the user has seller privileges
    if ($userRole !== 'seller' && $userRole !== 'both') {
        echo '<div class="alert alert-warning">You do not have the necessary privileges to view this page. Only sellers can view their listings.</div>';
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
    $ordering = $_GET['order_by'] ?? 'date';
    $resultsPerPage = 10;
    $curr_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($curr_page - 1) * $resultsPerPage;

    // Base query for retrieving listings
    $searchQuery = "
        SELECT Auctions.auctionID, Auctions.auctionTitle, Auctions.auctionDescription, 
               Auctions.startingPrice, Auctions.endTime, 
               COALESCE(MAX(Bids.bidPrice), Auctions.startingPrice) AS currentPrice, 
               COUNT(Bids.bidID) AS numberBids
        FROM Auctions
        LEFT JOIN Bids ON Auctions.auctionID = Bids.auctionID
        WHERE Auctions.sellerID = $userID
        GROUP BY Auctions.auctionID
    ";

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
        case 'date':
        default:
            $searchQuery .= " ORDER BY Auctions.endTime ASC, Auctions.auctionTitle ASC";
            break;
    }

    // Pagination logic
    $searchQuery .= " LIMIT $resultsPerPage OFFSET $offset";

    $searchResult = mysqli_query($connection, $searchQuery);

    // Total listings for pagination
    $paginationQuery = "
        SELECT COUNT(*) AS totalListings
        FROM Auctions
        WHERE Auctions.sellerID = $userID
    ";
    $paginationResult = mysqli_query($connection, $paginationQuery);
    $totalListings = mysqli_fetch_assoc($paginationResult)['totalListings'];
    $max_page = ceil($totalListings / $resultsPerPage);
    ?>

    <div class="mb-3">
        <label for="order_by" class="form-label">Sort by:</label>
        <select class="form-control" id="order_by" name="order_by" onchange="location = this.value;">
            <option value="mylistings.php?order_by=popularityByBids" <?= ($ordering === 'popularityByBids') ? 'selected' : '' ?>>Popularity: by bids</option>
            <option value="mylistings.php?order_by=priceHighToLow" <?= ($ordering === 'priceHighToLow') ? 'selected' : '' ?>>Price: highest first</option>
            <option value="mylistings.php?order_by=priceLowToHigh" <?= ($ordering === 'priceLowToHigh') ? 'selected' : '' ?>>Price: lowest first</option>
            <option value="mylistings.php?order_by=date" <?= ($ordering === 'date') ? 'selected' : '' ?>>Time: ending soonest</option>
        </select>
    </div>

    <?php
    // Display listings
    if ($searchResult && mysqli_num_rows($searchResult) > 0) {
        echo '<ul class="list-group">';
        while ($row = mysqli_fetch_assoc($searchResult)) {
            $currentPrice = $row['currentPrice'];
            $endDate = new DateTime($row['endTime']);

            // Function from utilities.php to display each listing
            print_listing_li(
                $row['auctionID'],
                $row['auctionTitle'],
                substr($row['auctionDescription'], 0, 200) . '...',
                $currentPrice,
                $row['numberBids'],
                $endDate
            );
        }
        echo '</ul>';
    } else {
        echo '<div class="alert alert-info">You have no active listings.</div>';
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
                echo '<li class="page-item"><a class="page-link" href="mylistings.php?' . $querystring . 'page=' . ($curr_page - 1) . '"><i class="fa fa-arrow-left"></i></a></li>';
            }
            for ($i = 1; $i <= $max_page; $i++) {
                if ($i == $curr_page) {
                    echo '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
                } else {
                    echo '<li class="page-item"><a class="page-link" href="mylistings.php?' . $querystring . 'page=' . $i . '">' . $i . '</a></li>';
                }
            }
            if ($curr_page < $max_page) {
                echo '<li class="page-item"><a class="page-link" href="mylistings.php?' . $querystring . 'page=' . ($curr_page + 1) . '"><i class="fa fa-arrow-right"></i></a></li>';
            }
            ?>
        </ul>
    </nav>

</div>

<?php include_once("footer.php"); ?>
