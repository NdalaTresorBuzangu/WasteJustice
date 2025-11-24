<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/aggregator_class.php';

class AggregatorController {
    private $aggregator;
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->aggregator = new AggregatorClass($connection);
    }
    
    public function getPendingDeliveries($aggregatorID) {
        return $this->aggregator->getPendingDeliveries($aggregatorID);
    }
    
    public function acceptDelivery($collectionID, $aggregatorID) {
        return $this->aggregator->acceptDelivery($collectionID, $aggregatorID);
    }
    
    public function rejectDelivery($collectionID, $aggregatorID) {
        return $this->aggregator->rejectDelivery($collectionID, $aggregatorID);
    }
    
    public function getAcceptedWaste($aggregatorID) {
        return $this->aggregator->getAcceptedWaste($aggregatorID);
    }
    
    public function createBatch($aggregatorID, $plasticTypeID, $collectionIDs) {
        return $this->aggregator->createBatch($aggregatorID, $plasticTypeID, $collectionIDs);
    }
    
    public function getCompaniesWithPrices($plasticTypeID) {
        return $this->aggregator->getCompaniesWithPrices($plasticTypeID);
    }
    
    public function sellBatchToCompany($batchID, $aggregatorID, $companyID) {
        return $this->aggregator->sellBatchToCompany($batchID, $aggregatorID, $companyID);
    }
    
    public function getEarnings($aggregatorID) {
        return $this->aggregator->getEarnings($aggregatorID);
    }
    
    public function getFeedback($aggregatorID) {
        return $this->aggregator->getFeedback($aggregatorID);
    }
}

