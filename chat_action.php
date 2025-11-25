<?php
include 'core.php';
include 'config.php';
isLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportID = $_POST['reportID'] ?? '';
    $message = trim($_POST['message'] ?? '');
    $senderID = $_SESSION['user_id'];

    if (!empty($reportID) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO Chat (reportID, senderID, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $reportID, $senderID, $message);
        $stmt->execute();
        $stmt->close();
    }
}
?>



