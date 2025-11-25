<?php
include 'core.php';
include 'config.php';
#isLogin();

// Ensure only students can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Affected Student') {
    echo "Access denied. Only students can access this page.";
    exit;
}

$studentID = $_SESSION['user_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT * FROM User WHERE userID = ?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch reports submitted by this student
$stmt = $conn->prepare("
    SELECT r.reportID, sub.schoolName, r.description, r.location, r.submissionDate,
           st.statusName, r.statusID, r.imagePath, sub.userID AS schoolID
    FROM Report r
    JOIN Status st ON r.statusID = st.statusID
    JOIN Subscribe sub ON r.schoolID = sub.userID
    WHERE r.userID = ?
    ORDER BY r.submissionDate DESC
");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$reports = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tshijuka Pack - Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container mt-4">
    <h1>Welcome, <?= htmlspecialchars($student['userName']) ?></h1>
    <p class="lead">Here are your submitted document requests (Tshijuka Pack).</p>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Report ID</th>
                <th>School</th>
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
                        <td><?= htmlspecialchars($row['schoolName']) ?></td>
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
                            <!-- Chat Button -->
                            <a href="chat.php?reportID=<?= urlencode($row['reportID']) ?>&schoolID=<?= urlencode($row['schoolID']) ?>" 
                               class="btn btn-sm btn-success">Chat with School</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No reports submitted yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
