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

// Must be recycling company
if ($userRole != 'Recycling Company') {
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

// Get company info
$companyInfo = $conn->prepare("SELECT * FROM CompanyRegistration WHERE userID = ?");
$companyInfo->bind_param("i", $userID);
$companyInfo->execute();
$company = $companyInfo->get_result()->fetch_assoc();

// Get subscription info
$subscription = $roleController->subscriptionController->hasActiveSubscription($userID);
$expiryNotice = $roleController->getExpiryNotice($userID);

// Get recycling class
require_once dirname(dirname(dirname(__FILE__))) . '/classes/recycling_class.php';
$recyclingClass = new RecyclingClass($conn);

// Get available batches
$plasticTypeFilter = isset($_GET['plasticTypeID']) ? intval($_GET['plasticTypeID']) : null;
$availableBatches = $recyclingClass->getAvailableBatches($plasticTypeFilter);

// Get purchase history
$purchaseHistory = $recyclingClass->getPurchaseHistory($userID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Company Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Recycling Company Dashboard <?php echo $subscription ? '✨ ' . $subscription['planName'] : ''; ?></p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/recycling/dashboard.php">Dashboard</a></li>
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
                    <?php echo isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : 'Your subscription is pending admin approval. You will become visible to aggregators once the admin verifies your payment and approves your subscription.'; ?>
                </p>
                <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.9rem;">
                    <strong>Note:</strong> This is standard practice to verify payments and prevent fraud. You will be notified once your subscription is approved.
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['subscription']) && $_GET['subscription'] == 'active'): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                ✓ Subscription activated successfully! You are now visible to aggregators.
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

        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">Welcome, <?php echo htmlspecialchars($company['companyName'] ?? $userName); ?>! 🏭</h2>
            </div>
            <p style="margin-top: 1rem;">Browse available plastic waste batches and purchase at transparent prices.</p>
            <?php if ($subscription): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                    <p style="margin: 0;">
                        <strong>✨ Plan:</strong> <?php echo htmlspecialchars($subscription['planName']); ?> | 
                        <strong>Expires:</strong> <?php echo date('M d, Y', strtotime($subscription['subscriptionEnd'])); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Batches -->
        <div class="card">
            <div class="card-header">
                <h2>📦 Available Plastic Waste Batches</h2>
            </div>
            <p>View all available batches with transparent pricing. Verify quality and purchase directly from aggregators.</p>
            
            <?php if ($availableBatches->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Aggregator</th>
                            <th>Plastic Type</th>
                            <th>Weight (kg)</th>
                            <th>Price/kg</th>
                            <th>Total Price</th>
                            <th>Created</th>
                            <th>Aggregator Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($batch = $availableBatches->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $batch['batchID']; ?></td>
                                <td><?php echo htmlspecialchars($batch['aggregatorName']); ?></td>
                                <td><strong><?php echo htmlspecialchars($batch['plasticType']); ?></strong></td>
                                <td><?php echo number_format($batch['totalWeight'], 2); ?> kg</td>
                                <td class="price-cell">GH₵<?php echo number_format($batch['companyPrice'], 2); ?>/kg</td>
                                <td class="price-cell"><strong>GH₵<?php echo number_format($batch['totalWeight'] * $batch['companyPrice'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($batch['createdAt'])); ?></td>
                                <td><?php echo number_format($batch['aggregatorRating'], 1); ?>/5.0 ⭐</td>
                                <td>
                                    <button onclick="verifyAndPurchase(<?php echo $batch['batchID']; ?>)" class="btn btn-primary">
                                        Verify & Purchase
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem; color: var(--gray);">
                    No available batches at this time. Check back later!
                </p>
            <?php endif; ?>
        </div>

        <!-- Purchase History -->
        <div class="card">
            <div class="card-header">
                <h2>📊 Purchase History</h2>
            </div>
            
            <?php if ($purchaseHistory->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Plastic Type</th>
                            <th>Weight (kg)</th>
                            <th>Aggregator</th>
                            <th>Sale Price</th>
                            <th>Purchase Date</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($purchase = $purchaseHistory->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $purchase['batchID']; ?></td>
                                <td><?php echo htmlspecialchars($purchase['typeName']); ?></td>
                                <td><?php echo number_format($purchase['totalWeight'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($purchase['aggregatorName']); ?></td>
                                <td class="price-cell">GH₵<?php echo number_format($purchase['salePrice'], 2); ?></td>
                                <td><?php echo $purchase['soldAt'] ? date('M d, Y', strtotime($purchase['soldAt'])) : 'N/A'; ?></td>
                                <td>
                                    <?php
                                    $status = $purchase['paymentStatus'];
                                    $badge_class = 'badge-pending';
                                    if ($status == 'completed') $badge_class = 'badge-completed';
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
                    No purchases yet. Browse available batches above to make your first purchase!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/recycling.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/subscription.js"></script>
</body>
</html>

