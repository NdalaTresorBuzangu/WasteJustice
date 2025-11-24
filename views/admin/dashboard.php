<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/controllers/role_controller.php';
require_once dirname(dirname(dirname(__FILE__))) . '/controllers/admin_controller.php';

// Check if logged in as admin
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Admin') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userName = $_SESSION['userName'];

// Get admin controller
$adminController = new AdminController($conn);

// Get analytics
$analytics = $adminController->getAnalytics();

// Get all users with subscription status
$allUsers = $adminController->getAllUsers();
$usersWithSubscriptions = [];
$usersResult = $conn->query("
    SELECT u.userID, 
           CASE 
               WHEN EXISTS (
                   SELECT 1 FROM Subscriptions s 
                   WHERE s.userID = u.userID 
                   AND s.paymentStatus = 'Success' 
                   AND s.isActive = TRUE 
                   AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
               ) THEN 'Active'
               WHEN EXISTS (
                   SELECT 1 FROM Subscriptions s 
                   WHERE s.userID = u.userID 
                   AND s.paymentStatus = 'Success'
               ) THEN 'Expired'
               ELSE 'Not Subscribed'
           END as subscriptionStatus
    FROM User u
    WHERE u.userRole != 'Admin'
");
while ($row = $usersResult->fetch_assoc()) {
    $usersWithSubscriptions[$row['userID']] = $row['subscriptionStatus'];
}

// Get all subscriptions
$subscriptions = $conn->query("
    SELECT s.*, u.userName, u.userRole 
    FROM Subscriptions s
    JOIN User u ON s.userID = u.userID
    ORDER BY s.createdAt DESC
    LIMIT 20
");

// Get pricing overview
$pricingOverview = $adminController->getPricingOverview();

// Get all transactions
$allTransactions = $adminController->getAllTransactions();

// Get all batches
$allBatches = $adminController->getAllBatches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Admin Control Panel</p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/subscriptions.php">Subscriptions</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/admin/setup_default_pricing.php" onclick="return confirm('This will add default pricing for all aggregators without pricing. Continue?')">Setup Pricing</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['success'] == 'approved'): ?>
                    ✓ User #<?php echo htmlspecialchars($_GET['userID'] ?? ''); ?> approved successfully!
                <?php elseif ($_GET['success'] == 'suspended'): ?>
                    ✓ User #<?php echo htmlspecialchars($_GET['userID'] ?? ''); ?> suspended successfully!
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="color: var(--primary-green);">Welcome, <?php echo htmlspecialchars($userName); ?>! 🔐</h2>
            <p>Complete system oversight and management</p>
        </div>

        <!-- Analytics Grid -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Users</h3>
                <div class="value"><?php echo array_sum($analytics['usersByRole']); ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Waste Collectors</h3>
                <div class="value"><?php echo $analytics['usersByRole']['Waste Collector'] ?? 0; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Aggregators</h3>
                <div class="value"><?php echo $analytics['usersByRole']['Aggregator'] ?? 0; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Recycling Companies</h3>
                <div class="value"><?php echo $analytics['usersByRole']['Recycling Company'] ?? 0; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Total Collections</h3>
                <div class="value"><?php echo $analytics['totalCollections']; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Total Weight (kg)</h3>
                <div class="value"><?php echo number_format($analytics['totalWeight'], 2); ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Total Payments (Net)</h3>
                <div class="value">GH₵<?php echo number_format($analytics['totalPayments'], 2); ?></div>
                <p style="font-size: 0.9rem; color: var(--gray);">Amount paid to users</p>
            </div>
            <div class="dashboard-card" style="border-left: 4px solid var(--primary-green);">
                <h3>Platform Fees Collected</h3>
                <div class="value" style="color: var(--primary-green);">GH₵<?php echo number_format($analytics['totalPlatformFees'] ?? 0, 2); ?></div>
                <p style="font-size: 0.9rem; color: var(--gray);">1% transaction fees</p>
            </div>
            <div class="dashboard-card">
                <h3>Gross Transaction Volume</h3>
                <div class="value">GH₵<?php echo number_format($analytics['totalGrossVolume'] ?? 0, 2); ?></div>
                <p style="font-size: 0.9rem; color: var(--gray);">Total before fees</p>
            </div>
            <div class="dashboard-card">
                <h3>Active Subscriptions</h3>
                <div class="value"><?php 
                    $activeSubs = $conn->query("SELECT COUNT(*) as count FROM Subscriptions WHERE paymentStatus = 'Success' AND isActive = TRUE")->fetch_assoc()['count'];
                    echo $activeSubs;
                ?></div>
            </div>
        </div>

        <!-- All Users with Subscription Status -->
        <div class="card">
            <div class="card-header">
                <h2>👥 All Users & Subscription Status</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Subscription Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allUsers->num_rows > 0): ?>
                        <?php while ($user = $allUsers->fetch_assoc()): 
                            $subStatus = $usersWithSubscriptions[$user['userID']] ?? 'Not Subscribed';
                            $subBadgeClass = 'badge-pending';
                            if ($subStatus == 'Active') $subBadgeClass = 'badge-completed';
                            if ($subStatus == 'Expired') $subBadgeClass = 'badge-cancelled';
                        ?>
                            <tr>
                                <td>#<?php echo $user['userID']; ?></td>
                                <td><?php echo htmlspecialchars($user['userName']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($user['userRole']); ?></span></td>
                                <td><?php echo htmlspecialchars($user['userEmail'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['status'] == 'active' ? 'badge-completed' : 'badge-cancelled'; ?>">
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                    <?php if ($user['status'] == 'pending'): ?>
                                        <form method="POST" action="<?php echo ACTIONS_URL; ?>/admin/approve_user.php" style="display: inline-block; margin-left: 0.5rem;">
                                            <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;" onclick="return confirm('Approve user #<?php echo $user['userID']; ?>?')">
                                                ✅ Approve
                                            </button>
                                        </form>
                                    <?php elseif ($user['status'] == 'active'): ?>
                                        <form method="POST" action="<?php echo ACTIONS_URL; ?>/admin/suspend_user.php" style="display: inline-block; margin-left: 0.5rem;">
                                            <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                            <button type="submit" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.85rem; background: var(--error); color: white;" onclick="return confirm('Suspend user #<?php echo $user['userID']; ?>?')">
                                                ⛔ Suspend
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $subBadgeClass; ?>">
                                        <?php echo htmlspecialchars($subStatus); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray);">
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Subscriptions -->
        <div class="card">
            <div class="card-header">
                <h2>💰 Recent Subscriptions</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subscriptions->num_rows > 0): ?>
                        <?php while ($sub = $subscriptions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['userName']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($sub['userRole']); ?></span></td>
                                <td><?php echo htmlspecialchars($sub['planName']); ?></td>
                                <td>GH₵<?php echo number_format($sub['amountPaid'], 2); ?></td>
                                <td>
                                    <?php
                                    $status = $sub['paymentStatus'];
                                    $badge_class = 'badge-pending';
                                    if ($status == 'Success') $badge_class = 'badge-completed';
                                    if ($status == 'Failed') $badge_class = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td><?php echo $sub['subscriptionStart'] ? date('M d, Y', strtotime($sub['subscriptionStart'])) : 'N/A'; ?></td>
                                <td><?php echo $sub['subscriptionEnd'] ? date('M d, Y', strtotime($sub['subscriptionEnd'])) : 'N/A'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray);">
                                No subscriptions yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pricing Overview -->
        <div class="card">
            <div class="card-header">
                <h2>💲 Pricing Overview</h2>
            </div>
            <h3>Aggregator Prices</h3>
            <table>
                <thead>
                    <tr>
                        <th>Plastic Type</th>
                        <th>Average Price/kg</th>
                        <th>Min Price/kg</th>
                        <th>Max Price/kg</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pricingOverview['aggregatorPrices'] as $price): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($price['typeName']); ?></td>
                            <td>GH₵<?php echo number_format($price['avgPrice'], 2); ?></td>
                            <td>GH₵<?php echo number_format($price['minPrice'], 2); ?></td>
                            <td>GH₵<?php echo number_format($price['maxPrice'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h3 style="margin-top: 2rem;">Company Prices</h3>
            <table>
                <thead>
                    <tr>
                        <th>Plastic Type</th>
                        <th>Average Price/kg</th>
                        <th>Min Price/kg</th>
                        <th>Max Price/kg</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pricingOverview['companyPrices'] as $price): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($price['typeName']); ?></td>
                            <td>GH₵<?php echo number_format($price['avgPrice'], 2); ?></td>
                            <td>GH₵<?php echo number_format($price['minPrice'], 2); ?></td>
                            <td>GH₵<?php echo number_format($price['maxPrice'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h2>📊 Recent Collections</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Collection ID</th>
                        <th>Collector</th>
                        <th>Plastic Type</th>
                        <th>Weight (kg)</th>
                        <th>Aggregator</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allTransactions->num_rows > 0): ?>
                        <?php $count = 0; while (($trans = $allTransactions->fetch_assoc()) && $count < 10): ?>
                            <tr>
                                <td>#<?php echo $trans['collectionID']; ?></td>
                                <td><?php echo htmlspecialchars($trans['collectorName']); ?></td>
                                <td><?php echo htmlspecialchars($trans['typeName']); ?></td>
                                <td><?php echo number_format($trans['weight'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($trans['aggregatorName'] ?? 'Not assigned'); ?></td>
                                <td><span class="badge badge-pending"><?php echo htmlspecialchars($trans['statusName']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($trans['collectionDate'])); ?></td>
                            </tr>
                            <?php $count++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray);">
                                No transactions yet.
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

