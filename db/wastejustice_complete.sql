-- ============================================
-- WasteJustice Complete Database
-- Plastic Waste Collection, Aggregation, and Sale
-- ============================================

DROP DATABASE IF EXISTS wastejustice;
CREATE DATABASE wastejustice;
USE wastejustice;

-- User table (all roles)
CREATE TABLE `User` (
    `userID` INT PRIMARY KEY AUTO_INCREMENT,
    `userName` VARCHAR(255) NOT NULL,
    `userContact` VARCHAR(20),
    `userEmail` VARCHAR(255) UNIQUE NOT NULL,
    `userPassword` VARCHAR(255) NOT NULL,
    `userRole` ENUM('Waste Collector', 'Aggregator', 'Recycling Company', 'Admin') NOT NULL,
    `latitude` DECIMAL(10, 8),
    `longitude` DECIMAL(11, 8),
    `address` VARCHAR(255),
    `rating` DECIMAL(3, 2) DEFAULT 0.00,
    `totalRatings` INT DEFAULT 0,
    `status` ENUM('active', 'pending', 'suspended') DEFAULT 'pending',
    `subscription_status` ENUM('free', 'trial', 'active', 'expired', 'cancelled') DEFAULT 'free',
    `subscription_expires` DATE NULL,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Aggregator registration table
CREATE TABLE `AggregatorRegistration` (
    `aggregatorID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL UNIQUE,
    `businessName` VARCHAR(255) NOT NULL,
    `contactPerson` VARCHAR(255),
    `businessLicense` VARCHAR(255),
    `capacity` DECIMAL(10, 2) COMMENT 'Storage capacity in kg',
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- Company registration table
CREATE TABLE `CompanyRegistration` (
    `registrationID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL UNIQUE,
    `companyName` VARCHAR(255) NOT NULL,
    `companyContact` VARCHAR(50),
    `companyEmail` VARCHAR(255),
    `businessLicense` VARCHAR(255),
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- Plastic waste types table
CREATE TABLE `PlasticType` (
    `plasticTypeID` INT PRIMARY KEY AUTO_INCREMENT,
    `typeName` VARCHAR(255) NOT NULL UNIQUE,
    `typeCode` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT
);

-- Transaction status table
CREATE TABLE `Status` (
    `statusID` INT PRIMARY KEY AUTO_INCREMENT,
    `statusName` VARCHAR(50) NOT NULL UNIQUE
);

-- Waste collection table (Collector uploads)
CREATE TABLE `WasteCollection` (
    `collectionID` INT PRIMARY KEY AUTO_INCREMENT,
    `collectorID` INT NOT NULL,
    `plasticTypeID` INT NOT NULL,
    `weight` DECIMAL(10, 2) NOT NULL,
    `aggregatorID` INT NULL COMMENT 'Selected aggregator',
    `collectionDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `statusID` INT DEFAULT 1 COMMENT '1=Pending, 2=Accepted, 3=Rejected, 4=Delivered',
    `location` VARCHAR(255),
    `latitude` DECIMAL(10, 8) NULL COMMENT 'GPS latitude of collection location',
    `longitude` DECIMAL(11, 8) NULL COMMENT 'GPS longitude of collection location',
    `notes` TEXT,
    `photoPath` VARCHAR(255),
    `hash` VARCHAR(64) UNIQUE COMMENT 'Prevent duplicates',
    FOREIGN KEY (`collectorID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`plasticTypeID`) REFERENCES `PlasticType`(`plasticTypeID`),
    FOREIGN KEY (`aggregatorID`) REFERENCES `User`(`userID`) ON DELETE SET NULL,
    FOREIGN KEY (`statusID`) REFERENCES `Status`(`statusID`),
    INDEX `idx_collector_date` (`collectorID`, `collectionDate`),
    INDEX `idx_status` (`statusID`),
    INDEX `idx_aggregator` (`aggregatorID`),
    INDEX `idx_location_coords` (`latitude`, `longitude`)
);

-- Aggregator pricing table (transparent prices)
CREATE TABLE `AggregatorPricing` (
    `pricingID` INT AUTO_INCREMENT PRIMARY KEY,
    `aggregatorID` INT NOT NULL,
    `plasticTypeID` INT NOT NULL,
    `pricePerKg` DECIMAL(10, 2) NOT NULL,
    `isActive` BOOLEAN DEFAULT TRUE,
    `updatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`aggregatorID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`plasticTypeID`) REFERENCES `PlasticType`(`plasticTypeID`),
    UNIQUE KEY `unique_aggregator_plastic` (`aggregatorID`, `plasticTypeID`),
    INDEX `idx_active` (`isActive`)
);

