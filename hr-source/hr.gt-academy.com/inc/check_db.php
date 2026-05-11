<?php
require_once __DIR__ . '/config.php';
$r = $connect_pdo->query("SELECT id, name FROM reports LIMIT 5");
while($row = $r->fetch()) {
    echo $row['id'] . ': ' . $row['name'] . "\n";
}
echo "---\n";
$u = $connect_pdo->query("SELECT UserID, FirstName, LastName FROM tblusers LIMIT 5");
while($row = $u->fetch()) {
    echo $row['UserID'] . ': ' . $row['FirstName'] . ' ' . $row['LastName'] . "\n";
}
