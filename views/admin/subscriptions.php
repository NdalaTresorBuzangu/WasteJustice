<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in as admin
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Admin') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userName = $_SESSION['userName'];

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$roleFilter = $_GET['role'] ?? 'all';

// Build query
$whereClause = "1=1";
if ($statusFilter != 'all') {
    if ($statusFilter == 'active') {
        $whereClause .= " AND s.paymentStatus = 'Success' AND s.isActive = 1 AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())";
    } elseif ($statusFilter == 'pending') {
        $whereClause .= " AND s.paymentStatus = 'Pending'";
    } elseif ($statusFilter == 'expired') {
        $whereClause .= " AND s.paymentStatus = 'Success' AND (s.subscriptionEnd < CURDATE() OR s.isActive = 0)";
    } elseif ($statusFilter == 'cancelled') {
        $whereClause .= " AND s.isActive = 0";
    }
}

if ($roleFilter != 'all') {
    $whereClause .= " AND u.userRole = '" . $conn->real_escape_string($roleFilter) . "'";
}

// Get all subscriptions with user details
$subscriptions = $conn->query("
    SELECT s.*, u.userName, u.userEmail, u.userRole, u.status as userStatus,
           ar.businessName, cr.companyName
    FROM Subscriptions s
    JOIN User u ON s.userID = u.userID
    LEFT JOIN AggregatorRegistration ar ON u.userID = ar.userID
    LEFT JOIN CompanyRegistration cr ON u.userID = cr.userID
    WHERE $whereClause
    ORDER BY s.createdAt DESC
");

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM Subscriptions")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM Subscriptions WHERE paymentStatus = 'Success' AND isActive = 1 AND (subscriptionEnd IS NULL OR subscriptionEnd >= CURDATE())")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM Subscriptions WHERE paymentStatus = 'Pending'")->fetch_assoc()['count'],
    'expired' => $conn->query("SELECT COUNT(*) as count FROM Subscriptions WHERE paymentStatus = 'Success' AND (subscriptionEnd < CURDATE() OR isActive = 0)")->fetch_assoc()['count'],
    'revenue' => $conn->query("SELECT SUM(amountPaid) as total FROM Subscriptions WHERE paymentStatus = 'Success'")->fetch_assoc()['total'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Admin - Subscription Management</p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/subscriptions.php">Subscriptions</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>💰 Subscription Management</h2>
            </div>
            
            <!-- Statistics -->
            <div class="dashboard-grid" style="margin-top: 1rem;">
                <div class="dashboard-card">
                    <h3>Total Subscriptions</h3>
                    <div class="value"><?php echo $stats['total']; ?></div>
                </div>
                <div class="dashboard-card" style="border-left: 4px solid var(--primary-green);">
                    <h3>Active</h3>
                    <div class="value" style="color: var(--primary-green);"><?php echo $stats['active']; ?></div>
                </div>
                <div class="dashboard-card" style="border-left: 4px solid var(--orange);">
                    <h3>Pending</h3>
                    <div class="value" style="color: var(--orange);"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="dashboard-card" style="border-left: 4px solid var(--error);">
                    <h3>Expired/Cancelled</h3>
                    <div class="value" style="color: var(--error);"><?php echo $stats['expired']; ?></div>
                </div>
                <div class="dashboard-card" style="border-left: 4px solid var(--primary-green);">
                    <h3>Total Revenue</h3>
                    <div class="value" style="color: var(--primary-green);">GH₵<?php echo number_format($stats['revenue'], 2); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div style="margin-top: 2rem; padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem;">
                <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div>
                        <label><strong>Status:</strong></label>
                        <select name="status" style="padding: 0.5rem; margin-left: 0.5rem;">
                            <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="expired" <?php echo $statusFilter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label><strong>Role:</strong></label>
                        <select name="role" style="padding: 0.5rem; margin-left: 0.5rem;">
                            <option value="all" <?php echo $roleFilter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="Aggregator" <?php echo $roleFilter == 'Aggregator' ? 'selected' : ''; ?>>Aggregator</option>
                            <option value="Recycling Company" <?php echo $roleFilter == 'Recycling Company' ? 'selected' : ''; ?>>Recycling Company</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo VIEWS_URL; ?>/admin/subscriptions.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <div class="card">
            <div class="card-header">
                <h2>📋 All Subscriptions</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Active</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subscriptions->num_rows > 0): ?>
                        <?php while ($sub = $subscriptions->fetch_assoc()): 
                            $businessName = $sub['businessName'] ?? $sub['companyName'] ?? $sub['userName'];
                            $isActive = $sub['isActive'] == 1;
                            $isExpired = $sub['subscriptionEnd'] && strtotime($sub['subscriptionEnd']) < time();
                            $paymentStatus = $sub['paymentStatus'];
                        ?>
                            <tr>
                                <td>#<?php echo $sub['subscriptionID']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($businessName); ?></strong><br>
                                    <small style="color: var(--gray);"><?php echo htmlspecialchars($sub['userEmail']); ?></small>
                                </td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($sub['userRole']); ?></span></td>
                                <td><?php echo htmlspecialchars($sub['planName']); ?></td>
                                <td>GH₵<?php echo number_format($sub['amountPaid'], 2); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'badge-pending';
                                    if ($paymentStatus == 'Success') $statusClass = 'badge-completed';
                                    if ($paymentStatus == 'Failed') $statusClass = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($paymentStatus); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isActive && !$isExpired): ?>
                                        <span class="badge badge-completed">✓ Active</span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="badge badge-cancelled">✗ Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">○ Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $sub['subscriptionStart'] ? date('M d, Y', strtotime($sub['subscriptionStart'])) : 'N/A'; ?></td>
                                <td><?php echo $sub['subscriptionEnd'] ? date('M d, Y', strtotime($sub['subscriptionEnd'])) : 'N/A'; ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php if ($paymentStatus == 'Pending'): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/actions/admin/approve_subscription.php" style="display: inline;" id="approveForm_<?php echo $sub['subscriptionID']; ?>">
                                                <input type="hidden" name="subscriptionID" value="<?php echo $sub['subscriptionID']; ?>">
                                                <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;" onclick="return confirm('Approve subscription #<?php echo $sub['subscriptionID']; ?>? This will change status from Pending to Success and make the aggregator/recycling company visible to other users.')">
                                                    ✅ Approve (Change to Success)
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($isActive && !$isExpired): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/actions/admin/cancel_subscription.php" style="display: inline;">
                                                <input type="hidden" name="subscriptionID" value="<?php echo $sub['subscriptionID']; ?>">
                                                <button type="submit" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.85rem; background: var(--error); color: white;" onclick="return confirm('Cancel subscription #<?php echo $sub['subscriptionID']; ?>?')">
                                                    ⛔ Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- View button removed - details shown in table -->
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 2rem; color: var(--gray);">
                                No subscriptions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>
</body>
</html>

