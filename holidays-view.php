<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("holidays-list"); </script>';
	die(); 
}
$appid  = 'HR';
$page_perm=['عرض اجازة رسمية'];
// $screen = 'العطلات (الاجازات الرسمية)';
// $page_title = 'إدارة العطلات (الاجازات)';
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
 include_once('inc/header.php');	
 
if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    $query = " SELECT a.Id,a.Holiday_ID ,b.branch_name,a.Name,a.Start_date,a.End_date,a.CreatedDate,a.LastUpdateDate,
 u.FirstName , u.LastName
FROM   holidays AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
	WHERE a.Id = :id 
	LIMIT 1 ";
	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $get_id
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		// 
        if ($row) {
            // استرداد جميع البيانات من holidays_day
            $holidayID = $row['Holiday_ID']; // استخدام Holiday_ID من النتيجة الأولى
            $sql2 = "
                SELECT 
                    Description,
                    Date,
                    LastUpdateDate
                FROM 
                    holidays_day
                WHERE 
                    HolidayID = :holidayID;
            ";
    
            $stmt2 = $connect_pdo->prepare($sql2);
            $stmt2->execute([':holidayID' => $holidayID]);
            $holidaysData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        // 
		

	}else{
		echo'<script> location.replace("holidays-list"); </script>';
		die();
	}
	
	
}




?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
            <span class="page-title"><?= $row['Name']?> </span>
           
          </div>
          <style>
          .current_balance{ text-align: right;margin:0;border-left:none;display:none} 
.bsuccess{ border-right:0.3rem solid green; display:block;}
.bdanger{ border-right:0.3rem solid red; display:block;}
          </style>

        </div>
      </div>
    </div>

    <!-- Main content -->
	<section class="content">
		<div class="container-fluid" >
	
		<?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
		<div class="alert alert-success alert-dismissible" id="result-alert">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <i class="icon fas fa-check"></i>
                 <?=$_SESSION['alert']?>
                 <?php $_SESSION['alert'] ='';?>
                </div>
	<?php endif;?>
			<div class="row">
			
				<div class="col-md-12">
					<div class="card">
						<div class="card-header p-2 d-print-none with-nav-btn">
							<div class=" btn-group dropdown-btn-group" style="direction:rtl">
								<a href="hr-setting" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-cog" aria-hidden="true"></span>
								قائمة الاعدادات</a>
								<a href="holidays-list" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-list" aria-hidden="true"></span>
								قائمة الإجازات</a>

								<?php
							if ($User->isAllowedPerm(['اضافة اجازة رسمية'], $appid)) {   ?>
								<a href="holidays-add" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-plus" aria-hidden="true"></span>
								اضافة أجازة جديدة</a>
								<?php  }
								if ($User->isAllowedPerm(['تعديل اجازة رسمية'], $appid)) {   ?>
								<a href="holidays-add?id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php  }
								if ($User->isAllowedPerm(['حذف اجازة رسمية'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client" value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button> 

								<?php  } ?>
                                
								
								
									


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن العطله(الاجازر)</strong></a>
              </li>            
			</ul>
           
            <div class="tab-content p-2" id="custom-content-above-tabContent" style="border-right: 1px solid #dddfe3;border-left: 1px solid #dddfe3;border-bottom: 1px solid #dddfe3; ">
			<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab" style="">
			  
			<div class="container-fluid">
			
            


				
                <div class="row">
                    <div class="col-md-6">
                         <h4 class="headline mt-4">السجل</h4>
                         <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table">
                            <tbody>
                             <?php if(!empty($row['CreatedDate']) ):?>
								<tr>
									<td >إنشئ في</td>
                                    <td><?= date($dateformat, strtotime($row['CreatedDate']))?></td>
								</tr>
                               <?php endif;?>
								<tr>
                                <?php if(!empty($row['FirstName']) ):?>
									<td>بواسطة</td>
									<td><?= $row['FirstName'].' '.$row['LastName']?></td>
								</tr>
                                 <?php endif;?>
                                 <?php if(!empty($row['LastUpdateDate']) ):?>
                                <tr>
									<td>آخر تعديل</td>
									<td><?= date($dateformat.'  h:i:s A', strtotime($row['LastUpdateDate']))?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['Start_date']) ):?>
                                <tr>
									<td>تاريخ البداية</td>
									<td><?= date($dateformat.'  h:i:s A', strtotime($row['Start_date']))?></td>
								</tr>
                                <?php endif;?> 
                                <?php if(!empty($row['End_date']) ):?>
                                <tr>
									<td>تاريخ النهاية</td>
									<td><?= date($dateformat.'  h:i:s A', strtotime($row['End_date']))?></td>
								</tr>
                                <?php endif;?>  
                                <!--  -->
                                    <?php
                                    if (!empty($holidaysData)) 
                                echo "<th>التاريخ</th> <th>الوصف</th> <th>اخر تعديل</th>";
                                        foreach ($holidaysData as $holiday) {
                                    ?>
                                    <tr>
                                    <td><?= $holiday['Date']?></td>
									<td><?= $holiday['Description']?></td>
                                    <td><?= $holiday['LastUpdateDate']?></td>
                                    </tr>
                                    <?php
                                        }
                                    ?>
                                <!--  -->
							</tbody>
						</table>
                    </div>
                </div>
			
			
			
			
			
			
			
	
			</div>
		  
		  </div>
			  
	
              
            </div>
          
			</div>
		</div>
		</div>
	</div>
	</div>
</section>





<?php
 include_once('inc/footer.php');
?>



<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>

<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script>

$(document).ready(function(){
	//data-widget
	
$("#purظ_nav").addClass('menu-open');
// $("#top_pur_menu").addClass('active');
// $("#pur_clients_menu").addClass('active');
    
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');


function confirm_remove (id) {
		// if(id !=''){
		// 	$('#modal_title').text('تأكيد عملية الحذف');
		// 		//   $('#modal_default .modal-body').addClass('loader');
		// 		  $('#modal_default .modal-dialog').removeClass('modal-lg');
		// 		$('#modal_default').modal({show:true});
		// 	  $('#modal_default .modal-body').load('./cli-app/holidays-remove?id='+id+'',function(){
                 
		// 	});
		// }
        if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/holidays-remove?id='+id+'',function(){
				 // $('#modal_default .modal-dialog').addClass('modal-md');
			});
		}
	}
    
    

$(document).on('click', '.remove_client', function(){
	var id = $(this).val();
	confirm_remove(id);

}); 


});
</script>