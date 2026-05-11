<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';
require_once __DIR__ . '/inc/AuditLog.php';
require_once __DIR__ . '/inc/functions.php';
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die();
}

if (isset($_POST['value'])) {
    $emp = $_POST['value'];
    $branch = $_POST['branch'];
    $for_whats = $_POST['for_whats'];

    $placeholders = implode(',', array_fill(0, count($emp), '?'));

    $query = "SELECT a.Id, a.UserID, b.branch_name, a.name, a.Status, a.LastUpdateDate, a.beneft_type, a.monthly, a.DueDate,
                     a.Amount, a.AmountType, a.Currency, u.FirstName, u.LastName, a.for_what
              FROM tblbenefit AS a
              LEFT JOIN branches AS b ON a.BranchID = b.branch_id
              LEFT JOIN tblusers AS u ON a.UserID = u.UserID
              WHERE a.BranchID = $branch AND a.UserID IN ($placeholders) AND a.for_what = $for_whats";

    $stmt = $connect_pdo->prepare($query);
    $stmt->execute($emp);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array();
    foreach ($result as $row) {
        $data[] = [
            'name'     => $row['name'],
            'branch'   => $row['branch_name'],
            'date'     => ($row['beneft_type'] == 1) ? 'شهرية' : 'تاريخ محدد',
            'type'     => ($row['beneft_type'] == 1)
                            ? 'نهاية كل شهر'
                            : (
                                ($row['monthly'] == 1)
                                ? "دورية خلال الفترة <br>{$row['DueDate']}"
                                : "مرة واحدة في <br>{$row['DueDate']}"
                            ),
            'money'    => $row['Amount'] . ' ' . (($row['AmountType'] == 'avg') ? '%' : $row['Currency']),
            'username' => $row['FirstName'] . ' ' . $row['LastName'],
            'check'    => ($row['Status'] == 1) ? 'معتمد' : 'غير معتمد',
            'link'     => '<a href="report-one-benefit?id=' . $row['Id'] . '" class="btn btn-info btn-sm show-details" data-id="' . $row['Id'] . '" title="تفاصيل"><i class="fa fa-eye"></i></a>',
        ];
    }

    $output = array(
        "section" => $data,
    );

    echo json_encode($output, JSON_UNESCAPED_UNICODE);
}
?>