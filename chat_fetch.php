<?php
include 'core.php';
include 'config.php';
isLogin();

if (!isset($_GET['reportID'])) {
    exit("Invalid request.");
}

$reportID = $_GET['reportID'];
$userID = $_SESSION['user_id'];

// Verify access
$stmt = $conn->prepare("SELECT * FROM Report WHERE reportID = ? AND (userID = ? OR schoolID = ?)");
$stmt->bind_param("sii", $reportID, $userID, $userID);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    exit("Access denied.");
}

// Fetch chat messages
$stmt = $conn->prepare("
    SELECT c.chatID, c.message, c.timestamp, u.userName, c.senderID
    FROM Chat c
    JOIN User u ON c.senderID = u.userID
    WHERE c.reportID = ?
    ORDER BY c.timestamp ASC
");
$stmt->bind_param("s", $reportID);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

if ($messages->num_rows > 0) {
    while ($msg = $messages->fetch_assoc()) {
        $isMe = $msg['senderID'] == $userID;
        echo '<div class="chat-message" style="text-align:'.($isMe?'right':'left').'">';
        echo '<strong>'.htmlspecialchars($msg['userName']).':</strong> ';
        echo htmlspecialchars($msg['message']);
        echo '<br><small class="text-muted">'.htmlspecialchars($msg['timestamp']).'</small>';
        echo '</div>';
    }
} else {
    echo "<p>No messages yet.</p>";
}
?>


