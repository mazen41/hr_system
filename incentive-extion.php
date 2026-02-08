<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
    $index = $_POST['value'];
    $type=$_POST["parent"];
    $msg='';
    $result=false;
    $data = array();
    $data_ = array();
if(empty($_POST['BranchID']))
{
    $msg="اختر الفرع اولا";
    $result=false;
}
elseif(empty($_POST['value']))
{
    $msg="يرجى اخيتار الفئة او المجموعه او الموظف على حسب اخيارك";
    $result=false;  
}
else{
    $result=true;
    $branches = $_POST['BranchID'];
        if (is_array($branches)) {
            $branches = implode(",", $branches); 
        }
        if (is_array($index)) {
            $index = implode(",", $index); 
        }
    if($type==2)
    {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name , t.GroupID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.GroupID in($index)       
        and  u.isemp is not null and resigned_or_dismissed is  null 
        and '$today_date' between t.new_s_date and t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name , t.GroupID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.GroupID in($index) 
       and  u.isemp is not null and resigned_or_dismissed is not null 
        or '$today_date' not between t.new_s_date and t.new_e_date";
    }
    if($type==3)
    {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name,t.GradeID 
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.GradeID in($index)       
        and  u.isemp is not null and resigned_or_dismissed is  null 
        and '$today_date' between t.new_s_date and t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name,t.GradeID 
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.GradeID in($index) 
        and  u.isemp is not null and resigned_or_dismissed is not null 
        or '$today_date' not between t.new_s_date and t.new_e_date";
    }
    if($type==4)
    {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.SectionID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.SectionID in($index)        
        and  u.isemp is not null and resigned_or_dismissed is  null 
        and '$today_date'  between t.new_s_date and t.new_e_date ";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.SectionID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and t.SectionID in($index) 
        and  u.isemp is not null and resigned_or_dismissed is not null 
        or '$today_date' not between t.new_s_date and t.new_e_date
        ";
    }
    if($type==5)
    {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.BranchID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        
         WHERE t.BranchID IN ($branches)and t.jobtitleID in($index)         
         and  u.isemp is not null and resigned_or_dismissed is  null 
        and '$today_date' between t.new_s_date and t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.BranchID
        FROM tblusers u
        LEFT JOIN  tblremewal AS t ON u.lastversion = t.Id
        WHERE t.BranchID IN ($branches)and t.jobtitleID in($index)  
         and  u.isemp is not null and resigned_or_dismissed is not null 
        or '$today_date' not between t.new_s_date and t.new_e_date";
    }
// // section

$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll();

$stmt_ = $connect_pdo->prepare($query_);
$stmt_->execute();
$results_ = $stmt_->fetchAll();

foreach($results as $row)
{
$data_array = array();
$data_array['data']=   array( 
'id' => $row['ID'], 'name' => $row['Name']); 
$data[] = $data_array;
}

foreach($results_ as $row_)
{
$data_array_ = array();
$data_array_['data']=   array( 
'id' => $row_['ID'], 'name' => $row_['Name']); 
$data_[] = $data_array_;
}



}
$output = array(
"result"=>$result,
"data"    => $data,
"data_"    => $data_,
"msg"=>$msg

);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
