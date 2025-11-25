<?php 
// progress.php
include 'config.php';

$statusMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['reportId'])) {
        $report_id = $_POST['reportId'];

        // Query to fetch the report details including the image
        $stmt = $conn->prepare("SELECT r.reportID, r.description, r.imagePath, s.statusName 
                                FROM Report r 
                                JOIN Status s ON r.statusID = s.statusID 
                                WHERE r.reportID = ?");
        $stmt->bind_param("s", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $report = $result->fetch_assoc();
            $statusMessage = "Report ID: " . htmlspecialchars($report['reportID']) . "<br>" .
                             "Description: " . htmlspecialchars($report['description']) . "<br>" .
                             "Status: " . htmlspecialchars($report['statusName']) . "<br>";

            // ✅ Fix image check
            if (!empty($report['imagePath'])) {
                $imagePath = $report['imagePath'];
                $fullPath = __DIR__ . '/' . $imagePath; // ensures correct server path

                if (file_exists($fullPath)) {
                    $statusMessage .= "<img src='" . htmlspecialchars($imagePath) . "' alt='Report Image' style='max-width: 400px; max-height: 300px;'>";
                } else {
                    $statusMessage .= "<p>Image file not found on server: " . htmlspecialchars($imagePath) . "</p>";
                }
            } else {
                $statusMessage .= "<p>No image submitted for this report.</p>";
            }
        } else {
            $statusMessage = "Report not found. Please check the Report ID.";
        }
        $stmt->close();
    } else {
        $statusMessage = "Please enter a valid Report ID.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Report Progress - CampusFixIt</title>
    <link rel="stylesheet" href="progress.css">
</head>
<body>
    <!-- Navigation Bar -->
    <header>
        <nav class="navbar">
            <h1 class="logo">Tshijuka RDP</h1>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="submit_report.php">Submit Report</a></li>
                <li><a href="progress.php">Track Report</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <h2>Track Report Progress</h2>
        <p>Enter your report ID below to check the current status of your report.</p>

        <!-- Report ID Form -->
        <form method="POST" action="progress.php">
            <input type="text" name="reportId" placeholder="Enter Report ID" required>
            <button type="submit">Check Status</button>
        </form>

        <!-- Display Status -->
        <div style="margin-top: 20px;">
            <p><?php echo $statusMessage; ?></p>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; Tshijuka RDP</p>
    </footer>
</body>
</html>

