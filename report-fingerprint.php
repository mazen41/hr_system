<?php
//session_start();
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();
    $allowed_branches = $User->allBranches($User->branches);
    $branch_ids = array_keys($allowed_branches); 
    $allowed_branch = implode(',', $branch_ids);
  
$parma = array();


$results_note = ["report_time" => $now_date]; 
$results_note += ['selected_period' => ''];   
$results_note += ['selected_branch' => []]; 
$results_note += ['name' =>'']; 
$filter = false;



$query = " SELECT a.FingerprintID ,b.branch_name,a.FingerprintName,a.FingerprintState,a.CreatedDate,
 u.FirstName , u.LastName
FROM  tbfingerprint AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
where a.BranchID in ($allowed_branch)
";

    if($_POST["is_date_search"] == "yes")
    {
        $display_search_note = true; 
    
        if(!empty($_POST["branchs"])){
            $in = "";
            $i = 0;
            foreach($_POST["branchs"] as $item)
            {
                    $key =":branchs".$i++;
                   $in .="$key,";
                    $in_params[$key] = $item;
                    $results_note['selected_branch'] []= "$item";
            }
            $in = rtrim($in,",");
            $query .= "  AND  a.BranchID IN ($in)  ";
            $parma = array_merge($parma,$in_params);        
        }	
    
        if(!empty($_POST["date_range"])){
            $range = explode(' - ',$_POST["date_range"]);
            $date_range[0] = date('Y-m-d',strtotime($range[0]));
            $date_range[1] = date('Y-m-d',strtotime($range[1]));
            $query .= '  AND  a.CreatedDate  BETWEEN  "'.$date_range[0].'" and "'.$date_range[1].'"  ';
            $results_note['selected_period']= '<b>للفترة : </b><span dir="ltr">'.$date_range[0].' - '.$date_range[1].'</span>'; 
        }


        
        if(!empty($_POST["name"])){
            $query .=" AND a.FingerprintName LIKE '%" . $_POST["name"] . "%' ";
            $results_note ['name'] = true; 
        } 
        
        if(!empty($_POST["filter_status"])){
            $status_arry = $_POST["filter_status"];
                $query .= " AND a.FingerprintState = ('$status_arry')";
        }
        
    }

    $query.='order by a.FingerprintID Asc ';

$query1 = '';

if($_POST["length"] != -1)
{
 $query1 = 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
}

$statement = $connect_pdo->prepare($query);

$statement->execute($parma);

$number_filter_row = $statement->rowCount();

$statement = $connect_pdo->prepare($query . $query1);

$statement->execute($parma);

$result = $statement->fetchAll();

$data = array();
$filtered_group ='';
foreach($result as $row)
{
 $sub_array = array();
 $sub_array[] = $row['FingerprintName'];
 $sub_array[] = $row['branch_name'];
 $sub_array[] = (($row['FingerprintState']==1)?'شغال':(($row['FingerprintState']==1)?'موقف':'جاري الصيانة'));
 $sub_array[] = $row['CreatedDate'];
 $sub_array[] = $row['FirstName'].' '.$row['LastName'];
 $sub_array[] = '<a href="report-one-fingerprint?id='.$row['FingerprintID'].'" class="btn btn-info btn-sm show-details" data-id="'.$row['FingerprintID'].'" title="تفاصيل"><i class="fa fa-eye"></i></button>';
 
 $data[] = $sub_array;
}



$output = array(
 'draw'   => intval($_POST['draw']),
 'data'   => $data,
"results_note" => $results_note
);

echo json_encode($output);


//}

?>