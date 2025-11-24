<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo APP_TAGLINE; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🌍 <?php echo APP_NAME; ?></h1>
                <p><?php echo APP_TAGLINE; ?></p>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/auth/login.php">Login</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/auth/signup.php">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Welcome to WasteJustice</h2>
            </div>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--gray);">
                <strong>Fair, Transparent, and Connected Waste Management in Ghana</strong>
            </p>
            <p style="margin-top: 1rem; line-height: 1.8;">
                WasteJustice is a digital platform that connects waste collectors and recycling companies 
                to make every transaction transparent, fair, and traceable. Together, we're building 
                a cleaner, more sustainable Ghana.
            </p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>For Waste Collectors</h3>
                <div class="value">♻️</div>
                <p>Track your waste collection, view transparent prices, and get fair compensation for your work.</p>
                <a href="<?php echo VIEWS_URL; ?>/auth/signup.php?role=collector" class="btn btn-primary mt-2">Register as Collector</a>
            </div>

            <div class="dashboard-card">
                <h3>For Recycling Companies</h3>
                <div class="value">🏭</div>
                <p>Connect with waste collectors, set fair prices, and build a sustainable supply chain.</p>
                <a href="<?php echo VIEWS_URL; ?>/auth/signup.php?role=company" class="btn btn-primary mt-2">Register as Company</a>
            </div>

            <div class="dashboard-card">
                <h3>Transparent System</h3>
                <div class="value">💚</div>
                <p>Every transaction is tracked, every price is visible, and every payment is fair.</p>
                <a href="<?php echo VIEWS_URL; ?>/auth/login.php" class="btn btn-secondary mt-2">Login to Dashboard</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>How It Works</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">1. Collect Waste</h3>
                    <p>Waste collectors gather recyclable materials from communities across Ghana.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">2. Record Transaction</h3>
                    <p>Log waste details in the system including type, weight, and location.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">3. Connect with Companies</h3>
                    <p>Recycling companies review requests and arrange collection.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">4. Fair Payment</h3>
                    <p>Transparent pricing ensures collectors receive fair compensation.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">5. Track Impact</h3>
                    <p>Monitor environmental impact and track waste management progress.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">6. Build Together</h3>
                    <p>Create a sustainable future through transparent collaboration.</p>
                </div>
            </div>
        </div>

        <div class="card text-center">
            <h2 style="color: var(--primary-green); margin-bottom: 1rem;">Ready to Get Started?</h2>
            <p style="margin-bottom: 2rem;">Join WasteJustice today and be part of Ghana's waste management revolution.</p>
            <a href="<?php echo VIEWS_URL; ?>/auth/signup.php" class="btn btn-primary" style="margin-right: 1rem;">Create Account</a>
            <a href="<?php echo VIEWS_URL; ?>/auth/login.php" class="btn btn-secondary">Login</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>
</body>
</html>
