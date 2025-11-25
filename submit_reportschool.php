<?php 
include 'core.php';
include 'config.php';
include 'nav.php'; // Include the navigation bar
isLogin(); // Check if the user is logged in
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School interface - Tshijuka RDP </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="submit-report.css"> <!-- Custom styles -->
    <link rel="stylesheet" href="nav.css"> <!-- Navigation bar styles -->
    <script src="https://kit.fontawesome.com/cb76afc7c2.js" crossorigin="anonymous"></script> <!-- Font Awesome -->
</head>
<body>
    <header class="container mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Submit the requested Document</h1>
            <!-- Logout Button -->
            <form action="logout.php" method="POST" class="d-inline">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </header>

    <main class="container mt-4">
        <p>Please fill out the form below to upload a document.</p>
        
        <form id="reportForm" enctype="multipart/form-data">
            <!-- User Name Input -->
            <div class="mb-3">
                <label for="userName" class="form-label">Your Name:</label>
                <input type="text" id="userName" name="userName" class="form-control" placeholder="Enter your name" required>
            </div>

            <!-- User Email Input -->
            <div class="mb-3">
                <label for="userEmail" class="form-label">Your Email:</label>
                <input type="email" id="userEmail" name="userEmail" class="form-control" placeholder="Enter your email" required>
            </div>

            <!-- Maintenance Type Select -->
            <div class="mb-3">
                <label for="maintenanceType" class="form-label">Select Maintenance Type:</label>
                <select id="maintenanceType" name="maintenanceType" class="form-select" required>
                    <option value="1">State exams</option>
                    <option value="2">P6</option>
                    <option value="3">P5</option>
                    <option value="4">P4</option>
                    <option value="5">P1 to P3</option>
                </select>
            </div>

            <!-- Location Input -->
            <div class="mb-3">
                <label for="location" class="form-label">Location:</label>
                <input type="text" id="location" name="location" class="form-control" placeholder="Enter the location of the issue (e.g., Building A, Room 101)" required>
            </div>

            <!-- Description Textarea -->
            <div class="mb-4">
                <label for="description" class="form-label">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe the issue" required></textarea>
            </div>

            <!-- Image Upload -->
            <div class="mb-4">
                <label for="image" class="form-label">Upload an Image (optional):</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*">
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </div>
        </form>
    </main>

    <footer class="text-center mt-5">
        <p>&copy; Tshijuka RDP</p>
    </footer>

    <script>
        document.getElementById('reportForm').addEventListener('submit', async function (event) {
            event.preventDefault(); // Prevent form from reloading the page

            const formData = new FormData(this); // Get form data

            try {
                const response = await fetch('submitreport_action.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json(); // Parse the JSON response

                if (result.success) {
                    alert(`Report submitted successfully! Your Report ID is: ${result.reportID}`);
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('An unexpected error occurred. Please try again.');
            }
        });
    </script>
</body>
</html>

