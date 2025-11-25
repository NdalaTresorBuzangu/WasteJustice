<?php
// Backend: Database connection (Ensure to include your own credentials)
include 'config.php';


// Function to get recent reports from the database
function getReports($conn) {
    $sql = "SELECT r.reportID AS report_id, r.description, r.location, r.submissionDate AS date_reported, 
                   r.statusID, s.statusName
            FROM report r
            JOIN Status s ON r.statusID = s.statusID
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
?>
