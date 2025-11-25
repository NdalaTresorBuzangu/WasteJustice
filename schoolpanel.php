<?php
include 'core.php';
include 'config.php';
include 'Functions_users_reports.php';
isLogin();

// Check if logged-in user is a School
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'School') {
    echo "Access denied. Only schools can access this page.";
    exit;
}

$schoolID = $_SESSION['user_id'];

// Fetch school info
$stmt = $conn->prepare("SELECT * FROM Subscribe WHERE userID = ?");
$stmt->bind_param("i", $schoolID);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submissions for updating reports
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['updateReportStatus'])) {
            $reportID = filter_input(INPUT_POST, 'updateReportStatus', FILTER_SANITIZE_STRING);
            $newStatusID = filter_input(INPUT_POST, 'newStatusID', FILTER_VALIDATE_INT);
            $file = isset($_FILES['reportFile']) ? $_FILES['reportFile'] : null;

            updateReportStatus($reportID, $newStatusID, $file);

            $_SESSION['message'] = "Report updated successfully.";
            $_SESSION['message_type'] = "success";
            header("Location: schoolpanel.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: schoolpanel.php");
        exit;
    }
}

// Fetch reports assigned to this school
$stmt = $conn->prepare("
    SELECT r.reportID, u.userID AS studentID, u.userName AS studentName, u.userEmail AS studentEmail,
           mt.typeName AS documentType, r.description, r.location, r.submissionDate,
           s.statusName, r.statusID, r.imagePath
    FROM Report r
    JOIN User u ON r.userID = u.userID
    JOIN MaintenanceType mt ON r.maintenanceTypeID = mt.maintenanceTypeID
    JOIN Status s ON r.statusID = s.statusID
    WHERE r.schoolID = ?
    ORDER BY r.submissionDate DESC
");
$stmt->bind_param("i", $schoolID);
$stmt->execute();
$reports = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Panel - Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container mt-4">
    <h1>Welcome, <?= htmlspecialchars($school['schoolName']) ?></h1>
    <p class="lead">Here are the reports submitted by students to your school.</p>

    <!-- Feedback Section -->
    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-{$_SESSION['message_type']}'>{$_SESSION['message']}</div>";
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
    ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Report ID</th>
                <th>Student</th>
                <th>Email</th>
                <th>Document Type</th>
                <th>Description</th>
                <th>Location</th>
                <th>Status</th>
                <th>Submitted On</th>
                <th>Attachment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reports->num_rows > 0): ?>
                <?php while ($row = $reports->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['reportID']) ?></td>
                        <td><?= htmlspecialchars($row['studentName']) ?></td>
                        <td><?= htmlspecialchars($row['studentEmail']) ?></td>
                        <td><?= htmlspecialchars($row['documentType']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['statusName']) ?></td>
                        <td><?= htmlspecialchars($row['submissionDate']) ?></td>
                        <td>
                            <?php if (!empty($row['imagePath'])): ?>
                                <a href="<?= htmlspecialchars($row['imagePath']) ?>" target="_blank">View</a>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Update Form -->
                            <form method="POST" enctype="multipart/form-data" class="mb-2">
                                <input type="file" name="reportFile" class="form-control mb-1" accept="image/*">
                                <select name="newStatusID" class="form-select mb-1">
                                    <option value="1" <?= $row['statusID']==1?'selected':'' ?>>Pending</option>
                                    <option value="2" <?= $row['statusID']==2?'selected':'' ?>>In Progress</option>
                                    <option value="3" <?= $row['statusID']==3?'selected':'' ?>>Completed</option>
                                    <option value="4" <?= $row['statusID']==4?'selected':'' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="updateReportStatus" value="<?= $row['reportID'] ?>" class="btn btn-sm btn-primary">Update</button>
                            </form>

                            <!-- Chat Button -->
                            <a href="chat.php?reportID=<?= urlencode($row['reportID']) ?>&studentID=<?= urlencode($row['studentID']) ?>" 
                               class="btn btn-sm btn-success">Chat</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">No reports submitted yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

