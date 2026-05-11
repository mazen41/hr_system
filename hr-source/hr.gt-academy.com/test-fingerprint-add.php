<?php
// Test fingerprint-add page data loading
$_GET['id'] = 4;
$_SERVER['REQUEST_METHOD'] = 'GET';

// Initialize session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1];
$user = 1;
$branch = 1;

// Include config
require_once 'inc/config.php';
require_once 'inc/User.php';
require_once 'inc/functions.php';

// Simulate User class for testing
class MockUser {
    public function allBranches($branches) {
        return [1 => 'شركة صدى الملاعب للملابس الرياضية'];
    }
}
$User = new MockUser();

// Include the fingerprint-add page logic
include 'fingerprint-add.php';