-- Company pricing table (transparent prices)
CREATE TABLE `CompanyPricing` (
    `pricingID` INT AUTO_INCREMENT PRIMARY KEY,
    `companyID` INT NOT NULL,
    `plasticTypeID` INT NOT NULL,
    `pricePerKg` DECIMAL(10, 2) NOT NULL,
    `isActive` BOOLEAN DEFAULT TRUE,
    `updatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`companyID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`plasticTypeID`) REFERENCES `PlasticType`(`plasticTypeID`),
    UNIQUE KEY `unique_company_plastic` (`companyID`, `plasticTypeID`),
    INDEX `idx_active` (`isActive`)
);

-- Aggregator batches (collected waste from collectors)
CREATE TABLE `AggregatorBatch` (
    `batchID` INT AUTO_INCREMENT PRIMARY KEY,
    `aggregatorID` INT NOT NULL,
    `plasticTypeID` INT NOT NULL,
    `totalWeight` DECIMAL(10, 2) NOT NULL,
    `collectionIDs` TEXT COMMENT 'Comma-separated collection IDs',
    `companyID` INT NULL COMMENT 'Sold to this company',
    `salePrice` DECIMAL(10, 2) NULL,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `soldAt` TIMESTAMP NULL,
    `statusID` INT DEFAULT 5 COMMENT '5=Available, 6=Sold',
    FOREIGN KEY (`aggregatorID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`plasticTypeID`) REFERENCES `PlasticType`(`plasticTypeID`),
    FOREIGN KEY (`companyID`) REFERENCES `User`(`userID`) ON DELETE SET NULL,
    FOREIGN KEY (`statusID`) REFERENCES `Status`(`statusID`),
    INDEX `idx_status` (`statusID`)
);

-- Payment transactions
CREATE TABLE `Payment` (
    `paymentID` INT AUTO_INCREMENT PRIMARY KEY,
    `collectionID` INT NULL,
    `batchID` INT NULL,
    `fromUserID` INT NOT NULL COMMENT 'Who pays',
    `toUserID` INT NOT NULL COMMENT 'Who receives',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT 'Net amount received by recipient (after platform fee)',
    `platformFee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT '1% platform fee for WasteJustice',
    `grossAmount` DECIMAL(10, 2) NOT NULL COMMENT 'Original amount before fee deduction',
    `paymentMethod` VARCHAR(50) DEFAULT 'Mobile Money',
    `mobileMoneyNumber` VARCHAR(20),
    `referenceNumber` VARCHAR(100),
    `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    `paidAt` TIMESTAMP NULL,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`collectionID`) REFERENCES `WasteCollection`(`collectionID`) ON DELETE SET NULL,
    FOREIGN KEY (`batchID`) REFERENCES `AggregatorBatch`(`batchID`) ON DELETE SET NULL,
    FOREIGN KEY (`fromUserID`) REFERENCES `User`(`userID`),
    FOREIGN KEY (`toUserID`) REFERENCES `User`(`userID`),
    INDEX `idx_status` (`status`),
    INDEX `idx_to_user` (`toUserID`)
);

-- Subscriptions table
CREATE TABLE `Subscriptions` (
    `subscriptionID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `planName` ENUM('Free', 'Basic', 'Standard', 'Premium') NOT NULL,
    `amountPaid` DECIMAL(10, 2) DEFAULT 0.00,
    `paymentStatus` ENUM('Pending', 'Success', 'Failed') DEFAULT 'Pending',
    `paymentMethod` VARCHAR(50) DEFAULT 'Mobile Money',
    `mobileMoneyNumber` VARCHAR(20),
    `referenceNumber` VARCHAR(100),
    `paymentDate` TIMESTAMP NULL,
    `subscriptionStart` DATE,
    `subscriptionEnd` DATE,
    `isActive` BOOLEAN DEFAULT FALSE,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    INDEX `idx_user` (`userID`),
    INDEX `idx_status` (`paymentStatus`, `isActive`)
);

-- Feedback/rating system
CREATE TABLE `Feedback` (
    `feedbackID` INT AUTO_INCREMENT PRIMARY KEY,
    `fromUserID` INT NOT NULL,
    `toUserID` INT NOT NULL,
    `collectionID` INT NULL,
    `batchID` INT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `comment` TEXT,
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`fromUserID`) REFERENCES `User`(`userID`),
    FOREIGN KEY (`toUserID`) REFERENCES `User`(`userID`),
    FOREIGN KEY (`collectionID`) REFERENCES `WasteCollection`(`collectionID`) ON DELETE SET NULL,
    FOREIGN KEY (`batchID`) REFERENCES `AggregatorBatch`(`batchID`) ON DELETE SET NULL,
    INDEX `idx_to_user` (`toUserID`)
);

