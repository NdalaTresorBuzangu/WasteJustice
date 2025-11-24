<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/collector_class.php';

class CollectorController {
    private $collector;
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->collector = new CollectorClass($connection);
    }
    
    public function uploadWaste($data) {
        return $this->collector->addWaste(
            $data['collectorID'],
            $data['plasticTypeID'],
            $data['weight'],
            $data['location'],
            $data['notes'] ?? '',
            $data['photoPath'] ?? ''
        );
    }
    
    public function updateWaste($collectionID, $collectorID, $data) {
        return $this->collector->updateWaste(
            $collectionID,
            $collectorID,
            $data['weight'] ?? null,
            $data['plasticTypeID'] ?? null,
            $data['location'] ?? null
        );
    }
    
    public function removeWaste($collectionID, $collectorID) {
        return $this->collector->removeWaste($collectionID, $collectorID);
    }
    
    public function getNearestAggregators($collectorLat, $collectorLng, $plasticTypeID) {
        return $this->collector->getNearestAggregators($collectorLat, $collectorLng, $plasticTypeID);
    }
    
    public function assignAggregator($collectionID, $collectorID, $aggregatorID) {
        return $this->collector->assignAggregator($collectionID, $collectorID, $aggregatorID);
    }
    
    public function getCollections($collectorID) {
        return $this->collector->listWasteCollections($collectorID);
    }
    
    public function getPayments($collectorID) {
        return $this->collector->getPayments($collectorID);
    }
}

