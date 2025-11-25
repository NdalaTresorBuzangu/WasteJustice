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

// Must be aggregator
if ($userRole != 'Aggregator') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

// Check subscription status (but don't block access - show upgrade banner instead)
$roleController = new RoleController($conn);
$accessCheck = $roleController->subscriptionController->validateAccess($userID, $userRole);
// Note: We allow access to dashboard even without subscription to show upgrade options

// Get user info
$userInfo = $conn->prepare("SELECT * FROM User WHERE userID = ?");
$userInfo->bind_param("i", $userID);
$userInfo->execute();
$user = $userInfo->get_result()->fetch_assoc();
$userName = $user['userName'];

// Get subscription info
$subscription = $roleController->subscriptionController->hasActiveSubscription($userID);
$expiryNotice = $roleController->getExpiryNotice($userID);

// Get aggregator class
require_once dirname(dirname(dirname(__FILE__))) . '/classes/aggregator_class.php';
$aggregatorClass = new AggregatorClass($conn);

// Debug: Check what's in the database
$debugAll = $conn->query("
    SELECT collectionID, collectorID, aggregatorID, statusID, weight 
    FROM WasteCollection 
    ORDER BY collectionID DESC 
    LIMIT 10
");

// Debug: Check what's in the database
$debugAll = $conn->query("
    SELECT collectionID, collectorID, aggregatorID, statusID, weight 
    FROM WasteCollection 
    ORDER BY collectionID DESC 
    LIMIT 10
");

// Get pending deliveries
$pendingDeliveries = $aggregatorClass->getPendingDeliveries($userID);

// Debug: Check total collections assigned to this aggregator
$debugQuery = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM WasteCollection 
    WHERE aggregatorID = ? AND statusID = 1
");
$debugQuery->bind_param("i", $userID);
$debugQuery->execute();
$debugResult = $debugQuery->get_result()->fetch_assoc();
$totalPendingForAggregator = $debugResult['total'] ?? 0;

// Debug: Check collections without aggregator
$debugQuery2 = $conn->query("
    SELECT COUNT(*) as total 
    FROM WasteCollection 
    WHERE aggregatorID IS NULL AND statusID = 1
");
$debugResult2 = $debugQuery2->fetch_assoc();
$totalUnassigned = $debugResult2['total'] ?? 0;

// Debug: Check collections assigned to this aggregator
$debugAssigned = $conn->prepare("
    SELECT collectionID, collectorID, aggregatorID, statusID 
    FROM WasteCollection 
    WHERE aggregatorID = ? AND statusID = 1
");
$debugAssigned->bind_param("i", $userID);
$debugAssigned->execute();
$debugAssignedResult = $debugAssigned->get_result();

// Debug: Check collections assigned to this aggregator
$debugAssigned = $conn->prepare("
    SELECT collectionID, collectorID, aggregatorID, statusID 
    FROM WasteCollection 
    WHERE aggregatorID = ? AND statusID = 1
");
$debugAssigned->bind_param("i", $userID);
$debugAssigned->execute();
$debugAssignedResult = $debugAssigned->get_result();

// Get accepted waste
$acceptedWaste = $aggregatorClass->getAcceptedWaste($userID);

// Get earnings
$earnings = $aggregatorClass->getEarnings($userID);

// Get feedback
$feedback = $aggregatorClass->getFeedback($userID);

// Get sold batches
$soldBatches = $aggregatorClass->getSoldBatches($userID);

// Get pending payments (need to be processed via Paystack)
$pendingPayments = $aggregatorClass->getPendingPayments($userID);

// Get payment history
$paymentHistory = $aggregatorClass->getPaymentHistory($userID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggregator Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Aggregator Dashboard <?php echo $subscription ? '✨ ' . $subscription['planName'] : ''; ?></p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/aggregator/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/subscription.php">Subscription</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['subscription']) && $_GET['subscription'] == 'pending'): ?>
            <div class="alert alert-warning" style="margin-bottom: 2rem; padding: 1.5rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 0.5rem;">
                <h3 style="margin: 0 0 0.5rem 0; color: #856404;">⏳ Subscription Pending Approval</h3>
                <p style="margin: 0; color: #856404; line-height: 1.6;">
                    <?php echo isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : 'Your subscription is pending admin approval. You will become visible to waste collectors once the admin verifies your payment and approves your subscription.'; ?>
                </p>
                <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.9rem;">
                    <strong>Note:</strong> This is standard practice to verify payments and prevent fraud. You will be notified once your subscription is approved.
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['subscription']) && $_GET['subscription'] == 'active'): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                ✓ Subscription activated successfully! You are now visible to waste collectors.
            </div>
        <?php endif; ?>
        
        <?php if (!$subscription): ?>
            <div style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.3rem;">🚀 Upgrade to Premium</h3>
                    <p style="margin: 0; opacity: 0.95; font-size: 0.95rem;">Unlock advanced features, priority support, and unlimited transactions. Start with a 7-day free trial!</p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="<?php echo VIEWS_URL; ?>/subscription.php" class="btn" style="background: white; color: var(--primary-green); font-weight: bold; padding: 0.75rem 2rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        Switch to Paid Version →
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($expiryNotice): ?>
            <div class="alert alert-<?php echo $expiryNotice['type'] == 'expired' ? 'error' : 'warning'; ?>">
                <strong>Subscription Notice:</strong> <?php echo htmlspecialchars($expiryNotice['message']); ?>
                <?php if ($expiryNotice['type'] == 'expired'): ?>
                    <a href="<?php echo VIEWS_URL; ?>/subscription.php" class="btn btn-primary" style="margin-left: 1rem;">Renew Now</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['subscription'])): ?>
            <div class="alert alert-success">
                ✓ Subscription activated! Welcome to premium features.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] == 'accepted'): ?>
                <div class="alert alert-success">
                    ✓ Delivery accepted! Payment of GH₵<?php echo number_format($_GET['amount'] ?? 0, 2); ?> will be processed.
                </div>
            <?php elseif ($_GET['success'] == 'rejected'): ?>
                <div class="alert alert-warning">
                    Delivery rejected successfully.
                </div>
            <?php elseif ($_GET['success'] == 'batch_created'): ?>
                <div class="alert alert-success">
                    ✓ Batch created successfully! You can now sell it to recycling companies.
                </div>
            <?php elseif ($_GET['success'] == 'sold'): ?>
                <div class="alert alert-success">
                    ✓ Batch sold successfully! Sale price: GH₵<?php echo number_format($_GET['salePrice'] ?? 0, 2); ?>
                </div>
            <?php elseif ($_GET['success'] == 'payment_processed'): ?>
                <div class="alert alert-success">
                    ✓ Payment processed successfully!
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
                <h2 style="color: var(--primary-green); margin: 0;">Welcome, <?php echo htmlspecialchars($userName); ?>! 📦</h2>
            </div>
            <p style="margin-top: 1rem;">Manage plastic waste deliveries, create batches, and sell to recycling companies.</p>
            <?php if ($subscription): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                    <p style="margin: 0;">
                        <strong>✨ Plan:</strong> <?php echo htmlspecialchars($subscription['planName']); ?> | 
                        <strong>Expires:</strong> <?php echo date('M d, Y', strtotime($subscription['subscriptionEnd'])); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>📦 Pending Deliveries</h3>
                <div class="value"><?php echo $pendingDeliveries->num_rows; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Awaiting your review</p>
            </div>
            <div class="dashboard-card" style="border-left: 4px solid var(--primary-green);">
                <h3>💰 Total Earnings</h3>
                <div class="value" style="color: var(--primary-green);">GH₵<?php echo number_format($earnings['totalEarnings'] ?? 0, 2); ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Net amount received</p>
            </div>
            <div class="dashboard-card">
                <h3>📊 Batches Created</h3>
                <div class="value"><?php echo $earnings['batchesSold'] ?? 0; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Ready for sale</p>
            </div>
            <div class="dashboard-card">
                <h3>✅ Collections Accepted</h3>
                <div class="value"><?php echo $earnings['collectionsAccepted'] ?? 0; ?></div>
                <p style="font-size: 0.9rem; color: var(--gray); margin-top: 0.5rem;">Total accepted</p>
            </div>
        </div>

        <!-- Debug Information (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <div class="card-header">
                <h3>🔍 Debug Information</h3>
            </div>
            <p><strong>Your Aggregator ID:</strong> <?php echo $userID; ?></p>
            <p><strong>Collections assigned to you (statusID=1):</strong> <?php echo $debugAssignedResult->num_rows; ?></p>
            <p><strong>Pending deliveries from query:</strong> <?php echo $pendingDeliveries->num_rows; ?></p>
            <p><strong>Unassigned collections:</strong> <?php echo $totalUnassigned; ?></p>
            <h4>Recent Collections in Database:</h4>
            <table style="font-size: 0.85rem;">
                <tr><th>ID</th><th>Collector</th><th>Aggregator ID</th><th>Status</th><th>Weight</th></tr>
                <?php 
                $debugAll->data_seek(0);
                while ($row = $debugAll->fetch_assoc()): 
                ?>
                    <tr>
                        <td>#<?php echo $row['collectionID']; ?></td>
                        <td><?php echo $row['collectorID']; ?></td>
                        <td><?php echo $row['aggregatorID'] ?? 'NULL'; ?></td>
                        <td><?php echo $row['statusID']; ?></td>
                        <td><?php echo $row['weight']; ?> kg</td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <h4>Collections Assigned to You:</h4>
            <table style="font-size: 0.85rem;">
                <tr><th>ID</th><th>Collector</th><th>Status</th></tr>
                <?php 
                $debugAssignedResult->data_seek(0);
                while ($row = $debugAssignedResult->fetch_assoc()): 
                ?>
                    <tr>
                        <td>#<?php echo $row['collectionID']; ?></td>
                        <td><?php echo $row['collectorID']; ?></td>
                        <td><?php echo $row['statusID']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>

        <!-- Pending Deliveries -->
        <div class="card">
            <div class="card-header">
                <h2>📦 Pending Plastic Waste Deliveries</h2>
                <a href="?debug=1" style="float: right; font-size: 0.85rem; color: var(--gray);">🔍 Debug</a>
            </div>
            
            <?php if ($totalPendingForAggregator > 0 || $totalUnassigned > 0): ?>
                <?php if ($totalPendingForAggregator == 0 && $totalUnassigned > 0): ?>
                    <div class="alert alert-info" style="margin-bottom: 1rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 0.5rem;">
                        <p style="margin: 0; color: #1565c0;">
                            <strong>ℹ️ Info:</strong> There are <?php echo $totalUnassigned; ?> collection(s) waiting to be assigned to aggregators. 
                            Waste collectors need to select an aggregator when submitting waste, or you can contact them to assign their collections to you.
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($pendingDeliveries->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Collection ID</th>
                            <th>Collector</th>
                            <th>Plastic Type</th>
                            <th>Weight (kg)</th>
                            <th>Location</th>
                            <th>Suggested Price</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($delivery = $pendingDeliveries->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $delivery['collectionID']; ?></td>
                                <td><?php echo htmlspecialchars($delivery['collectorName']); ?><br><small><?php echo htmlspecialchars($delivery['collectorContact']); ?></small></td>
                                <td><?php echo htmlspecialchars($delivery['typeName']); ?></td>
                                <td><?php echo number_format($delivery['weight'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($delivery['location']); ?></td>
                                <td>
                                    <?php if ($delivery['suggestedPrice'] > 0): ?>
                                        GH₵<?php echo number_format($delivery['suggestedPrice'], 2); ?>/kg
                                    <?php else: ?>
                                        <span style="color: var(--orange);">Price not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="price-cell">
                                    <?php if ($delivery['suggestedPrice'] > 0): ?>
                                        GH₵<?php echo number_format($delivery['weight'] * $delivery['suggestedPrice'], 2); ?>
                                    <?php else: ?>
                                        <span style="color: var(--orange);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($delivery['collectionDate'] ?? $delivery['createdAt'] ?? 'now')); ?></td>
                                <td>
                                    <button onclick="acceptDelivery(<?php echo $delivery['collectionID']; ?>)" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                        ✓ Accept
                                    </button>
                                    <button onclick="rejectDelivery(<?php echo $delivery['collectionID']; ?>)" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        ✗ Reject
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No pending deliveries at this time.
                </p>
            <?php endif; ?>
        </div>

        <!-- Pending Payments - Process via Paystack -->
        <?php if ($pendingPayments->num_rows > 0): ?>
        <div class="card" style="border-left: 4px solid var(--orange);">
            <div class="card-header">
                <h2>💳 Pending Payments - Process via Paystack</h2>
            </div>
            <p style="margin-bottom: 1rem; color: var(--orange); font-weight: 600;">
                ⚠️ You have <?php echo $pendingPayments->num_rows; ?> pending payment(s) that need to be processed. Click "Pay via Paystack" to complete payment to collectors.
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Collection ID</th>
                        <th>Collector</th>
                        <th>Plastic Type</th>
                        <th>Weight (kg)</th>
                        <th>Amount to Pay</th>
                        <th>Platform Fee</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $pendingPayments->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $payment['collectionID']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($payment['collectorName']); ?><br>
                                <small><?php echo htmlspecialchars($payment['collectorEmail']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($payment['typeName']); ?></td>
                            <td><?php echo number_format($payment['weight'], 2); ?> kg</td>
                            <td class="price-cell"><strong>GH₵<?php echo number_format($payment['amount'], 2); ?></strong></td>
                            <td style="color: var(--primary-green);">GH₵<?php echo number_format($payment['platformFee'] ?? 0, 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($payment['createdAt'])); ?></td>
                            <td>
                                <button onclick="processPayment(<?php echo $payment['collectionID']; ?>, <?php echo $payment['amount']; ?>, '<?php echo htmlspecialchars($payment['collectorEmail'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($payment['collectorName'], ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    💳 Pay via Paystack
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Accepted Waste - Create Batches -->
        <div class="card">
            <div class="card-header">
                <h2>📦 Accepted Waste - Create Batches</h2>
            </div>
            
            <?php if ($acceptedWaste->num_rows > 0): ?>
                <p>Group your accepted waste by plastic type to create batches for sale to recycling companies.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Plastic Type</th>
                            <th>Total Weight (kg)</th>
                            <th>Collections</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($waste = $acceptedWaste->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($waste['typeName']); ?></strong></td>
                                <td><?php echo number_format($waste['totalWeight'], 2); ?> kg</td>
                                <td><?php echo count(explode(',', $waste['collectionIDs'])); ?> collections</td>
                                <td>
                                    <button onclick="createBatch(<?php echo $waste['plasticTypeID']; ?>, '<?php echo $waste['collectionIDs']; ?>')" class="btn btn-primary">
                                        Create Batch for Sale
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No accepted waste yet. Accept deliveries from collectors to create batches.
                </p>
            <?php endif; ?>
        </div>

        <!-- Sold Batches -->
        <div class="card">
            <div class="card-header">
                <h2>💰 Sold Batches</h2>
            </div>
            
            <?php if ($soldBatches->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Plastic Type</th>
                            <th>Weight (kg)</th>
                            <th>Company</th>
                            <th>Gross Amount</th>
                            <th>Platform Fee</th>
                            <th>Net Amount</th>
                            <th>Sale Date</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($batch = $soldBatches->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $batch['batchID']; ?></td>
                                <td><?php echo htmlspecialchars($batch['typeName']); ?></td>
                                <td><?php echo number_format($batch['totalWeight'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($batch['companyName'] ?? 'N/A'); ?></td>
                                <td>GH₵<?php echo number_format($batch['grossAmount'] ?? $batch['salePrice'], 2); ?></td>
                                <td style="color: var(--primary-green);">GH₵<?php echo number_format($batch['platformFee'] ?? 0, 2); ?></td>
                                <td class="price-cell"><strong>GH₵<?php echo number_format($batch['netAmount'] ?? $batch['salePrice'], 2); ?></strong></td>
                                <td><?php echo $batch['soldAt'] ? date('M d, Y', strtotime($batch['soldAt'])) : 'N/A'; ?></td>
                                <td>
                                    <?php
                                    $status = $batch['paymentStatus'] ?? 'pending';
                                    $badge_class = 'badge-pending';
                                    if ($status == 'completed') $badge_class = 'badge-completed';
                                    if ($status == 'failed') $badge_class = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No batches sold yet. Create batches from accepted waste to start selling.
                </p>
            <?php endif; ?>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h2>💵 Payment History</h2>
            </div>
            
            <?php if ($paymentHistory->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>Gross Amount</th>
                            <th>Platform Fee</th>
                            <th>Net Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $paymentHistory->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $payment['paymentID']; ?></td>
                                <td>
                                    <?php if ($payment['collectionID']): ?>
                                        Collection #<?php echo $payment['collectionID']; ?>
                                    <?php elseif ($payment['batchID']): ?>
                                        Batch #<?php echo $payment['batchID']; ?>
                                    <?php else: ?>
                                        Payment
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($payment['fromUserName'] ?? 'N/A'); ?></td>
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
                    No payment history yet. Payments will appear here once transactions are completed.
                </p>
            <?php endif; ?>
        </div>

        <!-- Feedback -->
        <div class="card">
            <div class="card-header">
                <h2>⭐ Feedback & Ratings</h2>
            </div>
            <div style="padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem; margin-bottom: 1rem;">
                <p style="margin: 0; font-size: 1.1rem;">
                    <strong>Your Rating:</strong> 
                    <span style="color: var(--primary-green); font-size: 1.3rem; font-weight: bold;">
                        <?php echo number_format($user['rating'], 1); ?>/5.0
                    </span>
                    <span style="color: var(--gray);">(<?php echo $user['totalRatings']; ?> reviews)</span>
                </p>
            </div>
            <?php if ($feedback->num_rows > 0): ?>
                <p style="margin-bottom: 1rem;"><strong>Recent Feedback:</strong></p>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php $count = 0; while (($fb = $feedback->fetch_assoc()) && $count < 5): ?>
                        <div style="padding: 1rem; margin-bottom: 1rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                            <p style="margin: 0 0 0.5rem 0;">
                                <strong><?php echo htmlspecialchars($fb['fromUserName']); ?></strong>
                                <span style="color: var(--orange);"><?php echo str_repeat('⭐', $fb['rating']); ?></span>
                            </p>
                            <?php if ($fb['comment']): ?>
                                <p style="margin: 0; color: var(--gray);"><?php echo htmlspecialchars($fb['comment']); ?></p>
                            <?php endif; ?>
                            <small style="color: var(--gray);"><?php echo date('M d, Y', strtotime($fb['createdAt'])); ?></small>
                        </div>
                        <?php $count++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--gray);">No feedback received yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/aggregator.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/subscription.js"></script>
</body>
</html>

