<?php
// 1. Session and Security Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    die("Forbidden");
}

// Set header for JSON output
header('Content-Type: application/json; charset=utf-8');

/** 
 * PREPARE BRANCH IDs 
 * We convert keys to integers to prevent SQL injection in the IN() clause
 */
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_map('intval', array_keys($allowed_branches)); 

// If user has no branches, return empty arrays to prevent SQL errors
if (empty($branch_ids)) {
    echo json_encode([
        "section" => [], "jobtitle" => [], "groub_list" => [], 
        "JobGrade" => [], "Shift" => [], "branch" => []
    ]);
    exit;
}

$allowed_branch_str = implode(',', $branch_ids);

/** 
 * DATA FETCHING 
 */

// 1. Sections (Leaf nodes only)
$query_section = "SELECT c.Id, c.Name 
                  FROM tblsection AS c 
                  LEFT JOIN tblsection AS d ON c.Id = d.ParentID 
                  WHERE c.ParentID IS NOT NULL 
                  AND d.Id IS NULL 
                  AND c.BranchID IN ($allowed_branch_str)";
$stmt_section = $connect_pdo->prepare($query_section);
$stmt_section->execute();
$section_list = [];
foreach ($stmt_section->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $section_list[] = ['data' => ['id' => $row['Id'], 'name' => $row['Name']]];
}

// 2. Job Titles
$query_jobtitle = "SELECT Id, Name FROM tbljobtitle WHERE BranchID IN ($allowed_branch_str)";
$stmt_job = $connect_pdo->prepare($query_jobtitle);
$stmt_job->execute();
$jobtitle_list = [];
foreach ($stmt_job->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $jobtitle_list[] = ['data' => ['id' => $row['Id'], 'name' => $row['Name']]];
}

// 3. Groups
$query_group = "SELECT Id, Name FROM tblgroup WHERE BranchID IN ($allowed_branch_str)";
$stmt_group = $connect_pdo->prepare($query_group);
$stmt_group->execute();
$group_list = [];
foreach ($stmt_group->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $group_list[] = ['data' => ['id' => $row['Id'], 'name' => $row['Name']]];
}

// 4. Job Grades
$query_grade = "SELECT Id, Name FROM tbljobgrade WHERE BranchID IN ($allowed_branch_str)";
$stmt_grade = $connect_pdo->prepare($query_grade);
$stmt_grade->execute();
$jobgrade_list = [];
foreach ($stmt_grade->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $jobgrade_list[] = ['data' => ['id' => $row['Id'], 'name' => $row['Name']]];
}

// 5. Shifts
$query_shift = "SELECT ShiftID, ShiftName FROM tbshift WHERE ShiftState=0 AND BranchID IN ($allowed_branch_str)";
$stmt_shift = $connect_pdo->prepare($query_shift);
$stmt_shift->execute();
$shift_list = [];
foreach ($stmt_shift->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $shift_list[] = ['data' => ['id' => $row['ShiftID'], 'name' => $row['ShiftName']]];
}

/** 
 * FINAL OUTPUT 
 */
$output = [
    "section"    => $section_list,
    "jobtitle"   => $jobtitle_list,
    "groub_list" => $group_list,
    "JobGrade"   => $jobgrade_list,
    "Shift"      => $shift_list,
    "branch"     => $allowed_branches
];

echo json_encode($output, JSON_UNESCAPED_UNICODE);