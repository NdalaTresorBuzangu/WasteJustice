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
                   AND s.isActive = 1 
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

// Get all feedback
$allFeedback = $adminController->getAllFeedback();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--gray-light);
            flex-wrap: wrap;
        }
        .admin-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            color: var(--gray);
            transition: all 0.3s;
        }
        .admin-tab:hover {
            color: var(--primary-green);
            background: rgba(16, 185, 129, 0.1);
        }
        .admin-tab.active {
            color: var(--primary-green);
            border-bottom-color: var(--primary-green);
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-green);
        }
        .stat-card h4 {
            margin: 0 0 0.5rem 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-green);
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
        }
    </style>
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
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="<?php echo VIEWS_URL; ?>/admin/subscriptions.php">Subscriptions</a></li>
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

        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="showTab('overview')">📊 Overview</button>
            <button class="admin-tab" onclick="showTab('users')">👥 User Management</button>
            <button class="admin-tab" onclick="showTab('analytics')">📈 Analytics</button>
            <button class="admin-tab" onclick="showTab('transactions')">💰 Transactions</button>
            <button class="admin-tab" onclick="showTab('pricing')">💲 Pricing</button>
            <button class="admin-tab" onclick="showTab('feedback')">⭐ Feedback</button>
            <button class="admin-tab" onclick="showTab('settings')">⚙️ Settings</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
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
                        $activeSubs = $conn->query("SELECT COUNT(*) as count FROM Subscriptions WHERE paymentStatus = 'Success' AND isActive = 1")->fetch_assoc()['count'];
                        echo $activeSubs;
                    ?></div>
                </div>
            </div>

            <!-- Recent Activity Chart -->
            <div class="card">
                <div class="card-header">
                    <h2>📊 Recent Activity (Last 7 Days)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                <div class="card">
                    <div class="card-header">
                        <h3>🏆 Top Aggregators</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Business</th>
                                <th>Collections</th>
                                <th>Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($analytics['topAggregators'] ?? [], 0, 5) as $agg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agg['businessName']); ?></td>
                                    <td><?php echo $agg['totalCollections']; ?></td>
                                    <td><?php echo number_format($agg['totalWeight'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3>🌟 Top Collectors</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Collector</th>
                                <th>Collections</th>
                                <th>Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($analytics['topCollectors'] ?? [], 0, 5) as $col): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($col['userName']); ?></td>
                                    <td><?php echo $col['totalCollections']; ?></td>
                                    <td><?php echo number_format($col['totalWeight'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Management Tab -->
        <div id="users" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>👥 All Users & Subscription Status</h2>
                    <div class="action-buttons">
                        <a href="<?php echo ACTIONS_URL; ?>/admin/setup_default_pricing.php" class="btn btn-primary btn-small" onclick="return confirm('This will add default pricing for all aggregators without pricing. Continue?')">Setup Pricing</a>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Subscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($allUsers->num_rows > 0): ?>
                            <?php 
                            $allUsers->data_seek(0); // Reset pointer
                            while ($user = $allUsers->fetch_assoc()): 
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
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $subBadgeClass; ?>">
                                            <?php echo htmlspecialchars($subStatus); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['status'] == 'pending'): ?>
                                                <form method="POST" action="<?php echo ACTIONS_URL; ?>/admin/approve_user.php" style="display: inline;">
                                                    <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-small" onclick="return confirm('Approve user #<?php echo $user['userID']; ?>?')">✅ Approve</button>
                                                </form>
                                            <?php elseif ($user['status'] == 'active'): ?>
                                                <form method="POST" action="<?php echo ACTIONS_URL; ?>/admin/suspend_user.php" style="display: inline;">
                                                    <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                    <button type="submit" class="btn btn-small" style="background: var(--error); color: white;" onclick="return confirm('Suspend user #<?php echo $user['userID']; ?>?')">⛔ Suspend</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?php echo ACTIONS_URL; ?>/admin/approve_user.php" style="display: inline;">
                                                    <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-small">✅ Activate</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray);">
                                    No users found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>📈 Collections by Status</h2>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>📊 Collections by Plastic Type</h2>
                </div>
                <div class="chart-container">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>💰 Monthly Revenue (Last 6 Months)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="stats-grid">
                <?php foreach ($analytics['subscriptionStats'] ?? [] as $stat): ?>
                    <div class="stat-card">
                        <h4><?php echo htmlspecialchars($stat['paymentStatus']); ?> Subscriptions</h4>
                        <div class="value"><?php echo $stat['count']; ?></div>
                        <p style="margin-top: 0.5rem; color: var(--gray);">GH₵<?php echo number_format($stat['total'] ?? 0, 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div id="transactions" class="tab-content">
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
                        <?php 
                        $allTransactions->data_seek(0);
                        if ($allTransactions->num_rows > 0): ?>
                            <?php $count = 0; while (($trans = $allTransactions->fetch_assoc()) && $count < 20): ?>
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

        <!-- Pricing Tab -->
        <div id="pricing" class="tab-content">
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
        </div>

        <!-- Feedback Tab -->
        <div id="feedback" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>⭐ User Feedback & Ratings</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($allFeedback->num_rows > 0): ?>
                            <?php while ($fb = $allFeedback->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fb['fromUserName']); ?></td>
                                    <td><?php echo htmlspecialchars($fb['toUserName']); ?></td>
                                    <td>
                                        <span style="color: gold; font-size: 1.2rem;">
                                            <?php echo str_repeat('★', $fb['rating']); ?><?php echo str_repeat('☆', 5 - $fb['rating']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($fb['comment'] ?? 'No comment'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($fb['createdAt'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray);">
                                    No feedback yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>⚙️ System Settings & Actions</h2>
                </div>
                <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                    <div class="card">
                        <h3>📊 Subscriptions</h3>
                        <p>Manage user subscriptions</p>
                        <a href="<?php echo VIEWS_URL; ?>/admin/subscriptions.php" class="btn btn-primary">Manage Subscriptions</a>
                    </div>
                    <div class="card">
                        <h3>💲 Pricing Setup</h3>
                        <p>Set default pricing for aggregators</p>
                        <a href="<?php echo ACTIONS_URL; ?>/admin/setup_default_pricing.php" class="btn btn-primary" onclick="return confirm('This will add default pricing for all aggregators without pricing. Continue?')">Setup Pricing</a>
                    </div>
                    <div class="card">
                        <h3>📥 Export Data</h3>
                        <p>Export user and transaction data</p>
                        <button class="btn btn-primary" onclick="alert('Export feature coming soon!')">Export Data</button>
                    </div>
                    <div class="card">
                        <h3>🔒 Security</h3>
                        <p>View security logs and settings</p>
                        <button class="btn btn-primary" onclick="alert('Security panel coming soon!')">Security Panel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 <?php echo APP_NAME; ?>. Building a cleaner Ghana together.</p>
        <p style="margin-top: 0.5rem;">Fair • Transparent • Connected</p>
    </footer>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Activity Chart
        const activityData = <?php echo json_encode($analytics['recentActivity'] ?? []); ?>;
        const activityLabels = activityData.map(d => new Date(d.date).toLocaleDateString());
        const activityCounts = activityData.map(d => parseInt(d.count));

        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: activityLabels,
                datasets: [{
                    label: 'Collections',
                    data: activityCounts,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Status Chart
        const statusData = <?php echo json_encode($analytics['collectionsByStatus'] ?? []); ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(59, 130, 246)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Type Chart
        const typeData = <?php echo json_encode($analytics['collectionsByType'] ?? []); ?>;
        new Chart(document.getElementById('typeChart'), {
            type: 'bar',
            data: {
                labels: typeData.map(t => t.typeName),
                datasets: [{
                    label: 'Collections',
                    data: typeData.map(t => parseInt(t.count)),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)'
                }, {
                    label: 'Weight (kg)',
                    data: typeData.map(t => parseFloat(t.totalWeight || 0)),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true
                    }
                }
            }
        });

        // Revenue Chart
        const revenueData = <?php echo json_encode($analytics['monthlyRevenue'] ?? []); ?>;
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: revenueData.map(r => r.month),
                datasets: [{
                    label: 'Platform Fees',
                    data: revenueData.map(r => parseFloat(r.fees || 0)),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)'
                }, {
                    label: 'Gross Volume',
                    data: revenueData.map(r => parseFloat(r.gross || 0)),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