-- Insert plastic types
INSERT INTO `PlasticType` (`typeName`, `typeCode`, `description`) VALUES 
('HDPE', 'HDPE', 'High-Density Polyethylene - bottles, containers'),
('PET', 'PET', 'Polyethylene Terephthalate - water bottles, food containers'),
('PVC', 'PVC', 'Polyvinyl Chloride - pipes, packaging'),
('LDPE', 'LDPE', 'Low-Density Polyethylene - bags, films'),
('PP', 'PP', 'Polypropylene - containers, caps');

-- Insert statuses
INSERT INTO `Status` (`statusName`) VALUES 
('Pending'),
('Accepted'),
('Rejected'),
('Delivered'),
('Available'),
('Sold');

-- Insert default admin
INSERT INTO `User` (`userName`, `userContact`, `userEmail`, `userPassword`, `userRole`, `status`, `subscription_status`) VALUES 
('System Admin', '+233123456789', 'admin@wastejustice.com', 'admin123', 'Admin', 'active', 'active');

-- Insert sample users
INSERT INTO `User` (`userName`, `userContact`, `userEmail`, `userPassword`, `userRole`, `latitude`, `longitude`, `address`, `status`, `subscription_status`) VALUES 
('Kwame Mensah', '+233244123456', 'collector@wastejustice.com', 'collector123', 'Waste Collector', 5.6037, -0.1870, 'Accra Central', 'active', 'free'),
('Ama Osei', '+233244789012', 'aggregator1@wastejustice.com', 'agg123', 'Aggregator', 5.6050, -0.1900, 'Kaneshie Market', 'active', 'free'),
('Yaw Boateng', '+233244345678', 'aggregator2@wastejustice.com', 'agg456', 'Aggregator', 5.6000, -0.1850, 'Circle, Accra', 'active', 'free'),
('RecycleGhana Ltd', '+233302555666', 'company@wastejustice.com', 'company123', 'Recycling Company', 5.6100, -0.1950, 'Industrial Area, Accra', 'active', 'free');

-- Insert aggregator registrations
INSERT INTO `AggregatorRegistration` (`userID`, `businessName`, `contactPerson`, `capacity`) VALUES
(3, 'Green Collection Hub', 'Ama Osei', 5000.00),
(4, 'Eco Waste Center', 'Yaw Boateng', 3000.00);

