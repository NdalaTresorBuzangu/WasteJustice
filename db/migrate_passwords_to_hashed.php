<?php
/**
 * Password Migration Script
 * 
 * This script migrates all plain text passwords in the database to hashed passwords.
 * Run this ONCE after implementing password hashing to upgrade existing users.
 * 
 * WARNING: Make sure to backup your database before running this script!
 * 
 * Usage: php migrate_passwords_to_hashed.php
 * Or access via browser: http://localhost/WasteJustice/db/migrate_passwords_to_hashed.php
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Security: Only allow this to run in development or with admin authentication
// For production, add authentication check here
$allowMigration = true; // Set to false in production, or add proper authentication

if (!$allowMigration) {
    die("Migration is disabled. Enable it in the script or add proper authentication.");
}

echo "<h2>Password Migration Script</h2>";
echo "<p>Migrating plain text passwords to hashed passwords...</p>";

// Get all users with plain text passwords (not starting with $2y$, $2a$, or $2b$)
$query = "SELECT userID, userEmail, userPassword FROM User WHERE userPassword NOT LIKE '\$2y\$%' AND userPassword NOT LIKE '\$2a\$%' AND userPassword NOT LIKE '\$2b\$%'";
$result = $conn->query($query);

if (!$result) {
    die("Error: " . $conn->error);
}

$totalUsers = $result->num_rows;
$updatedCount = 0;
$errorCount = 0;

echo "<p>Found <strong>$totalUsers</strong> users with plain text passwords.</p>";
echo "<ul>";

while ($row = $result->fetch_assoc()) {
    $userID = $row['userID'];
    $userEmail = $row['userEmail'];
    $plainPassword = $row['userPassword'];
    
    // Hash the password
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    if ($hashedPassword === false) {
        echo "<li style='color: red;'>❌ Failed to hash password for user ID $userID ($userEmail)</li>";
        $errorCount++;
        continue;
    }
    
    // Update the password in database
    $updateStmt = $conn->prepare("UPDATE User SET userPassword = ? WHERE userID = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userID);
    
    if ($updateStmt->execute()) {
        echo "<li style='color: green;'>✅ Updated password for user ID $userID ($userEmail)</li>";
        $updatedCount++;
    } else {
        echo "<li style='color: red;'>❌ Failed to update password for user ID $userID ($userEmail): " . $updateStmt->error . "</li>";
        $errorCount++;
    }
    
    $updateStmt->close();
}

echo "</ul>";
echo "<hr>";
echo "<h3>Migration Summary</h3>";
echo "<p><strong>Total users found:</strong> $totalUsers</p>";
echo "<p><strong>Successfully updated:</strong> <span style='color: green;'>$updatedCount</span></p>";
echo "<p><strong>Errors:</strong> <span style='color: red;'>$errorCount</span></p>";

if ($errorCount == 0 && $updatedCount > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>";
    echo "<p><strong>IMPORTANT:</strong> Delete or secure this migration script after use.</p>";
} elseif ($errorCount > 0) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Migration completed with errors. Please review the errors above.</p>";
} else {
    echo "<p>No passwords needed migration (all are already hashed).</p>";
}

$conn->close();
?>

