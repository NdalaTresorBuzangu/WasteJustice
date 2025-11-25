<?php
// Include the backend logic for fetching reports
include 'report_action.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Reports - CampusFixIt</title>
    <link rel="stylesheet" href="nav.css"> <!-- Link the navigation bar styles -->
    <link rel="stylesheet" href="reports.css"> <!-- Link the page-specific styles -->
</head>
<body>
    <!-- Include the navigation bar -->
    <?php include 'nav.php'; ?>

    <header>
        <h1>Recent documents Reports</h1>
    </header>

    <main>
        <div class="report-grid">
            <?php
            // Fetch the recent reports from the database
            $reports = getReports($conn);
            if (count($reports) > 0):
                foreach ($reports as $report): ?>
                    <div class="report-card">
                        <span class="report-id">#FIX-<?php echo $report['report_id']; ?></span>
                        <h3><?php echo htmlspecialchars($report['description']); ?></h3>
                        <div class="report-details">
                            <div class="detail-item">
                                <span>Location: <?php echo htmlspecialchars($report['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Reported: <?php echo htmlspecialchars($report['date_reported']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Status: <?php echo htmlspecialchars($report['statusName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <a href="progress.php?id=<?php echo $report['report_id']; ?>" class="status-badge">Track Progress</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach;
            else: ?>
                <p>No documents found.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; Tshijuka RDP</p>
    </footer>
</body>
</html>
