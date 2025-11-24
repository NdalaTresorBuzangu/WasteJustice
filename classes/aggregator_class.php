<?php
require_once __DIR__ . '/../config/config.php';

class AggregatorClass {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // View pending deliveries
    public function getPendingDeliveries($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT wc.*, pt.typeName, u.userName as collectorName, u.userContact as collectorContact,
                   ap.pricePerKg as suggestedPrice
            FROM WasteCollection wc
            JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            JOIN User u ON wc.collectorID = u.userID
            JOIN AggregatorPricing ap ON wc.aggregatorID = ap.aggregatorID AND wc.plasticTypeID = ap.plasticTypeID
            WHERE wc.aggregatorID = ? AND wc.statusID = 1
            ORDER BY wc.collectionDate DESC
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Accept delivery
    public function acceptDelivery($collectionID, $aggregatorID) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Update collection status to accepted
            $stmt1 = $this->conn->prepare("UPDATE WasteCollection SET statusID = 2 WHERE collectionID = ? AND aggregatorID = ?");
            $stmt1->bind_param("ii", $collectionID, $aggregatorID);
            $stmt1->execute();
            
            // Get collection details for payment calculation
            $stmt2 = $this->conn->prepare("
                SELECT wc.weight, ap.pricePerKg, wc.collectorID 
                FROM WasteCollection wc
                JOIN AggregatorPricing ap ON wc.aggregatorID = ap.aggregatorID AND wc.plasticTypeID = ap.plasticTypeID
                WHERE wc.collectionID = ?
            ");
            $stmt2->bind_param("i", $collectionID);
            $stmt2->execute();
            $details = $stmt2->get_result()->fetch_assoc();
            
            // Calculate gross amount (original amount)
            $grossAmount = $details['weight'] * $details['pricePerKg'];
            
            // Calculate 1% platform fee for WasteJustice
            $platformFee = $grossAmount * 0.01;
            
            // Net amount that collector receives (99% of gross)
            $netAmount = $grossAmount - $platformFee;
            
            // Create pending payment with fee tracking
            $stmt3 = $this->conn->prepare("
                INSERT INTO Payment (collectionID, fromUserID, toUserID, amount, platformFee, grossAmount, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt3->bind_param("iiiddd", $collectionID, $aggregatorID, $details['collectorID'], $netAmount, $platformFee, $grossAmount);
            $stmt3->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Delivery accepted. Payment will be processed.', 'amount' => $netAmount, 'platformFee' => $platformFee, 'grossAmount' => $grossAmount];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to accept delivery: ' . $e->getMessage()];
        }
    }
    
    // Reject delivery
    public function rejectDelivery($collectionID, $aggregatorID) {
        $stmt = $this->conn->prepare("UPDATE WasteCollection SET statusID = 3, aggregatorID = NULL WHERE collectionID = ? AND aggregatorID = ?");
        $stmt->bind_param("ii", $collectionID, $aggregatorID);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Delivery rejected successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to reject delivery.'];
    }
    
    // Get accepted waste batches
    public function getAcceptedWaste($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT 
                wc.plasticTypeID,
                pt.typeName,
                SUM(wc.weight) as totalWeight,
                GROUP_CONCAT(wc.collectionID) as collectionIDs
            FROM WasteCollection wc
            JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            WHERE wc.aggregatorID = ? AND wc.statusID = 2
            GROUP BY wc.plasticTypeID
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Create batch for sale
    public function createBatch($aggregatorID, $plasticTypeID, $collectionIDs) {
        // Get total weight
        $ids = explode(',', $collectionIDs);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt1 = $this->conn->prepare("SELECT SUM(weight) as totalWeight FROM WasteCollection WHERE collectionID IN ($placeholders)");
        $stmt1->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt1->execute();
        $totalWeight = $stmt1->get_result()->fetch_assoc()['totalWeight'];
        
        // Create batch
        $stmt2 = $this->conn->prepare("
            INSERT INTO AggregatorBatch (aggregatorID, plasticTypeID, totalWeight, collectionIDs, statusID)
            VALUES (?, ?, ?, ?, 5)
        ");
        $stmt2->bind_param("iids", $aggregatorID, $plasticTypeID, $totalWeight, $collectionIDs);
        
        if ($stmt2->execute()) {
            // Update collections to delivered
            $stmt3 = $this->conn->prepare("UPDATE WasteCollection SET statusID = 4 WHERE collectionID IN ($placeholders)");
            $stmt3->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt3->execute();
            
            return ['success' => true, 'batchID' => $this->conn->insert_id, 'message' => 'Batch created successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to create batch.'];
    }
    
    // Get available companies with prices for selling (only subscribed companies)
    public function getCompaniesWithPrices($plasticTypeID) {
        $stmt = $this->conn->prepare("
            SELECT u.userID, cr.companyName, cp.pricePerKg
            FROM User u
            JOIN CompanyRegistration cr ON u.userID = cr.userID
            JOIN CompanyPricing cp ON u.userID = cp.companyID
            INNER JOIN Subscriptions s ON u.userID = s.userID
            WHERE u.userRole = 'Recycling Company'
            AND u.status = 'active'
            AND cp.plasticTypeID = ?
            AND cp.isActive = 1
            AND s.paymentStatus = 'Success'
            AND s.isActive = 1
            AND (s.subscriptionEnd IS NULL OR s.subscriptionEnd >= CURDATE())
            ORDER BY cp.pricePerKg DESC
        ");
        $stmt->bind_param("i", $plasticTypeID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Sell batch to company
    public function sellBatchToCompany($batchID, $aggregatorID, $companyID) {
        $this->conn->begin_transaction();
        
        try {
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
                throw new Exception('Company does not have an active subscription.');
            }
            
            // Get batch and company price
            $stmt1 = $this->conn->prepare("
                SELECT ab.totalWeight, ab.plasticTypeID, cp.pricePerKg
                FROM AggregatorBatch ab
                JOIN CompanyPricing cp ON ab.plasticTypeID = cp.plasticTypeID AND cp.companyID = ?
                WHERE ab.batchID = ? AND ab.aggregatorID = ? AND ab.statusID = 5
            ");
            $stmt1->bind_param("iii", $companyID, $batchID, $aggregatorID);
            $stmt1->execute();
            $batch = $stmt1->get_result()->fetch_assoc();
            
            if (!$batch) {
                throw new Exception('Batch not found or already sold.');
            }
            
            // Calculate gross sale price (original amount)
            $grossSalePrice = $batch['totalWeight'] * $batch['pricePerKg'];
            
            // Calculate 1% platform fee for WasteJustice
            $platformFee = $grossSalePrice * 0.01;
            
            // Net amount that aggregator receives (99% of gross)
            $netSalePrice = $grossSalePrice - $platformFee;
            
            // Update batch with gross sale price
            $stmt2 = $this->conn->prepare("
                UPDATE AggregatorBatch 
                SET companyID = ?, salePrice = ?, statusID = 6, soldAt = NOW()
                WHERE batchID = ?
            ");
            $stmt2->bind_param("idi", $companyID, $grossSalePrice, $batchID);
            $stmt2->execute();
            
            // Create payment with fee tracking
            $stmt3 = $this->conn->prepare("
                INSERT INTO Payment (batchID, fromUserID, toUserID, amount, platformFee, grossAmount, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt3->bind_param("iiiddd", $batchID, $companyID, $aggregatorID, $netSalePrice, $platformFee, $grossSalePrice);
            $stmt3->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Batch sold successfully.', 'salePrice' => $netSalePrice, 'platformFee' => $platformFee, 'grossAmount' => $grossSalePrice];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to sell batch: ' . $e->getMessage()];
        }
    }
    
    // Get earnings dashboard
    public function getEarnings($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as totalEarnings,
                COUNT(DISTINCT ab.batchID) as batchesSold,
                COUNT(DISTINCT wc.collectionID) as collectionsAccepted
            FROM User u
            LEFT JOIN AggregatorBatch ab ON u.userID = ab.aggregatorID
            LEFT JOIN WasteCollection wc ON u.userID = wc.aggregatorID AND wc.statusID = 2
            LEFT JOIN Payment p ON p.toUserID = u.userID
            WHERE u.userID = ?
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Get feedback received
    public function getFeedback($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT f.*, u.userName as fromUserName
            FROM Feedback f
            JOIN User u ON f.fromUserID = u.userID
            WHERE f.toUserID = ?
            ORDER BY f.createdAt DESC
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get sold batches
    public function getSoldBatches($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT ab.*, pt.typeName, cr.companyName, p.status as paymentStatus, p.amount as netAmount, p.platformFee, p.grossAmount
            FROM AggregatorBatch ab
            JOIN PlasticType pt ON ab.plasticTypeID = pt.plasticTypeID
            LEFT JOIN User u ON ab.companyID = u.userID
            LEFT JOIN CompanyRegistration cr ON u.userID = cr.userID
            LEFT JOIN Payment p ON ab.batchID = p.batchID AND p.toUserID = ?
            WHERE ab.aggregatorID = ? AND ab.statusID = 6
            ORDER BY ab.soldAt DESC
        ");
        $stmt->bind_param("ii", $aggregatorID, $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get pending payments (payments that need to be processed)
    public function getPendingPayments($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT p.*, wc.collectionID, wc.weight, pt.typeName,
                   u.userName as collectorName, u.userEmail as collectorEmail, u.userContact as collectorContact
            FROM Payment p
            JOIN WasteCollection wc ON p.collectionID = wc.collectionID
            JOIN PlasticType pt ON wc.plasticTypeID = pt.plasticTypeID
            JOIN User u ON p.toUserID = u.userID
            WHERE p.fromUserID = ? AND p.status = 'pending' AND p.collectionID IS NOT NULL
            ORDER BY p.createdAt DESC
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get payment history
    public function getPaymentHistory($aggregatorID) {
        $stmt = $this->conn->prepare("
            SELECT p.*, wc.collectionID, ab.batchID, pt.typeName,
                   u1.userName as fromUserName, u2.userName as toUserName
            FROM Payment p
            LEFT JOIN WasteCollection wc ON p.collectionID = wc.collectionID
            LEFT JOIN AggregatorBatch ab ON p.batchID = ab.batchID
            LEFT JOIN PlasticType pt ON (wc.plasticTypeID = pt.plasticTypeID OR ab.plasticTypeID = pt.plasticTypeID)
            LEFT JOIN User u1 ON p.fromUserID = u1.userID
            LEFT JOIN User u2 ON p.toUserID = u2.userID
            WHERE p.toUserID = ?
            ORDER BY p.createdAt DESC
            LIMIT 20
        ");
        $stmt->bind_param("i", $aggregatorID);
        $stmt->execute();
        return $stmt->get_result();
    }
}

