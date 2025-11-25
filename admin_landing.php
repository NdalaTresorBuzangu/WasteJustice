<?php
session_start();
include "Functions_users_reports.php"; // Include the functions file
include "core.php"; // Include the login check function (isLogin)
isLogin(); // Check if the user is logged in
isAdmin();

// Handle form submissions for adding, updating, and deleting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // ------------------ User Operations ------------------
        if (isset($_POST['addUser'])) {
            $userName = filter_input(INPUT_POST, 'userName', FILTER_SANITIZE_STRING);
            $userEmail = filter_input(INPUT_POST, 'userEmail', FILTER_VALIDATE_EMAIL);
            $userPassword = filter_input(INPUT_POST, 'userPassword', FILTER_SANITIZE_STRING);
            $userRole = filter_input(INPUT_POST, 'userRole', FILTER_SANITIZE_STRING);

            if ($userName && $userEmail && $userPassword && $userRole) {
                addUser($userName, $userEmail, $userPassword, $userRole);
                $_SESSION['message'] = "User added successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                throw new Exception("Invalid user data provided.");
            }
        } elseif (isset($_POST['deleteUser'])) {
            $userID = filter_input(INPUT_POST, 'deleteUser', FILTER_VALIDATE_INT);
            deleteUser($userID);
            $_SESSION['message'] = "User deleted successfully.";
            $_SESSION['message_type'] = "success";
        } elseif (isset($_POST['updateUserRole'])) {
            $userID = filter_input(INPUT_POST, 'updateUserRole', FILTER_VALIDATE_INT);
            $newRole = filter_input(INPUT_POST, 'newRole', FILTER_SANITIZE_STRING);
            updateUserRole($userID, $newRole);
            $_SESSION['message'] = "User role updated successfully.";
            $_SESSION['message_type'] = "success";
        }

        // ------------------ Report Operations ------------------
        if (isset($_POST['addReport'])) {
            $userID = filter_input(INPUT_POST, 'userID', FILTER_VALIDATE_INT);
            $schoolID = filter_input(INPUT_POST, 'schoolID', FILTER_VALIDATE_INT);
            $maintenanceTypeID = filter_input(INPUT_POST, 'maintenanceTypeID', FILTER_VALIDATE_INT);
            $statusID = filter_input(INPUT_POST, 'statusID', FILTER_VALIDATE_INT);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
            $file = $_FILES['reportImage'];

            if ($userID && $schoolID && $maintenanceTypeID && $statusID && $description && $location) {
                $reportID = addReport($userID, $schoolID, $maintenanceTypeID, $statusID, $description, $location, $file);
                $_SESSION['message'] = "Report added successfully. Report ID: {$reportID}";
                $_SESSION['message_type'] = "success";
            } else {
                throw new Exception("Invalid report data provided.");
            }
        } elseif (isset($_POST['deleteReport'])) {
            $reportID = filter_input(INPUT_POST, 'deleteReport', FILTER_SANITIZE_STRING);
            deleteReport($reportID);
            $_SESSION['message'] = "Report deleted successfully.";
            $_SESSION['message_type'] = "success";
        } elseif (isset($_POST['updateReportStatus'])) {
            $reportID = filter_input(INPUT_POST, 'updateReportStatus', FILTER_SANITIZE_STRING);
            $newStatusID = filter_input(INPUT_POST, 'newStatusID', FILTER_VALIDATE_INT);
            $file = isset($_FILES['updateReportImage']) ? $_FILES['updateReportImage'] : null;
            updateReportStatus($reportID, $newStatusID, $file);
            $_SESSION['message'] = "Report status updated successfully.";
            $_SESSION['message_type'] = "success";
        }

        header("Location: admin_landing.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: admin_landing.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script>
<title>Admin Dashboard</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">Admin Dashboard</h1>

    <div class="text-end mb-3">
        <form action="logout.php" method="POST">
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>
    </div>

    <!-- Feedback Section -->
    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-{$_SESSION['message_type']}'>{$_SESSION['message']}</div>";
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
    ?>

    <!-- Manage Users Section -->
    <div class="mb-5">
        <h2>Manage Users</h2>
        <form method="POST" class="mb-3">
            <input type="text" name="userName" class="form-control mb-2" placeholder="Name" required>
            <input type="email" name="userEmail" class="form-control mb-2" placeholder="Email" required>
            <input type="password" name="userPassword" class="form-control mb-2" placeholder="Password" required>
            <select name="userRole" class="form-control mb-2" required>
                <option value="Affected Student">Affected Student</option>
                <option value="School">School</option>
                <option value="Admin">Admin</option>
            </select>
            <button type="submit" name="addUser" class="btn btn-primary">Add User</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = getAllUsers();
                foreach ($users as $user) {
                    echo "<tr>
                        <td>{$user['userID']}</td>
                        <td>{$user['userName']}</td>
                        <td>{$user['userEmail']}</td>
                        <td>{$user['userRole']}</td>
                        <td>
                            <form action='' method='POST' style='display:inline;'>
                                <button type='submit' name='deleteUser' value='{$user['userID']}' class='btn btn-danger btn-sm'>Delete</button>
                            </form>
                            <form action='' method='POST' style='display:inline;'>
                                <select name='newRole' class='form-control-sm'>
                                    <option value='Admin' " . ($user['userRole']=='Admin'?'selected':'') . ">Admin</option>
                                    <option value='School' " . ($user['userRole']=='School'?'selected':'') . ">School</option>
                                    <option value='Affected Student' " . ($user['userRole']=='Affected Student'?'selected':'') . ">Affected Student</option>
                                </select>
                                <button type='submit' name='updateUserRole' value='{$user['userID']}' class='btn btn-warning btn-sm mt-1'>Update Role</button>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Manage Reports Section -->
    <div class="mb-5">
        <h2>Manage Documents / Reports</h2>
        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <select name="userID" class="form-control mb-2" required>
                <?php foreach ($users as $user) echo "<option value='{$user['userID']}'>{$user['userName']}</option>"; ?>
            </select>
            <select name="schoolID" class="form-control mb-2" required>
                <?php foreach ($users as $user) if($user['userRole']=='School') echo "<option value='{$user['userID']}'>{$user['userName']}</option>"; ?>
            </select>
            <input type="text" name="description" class="form-control mb-2" placeholder="Description" required>
            <input type="text" name="location" class="form-control mb-2" placeholder="Location" required>
            <input type="file" name="reportImage" class="form-control mb-2" accept="image/*" required>
            <select name="maintenanceTypeID" class="form-control mb-2" required>
                <option value="1">State exams</option>
                <option value="2">P6</option>
                <option value="3">P5</option>
                <option value="4">P4</option>
                <option value="5">P1 to P3</option>
            </select>
            <select name="statusID" class="form-control mb-2" required>
                <option value="1">Pending</option>
                <option value="2">In Progress</option>
                <option value="3">Completed</option>
                <option value="4">Cancelled</option>
            </select>
            <button type="submit" name="addReport" class="btn btn-primary">Add Document</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Image</th>
                    <th>Submission Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reports = getAllReports();
                foreach ($reports as $report) {
                    $statusID = $report['statusID'];
                    $imagePath = !empty($report['imagePath']) ? $report['imagePath'] : "uploads/images/placeholder.jpg";
                    echo "<tr>
                        <td>{$report['reportID']}</td>
                        <td>{$report['description']}</td>
                        <td>{$report['location']}</td>
                        <td>
                            <img src='{$imagePath}' alt='Report Image' style='width:100px;height:auto;'><br>
                            <a href='{$imagePath}' download class='btn btn-success btn-sm mt-1'>Download</a>
                        </td>
                        <td>{$report['submissionDate']}</td>
                        <td>" . getStatusById($statusID) . "</td>
                        <td>
                            <form action='' method='POST' style='display:inline;'>
                                <button type='submit' name='deleteReport' value='{$report['reportID']}' class='btn btn-danger btn-sm'>Delete</button>
                            </form>
                            <form action='' method='POST' enctype='multipart/form-data' style='display:inline; margin-top:5px;'>
                                <input type='file' name='updateReportImage' class='form-control-sm mb-1' accept='image/*'>
                                <select name='newStatusID' class='form-control-sm mb-1'>
                                    <option value='1' ".($statusID==1?'selected':'').">Pending</option>
                                    <option value='2' ".($statusID==2?'selected':'').">In Progress</option>
                                    <option value='3' ".($statusID==3?'selected':'').">Completed</option>
                                    <option value='4' ".($statusID==4?'selected':'').">Cancelled</option>
                                </select>
                                <button type='submit' name='updateReportStatus' value='{$report['reportID']}' class='btn btn-warning btn-sm'>Update</button>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

