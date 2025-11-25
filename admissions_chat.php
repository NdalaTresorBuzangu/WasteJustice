<?php
session_start();
include 'core.php';
include 'config.php';
isLogin();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    echo "Access denied.";
    exit;
}

$officeID = $_SESSION['user_id'];
$schoolID = isset($_GET['schoolID']) ? intval($_GET['schoolID']) : 0;
if ($schoolID <= 0) exit("Invalid school selected.");

// Fetch school info
$stmt = $conn->prepare("SELECT s.name AS schoolName, u.userEmail AS schoolEmail
                        FROM Subscribe s
                        JOIN User u ON s.userID = u.userID
                        WHERE s.userID = ? AND s.roleType = 'School'");
$stmt->bind_param("i", $schoolID);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$school) exit("School not found.");

// Handle sending messages + file attachments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $reportID = trim($_POST['reportID'] ?? '');
    $filePath = null;

    // Handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = basename($_FILES['attachment']['name']);
        $targetFile = $uploadDir . time() . "_" . $filename;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif','pdf'];
        if (in_array($fileType, $allowed)) {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                $filePath = 'uploads/' . basename($targetFile);
            } else {
                $_SESSION['message'] = "Failed to upload file.";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only images and PDFs allowed.";
            $_SESSION['message_type'] = "danger";
        }
    }

    if (!empty($message) || $filePath) {
        $stmt = $conn->prepare("INSERT INTO Chat (reportID, senderID, receiverID, message, filePath) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $reportID, $officeID, $schoolID, $message, $filePath);
        $stmt->execute();
        $stmt->close();
        header("Location: admissions_chat.php?schoolID={$schoolID}&reportID={$reportID}");
        exit;
    }
}

// Fetch students with reports in this school
$stmt = $conn->prepare("
    SELECT DISTINCT u.userID, u.userName, u.userEmail, r.reportID
    FROM Report r
    JOIN User u ON r.userID = u.userID
    WHERE r.schoolID = ?
    ORDER BY u.userName ASC
");
$stmt->bind_param("i", $schoolID);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Fetch chat messages for selected report
$selectedReportID = $_GET['reportID'] ?? '';
$chats = [];
if (!empty($selectedReportID)) {
    $stmt = $conn->prepare("
        SELECT c.*, u.userName AS senderName
        FROM Chat c
        JOIN User u ON c.senderID = u.userID
        WHERE c.reportID = ?
        ORDER BY c.timestamp ASC
    ");
    $stmt->bind_param("s", $selectedReportID);
    $stmt->execute();
    $chats = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissions Chat - <?= htmlspecialchars($school['schoolName']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
<h2>Chat with <?= htmlspecialchars($school['schoolName']) ?></h2>

<div class="mb-3">
    <label>Select Student Report:</label>
    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="schoolID" value="<?= $schoolID ?>">
        <select name="reportID" class="form-select">
            <option value="">-- Select a Student Report --</option>
            <?php while ($student = $students->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($student['reportID']) ?>" <?= ($student['reportID'] ?? '') === $selectedReportID ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['userName']) ?> (<?= htmlspecialchars($student['userEmail']) ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Load Chat</button>
    </form>
</div>

<?php if (!empty($selectedReportID)): ?>
    <h4>Chat for Report ID: <?= htmlspecialchars($selectedReportID) ?></h4>
    <div class="border p-3 mb-3" style="height:300px; overflow-y:auto;">
        <?php if ($chats->num_rows > 0): ?>
            <?php while ($chat = $chats->fetch_assoc()): ?>
                <p>
                    <strong><?= htmlspecialchars($chat['senderName']) ?>:</strong>
                    <?= htmlspecialchars($chat['message']) ?>
                    <?php if (!empty($chat['filePath'])): ?>
                        <br><a href="<?= htmlspecialchars($chat['filePath']) ?>" target="_blank">View Attachment</a>
                    <?php endif; ?>
                    <small class="text-muted">(<?= $chat['timestamp'] ?>)</small>
                </p>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No messages yet.</p>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" class="mb-3">
        <input type="hidden" name="reportID" value="<?= htmlspecialchars($selectedReportID) ?>">
        <div class="mb-2">
            <textarea name="message" class="form-control" rows="3" placeholder="Type your message..."></textarea>
        </div>
        <div class="mb-2">
            <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
        </div>
        <button type="submit" class="btn btn-success">Send</button>
    </form>
<?php else: ?>
    <p class="text-muted">Select a student report to start chatting.</p>
<?php endif; ?>

<a href="admissions_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</body>
</html>






