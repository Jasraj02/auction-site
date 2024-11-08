CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(250) NOT NULL,
    telephoneNumber VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(60) NOT NULL
);

CREATE TABLE Buyers (
    buyerID INT PRIMARY KEY,
    FOREIGN KEY (buyerID) REFERENCES Users(userID)
);

CREATE TABLE Sellers (
    sellerID INT PRIMARY KEY,
    FOREIGN KEY (sellerID) REFERENCES Users(userID)
);


CREATE TABLE Auctions (
	auctionID INT AUTO_INCREMENT PRIMARY KEY,
    FOREIGN KEY (sellerID) REFERENCES Sellers(sellerID),
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID),
    description VARCHAR(250) NOT NULL, 
    imageFileName VARCHAR(250) NOT NULL, 
    startingPrice DECIMAL(10, 2) NOT NULL,
    reservePrice DECIMAL(10, 2) NOT NULL,
    currentPrice DECIMAL(10, 2) NOT NULL,
    startTime TIMESTAMP NOT NULL, 
    endTime TIMESTAMP NOT NULL
);   

CREATE TABLE Categories (
	categoryID INT AUTO_INCREMENT PRIMARY KEY,
    categoryType VARCHAR(60) NOT NULL
);

CREATE TABLE UserViews (
    FOREIGN KEY (userID) REFERENCES Users(userID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    viewTime TIMESTAMP NOT NULL
);

CREATE TABLE Bids (
	bidID INT AUTO_INCREMENT PRIMARY KEY,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
 	FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID)   
);

CREATE TABLE Updates (
	updateID INT AUTO_INCREMENT PRIMARY KEY 	    
);

CREATE TABLE BuyerUpdates (
    buyerUpdateID INT PRIMARY KEY,
    FOREIGN KEY (buyerUpdateID) REFERENCES Updates(updateID),
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
   	FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (bidID) REFERENCES Bids(bidID),
    updateType VARCHAR(60),
    updateTime TIMESTAMP NOT NULL,
    readStatus BOOLEAN NOT NULL
);

CREATE TABLE SellerUpdates (
    sellerUpdateID INT PRIMARY KEY,
    FOREIGN KEY (sellerUpdateID) REFERENCES Updates(updateID),
    FOREIGN KEY (sellerID) REFERENCES Sellers(sellerID),
   	FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (bidID) REFERENCES Bids(bidID),
    updateType VARCHAR(60),
    updateTime TIMESTAMP NOT NULL,
    readStatus BOOLEAN NOT NULL
);

CREATE TABLE Questions (
	questionID INT AUTO_INCREMENT PRIMARY KEY,
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    questionText VARCHAR(250) NOT NULL, 
    questionTimestamp TIMESTAMP NOT NULL, 
    responseText VARCHAR(250), 
    responseTimestamp TIMESTAMP
);

CREATE TABLE Watchlists (
	FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    notificationEnabled BOOLEAN NOT NULL
);    