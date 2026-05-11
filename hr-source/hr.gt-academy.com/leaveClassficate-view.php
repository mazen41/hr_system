<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("leaveClassficate-list"); </script>';
	die(); 
}
$appid  = 'HR';
$page_perm=['عرض اجازة عامة'];
// $screen = 'انواع الاجازات';
// $page_title = 'إدارة انواع الاجازات';
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
 include_once('inc/header.php');
  
if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    $query = " SELECT a.Id  ,b.branch_name,a.Name,a.type,a.state,a.LastUpdateDate,a.RequiresAttachment,a.isaccept,a.Description,
 u.FirstName , u.LastName,a.chose,a.for_what ,a.Amount,a.AmountType
FROM   leaveclassification AS a
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
	
            if($row['state']==1){
			$stopped_status = 'موقف';
			$stopped_color = 'danger';
            }
            elseif($row['state']==Null){
                $stopped_status = 'نشط';
                $stopped_color = 'success';
            }
		        // تقسيم القيم في UserID إلى مصفوفة
				$user_ids = explode(",", $row['chose']);
				$placeholders = implode(",", array_fill(0, count($user_ids), "?"));
		
				// إعداد الاستعلام بناءً على قيمة for_what
				if ($row['for_what'] == 1) {
					$query_ = "SELECT UserID AS ID, CONCAT(FirstName, ' ', LastName) AS Name 
							   FROM tblusers WHERE UserID IN ($placeholders)";
				} elseif ($row['for_what'] == 2) {
					$query_ = "SELECT Id AS ID, Name FROM tblgroup WHERE Id IN ($placeholders)";
				} elseif ($row['for_what'] == 3) {
					$query_ = "SELECT Id AS ID, Name FROM tbljobgrade WHERE Id IN ($placeholders)";
				} elseif ($row['for_what'] == 4) {
					$query_ = "SELECT c.Id AS ID, c.Name AS Name
							   FROM tblsection AS c
							   LEFT JOIN tblsection AS d ON c.Id = d.ParentID
							   WHERE c.ParentID IS NOT NULL AND d.Id IS NULL 
							   AND c.Id IN ($placeholders)";
				} elseif ($row['for_what'] == 5) {
					$query_ = "SELECT Id AS ID, Name FROM tbljobtitle WHERE Id IN ($placeholders)";
				}
				
				if(!empty($row['for_what'])){
				$stmt = $connect_pdo->prepare($query_);
				$stmt->execute($user_ids);
				$results = $stmt->fetchAll();
				}
		
		

	}else{
		echo'<script> location.replace("leaveClassficate-list"); </script>';
		die();
	}
	
	
}




?>


	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
            <span class="page-title"><?= $row['Name']?> </span>
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
							<a href="leaveClassficate-list" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-list" aria-hidden="true"></span>
								قائمة العطلات</a>

								<?php
							if ($User->isAllowedPerm(['تعديل اجازة عامة'], $appid)) {   ?>
								<a href="leaveClassficate-add?id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php  } if ($User->isAllowedPerm(['اضافة اجازة عامة'], $appid)) { ?>
								<a href="leaveClassficate-add?copy=1&id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-copy" aria-hidden="true"></span>
								نسخ</a>
								<?php  } if ($User->isAllowedPerm(['حذف اجازة عامة'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>
								<?php  }  ?>
								
								
									


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن نوع الاجازة</strong></a>
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
                                <tr>
                                <?php if(!empty($row['isaccept']) ):?>
									<td>تحتاج موافقة</td>
									<td>
                                     <?= ($row['isaccept'] == 1 ? "نعم" :
                                        ($row['isaccept'] == 2 ? "لا" :"")); ?> 
                                    </td>
								</tr>
                                <?php endif;?>
                                <tr>
                                <?php if(!empty($row['type']) ):?>
									<td>نوع الاجازة</td>
									<td>
                                     <?= ($row['type'] == 1 ? "مدفوعة كليا" :
                                         ($row['type'] == 2 ? "مدفوعة جزئيا" :
                                        ($row['type'] == 3 ? "غير مدفوعة" :""))); ?> 
                                    </td>
								</tr>
                                <?php endif;?>

								<!--  -->
								<?php if(!empty($row['Amount']) ):?>
                                <tr>
									<td> <?= ($row['AmountType'] == "avg") ? 'النسبة'. '%' : 'المبلغ'?></td>
									<td> <?= ($row['AmountType'] == "avg") ? $row['Amount'] . '%' : $row['Amount'] ?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['for_what']) ):?>
                                <tr>
									<td>لمن الاجازة</td>
									<td> <?= ($row['for_what']==1 ? 'موظف':
                                             ($row['for_what']==2 ?'لمجموعة':
                                             ($row['for_what']==3 ?'لدرجة وظيفية':
                                             ($row['for_what']==4 ?'لقسم محدد':
                                             ($row['for_what']==5 ?'لمسمى وظيفي': ''))))) ?>  </td>
								</tr>
                                <?php endif;?>
								<!--  -->
								<?php if(!empty($row['chose']) ):?>
                                <tr>
                                    <td colspan='4'> هي لي التالي</td>
                                    <td></td>
                                </tr>
                                <tr>
									<th>الرقم</th>
                                    <th>الاسم</th>
									<td> 
                                    <?php
                                    $i=1;
                                    foreach ($results as $ins) { ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= $ins["Name"] ?></td>
                                        </tr>
                                    <?php } ?> 
                                             
                                            </td>
								</tr>
                                <?php endif;?>

                                <tr>
                                <?php if(!empty($row['RequiresAttachment']) ):?>
									<td>تتطلب مرفق</td>
									<td>
                                     <?= ($row['RequiresAttachment'] == 1 ? "نعم" :
                                        ($row['RequiresAttachment'] == 2 ? "لا" :"")); ?> 
                                    </td>
								</tr>
                                <?php endif;?>
                                <tr>
                                <?php if(!empty($row['Description']) ):?>
									<td>الوصف</td>
									<td><?= $row['Description']?></td>
								</tr>
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
			  $('#modal_default .modal-body').load('./hr-app/leaveClassficate-remove?id='+id+'',function(){
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