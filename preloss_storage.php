<?php
include 'core.php';
include 'config.php';
isLogin();

if ($_SESSION['user_role'] !== 'Affected Student') {
    echo "Access denied.";
    exit;
}

$studentID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docName = htmlspecialchars(trim($_POST['docName']));
    $docTypeID = intval($_POST['docType']);

    // Upload the file
    $uploadDir = __DIR__ . "/uploads/preloss/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES["document"]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["document"]["tmp_name"], $targetFile)) {
        $filePath = "uploads/preloss/" . $fileName;

        $stmt = $conn->prepare("INSERT INTO Report 
            (reportID, userID, schoolID, maintenanceTypeID, statusID, description, location, imagePath, submissionDate) 
            VALUES (?, ?, NULL, ?, 3, ?, '', ?, NOW())"); // status 3 = Completed
        $reportID = uniqid('preloss_');
        $stmt->bind_param("siiss", $reportID, $studentID, $docTypeID, $docName, $filePath);
        $stmt->execute();
        $stmt->close();

        $message = "Document stored successfully!";
    } else {
        $error = "Failed to upload document.";
    }
}

// Fetch student stored documents
$docs = $conn->query("SELECT r.reportID, mt.typeName, r.description, r.imagePath, r.submissionDate
                      FROM Report r 
                      JOIN MaintenanceType mt ON r.maintenanceTypeID = mt.maintenanceTypeID
                      WHERE r.userID = $studentID AND r.statusID=3 ORDER BY r.submissionDate DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pre-loss Document Storage</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Pre-loss: Store Your Documents</h2>
    <?php if(isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="docName" placeholder="Document Name" class="form-control mb-2" required>
        <select name="docType" class="form-control mb-2" required>
            <?php
            $types = $conn->query("SELECT * FROM MaintenanceType");
            while($type = $types->fetch_assoc()) {
                echo "<option value='{$type['maintenanceTypeID']}'>{$type['typeName']}</option>";
            }
            ?>
        </select>
        <input type="file" name="document" class="form-control mb-2" required>
        <button type="submit" class="btn btn-primary">Store Document</button>
    </form>

    <h4 class="mt-4">Your Stored Documents</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Document ID</th>
                <th>Type</th>
                <th>Name</th>
                <th>File</th>
                <th>Stored On</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $docs->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['reportID'] ?></td>
                    <td><?= htmlspecialchars($row['typeName']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><a href="<?= $row['imagePath'] ?>" target="_blank">View/Download</a></td>
                    <td><?= $row['submissionDate'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

