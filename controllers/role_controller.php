<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/subscription_controller.php';

class RoleController {
    private $conn;
    public $subscriptionController;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->subscriptionController = new SubscriptionController($connection);
    }
    
    // Check if user can access feature
    public function canAccess($userID, $userRole, $feature) {
        // Free features available to all
        $freeFeatures = [
            'upload_waste',
            'view_aggregators',
            'view_prices',
            'receive_payment',
            'leave_feedback'
        ];
        
        if (in_array($feature, $freeFeatures)) {
            return ['access' => true, 'reason' => 'free_feature'];
        }
        
        // Collectors have free access to all their features
        if ($userRole == 'Waste Collector') {
            return ['access' => true, 'reason' => 'collector_free'];
        }
        
        // Admin has access to everything
        if ($userRole == 'Admin') {
            return ['access' => true, 'reason' => 'admin'];
        }
        
        // Premium features require subscription
        $accessCheck = $this->subscriptionController->validateAccess($userID, $userRole);
        
        if ($accessCheck['access']) {
            return ['access' => true, 'reason' => 'subscribed', 'subscription' => $accessCheck['subscription'] ?? null];
        }
        
        return ['access' => false, 'reason' => 'no_subscription', 'message' => 'Subscription required'];
    }
    
    // Require subscription (redirect if not subscribed)
    public function requireSubscription($userID, $userRole) {
        $accessCheck = $this->subscriptionController->validateAccess($userID, $userRole);
        
        if (!$accessCheck['access']) {
            header("Location: " . VIEWS_URL . "/subscription.php");
            exit();
        }
        
        return $accessCheck;
    }
    
    // Get redirect URL based on role and subscription
    public function getRedirectURL($userID, $userRole) {
        // Collectors always go to free dashboard
        if ($userRole == 'Waste Collector') {
            return VIEWS_URL . '/collector/dashboard.php';
        }
        
        // Admin goes to admin dashboard
        if ($userRole == 'Admin') {
            return VIEWS_URL . '/admin/dashboard.php';
        }
        
        // Check subscription for aggregators and companies
        $accessCheck = $this->subscriptionController->validateAccess($userID, $userRole);
        
        if (!$accessCheck['access']) {
            return VIEWS_URL . '/subscription.php';
        }
        
        // Redirect to appropriate dashboard
        if ($userRole == 'Aggregator') {
            return VIEWS_URL . '/aggregator/dashboard.php';
        } elseif ($userRole == 'Recycling Company') {
            return VIEWS_URL . '/recycling/dashboard.php';
        }
        
        return BASE_URL . '/index.php';
    }
    
    // Get subscription expiry notice
    public function getExpiryNotice($userID) {
        $expiry = $this->subscriptionController->getSubscriptionExpiry($userID);
        
        if (!$expiry) {
            return null;
        }
        
        $expiryTimestamp = is_string($expiry) ? strtotime($expiry) : $expiry;
        $nowTimestamp = time();
        $daysUntilExpiry = ($expiryTimestamp - $nowTimestamp) / (60 * 60 * 24);
        
        if ($daysUntilExpiry < 0) {
            return [
                'type' => 'expired',
                'message' => 'Your subscription has expired. Please renew to continue using premium features.'
            ];
        } elseif ($daysUntilExpiry <= 7) {
            return [
                'type' => 'warning',
                'message' => "Your subscription expires in " . ceil($daysUntilExpiry) . " days. Consider renewing now.",
                'daysLeft' => ceil($daysUntilExpiry)
            ];
        }
        
        return null;
    }
}

