DROP DATABASE IF EXISTS auctionSite;

CREATE DATABASE auctionSite
DEFAULT CHARACTER SET utf8
DEFAULT COLLATE utf8_general_ci;

USE auctionSite;

CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    userRole VARCHAR(20) NOT NULL, 
    username VARCHAR(100) UNIQUE NOT NULL,            
    email VARCHAR(100) UNIQUE NOT NULL,
    userPassword VARCHAR(100) NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE Buyers (
    buyerID INT PRIMARY KEY,
    FOREIGN KEY (buyerID) REFERENCES Users(userID)
)
ENGINE = InnoDB;

CREATE TABLE Sellers (
    sellerID INT PRIMARY KEY,
    FOREIGN KEY (sellerID) REFERENCES Users(userID)
)
ENGINE = InnoDB;

CREATE TABLE Categories (
    categoryID INT AUTO_INCREMENT PRIMARY KEY,
    categoryType VARCHAR(60) NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE Auctions (
    auctionID INT AUTO_INCREMENT PRIMARY KEY,
    sellerID INT NOT NULL,
    categoryID INT NOT NULL,
    FOREIGN KEY (sellerID) REFERENCES Sellers(sellerID),
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID),
    description VARCHAR(250) NOT NULL, 
    imageFileName VARCHAR(250) NOT NULL, 
    startingPrice DECIMAL(10, 2) NOT NULL,
    reservePrice DECIMAL(10, 2) NOT NULL,
    currentPrice DECIMAL(10, 2) NOT NULL,
    startTime TIMESTAMP NOT NULL, 
    endTime TIMESTAMP NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE UserViews (
    userID INT NOT NULL,
    auctionID INT NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    viewTime TIMESTAMP NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE Bids (
    bidID INT AUTO_INCREMENT PRIMARY KEY,
    buyerID INT NOT NULL,
    auctionID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    bidPrice DECIMAL(10, 2) NOT NULL  
)
ENGINE = InnoDB;

CREATE TABLE Updates (
    updateID INT AUTO_INCREMENT PRIMARY KEY 	    
)
ENGINE = InnoDB;

CREATE TABLE BuyerUpdates (
    buyerUpdateID INT PRIMARY KEY,
    FOREIGN KEY (buyerUpdateID) REFERENCES Updates(updateID),
    buyerID INT NOT NULL,
    auctionID INT NOT NULL,
    bidID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (bidID) REFERENCES Bids(bidID)
)
ENGINE = InnoDB;

CREATE TABLE SellerUpdates (
    sellerUpdateID INT PRIMARY KEY,
    FOREIGN KEY (sellerUpdateID) REFERENCES Updates(updateID),
    sellerID INT NOT NULL,
    auctionID INT NOT NULL,
    bidID INT NOT NULL,
    FOREIGN KEY (sellerID) REFERENCES Sellers(sellerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (bidID) REFERENCES Bids(bidID)
)
ENGINE = InnoDB;

CREATE TABLE UpdateProperties (
    updateID INT NOT NULL,
    FOREIGN KEY (updateID) REFERENCES Updates(updateID),
    updateType VARCHAR(60),
    updateTime TIMESTAMP NOT NULL,
    readStatus BOOLEAN NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE Questions (
    questionID INT AUTO_INCREMENT PRIMARY KEY,
    auctionID INT NOT NULL,
    buyerID INT NOT NULL,
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    questionText VARCHAR(250) NOT NULL, 
    questionTimestamp TIMESTAMP NOT NULL, 
    responseText VARCHAR(250), 
    responseTimestamp TIMESTAMP
)
ENGINE = InnoDB;

CREATE TABLE Watchlists (
    buyerID INT NOT NULL,
    auctionID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    notificationEnabled BOOLEAN NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE Recommendations (
    recommendationID INT AUTO_INCREMENT PRIMARY KEY,
    buyerID INT NOT NULL,
    auctionID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    recommendationScore DECIMAL(3, 2) NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE BuyerPreferences (
    buyerID INT NOT NULL,
    categoryID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID)
)
ENGINE = InnoDB;
