<?php
include 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = htmlspecialchars(trim($_POST['userName']));
    $userEmail = filter_var(trim($_POST['userEmail']), FILTER_SANITIZE_EMAIL);
    $schoolID = intval($_POST['schoolID']);
    $maintenanceTypeID = intval($_POST['maintenanceType']);
    $location = htmlspecialchars(trim($_POST['location']));
    $description = htmlspecialchars(trim($_POST['description']));
    $reportID = uniqid('report_');

    if (empty($userName) || empty($userEmail) || empty($schoolID) || empty($maintenanceTypeID) || empty($location) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Check if user exists
        $stmt = $conn->prepare('SELECT userID FROM User WHERE userEmail = ?');
        $stmt->bind_param('s', $userEmail);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID);
            $stmt->fetch();
        } else {
            // Insert new student
            $stmt = $conn->prepare('INSERT INTO User (userName, userEmail, userRole, userPassword) VALUES (?, ?, "Affected Student", "default123")');
            $stmt->bind_param('ss', $userName, $userEmail);
            if (!$stmt->execute()) {
                throw new Exception('Error inserting student.');
            }
            $userID = $stmt->insert_id;
        }
        $stmt->close();

        // ✅ Handle image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "/uploads/images/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["image"]["name"]);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                // Save relative path in DB
                $imagePath = "uploads/images/" . $fileName;
            }
        }

        // Insert report
        $statusID = 1; // Pending
        $stmt = $conn->prepare('
            INSERT INTO Report 
            (reportID, userID, schoolID, maintenanceTypeID, statusID, description, location, imagePath, submissionDate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->bind_param('siiiisss', $reportID, $userID, $schoolID, $maintenanceTypeID, $statusID, $description, $location, $imagePath);

        if (!$stmt->execute()) {
            throw new Exception('Error submitting report.');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'reportID' => $reportID]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}
?>




