<?php
require_once __DIR__ . '/../config/config.php';

class CollectorClass {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Add waste collection
    public function addWaste($collectorID, $plasticTypeID, $weight, $location, $notes = '', $photoPath = '', $latitude = null, $longitude = null, $aggregatorID = null) {
        // Validate collector exists
        $userCheck = $this->conn->prepare("SELECT userID FROM User WHERE userID = ? AND userRole = 'Waste Collector'");
        $userCheck->bind_param("i", $collectorID);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid collector ID. Please log in again.'];
        }
        
        // Validate aggregator if provided (must have active subscription)
        if ($aggregatorID !== null) {
            $aggCheck = $this->conn->prepare("
                SELECT u.userID 
                FROM User u
                INNER JOIN Subscriptions s ON u.userID = s.userID
                WHERE u.userID = ? 
                AND u.userRole = 'Aggregator' 
                AND u.status = 'active'
                AND s.paymentStatus = 'Success'
                AND s.isActive = 1
                AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
            ");
            $aggCheck->bind_param("i", $aggregatorID);
            $aggCheck->execute();
            if ($aggCheck->get_result()->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid aggregator selected. The aggregator must have an active subscription. Please select a different aggregator.'];
            }
        }
        
        // Validate plastic type exists
        $typeCheck = $this->conn->prepare("SELECT plasticTypeID FROM PlasticType WHERE plasticTypeID = ?");
        $typeCheck->bind_param("i", $plasticTypeID);
        $typeCheck->execute();
        if ($typeCheck->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid plastic type selected.'];
        }
        
        // Generate hash to prevent duplicates
        $hash = md5($collectorID . $plasticTypeID . $weight . date('Y-m-d'));
        
        // Check for duplicate
        $check = $this->conn->prepare("SELECT collectionID FROM WasteCollection WHERE hash = ? AND collectionDate >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $check->bind_param("s", $hash);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Duplicate entry prevented. Similar waste already recorded today.'];
        }
        
        // Check if latitude/longitude columns exist, if not use basic insert
        $columns = "collectorID, plasticTypeID, weight, location, notes, photoPath, hash, statusID";
        $values = "?, ?, ?, ?, ?, ?, ?, 1";
        $types = "iidssss";
        $params = [$collectorID, $plasticTypeID, $weight, $location, $notes, $photoPath, $hash];
        
        // Add aggregatorID if provided (pre-selected)
        if ($aggregatorID !== null) {
            $columns .= ", aggregatorID";
            $values .= ", ?";
            $types .= "i";
            $params[] = $aggregatorID;
        }
        
        // Try to add latitude/longitude if provided
        if ($latitude !== null && $longitude !== null) {
            $columns .= ", latitude, longitude";
            $values .= ", ?, ?";
            $types .= "dd";
            $params[] = $latitude;
            $params[] = $longitude;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO WasteCollection 
            ($columns) 
            VALUES ($values)
        ");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            return ['success' => true, 'collectionID' => $this->conn->insert_id, 'message' => 'Waste collection recorded successfully.'];
        }
        
        // Get detailed error message
        $errorMsg = $stmt->error;
        if (strpos($errorMsg, 'foreign key constraint') !== false) {
            return ['success' => false, 'message' => 'Invalid user or aggregator. Please refresh the page and try again.'];
        }
        
        return ['success' => false, 'message' => 'Failed to record waste collection: ' . $errorMsg];
    }
    
    // Update waste collection
    public function updateWaste($collectionID, $collectorID, $weight = null, $plasticTypeID = null, $location = null) {
        $updates = [];
        $params = [];
        $types = '';
        
        if ($weight !== null) {
            $updates[] = "weight = ?";
            $params[] = $weight;
            $types .= 'd';
        }
        if ($plasticTypeID !== null) {
            $updates[] = "plasticTypeID = ?";
            $params[] = $plasticTypeID;
            $types .= 'i';
        }
        if ($location !== null) {
            $updates[] = "location = ?";
            $params[] = $location;
            $types .= 's';
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No updates provided.'];
        }
        
        $updates[] = "collectionID = ?";
        $params[] = $collectionID;
        $types .= 'i';
        $params[] = $collectorID;
        $types .= 'i';
        
        $sql = "UPDATE WasteCollection SET " . implode(', ', $updates) . " WHERE collectionID = ? AND collectorID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Waste collection updated successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update waste collection.'];
    }
    
    // Remove waste collection
    public function removeWaste($collectionID, $collectorID) {
        $stmt = $this->conn->prepare("DELETE FROM WasteCollection WHERE collectionID = ? AND collectorID = ? AND statusID = 1");
        $stmt->bind_param("ii", $collectionID, $collectorID);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Waste collection removed successfully.'];
        }
        return ['success' => false, 'message' => 'Cannot remove. Collection may have been accepted or already processed.'];
    }
    
    // List waste collections
    public function listWasteCollections($collectorID) {
        $stmt = $this->conn->prepare("
            SELECT wc.*, pt.typeName, s.statusName 
            FROM WasteCollection wc
            JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            JOIN Status s ON wc.statusID = s.statusID
            WHERE wc.collectorID = ?
            ORDER BY wc.collectionDate DESC
        ");
        $stmt->bind_param("i", $collectorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get nearest aggregators with transparent pricing (only subscribed aggregators)
    // Returns ALL subscribed aggregators, sorted by distance (those with GPS coordinates first)
    public function getNearestAggregators($collectorLat, $collectorLng, $plasticTypeID) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.userID as aggregatorID,
                ar.businessName,
                u.userName,
                u.userContact as contact,
                u.address,
                u.latitude,
                u.longitude,
                u.rating,
                u.totalRatings,
                ap.pricePerKg,
                ar.capacity,
                CASE 
                    WHEN u.latitude IS NOT NULL AND u.longitude IS NOT NULL 
                    THEN (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * 
                    cos(radians(u.longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(u.latitude))))
                    ELSE NULL
                END AS distance
            FROM User u
            JOIN AggregatorRegistration ar ON u.userID = ar.userID
            JOIN AggregatorPricing ap ON u.userID = ap.aggregatorID
            INNER JOIN Subscriptions s ON u.userID = s.userID
            WHERE u.userRole = 'Aggregator' 
            AND u.status = 'active'
            AND ap.plasticTypeID = ?
            AND ap.isActive = TRUE
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
            ORDER BY 
                CASE WHEN u.latitude IS NOT NULL AND u.longitude IS NOT NULL THEN 0 ELSE 1 END,
                distance ASC,
                u.userName
        ");
        $stmt->bind_param("dddi", $collectorLat, $collectorLng, $collectorLat, $plasticTypeID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Assign aggregator to collection
    public function assignAggregator($collectionID, $collectorID, $aggregatorID) {
        $stmt = $this->conn->prepare("
            UPDATE WasteCollection 
            SET aggregatorID = ? 
            WHERE collectionID = ? AND collectorID = ? AND statusID = 1
        ");
        $stmt->bind_param("iii", $aggregatorID, $collectionID, $collectorID);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Aggregator assigned successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to assign aggregator.'];
    }
    
    // Get payment history
    public function getPayments($collectorID) {
        $stmt = $this->conn->prepare("
            SELECT p.*, wc.collectionID, pt.typeName, p.grossAmount, p.platformFee
            FROM Payment p
            LEFT JOIN WasteCollection wc ON p.collectionID = wc.collectionID
            LEFT JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            WHERE p.toUserID = ?
            ORDER BY p.createdAt DESC
        ");
        $stmt->bind_param("i", $collectorID);
        $stmt->execute();
        return $stmt->get_result();
    }
}

