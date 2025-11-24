<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/admin_class.php';

class AdminController {
    private $admin;
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->admin = new AdminClass($connection);
    }
    
    public function getAllUsers($role = null) {
        return $this->admin->getAllUsers($role);
    }
    
    public function updateUserStatus($userID, $status) {
        return $this->admin->updateUserStatus($userID, $status);
    }
    
    public function getAnalytics() {
        return $this->admin->getAnalytics();
    }
    
    public function getAllTransactions() {
        return $this->admin->getAllTransactions();
    }
    
    public function getAllBatches() {
        return $this->admin->getAllBatches();
    }
    
    public function getAllFeedback() {
        return $this->admin->getAllFeedback();
    }
    
    public function getPricingOverview() {
        return $this->admin->getPricingOverview();
    }
}

