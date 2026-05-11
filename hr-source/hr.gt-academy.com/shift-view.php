<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("client-list"); </script>';
	die();
}
$appid  = 'HR';
$page_perm=['عرض فترة'];
// $screen = 'الفترات';
// $page_title = 'إدارة الفترات';

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

 include_once('inc/header.php');
 
if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    $query = " SELECT a.ShiftID,b.branch_name,a.ShiftState,a.ShiftName,a.ShiftStartTime,a.ShiftEndTime,a.LastUpdatetim,a.CreatedDate, 
    u.FirstName , u.LastName
   FROM tbshift AS a
   LEFT JOIN branches AS b ON a.BranchID = b.branch_id
   LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
	WHERE a.ShiftID = :id 
	LIMIT 1 ";
	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $get_id
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
	
            if($row['ShiftState']==1){
			$stopped_status = 'موقف';
			$stopped_color = 'danger';
            }
            elseif($row['ShiftState']==0){
                $stopped_status = 'نشط';
                $stopped_color = 'success';
                }
		
		

	}else{
		echo'<script> location.replace("shift-list"); </script>';
		die();
	}
	
	
}




?>


	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
            <span class="page-title"><?= $row['ShiftName']?> </span>
			<span class="badge badge-<?= $stopped_color?>"> <?= $stopped_status?> </span>
           
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
								<a href="shift-list" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-list" aria-hidden="true"></span>
								قائمة الفترات</a>

								<?php
							if ($User->isAllowedPerm(['اضافة فترة'], $appid)) {   ?>
								<a href="shift-add" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-plus" aria-hidden="true"></span>
								إضافتة فتره جديدة</a>
								<?php  } 
								if ($User->isAllowedPerm(['تعديل فترة'], $appid)) {   ?>
								<a href="shift-add?id=<?=$row['ShiftID']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php  } 
								
								if ($User->isAllowedPerm(['حذف فترة'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['ShiftID']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>

								<?php  } ?>
								
								
									


				 		

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن الفتره</strong></a>
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
                                 <?php if(!empty($row['LastUpdatetim']) ):?>
                                <tr>
									<td>آخر تعديل</td>
									<td><?= date($dateformat.'  h:i:s A', strtotime($row['LastUpdatetim']))?></td>
								</tr>
                                <!-- <tr>
									<td>كم عدد الموظفين</td>
									<td> لم تتم لعدم ربطها بالموظفين</td>
								</tr>
                                <tr>
									<td>كم عدد المستقيلين</td>
									<td> لم تتم لعدم ربطها بالمستقيلين</td>
								</tr> -->
                                <?php endif;?>
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



<!-- DataTables loaded from CDN in footer.php -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script>

$(document).ready(function(){
	//data-widget
	
$("#purظ_nav").addClass('menu-open');
// $("#top_pur_menu").addClass('active');
// $("#pur_clients_menu").addClass('active');
    
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');


function confirm_remove (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/shift-remove?id='+id+'',function(){
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