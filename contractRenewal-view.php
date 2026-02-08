<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("contractRenewal-list"); </script>';
	die(); 
} 
$appid  = 'HR';
$page_perm=['عرض عقد'];
// $screen = 'العقد';
// $page_title = 'إدارة العقود';

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة تجديد العقود';

 include_once('inc/header.php');
 
 if (isset($_GET['id'])) {
    $get_id = (int)$_GET['id'];  // تأكد من أن id هو عدد صحيح
    
    // الاستعلام الأساسي لجلب البيانات
    $query = "SELECT 	k.Id,u.UserID,u.FirstName, u.LastName,b.branch_name,
    k.SectionID,k.BranchID,k.GroupID,k.GradeID,k.shiftID,k.TypeID,k.fingerID,k.jobtitleID,
    k.Salary,k.Currency,k.new_s_date,k.new_e_date,
    a.Name as section_name,j.Name as jobtitle_name,c.Name as group_name,f.Name as name_grade,
    h.Name as name_n,k.day,k.Reason,k.state,k.CreatedBy
    
    FROM  tblremewal k

    left join  tblusers u ON u.UserID = k.CreatedBy
    left join branches b ON b.branch_id = k.BranchID

    left join  tblsection a ON a.Id = k.SectionID
    left join  tbljobtitle j ON j.Id = k.jobtitleID   
    left join   tblgroup c ON c.Id = k.GroupID
    left join   tbljobgrade f ON f.Id = k.GradeID
    left join   tblemploymenttype h ON h.Id = k.TypeID
              
              WHERE k.Id = :id
                
              LIMIT 1";
    
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $get_id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();
            if($row['state']==1){
                $stopped_status = 'معتمد';
                $stopped_color = 'success';
            }
            else{
                $stopped_status = 'مسودة';
                $stopped_color = 'danger';
                }
	}
  else{
		echo'<script> location.replace("contractRenewal-list"); </script>';
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
            <span class="page-title"><?= $row['FirstName'].' '.$row['LastName']?> </span>
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
                <?php
                if ($User->isAllowedPerm(['تعديل عقد'], $appid)) {   ?>
								<a href="contractRenewal-add?edit=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php  } if ($User->isAllowedPerm(['حذف عقد'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>
                <?php  } if ($User->isAllowedPerm(['اعتماد عقد'], $appid)) { ?>
                                <?php if(empty($row["state"])){ ?>
                                <button type="button" style="background-color:#53c45391" class="btn btn-default btn-green btn-sm quick-action-btn conform" value="<?=$row['Id']?>">
								<span class="	fas fa-check-circle" aria-hidden="true"></span>
								اعتماد</button>
                                <?php
                                }
                              }
                                ?>
								
								
									


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن ترقية الموظف</strong></a>
              </li>            
			</ul>
           
            <div class="tab-content p-2" id="custom-content-above-tabContent" style="border-right: 1px solid #dddfe3;border-left: 1px solid #dddfe3;border-bottom: 1px solid #dddfe3; ">
			<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab" style="">
			  
			<div class="container-fluid">
			
            


				
                <div class="row">
                    <div class="col-md-12">
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
                                 <?php if(!empty($row['branch_name']) ):?>
                                <tr>
									<td>فرع</td>
									<td><?= $row['branch_name']?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['jobtitleID']) ):?>
                                <tr>
									<td>المسمى الوظيفي</td>
									<td> <?= $row['jobtitle_name']?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['SectionID']) ):?>
                                <tr>
									<td>القسم</td>
									<td> <?= $row['section_name']?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['GroupID']) ):?>
                                <tr>
									<td>المجموعة الوظيفية</td>
									<td> <?= $row['group_name']?></td>
								</tr>
                                <?php endif;?>

                                <?php if(!empty($row['GradeID']) ):?>
                                <tr>
									<td>الدرجة الوظيفية</td>
									<td> <?= $row['name_grade']?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['TypeID']) ):?>
                                <tr>
									<td>نمط العمل</td>
									<td> <?= $row['name_n']?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['Salary']) ):?>
                                <tr>
									<td>الملبغ</td>
									<td> <?= $row['Salary'].' '.$row['Currency'] ?>  </td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['new_s_date']) ):?>
								<tr>
									<td >تاريخ بداية العقد الجديد</td>
                                    <td><?= date($dateformat, strtotime($row['new_s_date']))?></td>
								</tr>
                               <?php endif;?>
                               <?php if(!empty($row['new_e_date']) ):?>
								<tr>
									<td >تاريخ انتهاء العقد الجديد</td>
                                    <td><?= date($dateformat, strtotime($row['new_e_date']))?></td>
								</tr>
                               <?php endif;?>
                               <?php if(!empty($row['day']) ):?>
								<tr>
									<td >الايام قبل المغادرة</td>
                                    <td><?= $row['day'] ?></td>
								</tr>
                               <?php endif;?>
                                <?php if(!empty($row['Reason']) ):?>
                                <tr>
									<td>الوصف</td>
									<td> <?= $row['Reason'] ?>  </td>
								</tr>
                                <?php endif;?>
                                
                                <!--  -->
                                
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
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/contractRenewal-remove?id='+id+'',function(){
			});
		}
	}

function confirm_check (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية التاكيد');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/contractRenewal-conform?id='+id+'',function(){
			});
		}
	}    
    

$(document).on('click', '.remove_client', function(){
	var id = $(this).val();
	confirm_remove(id);

}); 

// 
$(document).on('click', '.conform', function(){
	var id = $(this).val();
	confirm_check(id);

}); 
// 

});
</script>