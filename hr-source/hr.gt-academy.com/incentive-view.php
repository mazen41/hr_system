<?php

if(!isset($_GET['id'])){
	echo'<script> location.replace("incentive-list"); </script>';
	die(); 
}  
$appid  = 'HR';
$page_perm=['عرض مكافئه','تعديل مكافئه','حذف مكافئه','اعتماد مكافئه','إضافة مكافئه'];
// $screen = 'المكافئات';
// $page_title = 'إدارة المكافئات';

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة المكافاّت ';

 include_once('inc/header.php');
 
 if (isset($_GET['id'])) {
    $get_id = (int)$_GET['id'];  // تأكد من أن id هو عدد صحيح
    
    // الاستعلام الأساسي لجلب البيانات
    $query = "SELECT a.Id, a.BranchID, a.UserID, a.name, a.Amount,a.AmountType, a.Currency, a.Reason, a.for_what, a.extionsion,a.Status ,
                     a.DueDate, a.CreatedDate, a.LastUpdateDate, a.CreatedBy, u.FirstName, u.LastName, d.branch_name,a.incentive_type
              FROM tblincentives AS a
              LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
              LEFT JOIN branches AS d ON a.BranchID = d.branch_ref
              WHERE a.Id = :id
              LIMIT 1";
    
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $get_id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();
            if($row['Status']==1){
                $stopped_status = 'معتمد';
                $stopped_color = 'success';
            }
            else{
                $stopped_status = 'مسودة';
                $stopped_color = 'danger';
                }
        
        // تقسيم القيم في UserID إلى مصفوفة
        $user_ids = explode(",", $row['UserID']);
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
            $query_ = "SELECT Id AS ID, Name FROM  tbljobtitle WHERE Id IN ($placeholders)";
        }
        
        else {
            echo 'قيمة for_what غير صالحة.';
            exit;
        }
        $stmt = $connect_pdo->prepare($query_);
        $stmt->execute($user_ids);
        $results = $stmt->fetchAll();

        // اللي تم استثنائهم هم 
        if(!empty($row["extionsion"])) 
        {
            $user_ids_E = explode(",", $row['extionsion']);
            $placeholders_E = implode(",", array_fill(0, count($user_ids_E), "?"));
            $query_Q = "SELECT UserID AS ID, CONCAT(FirstName, ' ', LastName) AS Name 
                       FROM tblusers WHERE UserID IN ($placeholders_E)";  
            $stmt_E = $connect_pdo->prepare($query_Q);
            $stmt_E->execute($user_ids_E);
            $results_E = $stmt_E->fetchAll();
        }

        //
        
        $flage=false;
if($row['incentive_type']==2)
{
    if($today_date < $row['DueDate'])
        $flage=true;
    elseif(empty($row['Status']))
        $flage=true;
}
else
{
    if(empty($row['Status']))
        $flage=true;
}
		
		

	}else{
		echo'<script> location.replace("incentive-list"); </script>';
		die();
	}
	
	
}




?>


	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid "> 
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
            <span class="page-title"><?= $row['name']?> </span>
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
                                <?php  if ($User->isAllowedPerm(['تعديل مكافئه'], $appid)) {  ?>
                                    <?php if($flage){ ?>
								<a href="incentive-add?id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-edit" aria-hidden="true"></span>
								تعديل</a>
								<?php }
                             } if ($User->isAllowedPerm(['حذف مكافئه'], $appid)) { ?>
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>
                                <?php  } if ($User->isAllowedPerm(['إضافة مكافئه'], $appid)) { ?>
                                <a href="incentive-add?copy=1&id=<?=$row['Id']?>" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fa fa-copy" aria-hidden="true"></span>
								نسخ</a>
                                <?php  } if ($User->isAllowedPerm(['اعتماد مكافئه'], $appid)) { ?>
                                <?php if(empty($row["Status"])){ ?>
                                <button type="button" style="background-color:#53c45391" class="btn btn-default btn-green btn-sm quick-action-btn conform" value="<?=$row['Id']?>">
								<span class="	fas fa-check-circle" aria-hidden="true"></span>
								اعتماد</button>
                                <?php
                                }  }
                                ?>
						


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن المكافئة</strong></a>
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
                                <?php if(!empty($row['DueDate']) ):?>
                                <tr>
									<td>تاريخ الاستحقاق</td>
									<td> <?= date($dateformat, strtotime($row['DueDate']))?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['Amount']) ):?>
                                <tr>
									<td> <?= ($row['AmountType'] == "avg") ? 'النسبة'. '%' : 'المبلغ'?></td>
									<td> <?= ($row['AmountType'] == "avg") ? $row['Amount'] . '%' : $row['Amount'].$row['Currency'] ?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['for_what']) ):?>
                                <tr>
									<td>لمن المكافئة</td>
									<td> <?= ($row['for_what']==1 ? 'موظف':
                                             ($row['for_what']==2 ?'لمجموعة':
                                             ($row['for_what']==3 ?'لدرجة وظيفية':
                                             ($row['for_what']==4 ?'لقسم محدد':
                                             ($row['for_what']==5 ?'لمسمى وظيفي': ''))))) ?>  </td>
								</tr>
                                <?php endif;?>
                                <!--  -->
                                <?php if(!empty($row['UserID']) ):?>
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
                                <!--  -->
                                <?php if(!empty($row['extionsion']) ):?>
                                <tr>
                                    <td colspan='4'> اللي تم استثنائهم</td>
                                    <td></td>
                                </tr>
                                <tr>
									<th>الرقم</th>
                                    <th>الاسم</th>
									<td> 
                                    <?php
                                    $i=1;
                                    foreach ($results_E as $ins) { ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= $ins["Name"] ?></td>
                                        </tr>
                                    <?php } ?> 
                                             
                                            </td>
								</tr>
                                <?php endif;?>
                                <!--  -->


                                <?php if(!empty($row['LastUpdateDate']) ):?>
                                <tr>
									<td>آخر تعديل</td>
									<td><?= date($dateformat.'  h:i:s A', strtotime($row['LastUpdateDate']))?></td>
								</tr>
                                <?php endif;?>
                                <!--  -->

                                <tr>
                                <?php if(!empty($row['Reason']) ):?>
									<td>اسباب المكافئة</td>
									<td><?= $row['Reason']?></td>
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
			  $('#modal_default .modal-body').load('./hr-app/incentive-remove?id='+id+'',function(){
				 // $('#modal_default .modal-dialog').addClass('modal-md');
			});
		}
	}

function confirm_check (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/incentive-conform?id='+id+'',function(){
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