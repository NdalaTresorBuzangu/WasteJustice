<?php 
include 'core.php';
include 'config.php';
include 'nav.php';
isLogin();

// Fetch subscribed schools (only active schools)
$schools = [];
$result = $conn->query("
    SELECT s.subscribeID, s.schoolName, u.userID 
    FROM Subscribe s 
    JOIN User u ON s.userID = u.userID 
    WHERE u.userRole = 'School'
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schools[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report - Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="submit-report.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>
<header class="container mt-3">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Submit a Request to Retrieve Your Document</h1>
        <form action="logout.php" method="POST" class="d-inline">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                Logout
            </button>
        </form>
    </div>
</header>

<main class="container mt-4">
    <form id="reportForm" enctype="multipart/form-data">
        <!-- Student Info -->
        <div class="mb-3">
            <label for="userName" class="form-label">Your Name:</label>
            <input type="text" id="userName" name="userName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Your Email:</label>
            <input type="email" id="userEmail" name="userEmail" class="form-control" required>
        </div>

        <!-- School Select -->
        <div class="mb-3">
            <label for="schoolID" class="form-label">Select Your School:</label>
            <select id="schoolID" name="schoolID" class="form-select" required>
                <option value="">-- Select School --</option>
                <?php foreach ($schools as $school): ?>
                    <option value="<?= htmlspecialchars($school['userID']) ?>">
                        <?= htmlspecialchars($school['schoolName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Document Type -->
        <div class="mb-3">
            <label for="maintenanceType" class="form-label">Document Type:</label>
            <select id="maintenanceType" name="maintenanceType" class="form-select" required>
                <option value="1">State exams</option>
                <option value="2">P6</option>
                <option value="3">P5</option>
                <option value="4">P4</option>
                <option value="5">P1 to P3</option>
            </select>
        </div>

        <!-- Location -->
        <div class="mb-3">
            <label for="location" class="form-label">Location:</label>
            <input type="text" id="location" name="location" class="form-control" required>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label for="description" class="form-label">Description:</label>
            <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
        </div>

        <!-- File Upload -->
        <div class="mb-4">
            <label for="image" class="form-label">Upload Supporting File (optional):</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*">
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">Submit Report</button>
        </div>
    </form>
</main>

<script>
document.getElementById('reportForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const formData = new FormData(this);

    try {
        const response = await fetch('submitreport_action.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();

        if (result.success) {
            alert(`Report submitted successfully! Your Report ID is: ${result.reportID}`);
            window.location.href = "submit_report.php";
        } else {
            alert(`Error: ${result.message}`);
        }
    } catch (error) {
        alert('An unexpected error occurred. Please try again.');
    }
});
</script>
</body>
</html>



