<?php
// Test fingerprint-list API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['draw'] = 1;
$_POST['is_date_search'] = 'no';
$_POST['date_range'] = '';
$_POST['branchs'] = [];
$_POST['states'] = '';
$_GET['action'] = 'fingerprint-list';

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
