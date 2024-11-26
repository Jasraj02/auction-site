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

INSERT INTO Categories (categoryID, categoryType) VALUES (1, 'art');
INSERT INTO Categories (categoryID, categoryType) VALUES (2, 'electronics');
INSERT INTO Categories (categoryID, categoryType) VALUES (3, 'fashion');
INSERT INTO Categories (categoryID, categoryType) VALUES (4, 'health');
INSERT INTO Categories (categoryID, categoryType) VALUES (5, 'home');
INSERT INTO Categories (categoryID, categoryType) VALUES (6, 'lifestyle');
INSERT INTO Categories (categoryID, categoryType) VALUES (7, 'media');
INSERT INTO Categories (categoryID, categoryType) VALUES (8, 'others');
INSERT INTO Categories (categoryID, categoryType) VALUES (9, 'vehicles');
INSERT INTO Categories (categoryID, categoryType) VALUES (10, 'workplace');

CREATE TABLE Images (
    imageID INT AUTO_INCREMENT PRIMARY KEY,
    imageFileName VARCHAR(250) NOT NULL,
    imageFile MEDIUMBLOB NOT NULL
)
ENGINE = InnoDB;

CREATE TABLE AuctionStatus (
    auctionStatusID INT AUTO_INCREMENT PRIMARY KEY,
    auctionStatusType VARCHAR(60) NOT NULL
)
ENGINE = InnoDB;

INSERT INTO AuctionStatus (auctionStatusID, auctionStatusType) VALUES (1, 'ongoing');
INSERT INTO AuctionStatus (auctionStatusID, auctionStatusType) VALUES (2, 'completed');
INSERT INTO AuctionStatus (auctionStatusID, auctionStatusType) VALUES (3, 'unsuccessful');
INSERT INTO AuctionStatus (auctionStatusID, auctionStatusType) VALUES (4, 'expiredNoBids');

CREATE TABLE Auctions (
    auctionID INT AUTO_INCREMENT PRIMARY KEY,
    auctionTitle VARCHAR(50) NOT NULL,
    sellerID INT NOT NULL,
    categoryID INT NOT NULL,
    auctionStatusID INT NOT NULL,
    FOREIGN KEY (sellerID) REFERENCES Sellers(sellerID),
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID),
    FOREIGN KEY (auctionStatusID) REFERENCES AuctionStatus(auctionStatusID),
    auctionDescription VARCHAR(100) NOT NULL, 
    imageID INT,
    FOREIGN KEY (imageID) REFERENCES Images(imageID),
    startingPrice DECIMAL(10, 2) NOT NULL,
    reservePrice DECIMAL(10, 2) NOT NULL,
    currentPrice DECIMAL(10, 2) NOT NULL,
    startTime TIMESTAMP NOT NULL, 
    endTime TIMESTAMP NOT NULL, 
    expiryNotificationsSent BOOLEAN NOT NULL DEFAULT 0   
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

CREATE TABLE NotificationContent (
    notificationTypeID INT AUTO_INCREMENT PRIMARY KEY,
    notificationType VARCHAR(60) NOT NULL    
)
ENGINE = InnoDB;

INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (1, 'completedWinner');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (2, 'completedSeller');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (3, 'completedWatchlist');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (4, 'unsuccessfulBidder');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (5, 'unsuccessfulSeller');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (6, 'unsuccessfulWatchlist');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (7, 'noBidsSeller');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (8, 'noBidsWatchlist');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (9, 'ongoingOutbid');
INSERT INTO NotificationContent (notificationTypeID, notificationType) VALUES (10, '2fa');

CREATE TABLE Notifications (
    notificationID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    auctionID INT,
    notificationTypeID INT NOT NULL, 
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID)
)
ENGINE = InnoDB;

CREATE TABLE Authentication (
    userID INT NOT NULL,
    authenticationCode INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID)
)
ENGINE = InnoDB;

CREATE TABLE Watchlists (
    buyerID INT NOT NULL,
    auctionID INT NOT NULL,
    FOREIGN KEY (buyerID) REFERENCES Buyers(buyerID),
    FOREIGN KEY (auctionID) REFERENCES Auctions(auctionID),
    notificationEnabled BOOLEAN NOT NULL DEFAULT TRUE
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

CREATE TABLE Preferences (
    userID INT NOT NULL,
    categoryID INT NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID),
    FOREIGN KEY (categoryID) REFERENCES Categories(categoryID)
)
ENGINE = InnoDB;

ALTER TABLE Preferences
ADD CONSTRAINT fk_user FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
ADD CONSTRAINT fk_category FOREIGN KEY (categoryID) REFERENCES Categories(categoryID) ON DELETE CASCADE;


