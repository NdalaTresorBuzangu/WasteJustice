<?php
require_once __DIR__ . '/../config/config.php';

class SubscriptionController {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Create subscription (Standard E-commerce: Payment verification before activation)
    public function createSubscription($userID, $planName, $amount, $paymentMethod, $mobileMoneyNumber, $referenceNumber, $freeTrial = false) {
        $this->conn->begin_transaction();
        
        try {
            // If free trial, set amount to 0 and set trial end date
            if ($freeTrial) {
                $amount = 0;
                $subscriptionStart = date('Y-m-d');
                $subscriptionEnd = date('Y-m-d', strtotime('+7 days'));
                $paymentStatus = 'Success'; // Free trial is auto-approved
                $isActive = 1;
            } else {
                $subscriptionStart = date('Y-m-d');
                $subscriptionEnd = date('Y-m-d', strtotime('+1 month'));
                // For paid subscriptions: Require admin approval (Standard E-commerce practice)
                // Payment verification and admin approval required before activation
                // This prevents fraud and ensures payment is verified
                $paymentStatus = 'Pending'; // Requires admin approval
                $isActive = 0; // Not active until admin approves
            }
            
            // Insert subscription record
            $stmt = $this->conn->prepare("
                INSERT INTO Subscriptions 
                (userID, planName, amountPaid, paymentStatus, paymentMethod, mobileMoneyNumber, referenceNumber, subscriptionStart, subscriptionEnd, isActive)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // Type string: i=integer, s=string, d=double, i=integer (10 parameters total)
            $stmt->bind_param("isdssssssi", $userID, $planName, $amount, $paymentStatus, $paymentMethod, $mobileMoneyNumber, $referenceNumber, $subscriptionStart, $subscriptionEnd, $isActive);
            $stmt->execute();
            
            // Update user subscription status (only if payment is successful)
            if ($paymentStatus == 'Success') {
                $subscriptionStatus = $freeTrial ? 'trial' : 'active';
                $updateStmt = $this->conn->prepare("
                    UPDATE User 
                    SET subscription_status = ?, subscription_expires = ?
                    WHERE userID = ?
                ");
                $updateStmt->bind_param("ssi", $subscriptionStatus, $subscriptionEnd, $userID);
                $updateStmt->execute();
            }
            
            // Update payment date
            $updateDateStmt = $this->conn->prepare("
                UPDATE Subscriptions 
                SET paymentDate = NOW()
                WHERE subscriptionID = ?
            ");
            $subscriptionID = $this->conn->insert_id;
            $updateDateStmt->bind_param("i", $subscriptionID);
            $updateDateStmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => $freeTrial ? 'Free trial activated successfully!' : ($paymentStatus == 'Success' ? 'Subscription activated successfully!' : 'Subscription created. Payment verification pending.'),
                'subscriptionID' => $subscriptionID,
                'subscriptionEnd' => $subscriptionEnd,
                'paymentStatus' => $paymentStatus,
                'isActive' => $isActive
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Subscription failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Check if user has active subscription (Standard E-commerce: Check payment status and expiry)
    public function hasActiveSubscription($userID) {
        $stmt = $this->conn->prepare("
            SELECT * FROM Subscriptions 
            WHERE userID = ? 
            AND paymentStatus = 'Success' 
            AND isActive = 1 
            AND (subscriptionEnd IS NULL OR subscriptionEnd >= CURDATE())
            ORDER BY subscriptionID DESC LIMIT 1
        ");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Verify payment and activate subscription (Standard E-commerce: Payment gateway callback)
    public function verifyPaymentAndActivate($subscriptionID, $paymentReference, $verified = true) {
        $this->conn->begin_transaction();
        
        try {
            // Get subscription
            $stmt = $this->conn->prepare("SELECT * FROM Subscriptions WHERE subscriptionID = ?");
            $stmt->bind_param("i", $subscriptionID);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();
            
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            if ($verified) {
                // Activate subscription
                $updateStmt = $this->conn->prepare("
                    UPDATE Subscriptions 
                    SET paymentStatus = 'Success', 
                        isActive = 1, 
                        paymentDate = NOW(),
                        referenceNumber = ?
                    WHERE subscriptionID = ?
                ");
                $updateStmt->bind_param("si", $paymentReference, $subscriptionID);
                $updateStmt->execute();
                
                // Update user status
                $updateUserStmt = $this->conn->prepare("
                    UPDATE User 
                    SET subscription_status = 'active', 
                        subscription_expires = ?
                    WHERE userID = ?
                ");
                $subscriptionEnd = $subscription['subscriptionEnd'] ?? date('Y-m-d', strtotime('+1 month'));
                $updateUserStmt->bind_param("si", $subscriptionEnd, $subscription['userID']);
                $updateUserStmt->execute();
                
                $this->conn->commit();
                return ['success' => true, 'message' => 'Payment verified and subscription activated'];
            } else {
                // Mark payment as failed
                $updateStmt = $this->conn->prepare("
                    UPDATE Subscriptions 
                    SET paymentStatus = 'Failed'
                    WHERE subscriptionID = ?
                ");
                $updateStmt->bind_param("i", $subscriptionID);
                $updateStmt->execute();
                
                $this->conn->commit();
                return ['success' => false, 'message' => 'Payment verification failed'];
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Check if user has free access (collectors)
    public function hasFreeAccess($userRole) {
        return $userRole == 'Waste Collector';
    }
    
    // Validate subscription access
    public function validateAccess($userID, $userRole) {
        // Free access for collectors
        if ($this->hasFreeAccess($userRole)) {
            return ['access' => true, 'type' => 'free'];
        }
        
        // Admin always has access
        if ($userRole == 'Admin') {
            return ['access' => true, 'type' => 'admin'];
        }
        
        // Paid users need active subscription
        $subscription = $this->hasActiveSubscription($userID);
        if ($subscription) {
            return [
                'access' => true,
                'type' => 'paid',
                'subscription' => $subscription
            ];
        }
        
        return [
            'access' => false,
            'type' => 'no_subscription',
            'message' => 'Subscription required for premium features'
        ];
    }
    
    // Get subscription expiry
    public function getSubscriptionExpiry($userID) {
        $subscription = $this->hasActiveSubscription($userID);
        return $subscription ? $subscription['subscriptionEnd'] : null;
    }
    
    // Cancel subscription
    public function cancelSubscription($userID) {
        $stmt = $this->conn->prepare("
            UPDATE Subscriptions 
            SET isActive = 0
            WHERE userID = ? AND isActive = 1
        ");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        
        // Update user status
        $updateStmt = $this->conn->prepare("
            UPDATE User 
            SET subscription_status = 'cancelled'
            WHERE userID = ?
        ");
        $updateStmt->bind_param("i", $userID);
        $updateStmt->execute();
        
        return ['success' => true, 'message' => 'Subscription cancelled successfully'];
    }
    
    // Renew subscription
    public function renewSubscription($userID, $subscriptionID) {
        // Get current subscription
        $stmt = $this->conn->prepare("SELECT * FROM Subscriptions WHERE subscriptionID = ? AND userID = ?");
        $stmt->bind_param("ii", $subscriptionID, $userID);
        $stmt->execute();
        $subscription = $stmt->get_result()->fetch_assoc();
        
        if (!$subscription) {
            return ['success' => false, 'message' => 'Subscription not found'];
        }
        
        // Calculate new end date (add 1 month from current end or today)
        $currentEnd = $subscription['subscriptionEnd'] ?: date('Y-m-d');
        $newEnd = date('Y-m-d', strtotime($currentEnd . ' +1 month'));
        
        // Update subscription
        $updateStmt = $this->conn->prepare("
            UPDATE Subscriptions 
            SET subscriptionEnd = ?, isActive = 1, paymentDate = NOW()
            WHERE subscriptionID = ?
        ");
        $updateStmt->bind_param("si", $newEnd, $subscriptionID);
        $updateStmt->execute();
        
        // Update user
        $updateUserStmt = $this->conn->prepare("
            UPDATE User 
            SET subscription_status = 'active', subscription_expires = ?
            WHERE userID = ?
        ");
        $updateUserStmt->bind_param("si", $newEnd, $userID);
        $updateUserStmt->execute();
        
        return ['success' => true, 'message' => 'Subscription renewed successfully', 'newEndDate' => $newEnd];
    }
}

