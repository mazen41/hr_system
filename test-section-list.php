<?php
// Test the section-list API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['draw'] = 1;
$_POST['is_date_search'] = 'no';
$_POST['date_range'] = '';
$_POST['branchs'] = [];
$_GET['action'] = 'section-list';

// Include the main file
require_once 'config.php';
require_once 'User.php';
require_once 'functions.php';

// Initialize database
$connect_pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
$connect_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Set up session
session_start();
$_SESSION['user'] = ['UserID' => 1];
$_SESSION['branch'] = 1;
$user = 1;
$branch = 1;

// Include the index.php file
include 'hr-app/index.php';
