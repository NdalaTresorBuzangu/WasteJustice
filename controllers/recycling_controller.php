<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/recycling_class.php';

class RecyclingController {
    private $recycling;
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->recycling = new RecyclingClass($connection);
    }
    
    public function getAvailableBatches($plasticTypeID = null) {
        return $this->recycling->getAvailableBatches($plasticTypeID);
    }
    
    public function verifyAndPurchase($batchID, $companyID, $qualityVerified = true) {
        return $this->recycling->verifyAndPurchase($batchID, $companyID, $qualityVerified);
    }
    
    public function getPurchaseHistory($companyID) {
        return $this->recycling->getPurchaseHistory($companyID);
    }
    
    public function getPaymentHistory($companyID) {
        return $this->recycling->getPaymentHistory($companyID);
    }
    
    public function leaveFeedback($fromCompanyID, $toAggregatorID, $batchID, $rating, $comment) {
        return $this->recycling->leaveFeedback($fromCompanyID, $toAggregatorID, $batchID, $rating, $comment);
    }
}

