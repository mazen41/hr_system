<?php
$doc_id                 = 29;
require($coreDir.'sheard/functions/inv-payments-fun.php');

$msg="";
$result=true;
$rows=$_POST['rows'];
$date_range=$_POST['date_range'];
$branchs=!empty($_POST['branchs'])?$_POST['branchs']:null;
$treasur=$_POST['treasur'];
$payment_methods=$_POST['payment_methods'];
$treasur=$_POST['treasur'];
$payment_methods=$_POST['payment_methods'];


$treasur_account = !empty($treasur) ? getTreasurAccount($connect_pdo,$treasur) : null;

$salary_summary = [

    floatval(str_replace(',','',$_POST['sum_salary'])),
    floatval(str_replace(',','',$_POST['sum_incentive'])),
    floatval(str_replace(',','',$_POST['sum_benefit'])),
    floatval(str_replace(',','',$_POST['net_salary'])),
    floatval(str_replace(',','',$_POST['sum_advance'])),
    floatval(str_replace(',','',$_POST['sum_dection']))
];
 
$account_side = [
    
    "D",
    "D",
    "D",

    "C",
    "C",
    "C"
];
$currency=$_POST['currency'];

// اولا يتم الفحص هل قد تم صرف المرتبات حق هذا الشهر والسنه او لا 
$date_range = [];
$range = explode(' - ',$_POST["date_range"]);
$date_range[1] = date('Y-m-d',strtotime($range[1]));
$month = date('m', strtotime($range[1])); // رقم الشهر
$year = date('Y', strtotime($range[1]));  // السنه

function check_is_found($connect, $month, $year, $branches_array)
{
    $sql = "SELECT Id FROM salary_registration WHERE month = :month AND year = :year";
    
    // بناء شروط FIND_IN_SET
    $conditions = [];
    foreach ($branches_array as $index => $branch_id) {
        $conditions[] = "FIND_IN_SET(:branch$index, BranchID)";
    }

    if (!empty($conditions)) {
        $sql .= " AND (" . implode(' OR ', $conditions) . ")";
    }

    $sql .= " LIMIT 1";

    $stmt = $connect->prepare($sql);
    $stmt->bindValue(':month', $month, PDO::PARAM_STR);
    $stmt->bindValue(':year', $year, PDO::PARAM_STR);

    // ربط كل فرع
    foreach ($branches_array as $index => $branch_id) {
        $stmt->bindValue(":branch$index", $branch_id, PDO::PARAM_STR);
    }

    $stmt->execute();
    return $stmt->rowCount() > 0;
}


