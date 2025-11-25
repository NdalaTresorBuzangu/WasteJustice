-- Drop the database if it already exists 
DROP DATABASE IF EXISTS campus_maintenance;

-- Create the new database
CREATE DATABASE campus_maintenance;
USE campus_maintenance;

-- User table
CREATE TABLE `User` (
    `userID` INT PRIMARY KEY AUTO_INCREMENT,
    `userName` VARCHAR(255) NOT NULL,
    `userContact` VARCHAR(20),
    `userEmail` VARCHAR(255) UNIQUE NOT NULL,
    `userPassword` VARCHAR(255) NOT NULL,
    `userRole` ENUM('Affected Student', 'School', 'Admin') NOT NULL
);

-- Subscribe table (School subscription info)
CREATE TABLE `Subscribe` (
    `subscribeID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `schoolName` VARCHAR(255) NOT NULL,
    `schoolContact` VARCHAR(50),
    `schoolEmail` VARCHAR(255),
    `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- MaintenanceType table
CREATE TABLE `MaintenanceType` (
    `maintenanceTypeID` INT PRIMARY KEY AUTO_INCREMENT,
    `typeName` VARCHAR(255) NOT NULL
);

-- Status table
CREATE TABLE `Status` (
    `statusID` INT PRIMARY KEY AUTO_INCREMENT,
    `statusName` VARCHAR(50) NOT NULL
);

-- Report table
CREATE TABLE `Report` (
    `reportID` VARCHAR(50) PRIMARY KEY,
    `userID` INT,
    `schoolID` INT,
    `maintenanceTypeID` INT,
    `statusID` INT,
    `description` TEXT NOT NULL,
    `location` VARCHAR(255),
    `imagePath` VARCHAR(255),
    `submissionDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completionDate` DATE,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`schoolID`) REFERENCES `User`(`userID`) ON DELETE CASCADE,
    FOREIGN KEY (`maintenanceTypeID`) REFERENCES `MaintenanceType`(`maintenanceTypeID`),
    FOREIGN KEY (`statusID`) REFERENCES `Status`(`statusID`)
);

-- PrelossDocuments table (student uploaded documents before loss)
CREATE TABLE `PrelossDocuments` (
    `prelossID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `filePath` VARCHAR(255) NOT NULL,
    `uploadedOn` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- TshijukaPackHistory table
CREATE TABLE `TshijukaPackHistory` (
    `packID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `documentIDs` TEXT NOT NULL,
    `classification` VARCHAR(255) NOT NULL,
    `institutionEmail` VARCHAR(255) NOT NULL,
    `sharedOn` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`userID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- Chat table (NEW, for private student-school conversations)
CREATE TABLE `Chat` (
    `chatID` INT AUTO_INCREMENT PRIMARY KEY,
    `reportID` VARCHAR(50) NOT NULL,
    `senderID` INT NOT NULL,
    `message` TEXT NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reportID`) REFERENCES `Report`(`reportID`) ON DELETE CASCADE,
    FOREIGN KEY (`senderID`) REFERENCES `User`(`userID`) ON DELETE CASCADE
);

-- Insert initial data
INSERT INTO `MaintenanceType` (`typeName`) VALUES 
('State exams'), ('P6'), ('P5'), ('P4'), ('P1 to P3');

INSERT INTO `Status` (`statusName`) VALUES 
('Pending'), ('In Progress'), ('Completed'), ('Cancelled');

-- Users
INSERT INTO `User` (`userName`, `userContact`, `userEmail`, `userPassword`, `userRole`) VALUES 
('John Doe', '123456789', 'student1@example.com', 'student_pass', 'Affected Student'),
('ABC Institute', '111222333', 'school@example.com', 'school_pass', 'School'),
('Tresor Ndala', '999888777', 'ndalabuzangu@gmail.com', 'Ndala1950@@', 'Admin');

-- Example subscription (School must subscribe)
INSERT INTO `Subscribe` (`userID`, `schoolName`, `schoolContact`, `schoolEmail`) VALUES
(2, 'ABC Institute', '111222333', 'school@example.com');

-- Reports (fixed imagePath without leading /)
INSERT INTO `Report` 
(`reportID`, `userID`, `schoolID`, `maintenanceTypeID`, `statusID`, `description`, `location`, `imagePath`) VALUES 
('report_001', 1, 2, 2, 1, 'Leaking pipe in the main hall', 'Main Hall', 'uploads/images/RPT001.jpg'),
('report_002', 1, 2, 1, 3, 'Flickering lights in the library', 'Library', 'uploads/images/RPT002.jpg');

-- PrelossDocuments example
INSERT INTO `PrelossDocuments` (`userID`, `title`, `filePath`) VALUES
(1, 'Birth Certificate', 'uploads/preloss/birth_certificate.pdf'),
(1, 'Primary School Completion', 'uploads/preloss/primary_completion.pdf');

-- Example chat messages
INSERT INTO `Chat` (`reportID`, `senderID`, `message`) VALUES
('report_001', 1, 'Hello, I submitted my document request.'),
('report_001', 2, 'We received your request and are working on it.');




