<?php
// Include core.php to access isLogin() function
include('core.php');

// Call the isLogin function to check if the user is logged in
isLogin();

// Fetch analytics data from the database
include_once "Functions_users_reports.php";  // Include functions for fetching report data

// Check if database connection exists
if (!isset($conn)) {
    die("Error: Database connection not initialized in 'core.php'.");
}

// Fetch analytics data
try {
    $totalReports = getTotalReports($conn); 
    $completedReports = getCompletedReports($conn); 
    $pendingReports = getPendingReports($conn); 
    $inProgressReports = getInProgressReports($conn); 
} catch (Exception $e) {
    die("Error fetching analytics data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <title>Analytics - Tshijuka RDP</title>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Documents Analytics</h1>

        <div class="row">
            <!-- Total Reports -->
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white mb-4">
                    <div class="card-body">
                        <h3>Total Documents</h3>
                        <p><?php echo htmlspecialchars($totalReports); ?></p>
                    </div>
                </div>
            </div>

            <!-- Completed Reports -->
            <div class="col-md-3">
                <div class="card text-center bg-success text-white mb-4">
                    <div class="card-body">
                        <h3>Completed Documents</h3>
                        <p><?php echo htmlspecialchars($completedReports); ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Reports -->
            <div class="col-md-3">
                <div class="card text-center bg-warning text-white mb-4">
                    <div class="card-body">
                        <h3>Pending Documents</h3>
                        <p><?php echo htmlspecialchars($pendingReports); ?></p>
                    </div>
                </div>
            </div>

            <!-- In Progress Reports -->
            <div class="col-md-3">
                <div class="card text-center bg-info text-white mb-4">
                    <div class="card-body">
                        <h3>In Progress Documents</h3>
                        <p><?php echo htmlspecialchars($inProgressReports); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


