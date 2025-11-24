<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fromUserID = $_SESSION['userID'];
    $toUserID = intval($_POST['toUserID']);
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'] ?? '';
    $collectionID = isset($_POST['collectionID']) ? intval($_POST['collectionID']) : null;
    $batchID = isset($_POST['batchID']) ? intval($_POST['batchID']) : null;
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit();
    }
    
    $stmt = $conn->prepare("
        INSERT INTO Feedback (fromUserID, toUserID, collectionID, batchID, rating, comment)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiiis", $fromUserID, $toUserID, $collectionID, $batchID, $rating, $comment);
    
    if ($stmt->execute()) {
        // Update user rating
        $updateRatingStmt = $conn->prepare("
            UPDATE User 
            SET rating = (
                SELECT AVG(rating) 
                FROM Feedback 
                WHERE toUserID = ?
            ),
            totalRatings = (
                SELECT COUNT(*) 
                FROM Feedback 
                WHERE toUserID = ?
            )
            WHERE userID = ?
        ");
        $updateRatingStmt->bind_param("iii", $toUserID, $toUserID, $toUserID);
        $updateRatingStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>

