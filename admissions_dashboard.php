<?php
session_start();
include 'core.php';
include 'config.php';
isLogin();

// Ensure only Admissions Office role can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    echo "Access denied. Only Admissions Office users can access this page.";
    exit;
}

$officeID = $_SESSION['user_id'];

// Fetch Admissions Office info
$stmt = $conn->prepare("SELECT * FROM Subscribe WHERE userID = ? AND roleType = 'Admissions Office' LIMIT 1");
$stmt->bind_param("i", $officeID);
$stmt->execute();
$office = $stmt->get_result()->fetch_assoc();
$stmt->close();

$officeName = htmlspecialchars($office['name'] ?? 'Admissions Office');

// Fetch all subscribed schools
$stmt = $conn->prepare("SELECT s.userID, s.name AS schoolName, u.userEmail AS schoolEmail
                        FROM Subscribe s
                        JOIN User u ON s.userID = u.userID
                        WHERE s.roleType = 'School'
                        ORDER BY s.name ASC");
$stmt->execute();
$schools = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admissions Dashboard - Tshijuka RDP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container mt-4">
    <h1>Welcome, <?= $officeName ?></h1>
    <p class="lead">Here are all subscribed schools. Click "Chat" to verify student documents.</p>

    <!-- Feedback Section -->
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['message_type'] ?? 'info') ?>">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>School Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($schools->num_rows > 0): ?>
                <?php while ($row = $schools->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['schoolName'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['schoolEmail'] ?? '') ?></td>
                        <td>
                            <!-- Chat Button -->
                            <a href="admissions_chat.php?schoolID=<?= urlencode($row['userID'] ?? '') ?>" 
                               class="btn btn-sm btn-success">Chat</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">No schools have subscribed yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>