function account_is_found($connect, $id)
{
    $sql = "SELECT AccountID  FROM  tblaccountguide WHERE AccountID = :id LIMIT 1";
    $stmt = $connect->prepare($sql);
     $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// 
function clean_money($value) {
    // إزالة جميع الحروف غير الأرقام، النقطة، الفاصلة
    $value = preg_replace('/[^\d\.\,]/', '', $value);

    // إزالة الفاصلة (,) لأنها تفصل الآلاف
    $value = str_replace(',', '', $value);

    // نحول النص إلى رقم موجب (float)
    return number_format(abs(floatval($value)), 2, '.', '');
}

function create_salary_registration($connect,$registration_id,$registration_id_end,$month,$year,$branch,$user,$today_date,$now_date){
    $query = "INSERT INTO   salary_registration SET
        registration_id     =:registration_id,
        registration_id_end      =:registration_id_end,
        month        =:month,
        year        =:year,
        BranchID        =:BranchID,
        created_by        =:created_by,
        created_date            =:created_date,
        lastupdatedate            =:lastupdatedate
    ";
    $stm = $connect->prepare($query);
    $stm->execute(
            array(
                'registration_id'    => $registration_id,
                'registration_id_end'     => $registration_id_end, 
                'month'        => $month, 
                'year'        => $year,
                'BranchID'        => $branch, 
                'created_by'        => $user, 
                'created_date'            => $today_date, 
                'lastupdatedate'            => $now_date
            
            )
    );  
    $created_id = $connect->lastInsertId();
    if($created_id > 0){
        return $created_id;
    }
            return false;
}
function create_emp_salary($connect,$UserID,$incentive,$benefit,$advances,$deductions,$absent_salary,$net_salary,$end_salary,$month,$id_registration,$user,$today_date,$now_date){
    $query = "INSERT INTO   emp_salary SET
        UserID     =:UserID,
        incentive      =:incentive,
        benefit        =:benefit,
        advances        =:advances,
        deductions        =:deductions,
        absent_salary        =:absent_salary,
        net_salary        =:net_salary,
        end_salary        =:end_salary,
        month        =:month,
         id_registration        =:id_registration,
          created_by        =:created_by,
        created_date            =:created_date,
        lastupdatedate            =:lastupdatedate
    ";
    $stm = $connect->prepare($query);
    $stm->execute(
            array(
                'UserID'    => $UserID,
                'incentive'    => $incentive,
                'benefit'    => $benefit,
                'advances'    => $advances,
                'deductions'    => $deductions,
                'absent_salary'     => $absent_salary, 
                'net_salary'        => $net_salary, 
                'end_salary'        => $end_salary, 
                'month'        => $month, 
                'id_registration'        => $id_registration, 
                'created_by'        => $user, 
                'created_date'            => $today_date, 
                'lastupdatedate'            => $now_date
            
            )
    );  
    $created_id = $connect->lastInsertId();
    if($created_id > 0){
        return true;
    }
            return false;
}

//
//
function create_remain($connect,$UserID,$remain_salary,$date,$user,$today_date,$now_date){
    $query = "INSERT INTO   salary_before_this_month SET
        UserID     =:UserID,
        remain_salary      =:remain_salary,
        date        =:date,
        createdby        =:createdby,
        created_date        =:created_date,
        lastupdatedate        =:lastupdatedate
    ";
    $stm = $connect->prepare($query);
    $stm->execute(
            array(
                'UserID'    => $UserID,
                'remain_salary'    => $remain_salary,
                'date'    => $date,
                'createdby'    => $user,
                'created_date'    => $today_date,
                'lastupdatedate'     => $now_date
            
            )
    );  
    $created_id = $connect->lastInsertId();
    if($created_id > 0){
        return true;
    }
            return false;
}
//
if(empty($branchs))
    {
    $msg="يجب تحديد الفروع المراد صرف الرواتب لموظفيها";
    $result=False; 
    } 
    elseif(empty($rows))
    {
        $msg="لايوجد رواتب موظفين ";
    $result=False; 
    }
elseif(check_is_found($connect_pdo, $month, $year,$branchs))
{
    $msg="تم صرف الرواتب لهذا الشهر  وهذه الفروع او يوجد احد الفروع قد تم صرف الرواتب له قم ب ازالتة من ضمن الفروع";
    $result=False;
}
elseif(empty($payment_methods))
{
    $msg="يجب اختيار طرق الدفع";
    $result=False;
}
elseif(empty($treasur))
{
    $msg="يجب اختيار طرق الخزينة";
    $result=False;
}
elseif(empty($treasur_account))
{
    $msg="حساب الخزينة غير موجود";
    $result=False;
}
elseif(empty($_POST['account_id_0']))
{
    $msg="اختر حساب مرتبات الموظفين";
    $result=False;
}
elseif(empty($_POST['account_id_1']))
{
    $msg="اختر حساب مكافئات الموظفين";
    $result=False;
}
elseif(empty($_POST['account_id_2']))
{
    $msg="اختر حساب تعويضات الموظفين";
    $result=False;
}
elseif(empty($_POST['account_id_3']))
{
    $msg="اختر حساب سلف الموظفين";
    $result=False;
}
elseif(empty($_POST['account_id_4']))
{
    $msg="اختر حساب خصومات الموظفين";
    $result=False;
}
elseif(empty($_POST['account_id_5']))
{
    $msg="اختر حساب مستحقات الموظفين";
    $result=False;
}elseif(!account_is_found($connect_pdo, $_POST['account_id_0']))
{
    $msg=" حساب مرتبات الموظفين غير موجود";
    $result=False;
}
elseif(!account_is_found($connect_pdo, $_POST['account_id_1']))
{
    $msg=" حساب مكافئات الموظفين غير موجود";
    $result=False;
}
elseif(!account_is_found($connect_pdo, $_POST['account_id_2']))
{
    $msg=" حساب تعويضات الموظفين غير موجود";
    $result=False;
}
elseif(!account_is_found($connect_pdo, $_POST['account_id_3']))
{
    $msg=" حساب سلف الموظفين غير موجود";
    $result=False;
}
elseif(!account_is_found($connect_pdo, $_POST['account_id_4']))
{
    $msg=" حساب خصومات الموظفين غير موجود";
    $result=False;
}
elseif(!account_is_found($connect_pdo, $_POST['account_id_5']))
{
    $msg=" حساب مستحقات الموظفين غير موجود";
    $result=False;
}
// account_is_found($connect, $id)
else{

    $acount_id = [
    
    $_POST['account_id_0'],
    $_POST['account_id_1'],
    $_POST['account_id_2'],

    $_POST['account_id_5'],
    $_POST['account_id_4'],
    $_POST['account_id_3'],
];

$branches = implode(',', $_POST["branchs"]);


// 
require $coreDir.'sheard/classes/double-entry-class.php';

$IntryClass = new Account($doc_id,$branch);




$data = array(
'TheDate'		=>$today_date,
'IsCredend'	=> 1,
'RecordID'		=> '',  // يتم تحديثة لاحقاً 
'RecordNunmber'	=> null, // تلقائي
'Notes'			=>"صرف مستحقات الموظفين",
'UserID'		=>$user,
'BranchID'		=>$branch, 
'RowTime'		=>$now_date,
'RowVersion'	=>$now_date
); 

 
$header_entry = array(
'TheDate'		=> $today_date,
'IsCredend'	=> 1, // تلقائي

'RecordID'		=> '', // الفاتورة ID
'RecordNunmber'	=> null,  // موجود
'Notes'			=> "استحقاق رواتب الموظفين",
'UserID'		=> $user,
'BranchID'		=> $branch,
'RowTime'		=> $now_date,
'RowVersion'	=> $now_date
);
$header_intry_id = $IntryClass->insertNewIntry($connect_pdo,$data);

                    if($header_intry_id > 0){
                        $total = 0 ;
                        
                        $lines = 0;
                        for($count = 0; $count < 6; $count++){
                                    $entry_detail = array(
                                        'ParentID'  		=> $header_intry_id,
                                        'CostCenterID' 			=> null,
                                        'Notes' 			=> null,
                                        'UserID' 			=> $user,
                                       // 'BranchID' 			=> $branch,
                                        'CurrencyID'  		=>  $currency,
                                        'ExchangePrice'  	=> 1,
                                        'Amount'  		    => $salary_summary[$count],
                                    );
                                    $account_id      = $acount_id[$count];  
                                    $entry_side=$account_side[$count];
                                    
                                    $insert_detail = $IntryClass->detailEntryInsert($connect_pdo, $entry_detail,$account_id,$entry_side);
                                    if($insert_detail['id'] > 0){
                                        $total = $total + $insert_detail['amount'];
                                    }
                                    
                                    $lines ++;
                                    $result = true; 
                                    $msg = !empty($header_intry_id) ? 'تم حفظ التغييرات' : 'تم اضافة القيد';
                                    $_SESSION['alert'] = $msg;
                                    $_SESSION['alert_style'] = 'success';
                                    $reload = true;     
                        } // end lines loop
                     
                // $IntryClass->UpdateHeaderIntry($connect_pdo,$header_intry_id,$total);
                } 
                    //   insert auther قيد
                    $header_intry_id_2 = $IntryClass->insertNewIntry($connect_pdo,$header_entry);
                     if($header_intry_id_2 > 0)
                        {
                        $entry_detail_2 = array(
                                        'ParentID'  		=> $header_intry_id_2,
                                        'CostCenterID' 			=> null,
                                        'Notes' 			=> null,
                                        'UserID' 			=> $user,
                                       // 'BranchID' 			=> $branch,
                                        'CurrencyID'  		=>  $currency,
                                        'ExchangePrice'  	=> 1,
                                        'Amount'  		    => $salary_summary[3],
                                    );
                                    $total_2=0;
                            $insert_detail_2 = $IntryClass->detailEntryInsert($connect_pdo, $entry_detail_2,$acount_id[3],'D');
                            if($insert_detail_2['id'] > 0){
                                        $total_2 = $total_2 + $insert_detail_2['amount'];
                                    }
                            $insert_detail_2 = $IntryClass->detailEntryInsert($connect_pdo, $entry_detail_2,$treasur_account,'C');
                            if($insert_detail_2['id'] > 0){
                                        $total_2 = $total_2 + $insert_detail_2['amount'];
                                    }

                         $IntryClass->UpdateHeaderIntry($connect_pdo,$header_intry_id_2,$total_2); 
                        }
                        // اضافة الى جدول الرواتب
                     $id_registration_to_emp=create_salary_registration($connect_pdo,$header_intry_id,$header_intry_id_2,$month,$year,$branches,$user,$today_date,$now_date);
                    foreach ($rows as $row) {
                    //    if(! emp_is_found($connect_pdo, $row[0],$month, $year))
                            create_emp_salary($connect_pdo,$row[0],clean_money($row[9])   ,clean_money($row[8]) ,clean_money($row[5])  ,clean_money($row[6])    ,clean_money($row[7])      ,clean_money($row[3])    ,clean_money($row[13])    ,$month,$id_registration_to_emp,$user,$today_date,$now_date);
                            if($row[13] <0){
                            create_remain($connect_pdo,$row[0],clean_money($row[13]) ,$date_range[1],$user,$today_date,$now_date);
                            }
                    }







                    



// end else
}
$output = array(
 'result'   => $result,
 'msg'   => $msg,
 "salary_summary"=>$salary_summary,
 "date_range"=>$date_range,
 "row"=>$rows,
 "month"=>$month,
 "date_1"=>$date_range[1],
);

echo json_encode($output);

?>