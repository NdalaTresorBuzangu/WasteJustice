<?php
require_once __DIR__ . '/../config/config.php';

class RecyclingClass {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Get available batches with transparent pricing (only from subscribed aggregators)
    public function getAvailableBatches($plasticTypeID = null) {
        $sql = "
            SELECT 
                ab.batchID,
                ab.aggregatorID,
                ar.businessName as aggregatorName,
                pt.plasticTypeID,
                pt.typeName as plasticType,
                ab.totalWeight,
                cp.pricePerKg as companyPrice,
                (ab.totalWeight * cp.pricePerKg) as totalPrice,
                ab.createdAt,
                u.rating as aggregatorRating
            FROM AggregatorBatch ab
            JOIN User u ON ab.aggregatorID = u.userID
            JOIN AggregatorRegistration ar ON u.userID = ar.userID
            JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
            JOIN CompanyPricing cp ON ab.plasticTypeID = cp.plasticTypeID
            INNER JOIN Subscriptions s ON u.userID = s.userID
            WHERE ab.statusID = 5
            AND u.status = 'active'
            AND cp.isActive = 1
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
        ";
        
        if ($plasticTypeID) {
            $sql .= " AND ab.plasticTypeID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $plasticTypeID);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Verify quality and confirm purchase
    public function verifyAndPurchase($batchID, $companyID, $qualityVerified = true) {
        if (!$qualityVerified) {
            return ['success' => false, 'message' => 'Quality verification failed. Purchase cannot be completed.'];
        }
        
        // Verify company has active subscription
        $subCheck = $this->conn->prepare("
            SELECT s.subscriptionID 
            FROM Subscriptions s
            JOIN User u ON s.userID = u.userID
            WHERE s.userID = ? 
            AND u.userRole = 'Recycling Company'
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
        ");
        $subCheck->bind_param("i", $companyID);
        $subCheck->execute();
        if ($subCheck->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'You must have an active subscription to purchase batches. Please subscribe first.'];
        }
        
        // Get aggregator from batch
        $stmt1 = $this->conn->prepare("SELECT aggregatorID FROM AggregatorBatch WHERE batchID = ? AND statusID = 5");
        $stmt1->bind_param("i", $batchID);
        $stmt1->execute();
        $batch = $stmt1->get_result()->fetch_assoc();
        
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found or already sold.'];
        }
        
        // Use aggregator sell method (reuse logic)
        require_once __DIR__ . '/aggregator_class.php';
        $aggClass = new AggregatorClass($this->conn);
        return $aggClass->sellBatchToCompany($batchID, $batch['aggregatorID'], $companyID);
    }
    
    // Get purchase history
    public function getPurchaseHistory($companyID) {
        $stmt = $this->conn->prepare("
            SELECT ab.*, pt.typeName, ar.businessName as aggregatorName, p.status as paymentStatus
            FROM AggregatorBatch ab
            JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
            JOIN User u ON ab.aggregatorID = u.userID
            JOIN AggregatorRegistration ar ON u.userID = ar.userID
            LEFT JOIN Payment p ON ab.batchID = p.batchID
            WHERE ab.companyID = ?
            ORDER BY ab.soldAt DESC
        ");
        $stmt->bind_param("i", $companyID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get payment history
    public function getPaymentHistory($companyID) {
        $stmt = $this->conn->prepare("
            SELECT p.*, ab.batchID, pt.typeName
            FROM Payment p
            JOIN AggregatorBatch ab ON p.batchID = ab.batchID
            JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
            WHERE p.fromUserID = ?
            ORDER BY p.createdAt DESC
        ");
        $stmt->bind_param("i", $companyID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Leave feedback for aggregator
    public function leaveFeedback($fromCompanyID, $toAggregatorID, $batchID, $rating, $comment) {
        $stmt = $this->conn->prepare("
            INSERT INTO Feedback (fromUserID, toUserID, batchID, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiis", $fromCompanyID, $toAggregatorID, $batchID, $rating, $comment);
        
        if ($stmt->execute()) {
            // Update aggregator rating
            $this->updateAggregatorRating($toAggregatorID);
            return ['success' => true, 'message' => 'Feedback submitted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to submit feedback.'];
    }
    
    // Update aggregator rating
    private function updateAggregatorRating($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT AVG(rating) as avgRating, COUNT(*) as totalRatings
            FROM Feedback
            WHERE toUserID = ?
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $updateStmt = $this->conn->prepare("
            UPDATE User 
            SET rating = ?, totalRatings = ?
            WHERE userID = ?
        ");
        $updateStmt->bind_param("dii", $result['avgRating'], $result['totalRatings'], $aggregatorID);
        $updateStmt->execute();
    }
}

