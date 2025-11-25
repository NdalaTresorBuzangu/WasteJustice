<?php
include 'core.php';
include 'config.php';
isLogin();

if ($_SESSION['user_role'] !== 'Affected Student') {
    echo json_encode(['success'=>false, 'message'=>'Access denied']);
    exit;
}

$userID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documents = $_POST['documents'] ?? [];
    $classification = htmlspecialchars(trim($_POST['classification']));
    $institutionEmail = filter_var(trim($_POST['institutionEmail']), FILTER_VALIDATE_EMAIL);

    if(empty($documents) || empty($classification) || !$institutionEmail){
        echo json_encode(['success'=>false, 'message'=>'All fields are required']);
        exit;
    }

    // Save the pack in TshijukaPackHistory
    $documentIDs = implode(',', $documents);
    $stmt = $conn->prepare("
        INSERT INTO TshijukaPackHistory (userID, documentIDs, classification, institutionEmail, sharedOn)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $userID, $documentIDs, $classification, $institutionEmail);

    if($stmt->execute()){
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Failed to share pack']);
    }
    $stmt->close();
}
?>


