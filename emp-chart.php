 <?php
 //session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();
    $data =array(
    'cots' => 0, 'totals' => 0, 'advance_1' => 0, 'advance_2' => 0,
    'xy'  => [],'yv'  => [],  'yvp'      => [], 'style'   => []
    );
    $parma = array(
        'user'    => $user,
    );
    
    $filter_by = null;
    
    if(!empty($_POST["filter_by"])){
        $_POST["filter_by"] = sanitizingData($_POST["filter_by"]);
        
       if(in_array($_POST["filter_by"],['this_month','this_day'])){
           $filter_by = $_POST["filter_by"];
       }
    }

    
$query = "SELECT UserID,Amount,currency,DueDate,Status,type,
cast(DATE_FORMAT(DueDate, '%M') as char) as month
FROM tblempadvances

where Status is not null and UserID=:user
 
";

//$_POST["period"]            = 'ctoday';
 if(!empty($filter_by)){
     
     if($filter_by == 'this_day'){
            $query .=  ' and DueDate = "'.$today_date.'" ';
            
        }elseif($filter_by == 'this_month' ){
            $query .= ' AND MONTH(DueDate) = ' . date('m') . ' AND YEAR(DueDate) = ' . date('Y') . ' ';
    
        }
        else{  }
        

 }

 $stm = $connect_pdo->prepare($query);

$stm->execute($parma);

if($stm->rowCount() > 0){
		
    $result = $stm->fetchAll();

    $p = 0;
    $advance_1  = 0;
    $advance_2  = 0;
    $payed       = 0;
    foreach($result as $row)
        {
            if($row["type"]==1)
            {
                 $advance_1  += $row["Amount"];
            }
            if($row["type"]==2)
            {
                 $advance_2  += $row["Amount"];
            }
            
           
            
            $data['style'][]  = '#' . rand(100000, 999999) . '';
            $p ++;
        }
         $data['cots']     = $p;
        $data['xy'][]     = $row["month"];
        $data['yv'][] =$advance_1;
        $data['yvp'][] =$advance_2;
        $data['totals']           = $advance_1+$advance_2; 
        $data['advance_1']     = $advance_1; 
        $data['advance_2']   = $advance_2;


    
        
} 
echo json_encode($data);


?>
