<?php
session_start();
include 'core.php';
include 'config.php';
#isLogin();

// Only allow School users
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'School') {
    die("Access denied. Only schools can subscribe.");
}

$userID = $_SESSION['user_id'];

// ✅ Check if the school is already subscribed
$checkStmt = $conn->prepare("SELECT subscribeID FROM Subscribe WHERE userID = ?");
$checkStmt->bind_param('i', $userID);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Already subscribed → redirect
    header("Location: schoolpanel.php");
    exit();
}
$checkStmt->close();

// ✅ Handle new subscription form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolName    = htmlspecialchars(trim($_POST['schoolName']));
    $schoolContact = htmlspecialchars(trim($_POST['schoolContact']));
    $schoolEmail   = htmlspecialchars(trim($_POST['schoolEmail']));

    // ✅ Insert subscription record
    $stmt = $conn->prepare("INSERT INTO Subscribe (userID, schoolName, schoolContact, schoolEmail)
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $userID, $schoolName, $schoolContact, $schoolEmail);

    if ($stmt->execute()) {
        echo "<script>alert('School subscribed successfully!'); window.location='schoolpanel.php';</script>";
    } else {
        echo "<script>alert('Error inserting: " . $conn->error . "');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Subscription</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>New School Subscription</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="schoolName" class="form-label">School Name</label>
            <input type="text" name="schoolName" id="schoolName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="schoolContact" class="form-label">Contact</label>
            <input type="text" name="schoolContact" id="schoolContact" class="form-control">
        </div>
        <div class="mb-3">
            <label for="schoolEmail" class="form-label">Email</label>
            <input type="email" name="schoolEmail" id="schoolEmail" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Subscribe</button>
    </form>
</body>
</html>

