<?php
// Test fingerprint-add API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['id'] = 4;
$_POST['devicetname'] = 'Test Device Updated';
$_POST['branchs_list'] = 1;
$_POST['decvicestate'] = 1;
$_POST['devicetype'] = 'ZKTeco';
$_POST['deviceserialnumber'] = 'ZK-TEST-001';
$_POST['ip'] = '192.168.1.100';
$_POST['port'] = '4370';
$_GET['action'] = 'fingerprint-add';

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
