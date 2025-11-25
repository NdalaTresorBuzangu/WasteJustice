<?php
include "config.php";  // Include the database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------ USER FUNCTIONS ------------------

// Get all users
function getAllUsers() {
    global $conn;
    $sql = "SELECT userID, userName, userEmail, userRole FROM User";
    $result = $conn->query($sql);
    $users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

// Add a user
function addUser($userName, $userEmail, $userPassword, $userRole) {
    global $conn;
    $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO User (userName, userEmail, userPassword, userRole) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $userName, $userEmail, $hashedPassword, $userRole);
    $stmt->execute();
    $stmt->close();
}

// Delete a user
function deleteUser($userID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM User WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();
}

// Update user role
function updateUserRole($userID, $newRole) {
    global $conn;
    $stmt = $conn->prepare("UPDATE User SET userRole = ? WHERE userID = ?");
    $stmt->bind_param("si", $newRole, $userID);
    $stmt->execute();
    $stmt->close();
}

// ------------------ REPORT FUNCTIONS ------------------

// Get all reports with user and maintenance info
function getAllReports() {
    global $conn;
    $sql = "SELECT 
                r.reportID, 
                r.userID,
                r.schoolID,
                u.userName AS userName, 
                s.userName AS schoolName, 
                r.maintenanceTypeID, 
                m.typeName AS maintenanceType, 
                r.statusID, 
                st.statusName AS statusName, 
                r.description, 
                r.location, 
                r.imagePath,
                r.submissionDate, 
                r.completionDate
            FROM Report r
            LEFT JOIN User u ON r.userID = u.userID
            LEFT JOIN User s ON r.schoolID = s.userID
            LEFT JOIN MaintenanceType m ON r.maintenanceTypeID = m.maintenanceTypeID
            LEFT JOIN Status st ON r.statusID = st.statusID
            ORDER BY r.submissionDate DESC";
    $result = $conn->query($sql);
    $reports = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    }
    return $reports;
}

// Add a report with optional image
function addReport($userID, $schoolID, $maintenanceTypeID, $statusID, $description, $location, $file = null) {
    global $conn;

    $reportID = uniqid('report_');
    $imagePath = null;

    // Handle image upload
    if ($file && isset($file['error']) && $file['error'] == 0) {
        $uploadDir = "uploads/images/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid('img_') . "_" . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO Report (reportID, userID, schoolID, maintenanceTypeID, statusID, description, location, imagePath, submissionDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siiiisss", $reportID, $userID, $schoolID, $maintenanceTypeID, $statusID, $description, $location, $imagePath);
    $stmt->execute();
    $stmt->close();

    return $reportID;
}

// Delete a report
function deleteReport($reportID) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Report WHERE reportID = ?");
    $stmt->bind_param("s", $reportID);
    $stmt->execute();
    $stmt->close();
}

// Update report status with optional image
function updateReportStatus($reportID, $newStatusID, $file = null) {
    global $conn;
    $imagePath = null;

    // Handle image upload
    if ($file && isset($file['error']) && $file['error'] == 0) {
        $uploadDir = "uploads/images/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid('img_') . "_" . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    // Update status and optional image
    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE Report SET statusID = ?, imagePath = ? WHERE reportID = ?");
        $stmt->bind_param("iss", $newStatusID, $imagePath, $reportID);
    } else {
        $stmt = $conn->prepare("UPDATE Report SET statusID = ? WHERE reportID = ?");
        $stmt->bind_param("is", $newStatusID, $reportID);
    }
    $stmt->execute();
    $stmt->close();

    // Send email if completed
    if ($newStatusID == 3) { // Completed
        $stmt = $conn->prepare("SELECT u.userEmail, u.userName FROM Report r JOIN User u ON r.userID = u.userID WHERE r.reportID = ?");
        $stmt->bind_param("s", $reportID);
        $stmt->execute();
        $stmt->bind_result($userEmail, $userName);
        $stmt->fetch();
        $stmt->close();

        if (!empty($userEmail)) sendCompletionEmail($userEmail, $userName, $reportID);
    }
}

// Get status name by ID
function getStatusById($statusID) {
    global $conn;
    $stmt = $conn->prepare("SELECT statusName FROM Status WHERE statusID = ?");
    $stmt->bind_param("i", $statusID);
    $stmt->execute();
    $stmt->bind_result($statusName);
    $status = null;
    if ($stmt->fetch()) $status = $statusName;
    $stmt->close();
    return $status;
}

// Send email when report completed
function sendCompletionEmail($userEmail, $userName, $reportID) {
    $subject = "Maintenance Report Completed";
    $message = "
    <html>
    <head><title>Maintenance Report Completed</title></head>
    <body>
        <p>Dear {$userName},</p>
        <p>Your maintenance report (ID: {$reportID}) has been marked as <strong>Completed</strong>.</p>
        <p>Thank you for using our service!</p>
    </body>
    </html>
    ";
    mail($userEmail, $subject, $message, "Content-type:text/html;charset=UTF-8");
}

// ------------------ REPORT STATISTICS ------------------
function getTotalReports() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Report");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getCompletedReports() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Report WHERE statusID = 3");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getPendingReports() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Report WHERE statusID = 1");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
function getInProgressReports() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) AS total FROM Report WHERE statusID = 2");
    return $result ? $result->fetch_assoc()['total'] : 0;
}
?>



