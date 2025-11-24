<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Check if logged in
if (!isset($_SESSION['userID'])) {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userRole = $_SESSION['userRole'];
$userID = $_SESSION['userID'];

// Collectors always get free access - redirect to dashboard
if ($userRole == 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/collector/dashboard.php");
    exit();
}

// Admin doesn't need subscription
if ($userRole == 'Admin') {
    header("Location: " . VIEWS_URL . "/admin/dashboard.php");
    exit();
}

// Check if user already has active subscription
$subscriptionCheck = $conn->prepare("
    SELECT * FROM Subscriptions 
    WHERE userID = ? AND paymentStatus = 'Success' AND isActive = 1 
    AND (subscriptionEnd IS NULL OR subscriptionEnd >= CURDATE())
    ORDER BY subscriptionID DESC LIMIT 1
");
$subscriptionCheck->bind_param("i", $userID);
$subscriptionCheck->execute();
$activeSubscription = $subscriptionCheck->get_result()->fetch_assoc();

// If has active subscription, redirect to dashboard
if ($activeSubscription) {
    if ($userRole == 'Aggregator') {
        header("Location: " . VIEWS_URL . "/aggregator/dashboard.php");
    } elseif ($userRole == 'Recycling Company') {
        header("Location: " . VIEWS_URL . "/recycling/dashboard.php");
    }
    exit();
}

// Get user's subscription history
$history = $conn->prepare("SELECT * FROM Subscriptions WHERE userID = ? ORDER BY createdAt DESC");
$history->bind_param("i", $userID);
$history->execute();
$subscriptionHistory = $history->get_result();

// Subscription plans
$plans = [
    'Basic' => [
        'name' => 'Basic',
        'price' => 50.00,
        'period' => 'Monthly',
        'features' => [
            'Access to aggregator/recycling dashboard',
            'View all waste collections',
            'Process transactions',
            'Basic analytics',
            'Email support'
        ]
    ],
    'Standard' => [
        'name' => 'Standard',
        'price' => 100.00,
        'period' => 'Monthly',
        'features' => [
            'Everything in Basic',
            'Advanced analytics',
            'Priority support',
            'Batch management',
            'Custom reports',
            'API access'
        ]
    ],
    'Premium' => [
        'name' => 'Premium',
        'price' => 200.00,
        'period' => 'Monthly',
        'features' => [
            'Everything in Standard',
            'Unlimited transactions',
            'Dedicated account manager',
            'White-label options',
            'Custom integrations',
            '24/7 support'
        ]
    ]
];

$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">✓ Subscription successful! Redirecting to dashboard...</div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="alert alert-error">✗ ' . htmlspecialchars(urldecode($_GET['error'])) . '</div>';
}

// Get user name for display
$userName = $_SESSION['userName'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
    <style>
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .plan-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 2px solid var(--light-gray);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-green);
        }
        
        .plan-card.featured {
            border-color: var(--primary-green);
            border-width: 3px;
        }
        
        .plan-card.featured::before {
            content: '⭐ RECOMMENDED';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-green);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .plan-header h3 {
            color: var(--primary-green);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark);
            margin: 1rem 0;
        }
        
        .plan-price span {
            font-size: 1rem;
            color: var(--gray);
            font-weight: normal;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            color: var(--gray-dark);
        }
        
        .plan-features li::before {
            content: '✓ ';
            color: var(--primary-green);
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .free-notice {
            background: linear-gradient(135deg, var(--very-light-green), var(--light-green));
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-green);
        }
        
        .subscription-history {
            margin-top: 3rem;
        }
        
        .payment-form {
            margin-top: 2rem;
        }
        
        .trial-badge {
            background: var(--orange);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p><?php echo htmlspecialchars($userRole); ?> Subscription Plans</p>
            </div>
            <nav>
                <ul>
                    <?php if ($userRole == 'Aggregator'): ?>
                        <li><a href="<?php echo VIEWS_URL; ?>/aggregator/dashboard.php">Dashboard</a></li>
                    <?php elseif ($userRole == 'Recycling Company'): ?>
                        <li><a href="<?php echo VIEWS_URL; ?>/recycling/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo VIEWS_URL; ?>/subscription.php">Subscription</a></li>
                    <li><a href="<?php echo ACTIONS_URL; ?>/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php echo $message; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green); margin: 0;">Choose Your Subscription Plan 💳</h2>
            </div>
            <p style="margin-top: 1rem;">Welcome, <?php echo htmlspecialchars($userName); ?>! Access premium features to grow your waste management business.</p>
        </div>

        <div class="free-notice" style="background: linear-gradient(135deg, var(--very-light-green), var(--light-green)); padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; border-left: 4px solid var(--primary-green);">
            <h3 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">🆓 Free Version Available</h3>
            <p style="margin: 0;"><strong>Waste Collectors</strong> get full free access! Upload waste, select aggregators, view transparent pricing, and receive payments - all at no cost.</p>
        </div>

        <div class="trial-badge" style="background: linear-gradient(135deg, var(--orange), #ff8c42); color: white; padding: 1rem; border-radius: 0.5rem; display: block; margin-bottom: 2rem; text-align: center; font-weight: 600; box-shadow: var(--shadow);">
            ✨ 7-Day Free Trial Available - Test premium features risk-free!
        </div>

        <div class="plans-container">
            <!-- Basic Plan -->
            <div class="plan-card">
                <div class="plan-header">
                    <h3>Basic</h3>
                    <p>Perfect for getting started</p>
                </div>
                <div class="plan-price">
                    GH₵<?php echo number_format($plans['Basic']['price'], 2); ?>
                    <span>/month</span>
                </div>
                <ul class="plan-features">
                    <?php foreach ($plans['Basic']['features'] as $feature): ?>
                        <li><?php echo htmlspecialchars($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button onclick="selectPlan('Basic', <?php echo $plans['Basic']['price']; ?>)" class="btn btn-primary" style="width: 100%;">
                    Select Basic Plan
                </button>
            </div>

            <!-- Standard Plan -->
            <div class="plan-card featured">
                <div class="plan-header">
                    <h3>Standard</h3>
                    <p>Most popular choice</p>
                </div>
                <div class="plan-price">
                    GH₵<?php echo number_format($plans['Standard']['price'], 2); ?>
                    <span>/month</span>
                </div>
                <ul class="plan-features">
                    <?php foreach ($plans['Standard']['features'] as $feature): ?>
                        <li><?php echo htmlspecialchars($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button onclick="selectPlan('Standard', <?php echo $plans['Standard']['price']; ?>)" class="btn btn-primary" style="width: 100%;">
                    Select Standard Plan
                </button>
            </div>

            <!-- Premium Plan -->
            <div class="plan-card">
                <div class="plan-header">
                    <h3>Premium</h3>
                    <p>For growing businesses</p>
                </div>
                <div class="plan-price">
                    GH₵<?php echo number_format($plans['Premium']['price'], 2); ?>
                    <span>/month</span>
                </div>
                <ul class="plan-features">
                    <?php foreach ($plans['Premium']['features'] as $feature): ?>
                        <li><?php echo htmlspecialchars($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button onclick="selectPlan('Premium', <?php echo $plans['Premium']['price']; ?>)" class="btn btn-primary" style="width: 100%;">
                    Select Premium Plan
                </button>
            </div>
        </div>

        <!-- Payment Form (Hidden initially) -->
        <div class="card payment-form" id="paymentForm" style="display: none;">
            <div class="card-header">
                <h2 id="selectedPlanName" style="color: var(--primary-green); margin: 0;">Complete Your Subscription</h2>
            </div>
            <form id="subscriptionForm" method="POST" action="<?php echo ACTIONS_URL; ?>/subscription_action.php" style="margin-top: 1.5rem;">
                <input type="hidden" name="planName" id="planNameInput">
                <input type="hidden" name="amount" id="amountInput">
                
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select id="paymentMethod" name="paymentMethod" required onchange="togglePaymentFields()">
                        <option value="">Select payment method</option>
                        <option value="Mobile Money">Mobile Money (MTN/Vodafone/AirtelTigo)</option>
                        <option value="Card">Credit/Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div id="mobileMoneyFields" style="display: none;">
                    <div class="form-group">
                        <label>Mobile Money Number *</label>
                        <input type="tel" name="mobileMoneyNumber" placeholder="e.g., +233 XX XXX XXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label>Reference Number *</label>
                    <input type="text" name="referenceNumber" required placeholder="Payment reference number">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" required> 
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="freeTrial" name="freeTrial" value="1"> 
                        Start with 7-day free trial
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Subscribe Now - GH₵<span id="displayAmount">0.00</span>
                </button>
                <button type="button" onclick="cancelSubscription()" class="btn btn-secondary" style="width: 100%; margin-top: 1rem;">
                    Cancel
                </button>
            </form>
        </div>

        <?php if ($subscriptionHistory->num_rows > 0): ?>
        <div class="card subscription-history">
            <div class="card-header">
                <h2>📜 Subscription History</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sub = $subscriptionHistory->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['planName']); ?></td>
                            <td>GH₵<?php echo number_format($sub['amountPaid'], 2); ?></td>
                            <td>
                                <?php
                                $status = $sub['paymentStatus'];
                                $badgeClass = 'badge-pending';
                                if ($status == 'Success') $badgeClass = 'badge-completed';
                                if ($status == 'Failed') $badgeClass = 'badge-cancelled';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td><?php echo $sub['subscriptionStart'] ? date('M d, Y', strtotime($sub['subscriptionStart'])) : 'N/A'; ?></td>
                            <td><?php echo $sub['subscriptionEnd'] ? date('M d, Y', strtotime($sub['subscriptionEnd'])) : 'N/A'; ?></td>
                            <td><?php echo $sub['paymentDate'] ? date('M d, Y', strtotime($sub['paymentDate'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/subscription.js"></script>
    <script>
        function selectPlan(planName, amount) {
            document.getElementById('planNameInput').value = planName;
            document.getElementById('amountInput').value = amount;
            document.getElementById('selectedPlanName').textContent = `Subscribe to ${planName} Plan`;
            document.getElementById('displayAmount').textContent = amount.toFixed(2);
            
            // Show payment form
            document.getElementById('paymentForm').style.display = 'block';
            document.getElementById('paymentForm').scrollIntoView({ behavior: 'smooth' });
            
            // If free trial checked, set amount to 0
            const freeTrial = document.getElementById('freeTrial');
            freeTrial.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('amountInput').value = 0;
                    document.getElementById('displayAmount').textContent = '0.00 (Free Trial)';
                    document.querySelector('button[type="submit"]').textContent = 'Start Free Trial';
                } else {
                    document.getElementById('amountInput').value = amount;
                    document.getElementById('displayAmount').textContent = amount.toFixed(2);
                    document.querySelector('button[type="submit"]').textContent = 'Subscribe Now - GH₵' + amount.toFixed(2);
                }
            });
        }
        
        function cancelSubscription() {
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('subscriptionForm').reset();
        }
        
        function togglePaymentFields() {
            const method = document.getElementById('paymentMethod').value;
            const mobileFields = document.getElementById('mobileMoneyFields');
            if (method === 'Mobile Money') {
                mobileFields.style.display = 'block';
            } else {
                mobileFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>

