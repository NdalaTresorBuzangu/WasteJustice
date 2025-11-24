<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

// Check if logged in
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] != 'Waste Collector') {
    header("Location: " . VIEWS_URL . "/auth/login.php");
    exit();
}

$userID = $_SESSION['userID'];
$userName = $_SESSION['userName'];

// Get payment amount from query parameter or default
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : 'Waste Collection Payment';

// Generate unique reference
$reference = 'WJ-' . time() . '-' . $userID . '-' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .payment-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .amount-display {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--very-light-green), #e8f5e9);
            border-radius: 0.75rem;
            margin-bottom: 2rem;
        }
        .amount-display .label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        .amount-display .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-green);
        }
        .payment-form {
            margin-top: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-gray);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        .payment-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 1rem;
        }
        .payment-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .payment-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .info-box {
            background: var(--very-light-green);
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary-green);
            margin-bottom: 1.5rem;
        }
        .info-box p {
            margin: 0.5rem 0;
            color: var(--gray-dark);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p>Waste Collector Payment</p>
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
        <div class="payment-container">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 style="color: var(--primary-green); margin: 0;">💳 Make Payment</h2>
                </div>
                <p style="margin-top: 1rem;">Complete your payment securely using Paystack</p>
            </div>

            <div class="payment-card">
                <div class="amount-display">
                    <div class="label">Amount to Pay</div>
                    <div class="value">GH₵<?php echo number_format($amount, 2); ?></div>
                </div>

                <div class="info-box">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-green);">🔒 Secure Payment</h4>
                    <p>Your payment is processed securely by Paystack. We do not store your card details.</p>
                    <p><strong>Payment for:</strong> <?php echo htmlspecialchars($description); ?></p>
                </div>

                <form id="paymentForm" class="payment-form">
                    <input type="hidden" id="reference" value="<?php echo htmlspecialchars($reference); ?>">
                    <input type="hidden" id="amount" value="<?php echo $amount; ?>">
                    <input type="hidden" id="user_id" value="<?php echo $userID; ?>">
                    <input type="hidden" id="description" value="<?php echo htmlspecialchars($description); ?>">

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="your.email@example.com"
                            value="<?php echo htmlspecialchars($_SESSION['userEmail'] ?? ''); ?>"
                        >
                    </div>

                    <button type="submit" class="payment-button" id="payButton">
                        💳 Pay GH₵<?php echo number_format($amount, 2); ?>
                    </button>
                </form>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--light-gray);">
                    <h4 style="color: var(--dark); margin-bottom: 1rem;">Accepted Payment Methods</h4>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <span style="padding: 0.5rem 1rem; background: var(--very-light-green); border-radius: 0.5rem;">💳 Card</span>
                        <span style="padding: 0.5rem 1rem; background: var(--very-light-green); border-radius: 0.5rem;">📱 Mobile Money</span>
                        <span style="padding: 0.5rem 1rem; background: var(--very-light-green); border-radius: 0.5rem;">🏦 Bank Transfer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/paystack_payment.js"></script>
</body>
</html>

