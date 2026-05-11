<?php
// Test fingerprint attendance logs API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['draw'] = 1;
$_POST['device_id'] = 4;
$_GET['action'] = 'fingerprint-attendance-logs';

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

// Include the API handler
include 'hr-app/index.php';
