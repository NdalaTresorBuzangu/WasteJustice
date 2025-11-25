<?php
// Include the configuration file to set up the database connection
include 'config.php';

// Function to fetch all submitted image paths from the report table
function fetchSubmittedImages() {
    global $conn;

    // Update the SQL query to fetch the reportID and imagePath from the report table
    $sql = "SELECT report.reportID, report.imagePath, user.userName 
            FROM report
            JOIN User AS user ON report.userID = user.userID
            WHERE report.imagePath IS NOT NULL AND report.imagePath != '' 
            ORDER BY report.submissionDate DESC";

    // Execute the query and fetch the results
    $result = $conn->query($sql);
    $images = [];

    // If results are found, store them in an array
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
    }

    // Return the array of images
    return $images;
}

// Fetch all submitted images
$images = fetchSubmittedImages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Images</title>
    <style>
        img {
            max-width: 80%;
            height: auto;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .image-container div {
            margin: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Submitted Images</h1>
    
    <?php if (!empty($images)): ?>
        <div class="image-container">
            <?php foreach ($images as $image): ?>
                <div class="container">
                    <h3>Report ID: <?php echo htmlspecialchars($image['reportID']); ?></h3>
                    <p>Submitted by: <?php echo htmlspecialchars($image['userName']); ?></p>
                    <!-- Adjust the image source if necessary -->
                    <img src="uploads/images/<?php echo htmlspecialchars($image['imagePath']); ?>" alt="Submitted Image">
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Print button -->
        <button onclick="printReport(this)">Print Report</button>
    <?php else: ?>
        <p>No images have been submitted yet.</p>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function printReport(button) {
            // Clone the closest .container and remove unwanted elements
            var reportContainer = $(button).closest('.container').clone();   
            reportContainer.find(".register").remove();  // If there is any register section to remove
            reportContainer.find('table').css('border-collapse', 'collapse').find('td, th').css('border', '1px solid #ddd');
            reportContainer.find(".register").remove();

            // Apply styling for the print window
            reportContainer.find('div').css({
                'width': '100%',
                'margin': '0',
                'padding': '10px',
                'display': 'flex',
                'flex-direction': 'column',
                'align-items': 'center',
                'text-align': 'center'
            });

            // Specific styles for images
            reportContainer.find('img').css({
                'max-width': '80%',
                'height': 'auto'
            });

            // Opening the print window and writing the cloned HTML to it
            var printWindow = window.open('', '_blank');
            printWindow.document.open();
            printWindow.document.write(reportContainer.html());
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>