-- Insert company registration
INSERT INTO `CompanyRegistration` (`userID`, `companyName`, `companyContact`, `companyEmail`) VALUES
(5, 'RecycleGhana Ltd', '+233302555666', 'company@wastejustice.com');

-- Insert aggregator pricing
INSERT INTO `AggregatorPricing` (`aggregatorID`, `plasticTypeID`, `pricePerKg`) VALUES
(3, 1, 5.00), (3, 2, 4.50), (3, 3, 4.00), (3, 4, 3.50), (3, 5, 4.20),  -- Aggregator 1 prices
(4, 1, 5.20), (4, 2, 4.70), (4, 3, 4.10), (4, 4, 3.60), (4, 5, 4.30);  -- Aggregator 2 prices

-- Insert company pricing
INSERT INTO `CompanyPricing` (`companyID`, `plasticTypeID`, `pricePerKg`) VALUES
(5, 1, 7.00), (5, 2, 6.50), (5, 3, 6.00), (5, 4, 5.50), (5, 5, 6.20);  -- Company prices

-- Create view for nearest aggregators with pricing (only subscribed aggregators)
CREATE VIEW `nearest_aggregators` AS
SELECT 
    u.userID as aggregatorID,
    ar.businessName,
    u.userName,
    u.userContact as contact,
    u.address,
    u.latitude,
    u.longitude,
    u.rating,
    u.totalRatings,
    pt.plasticTypeID,
    pt.typeName as plasticType,
    ap.pricePerKg,
    ar.capacity,
    u.status
FROM User u
JOIN AggregatorRegistration ar ON u.userID = ar.userID
JOIN AggregatorPricing ap ON u.userID = ap.aggregatorID
JOIN PlasticType pt ON ap.plasticTypeID = pt.plasticTypeID
INNER JOIN Subscriptions s ON u.userID = s.userID
WHERE u.userRole = 'Aggregator' 
AND u.status = 'active' 
AND ap.isActive = TRUE
AND s.paymentStatus = 'Success'
AND s.isActive = TRUE
AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE());

-- Create view for available batches with transparent pricing
CREATE VIEW `available_batches` AS
SELECT 
    ab.batchID,
    ab.aggregatorID,
    ar.businessName as aggregatorName,
    pt.plasticTypeID,
    pt.typeName as plasticType,
    ab.totalWeight,
    cp.pricePerKg as companyPrice,
    ab.createdAt,
    ab.statusID,
    s.statusName
FROM AggregatorBatch ab
JOIN User u ON ab.aggregatorID = u.userID
JOIN AggregatorRegistration ar ON u.userID = ar.userID
JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
JOIN CompanyPricing cp ON ab.plasticTypeID = cp.plasticTypeID
JOIN Status s ON ab.statusID = s.statusID
WHERE ab.statusID = 5
AND cp.isActive = TRUE
ORDER BY ab.createdAt DESC;

-- ============================================
-- Migration: Add Platform Fee Support
-- ============================================
-- If you have an existing database, run these ALTER statements:
-- ALTER TABLE `Payment` 
-- ADD COLUMN `platformFee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT '1% platform fee for WasteJustice' AFTER `amount`,
-- ADD COLUMN `grossAmount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Original amount before fee deduction' AFTER `platformFee`;
-- 
-- UPDATE `Payment` SET `grossAmount` = `amount` WHERE `grossAmount` = 0;

-- PaystackPayments table for Paystack payment integration
CREATE TABLE IF NOT EXISTS `PaystackPayments` (
    `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `reference` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    `paystack_reference` VARCHAR(100) NULL,
    `payment_method` VARCHAR(50) NULL,
    `currency` VARCHAR(10) DEFAULT 'GHS',
    `description` TEXT NULL,
    `metadata` JSON NULL,
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_reference` (`reference`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

