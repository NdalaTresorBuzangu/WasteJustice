<?php
session_start();
include 'core.php';
include 'config.php';
isLogin();

// Ensure only Admissions Office can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admissions Office') {
    die("Access denied. Only Admissions Office can subscribe.");
}

$userID = $_SESSION['user_id'];

// Check if already subscribed
$stmt = $conn->prepare("SELECT subscriptionID FROM Subscribe WHERE userID = ? AND roleType='Admissions Office' LIMIT 1");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result && $result->num_rows > 0) {
    // Already subscribed → go to dashboard
    header("Location: admissions_dashboard.php");
    exit();
}

// Handle subscription form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $officeName    = trim($_POST['officeName'] ?? '');
    $officeContact = trim($_POST['officeContact'] ?? '');

    if (!empty($officeName)) {
        // Insert into Subscribe table
        $stmt = $conn->prepare("INSERT INTO Subscribe (userID, roleType, name) VALUES (?, 'Admissions Office', ?)");
        $stmt->bind_param("is", $userID, $officeName);

        if ($stmt->execute()) {
            $stmt->close();

            // Update User table (only name + contact, NOT email)
            $updateStmt = $conn->prepare("UPDATE User SET userName=?, userContact=? WHERE userID=?");
            $updateStmt->bind_param("ssi", $officeName, $officeContact, $userID);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: admissions_dashboard.php");
            exit();
        } else {
            $error = "Failed to save subscription: " . $conn->error;
        }
    } else {
        $error = "Office name is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admissions Office Subscription</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Admissions Office Subscription</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="officeName" class="form-label">Office Name</label>
            <input type="text" name="officeName" id="officeName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="officeContact" class="form-label">Office Contact</label>
            <input type="text" name="officeContact" id="officeContact" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Subscribe</button>
    </form>
</body>
</html>







