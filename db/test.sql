-- Step 1: Insert dummy users (30 buyers, 20 sellers, and 10 with 'both' roles)
INSERT INTO Users (userRole, username, email, userPassword)
SELECT 
    CASE 
        WHEN id <= 30 THEN 'buyer'
        WHEN id <= 50 THEN 'seller'
        ELSE 'both'
    END AS userRole,
    CONCAT('user', id) AS username,
    CONCAT('user', id, '@example.com') AS email,
    CONCAT('password', id) AS userPassword
FROM (SELECT @id := @id + 1 AS id 
      FROM (SELECT @id := 0) seed, information_schema.tables LIMIT 60) ids;

-- Step 2: Populate Buyers table
INSERT INTO Buyers (buyerID)
SELECT userID FROM Users WHERE userRole IN ('buyer', 'both');

-- Step 3: Populate Sellers table
INSERT INTO Sellers (sellerID)
SELECT userID FROM Users WHERE userRole IN ('seller', 'both');

-- Step 4: Insert dummy auctions (50 auctions across 20 sellers and 10 categories)
INSERT INTO Auctions (sellerID, categoryID, auctionStatusID, auctionTitle, auctionDescription, startingPrice, reservePrice, currentPrice, startTime, endTime)
SELECT
    (SELECT sellerID FROM Sellers ORDER BY RAND() LIMIT 1) AS sellerID, -- Random valid sellerID
    1 + FLOOR(RAND() * 10) AS categoryID, -- Random categoryID from 1-10
    1 AS auctionStatusID, -- Default value set to 'ongoing'
    CONCAT('Auction Title ', id) AS auctionTitle,
    CONCAT('Auction Description for Item ', id) AS auctionDescription,
    10.00 + (RAND() * 490.00) AS startingPrice, -- Random starting price between 10.00 and 500.00
    20.00 + (RAND() * 480.00) AS reservePrice, -- Random reserve price between 20.00 and 500.00
    10.00 + (RAND() * 500.00) AS currentPrice, -- Random current price between 10.00 and 500.00
    NOW() - INTERVAL FLOOR(RAND() * 10) DAY AS startTime, -- Random start time within the last 10 days
    NOW() + INTERVAL FLOOR(RAND() * 10 + 5) DAY AS endTime -- Random end time 5-15 days in the future
FROM (SELECT @id := @id + 1 AS id 
      FROM (SELECT @id := 0) seed, information_schema.tables LIMIT 50) t;

-- Step 5: Insert dummy bids (500 bids randomly distributed across auctions and a subset of buyers)
INSERT INTO Bids (buyerID, auctionID, bidPrice)
SELECT
    (SELECT buyerID FROM Buyers WHERE RAND() < 0.6 ORDER BY RAND() LIMIT 1) AS buyerID, -- 60% chance a buyer is chosen
    (SELECT auctionID FROM Auctions ORDER BY RAND() LIMIT 1) AS auctionID, -- Random valid auctionID
    10.00 + (RAND() * 490.00) AS bidPrice -- Random bid price between 10.00 and 500.00
FROM (SELECT @id := @id + 1 AS id 
      FROM (SELECT @id := 0) seed, information_schema.tables LIMIT 500) t;

-- Step 6: Insert random buyer preferences (30% chance for a buyer-category pair to exist)
-- Some buyers will have preferences, some won't
INSERT INTO BuyerPreferences (buyerID, categoryID)
SELECT 
    b.buyerID, 
    c.categoryID
FROM Buyers b
JOIN (SELECT categoryID FROM Categories) c 
ON RAND() < 0.3 -- 30% chance for a buyer-category pair to exist
WHERE b.buyerID NOT IN (SELECT buyerID FROM Bids) -- Only buyers who haven't placed bids
ORDER BY RAND();

-- Step 7.1: Manually add a few buyers to the Users table
INSERT INTO Users (userRole, username, email, userPassword)
VALUES 
    ('buyer', 'user101', 'user101@example.com', 'password101'),
    ('buyer', 'user102', 'user102@example.com', 'password102'),
    ('buyer', 'user103', 'user103@example.com', 'password103');

-- Step 7.2: Insert the newly created users into the Buyers table
INSERT INTO Buyers (buyerID)
SELECT userID FROM Users WHERE username IN ('user101', 'user102', 'user103');

-- Step 7.3: Add buyer preferences for specific buyers
INSERT INTO BuyerPreferences (buyerID, categoryID)
VALUES 
    ((SELECT userID FROM Users WHERE username = 'user101'), 1),  -- user101 prefers 'art' category
    ((SELECT userID FROM Users WHERE username = 'user103'), 3);  -- user103 prefers 'fashion' category