-- Version 1 (if sufficient bid history): 
WITH rankQuery AS (
    -- Rank query: Count of overlapping auctions based on similar buyers' bidding histories
    SELECT buyerID, COUNT(DISTINCT auctionID) AS buyerRank
    FROM bids
    WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = 1)
    AND buyerID != 1
    GROUP BY buyerID
),
auctionsQuery AS (
    -- All auctions bid on by similar buyers but not bid on by the given user (buyerID = 1)
    SELECT DISTINCT auctionID
    FROM bids 
    WHERE buyerID IN (SELECT DISTINCT buyerID FROM bids WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = 1) AND buyerID != 1)
    -- Exclude auctions given buyer has already bid on or won (by extension)
    AND auctionID NOT IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = 1)
    -- Exclude auctions on given buyer's watchlist
    AND auctionID NOT IN (SELECT auctionID FROM watchlists WHERE buyerID = 1)
),
categoriesQuery AS (
    -- All categories associated with given buyer's bid history
    SELECT DISTINCT categoryID
    FROM auctions
    WHERE auctionID IN (SELECT DISTINCT auctionID FROM bids WHERE buyerID = 1)
)
-- Join the two subqueries to sum the rank values for the auctions (without grouping, multiple rows for each auction by buyer/rank)
SELECT a.auctionID, SUM(r.buyerRank) AS totalRank
FROM auctionsQuery a
-- Left joins since we don't want to exclude auctions outside of given buyer's category history
LEFT JOIN auctions auc ON a.auctionID = auc.auctionID
LEFT JOIN categoriesQuery c ON auc.categoryID = c.categoryID
-- Join table to access relevant buyerIDs 
INNER JOIN bids b ON a.auctionID = b.auctionID 
INNER JOIN rankQuery r ON b.buyerID = r.buyerID
-- Filter for live auctions
WHERE auc.endTime > NOW()
GROUP BY a.auctionID
-- Score weighted up by 25% if auction intersects with given buyer's category history
ORDER BY CASE WHEN c.categoryID IS NOT NULL THEN (totalRank * 1.25) ELSE totalRank END DESC
LIMIT 5;

-- Version 2 (if no bid history but some buyerPreferences)
SELECT a.auctionID
FROM auctions a
WHERE a.categoryID IN (SELECT categoryID FROM buyerPreferences WHERE buyerID = 1)
AND a.endTime > NOW()
ORDER BY (SELECT COUNT(*) FROM bids b WHERE a.auctionID = b.auctionID) DESC
LIMIT 5;

-- Version 3 (if no bid history and no buyerPreferences)
SELECT a.auctionID
FROM auctions a
WHERE a.endTime > NOW()
ORDER BY (SELECT COUNT(*) FROM bids b WHERE a.auctionID = b.auctionID) DESC
LIMIT 5;

/* 
Micro Workflow (done):
i. Has user made any bids?
    1. Yes: Proceed with main query 
        i. Does main query yield N results?
            1. Yes: Done
            2. No: Does user have buyerPreferences?
                i. Yes: Proceed with fallback (with buyerPreferences)
                    1. Do both queries yield N results?
                        i. Yes: Done
                        ii. No: Proceed with fallback (without buyerPreferences) and provide recommendations whether or not they total to N
                ii. No: Proceed to fallback (without buyerPreferences) and provide recommendations whether or not they total to N
    2. No: Does user have buyerPreferences?
        i. Yes: Proceed with fallback (with buyerPreferences)
            1. Does query yield N results?
                i. Yes: Done
                ii. No: Proceed with fallback (without buyerPreferences) and provide recommendations whether or not they total to N
        ii. No: Proceed to fallback (without buyerPreferences) and provide recommendations whether or not they total to N

Macro Workflow (outstanding): 
* Pre-calculate recommendations for each user at a regular interval (eg. overnight); keep a larger amount (eg. 25)
* Each time a user accesses recommendations, filter the larger pre-calculated set by live auctions and limit to a lower amount (eg. 10)
* If recommendations are less than 10, exceptionally run the micro workflow there and then for the remainder 
* If auction expires, delete it from pre-calculated set of recommendations (that logic applies to auction expiry event, not this)

Plan ahead (Raj):
i. buyerPreferences / update buyer registration [Patch 1]
ii. recommendations [Patch 1]
iii. userViews [Patch 2]
v. seller recommendations: general insights for "My Listings" (views, bids) [Patch 2]
vi. search: sort by views, filter by buyer preferences [Patch 2]
iv. questions [Patch 3]
vi. Remainder (delegated): updates/notifications, watchlist


