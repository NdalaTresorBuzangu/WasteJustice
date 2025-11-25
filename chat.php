<?php
include 'core.php';
include 'config.php';
isLogin();

if (!isset($_GET['reportID'])) {
    echo "Invalid request.";
    exit;
}

$reportID = $_GET['reportID'];
$userID = $_SESSION['user_id'];

// Check if user is part of this report
$stmt = $conn->prepare("SELECT * FROM Report WHERE reportID = ? AND (userID = ? OR schoolID = ?)");
$stmt->bind_param("sii", $reportID, $userID, $userID);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) {
    echo "Access denied.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Report <?= htmlspecialchars($reportID) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .chat-box { height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9; }
        .chat-message { margin-bottom: 10px; }
        .chat-message strong { color: #007bff; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container mt-4">
    <h2>Chat for Report: <?= htmlspecialchars($reportID) ?></h2>

    <div id="chat-box" class="chat-box mb-3"></div>

    <form id="chatForm">
        <input type="hidden" name="reportID" value="<?= htmlspecialchars($reportID) ?>">
        <div class="mb-3">
            <textarea name="message" id="message" class="form-control" placeholder="Type your message..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>

<script>
// Function to fetch chat messages
function loadMessages() {
    const reportID = "<?= htmlspecialchars($reportID) ?>";
    fetch("chat_fetch.php?reportID=" + reportID)
        .then(res => res.text())
        .then(data => {
            document.getElementById("chat-box").innerHTML = data;
            let chatBox = document.getElementById("chat-box");
            chatBox.scrollTop = chatBox.scrollHeight; // auto-scroll
        });
}

// Auto-refresh every 3 seconds
setInterval(loadMessages, 3000);
window.onload = loadMessages;

// Handle form submit with AJAX
document.getElementById("chatForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("chat_action.php", {
        method: "POST",
        body: formData
    })
    .then(() => {
        document.getElementById("message").value = "";
        loadMessages();
    });
});
</script>
</body>
</html>

