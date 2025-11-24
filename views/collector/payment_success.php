<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$reference = isset($_GET['reference']) ? htmlspecialchars($_GET['reference']) : '';

// Verify payment with backend
$paymentVerified = false;
$paymentData = null;

if ($reference) {
    // Call verification API
    $verifyUrl = BASE_URL . '/actions/verify_payment.php?reference=' . urlencode($reference);
    $verifyResponse = @file_get_contents($verifyUrl);
    
    if ($verifyResponse) {
        $verifyResult = json_decode($verifyResponse, true);
        if ($verifyResult && $verifyResult['status'] === 'success') {
            $paymentVerified = true;
            $paymentData = $verifyResult;
        }
    }
}

// Get payment details from database
$paymentDetails = null;
if ($reference) {
    $stmt = $conn->prepare("
        SELECT * FROM PaystackPayments 
        WHERE reference = ? AND user_id = ?
    ");
    $stmt->bind_param("si", $reference, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $paymentDetails = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .success-card {
            background: white;
            border-radius: 1rem;
            padding: 3rem 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .payment-details {
            background: var(--very-light-green);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin: 2rem 0;
            text-align: left;
        }
        .payment-details h3 {
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        .payment-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--light-gray);
        }
        .payment-details .detail-row:last-child {
            border-bottom: none;
        }
        .payment-details .label {
            color: var(--gray);
            font-weight: 600;
        }
        .payment-details .value {
            color: var(--dark);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Payment Confirmation</p>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="success-container">
            <?php if ($paymentVerified || ($paymentDetails && $paymentDetails['status'] === 'success')): ?>
                <div class="success-card">
                    <div class="success-icon">✅</div>
                    <h2 style="color: var(--primary-green); margin-bottom: 1rem;">Payment Successful!</h2>
                    <p style="font-size: 1.1rem; color: var(--gray-dark); margin-bottom: 2rem;">
                        Your payment has been processed successfully. Thank you for your payment!
                    </p>

                    <?php if ($paymentDetails): ?>
                        <div class="payment-details">
                            <h3>📋 Payment Details</h3>
                            <div class="detail-row">
                                <span class="label">Payment ID:</span>
                                <span class="value">#<?php echo $paymentDetails['payment_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Reference:</span>
                                <span class="value"><?php echo htmlspecialchars($paymentDetails['reference']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Amount Paid:</span>
                                <span class="value" style="color: var(--primary-green); font-size: 1.2rem;">
                                    GH₵<?php echo number_format($paymentDetails['amount'], 2); ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Payment Method:</span>
                                <span class="value"><?php echo ucfirst($paymentDetails['payment_method'] ?? 'Card'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Status:</span>
                                <span class="value" style="color: var(--primary-green);">
                                    ✅ <?php echo ucfirst($paymentDetails['status']); ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo date('F d, Y h:i A', strtotime($paymentDetails['date'])); ?></span>
                            </div>
                            <?php if ($paymentDetails['description']): ?>
                                <div class="detail-row">
                                    <span class="label">Description:</span>
                                    <span class="value"><?php echo htmlspecialchars($paymentDetails['description']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 2rem;">
                        <a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                            🏠 Go to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="alert alert-warning">
                        <h2 style="color: var(--orange); margin-bottom: 1rem;">⏳ Payment Verification</h2>
                        <p>We are verifying your payment. Please wait a moment...</p>
                        <?php if ($reference): ?>
                            <p><strong>Reference:</strong> <?php echo htmlspecialchars($reference); ?></p>
                        <?php endif; ?>
                    </div>
                    <p style="margin-top: 1rem;">
                        <a href="<?php echo VIEWS_URL; ?>/collector/dashboard.php" class="btn btn-secondary">
                            Go to Dashboard
                        </a>
                    </p>
                </div>
                
                <script>
                    // Auto-refresh after 3 seconds to check payment status
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                </script>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>
</body>
</html>

