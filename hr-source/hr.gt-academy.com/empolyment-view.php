<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("insurance-list"); </script>';
	die(); 
}	
$appid  = 'HR';
$page_perm=['عرض نمط عمل'];
// $screen = 'الدرجة الوظيفيه';
// $page_title = 'إدارة الدرجات الوظيفية';
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
 include_once('inc/header.php');
 
if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    $query = " SELECT a.Id, b.branch_name, a.Name, a.CreatedDate
FROM tblemploymenttype AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
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
		
		

	}else{
		echo'<script> location.replace("empolyment-list"); </script>';
		die();
	}
	
	
}




?>


	
   
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
								<a href="empolyment-list" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-list" aria-hidden="true"></span>
								قائمة الانماط</a>

							<?php

							if ($User->isAllowedPerm(['اضافة نمط عمل'], $appid)) {   ?>
								<a href="empolyment-add" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-plus" aria-hidden="true"></span>
								اضافة نمط جديد</a>
								<?php  } 
								if ($User->isAllowedPerm(['تعديل نمط عمل'], $appid)) {   ?>
								<a href="empolyment-add?id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php  }
								if ($User->isAllowedPerm(['حذف نمط عمل'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>

								<?php  } ?>
								
								
									


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن النمط</strong></a>
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
									<td>بواسطة</td>
									<td>System</td>
								</tr>
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
			  $('#modal_default .modal-body').load('./hr-app/empolyment-remove?id='+id+'',function(){
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