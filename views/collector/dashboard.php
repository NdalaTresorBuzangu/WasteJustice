<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/controllers/role_controller.php';

// Check if logged in
if (!isset($_SESSION['userID'])) {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userRole = $_SESSION['userRole'];
$userID = $_SESSION['userID'];

// Free access for collectors only
if ($userRole != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

// Verify free access (collectors always have free access)
$roleController = new RoleController($conn);
$accessCheck = $roleController->canAccess($userID, $userRole, 'upload_waste');

if (!$accessCheck['access']) {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userName = $_SESSION['userName'];
$userID = $_SESSION['userID'];

// Get collector's waste collections
require_once dirname(dirname(dirname(__FILE__))) . '/classes/collector_class.php';
$collectorClass = new CollectorClass($conn);
$collections = $collectorClass->listWasteCollections($userID);

// Get statistics
$total_collections = $collections->num_rows;
$pending = $conn->query("SELECT COUNT(*) as count FROM WasteCollection WHERE collectorID = $userID AND statusID = 1")->fetch_assoc()['count'];
$accepted = $conn->query("SELECT COUNT(*) as count FROM WasteCollection WHERE collectorID = $userID AND statusID = 2")->fetch_assoc()['count'];
$delivered = $conn->query("SELECT COUNT(*) as count FROM WasteCollection WHERE collectorID = $userID AND statusID = 4")->fetch_assoc()['count'];

// Get payments
$payments = $collectorClass->getPayments($userID);
$totalEarnings = 0;
$paymentHistory = [];
while ($payment = $payments->fetch_assoc()) {
    if ($payment['status'] == 'completed') {
        $totalEarnings += $payment['amount'];
    }
    $paymentHistory[] = $payment;
}
// Get payment history result again for display
$paymentHistoryResult = $collectorClass->getPayments($userID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Waste Collector Dashboard</p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/view_aggregators.php">View Aggregators</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php">Submit Waste</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['payment']) && $_GET['payment'] == 'cancelled'): ?>
            <div class="alert alert-warning">
                ⚠️ Payment was cancelled. You can try again anytime.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] == 'uploaded'): ?>
                <div class="alert alert-success">
                    ✓ Waste submitted successfully! An aggregator will review and accept your delivery.
                </div>
            <?php elseif ($_GET['success'] == 'assigned'): ?>
                <div class="alert alert-success">
                    ✓ Aggregator assigned successfully! Wait for delivery acceptance.
                </div>
            <?php elseif ($_GET['success'] == 'updated'): ?>
                <div class="alert alert-success">
                    ✓ Collection updated successfully!
                </div>
            <?php elseif ($_GET['success'] == 'removed'): ?>
                <div class="alert alert-success">
                    ✓ Collection removed successfully!
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">Welcome back, <?php echo htmlspecialchars($userName); ?>! 👋</h2>
            </div>
            <p style="margin-top: 1rem;">Track your waste collection transactions and monitor your impact.</p>
        </div>

        <div class="free-badge" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center;">
            <h3 style="margin: 0;">🆓 FREE VERSION</h3>
            <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Full access to all collector features at no cost!</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>📦 Total Collections</h3>
                <div class="value"><?php echo $total_collections; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">All time collections</p>
            </div>
            <div class="dashboard-card" style="border-left: 4px solid var(--primary-green);">
                <h3>💰 Total Earnings</h3>
                <div class="value" style="color: var(--primary-green);">GH₵<?php echo number_format($totalEarnings, 2); ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Net amount received</p>
            </div>
            <div class="dashboard-card">
                <h3>⏳ Pending</h3>
                <div class="value"><?php echo $pending; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Awaiting acceptance</p>
            </div>
            <div class="dashboard-card">
                <h3>✅ Delivered</h3>
                <div class="value"><?php echo $delivered; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Successfully delivered</p>
            </div>
        </div>

        <div style="margin: 2rem 0; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="<?php echo VIEWS_URL; ?>/collector/view_aggregators.php" class="btn btn-primary btn-large">
                🏭 View Aggregators & Prices
            </a>
            <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php" class="btn btn-primary btn-large">
                ♻️ Upload Plastic Waste
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>♻️ My Waste Collections</h2>
            </div>
            
            <?php if ($collections->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Collection ID</th>
                            <th>Plastic Type</th>
                            <th>Weight (kg)</th>
                            <th>Location</th>
                            <th>Aggregator</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $collections->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['collectionID']; ?></td>
                                <td><?php echo htmlspecialchars($row['typeName']); ?></td>
                                <td><?php echo number_format($row['weight'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td>
                                    <?php
                                    if ($row['aggregatorID']) {
                                        $aggStmt = $conn->prepare("SELECT ar.businessName FROM AggregatorRegistration ar JOIN User u ON ar.userID = u.userID WHERE u.userID = ?");
                                        $aggStmt->bind_param("i", $row['aggregatorID']);
                                        $aggStmt->execute();
                                        $aggName = $aggStmt->get_result()->fetch_assoc()['businessName'];
                                        echo htmlspecialchars($aggName);
                                    } else {
                                        echo '<a href="' . VIEWS_URL . '/collector/view_aggregators.php?selectCollection=' . $row['collectionID'] . '">Select Aggregator</a>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['statusName'];
                                    $badge_class = 'badge-pending';
                                    if ($status == 'Accepted') $badge_class = 'badge-processing';
                                    if ($status == 'Delivered') $badge_class = 'badge-completed';
                                    if ($status == 'Rejected') $badge_class = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['collectionDate'])); ?></td>
                                <td>
                                    <?php if ($row['statusID'] == 1): ?>
                                        <a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php?remove=<?php echo $row['collectionID']; ?>" onclick="return confirm('Remove this collection?')" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">Remove</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No collections yet. <a href="<?php echo VIEWS_URL; ?>/collector/submit_waste.php">Upload your first plastic waste!</a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h2>💵 Payment History</h2>
            </div>
            
            <?php if ($paymentHistoryResult->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Collection ID</th>
                            <th>Plastic Type</th>
                            <th>Gross Amount</th>
                            <th>Platform Fee</th>
                            <th>Net Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $paymentHistoryResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $payment['paymentID']; ?></td>
                                <td>#<?php echo $payment['collectionID'] ?? 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($payment['typeName'] ?? 'N/A'); ?></td>
                                <td>GH₵<?php echo number_format($payment['grossAmount'] ?? $payment['amount'], 2); ?></td>
                                <td style="color: var(--primary-green);">GH₵<?php echo number_format($payment['platformFee'] ?? 0, 2); ?></td>
                                <td class="price-cell"><strong>GH₵<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $status = $payment['status'];
                                    $badge_class = 'badge-pending';
                                    if ($status == 'completed') $badge_class = 'badge-completed';
                                    if ($status == 'failed') $badge_class = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($payment['createdAt'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No payment history yet. Payments will appear here once your deliveries are accepted and paid.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/collector.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/subscription.js"></script>
</body>
</html>
<?php
// Handle remove action
if (isset($_GET['remove'])) {
    require_once dirname(dirname(dirname(__FILE__))) . '/actions/remove_waste_action.php';
}
?>

