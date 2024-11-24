<?php
//https://blog.expertrec.com/autocomplete-search-in-php/
require_once("database.php");

if (!empty($_POST["keyword"])) {
    $keyword = mysqli_real_escape_string($connection, $_POST["keyword"]);
    $query = "SELECT auctionTitle FROM Auctions WHERE auctionTitle LIKE '$keyword%' AND endTime > CURRENT_TIMESTAMP() ORDER BY auctionTitle LIMIT 5";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        ?>
        <style>
            #auction-list {
                list-style-type: none;
                padding: 10px;
                margin: 0;
                border: 1px solid #ccc;
                border-radius: 5px;
                max-width: 300px;
                max-height: 80px
                background-color: #f9f9f9;
            }

            #auction-list li {
                padding: 10px;
                cursor: pointer;
                color: blue;
                font-weight: bold;
                text-align: left;
                max-width: 300px;
                max-height: 80px                
                transition: background-color 0.3s ease;
            }

            #auction-list li:hover {
                background-color: #e0e0e0;
            }

            #auction-list li:active {
                background-color: #d0d0d0;
            }
   
            #search-bar {
                width: 400px; 
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
        </style>

        <ul id="auction-list">
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <li onClick="selectAuction('<?php echo htmlspecialchars($row["auctionTitle"], ENT_QUOTES); ?>');">
                <?php echo htmlspecialchars($row["auctionTitle"]); ?>
            </li>
            <?php 
        } 
        ?>
        </ul>
        <?php 
    } else {
        ?>

<style>
            #auction-list {
                list-style-type: none;
                padding: 10px;
                margin: 0;
                border: 1px solid #ccc;
                border-radius: 5px;
                max-width: 300px;
                max-height: 80px
                background-color: #f9f9f9;


            }
            #auction-list li {
                padding: 10px;
                color: blue;
                font-weight: bold;
                text-align: left;
                max-width: 300px;
                max-height: 80px                
                transition: background-color 0.3s ease;
            }
        </style>

        <ul id="auction-list">
            <li>No results found</li>
        </ul>
        <?php
    }
}
?>
