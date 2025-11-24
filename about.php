<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo APP_NAME; ?></title>
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
                <h2>About WasteJustice</h2>
            </div>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--gray);">
                <strong>Transforming waste management in Ghana through transparency, fairness, and technology.</strong>
            </p>
        </div>

        <!-- The Problem -->
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--danger-color);">🚨 The Problem We're Solving</h2>
            </div>
            <div style="line-height: 1.8;">
                <h3 style="color: var(--primary-green); margin-top: 1.5rem; margin-bottom: 1rem;">Waste Management Crisis in Ghana</h3>
                <p>
                    Ghana faces a significant waste management challenge. Every day, thousands of tons of plastic waste 
                    are generated, but the system for collecting, processing, and recycling this waste is fragmented and 
                    inefficient. This creates several critical problems:
                </p>
                
                <div style="margin: 2rem 0; padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--danger-color);">
                    <h4 style="color: var(--danger-color); margin-bottom: 1rem;">Key Challenges:</h4>
                    <ul style="line-height: 2; padding-left: 1.5rem;">
                        <li><strong>Lack of Transparency:</strong> Waste collectors don't know fair market prices, leading to exploitation and unfair compensation.</li>
                        <li><strong>Fragmented Supply Chain:</strong> No efficient connection between waste collectors, aggregators, and recycling companies.</li>
                        <li><strong>Payment Delays:</strong> Collectors often wait weeks or months to receive payment for their work.</li>
                        <li><strong>Price Manipulation:</strong> Middlemen can exploit information asymmetry to offer unfair prices.</li>
                        <li><strong>No Tracking:</strong> Waste transactions are not properly documented, making accountability impossible.</li>
                        <li><strong>Environmental Impact:</strong> Poor waste management leads to pollution, health hazards, and environmental degradation.</li>
                    </ul>
                </div>

                <p style="margin-top: 1.5rem;">
                    These problems disproportionately affect waste collectors, who are often from vulnerable communities 
                    and depend on waste collection as their primary source of income. Without fair compensation and 
                    transparent processes, they struggle to make a sustainable living while contributing to environmental 
                    cleanup.
                </p>
            </div>
        </div>

        <!-- The Solution -->
        <div class="card">
            <div class="card-header">
                <h2 style="color: var(--primary-green);">💚 How WasteJustice Solves It</h2>
            </div>
            <div style="line-height: 1.8;">
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">
                    WasteJustice is a digital platform that brings transparency, fairness, and efficiency to waste 
                    management in Ghana. We connect all stakeholders in the waste value chain through technology.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 2rem 0;">
                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">🔍 Transparent Pricing</h3>
                        <p>
                            All prices are visible to everyone. Waste collectors can see what aggregators are paying 
                            before they deliver, eliminating price manipulation and ensuring fair compensation.
                        </p>
                    </div>

                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">⚡ Fast Payments</h3>
                        <p>
                            Automated payment processing ensures collectors receive payment quickly after delivery 
                            acceptance. No more waiting weeks for compensation.
                        </p>
                    </div>

                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">📊 Complete Tracking</h3>
                        <p>
                            Every transaction is recorded and tracked from collection to final sale. This creates 
                            accountability and helps build trust in the system.
                        </p>
                    </div>

                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">🤝 Direct Connections</h3>
                        <p>
                            Our platform directly connects waste collectors with aggregators and recycling companies, 
                            reducing middlemen and ensuring more value reaches the collectors.
                        </p>
                    </div>

                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">⭐ Rating System</h3>
                        <p>
                            A feedback and rating system helps build trust. Collectors can rate aggregators, and 
                            aggregators can rate companies, creating accountability throughout the chain.
                        </p>
                    </div>

                    <div style="padding: 1.5rem; background: var(--very-light-green); border-radius: 0.5rem; border-left: 4px solid var(--primary-green);">
                        <h3 style="color: var(--primary-green); margin-bottom: 1rem;">🌱 Environmental Impact</h3>
                        <p>
                            By making waste collection more profitable and efficient, we incentivize more people to 
                            collect waste, leading to cleaner communities and better environmental outcomes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="card">
            <div class="card-header">
                <h2>🔄 How WasteJustice Works</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">♻️</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">1. Collect Waste</h3>
                    <p>Waste collectors gather recyclable plastic materials from communities across Ghana and log them in the system with photos and details.</p>
                </div>
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">2. View Transparent Prices</h3>
                    <p>Collectors can see all aggregator prices before choosing where to deliver, ensuring they get the best deal.</p>
                </div>
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">3. Deliver to Aggregator</h3>
                    <p>Collectors select an aggregator and deliver their waste. The aggregator verifies quality and accepts the delivery.</p>
                </div>
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💵</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">4. Receive Payment</h3>
                    <p>Payment is processed automatically. Collectors receive their compensation quickly and transparently.</p>
                </div>
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏭</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">5. Aggregator Creates Batches</h3>
                    <p>Aggregators group waste by type and create batches for sale to recycling companies at competitive prices.</p>
                </div>
                <div style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                    <h3 style="color: var(--primary-green); margin-bottom: 0.5rem;">6. Companies Purchase</h3>
                    <p>Recycling companies verify quality and purchase batches, completing the cycle and ensuring waste is properly recycled.</p>
                </div>
            </div>
        </div>

        <!-- Our Values -->
        <div class="card">
            <div class="card-header">
                <h2>💎 Our Core Values</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <div>
                    <h3 style="color: var(--primary-green);">⚖️ Fairness</h3>
                    <p>Every stakeholder in the waste value chain deserves fair compensation for their contribution. We ensure transparency prevents exploitation.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green);">🔓 Transparency</h3>
                    <p>All prices, transactions, and processes are visible to relevant parties. No hidden fees, no secret deals.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green);">🤝 Trust</h3>
                    <p>Through ratings, reviews, and verified transactions, we build trust between all parties in the ecosystem.</p>
                </div>
                <div>
                    <h3 style="color: var(--primary-green);">🌍 Impact</h3>
                    <p>We measure success not just in transactions, but in environmental impact and improved livelihoods for waste collectors.</p>
                </div>
            </div>
        </div>

        <!-- Impact -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); color: white;">
            <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.3);">
                <h2 style="color: white;">📈 Our Impact</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 2rem; text-align: center;">
                <div>
                    <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">Fair</div>
                    <p style="opacity: 0.9;">Compensation for all collectors</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">Fast</div>
                    <p style="opacity: 0.9;">Quick payment processing</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">Transparent</div>
                    <p style="opacity: 0.9;">Visible pricing for everyone</p>
                </div>
                <div>
                    <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem;">Connected</div>
                    <p style="opacity: 0.9;">Direct links in the value chain</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="card text-center">
            <h2 style="color: var(--primary-green); margin-bottom: 1rem;">Join the WasteJustice Movement</h2>
            <p style="margin-bottom: 2rem; font-size: 1.1rem;">
                Whether you're a waste collector, aggregator, or recycling company, WasteJustice provides the tools 
                and transparency you need to succeed. Together, we're building a cleaner, more sustainable Ghana.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo VIEWS_URL; ?>/auth/signup.php" class="btn btn-primary">Get Started</a>
                <a href="<?php echo VIEWS_URL; ?>/auth/login.php" class="btn btn-secondary">Login</a>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>
</body>
</html>

