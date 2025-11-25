<?php
include 'core.php';
isLogin();

if ($_SESSION['user_role'] !== 'Affected Student') {
    echo "Access denied.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard - Tshijuka RDP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #0d6efd 0%, #1a73e8 100%);
        color: #fff;
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
    }
    .dashboard-card {
        background-color: #fff;
        color: #333;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        padding: 2rem;
        transition: all 0.3s ease-in-out;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.25);
    }
    .list-group-item {
        border: none;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: 0.3s;
    }
    .list-group-item:hover {
        background-color: #0d6efd;
        color: #fff;
    }
    .header-section {
        text-align: center;
        margin-bottom: 2rem;
    }
    .header-section h1 {
        font-weight: 600;
    }
    .tagline {
        font-size: 1.1rem;
        color: #cce0ff;
    }
    .logout-btn {
        background: #fff;
        color: #0d6efd;
        border: none;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        transition: 0.3s;
    }
    .logout-btn:hover {
        background: #cce0ff;
        color: #084298;
    }
</style>
</head>
<body>

<div class="container py-5">
    <div class="header-section">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
        <p class="tagline">Empowering you to recover and protect your academic journey.</p>
        <a href="logout.php" class="logout-btn mt-3">Logout</a>
    </div>

    <div class="dashboard-card">
        <h4 class="text-center mb-4 text-primary">Your Recovery Tools</h4>
        <div class="list-group">
            <a href="postloss_submission.php" class="list-group-item list-group-item-action">
                <strong>📄 Post-loss Recovery</strong> — Retrieve lost academic documents securely.
            </a>
            <a href="preloss_storage.php" class="list-group-item list-group-item-action">
                <strong>🔒 Pre-loss Storage</strong> — Store and protect your important files in advance.
            </a>
            <a href="tshijuka_pack.php" class="list-group-item list-group-item-action">
                <strong>📦 Tshijuka Pack</strong> — Manage and share your verified document collection.
            </a>
        </div>
    </div>

    <footer class="text-center mt-5 text-light">
        <small>© <?= date('Y') ?> Tshijuka Refugee Document Recovery Platform • Built for Hope & Resilience</small>
    </footer>
</div>

</body>
</html>








