<?php
require_once __DIR__ . '/../config/config.php';

class AdminClass {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Get all users
    public function getAllUsers($role = null) {
        $sql = "SELECT * FROM User WHERE userRole != 'Admin'";
        if ($role) {
            $sql .= " AND userRole = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $role);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Approve/suspend user
    public function updateUserStatus($userID, $status) {
        $stmt = $this->conn->prepare("UPDATE User SET status = ? WHERE userID = ?");
        $stmt->bind_param("si", $status, $userID);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User status updated successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update user status.'];
    }
    
    // Get analytics
    public function getAnalytics() {
        $data = [];
        
        // Total collections
        $result = $this->conn->query("SELECT COUNT(*) as total FROM WasteCollection");
        $data['totalCollections'] = $result->fetch_assoc()['total'];
        
        // Total weight collected
        $result = $this->conn->query("SELECT SUM(weight) as total FROM WasteCollection");
        $data['totalWeight'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total batches
        $result = $this->conn->query("SELECT COUNT(*) as total FROM AggregatorBatch");
        $data['totalBatches'] = $result->fetch_assoc()['total'];
        
        // Total sold batches
        $result = $this->conn->query("SELECT COUNT(*) as total FROM AggregatorBatch WHERE statusID = 6");
        $data['soldBatches'] = $result->fetch_assoc()['total'];
        
        // Total payments (net amount to recipients)
        $result = $this->conn->query("SELECT SUM(amount) as total FROM Payment WHERE status = 'completed'");
        $data['totalPayments'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total platform fees collected (1% of all transactions)
        $result = $this->conn->query("SELECT SUM(platformFee) as total FROM Payment WHERE status = 'completed'");
        $data['totalPlatformFees'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total gross transaction volume
        $result = $this->conn->query("SELECT SUM(grossAmount) as total FROM Payment WHERE status = 'completed'");
        $data['totalGrossVolume'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total users by role
        $result = $this->conn->query("SELECT userRole, COUNT(*) as count FROM User WHERE userRole != 'Admin' GROUP BY userRole");
        $data['usersByRole'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['usersByRole'][$row['userRole']] = $row['count'];
        }
        
        // Average ratings
        $result = $this->conn->query("SELECT AVG(rating) as avg FROM Feedback");
        $data['averageRating'] = $result->fetch_assoc()['avg'] ?? 0;
        
        return $data;
    }
    
    // Get all transactions
    public function getAllTransactions() {
        $stmt = $this->conn->prepare("
            SELECT 
                wc.collectionID,
                wc.weight,
                pt.typeName,
                u1.userName as collectorName,
                u2.userName as aggregatorName,
                s.statusName,
                wc.collectionDate
            FROM WasteCollection wc
            JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            JOIN Status s ON wc.statusID = s.statusID
            LEFT JOIN User u1 ON wc.collectorID = u1.userID
            LEFT JOIN User u2 ON wc.aggregatorID = u2.userID
            ORDER BY wc.collectionDate DESC
        ");
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get all batches
    public function getAllBatches() {
        $stmt = $this->conn->prepare("
            SELECT 
                ab.*,
                pt.typeName,
                ar.businessName as aggregatorName,
                cr.companyName,
                s.statusName
            FROM AggregatorBatch ab
            JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
            JOIN User u ON ab.aggregatorID = u.userID
            JOIN AggregatorRegistration ar ON u.userID = ar.userID
            LEFT JOIN User u2 ON ab.companyID = u2.userID
            LEFT JOIN CompanyRegistration cr ON u2.userID = cr.userID
            JOIN Status s ON ab.statusID = s.statusID
            ORDER BY ab.createdAt DESC
        ");
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get all feedback
    public function getAllFeedback() {
        $stmt = $this->conn->prepare("
            SELECT 
                f.*,
                u1.userName as fromUserName,
                u2.userName as toUserName
            FROM Feedback f
            JOIN User u1 ON f.fromUserID = u1.userID
            JOIN User u2 ON f.toUserID = u2.userID
            ORDER BY f.createdAt DESC
        ");
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Monitor pricing
    public function getPricingOverview() {
        $data = [];
        
        // Aggregator prices by plastic type
        $result = $this->conn->query("
            SELECT pt.typeName, AVG(ap.pricePerKg) as avgPrice, MIN(ap.pricePerKg) as minPrice, MAX(ap.pricePerKg) as maxPrice
            FROM AggregatorPricing ap
            JOIN PlasticType pt ON ap.plasticTypeID = pt.plasticTypeID
            WHERE ap.isActive = TRUE
            GROUP BY pt.plasticTypeID
        ");
        $data['aggregatorPrices'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['aggregatorPrices'][] = $row;
        }
        
        // Company prices by plastic type
        $result = $this->conn->query("
            SELECT pt.typeName, AVG(cp.pricePerKg) as avgPrice, MIN(cp.pricePerKg) as minPrice, MAX(cp.pricePerKg) as maxPrice
            FROM CompanyPricing cp
            JOIN PlasticType pt ON cp.plasticTypeID = pt.plasticTypeID
            WHERE cp.isActive = TRUE
            GROUP BY pt.plasticTypeID
        ");
        $data['companyPrices'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['companyPrices'][] = $row;
        }
        
        return $data;
    }
}

