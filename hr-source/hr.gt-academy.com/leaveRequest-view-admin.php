<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("EmpAdvances-list-admin"); </script>';
	die(); 
} 
$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة الاجازات';
 include_once('inc/header.php');
 
 if (isset($_GET['id'])) {
    $get_id = (int)$_GET['id'];  // تأكد من أن id هو عدد صحيح
    
    // الاستعلام الأساسي لجلب البيانات
    $query = "SELECT a.Id , a.leavetype, a.leave_start_date, a.leave_end_date,a.day_leave,a.status,a.Draft,a.description,a.path,
                     a.CreatedDate, a.LastUpdateDate, u.FirstName, u.LastName
              FROM  tblleaverequest AS a
              LEFT JOIN tblusers AS u ON a.UserID = u.UserID
              WHERE a.Id = :id
              LIMIT 1";
    
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $get_id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();
            if($row['status']==1){
                $stopped_status = 'معتمد';
                $stopped_color = 'success';
            }
			            elseif($row['status']==2){
                $stopped_status = 'مرفوص';
                $stopped_color = 'danger';
            }
            else{
                $stopped_status = 'غير معتمد';
                $stopped_color = 'danger';
                }

        


        // اللي تم استثنائهم هم 

        // 
		
		

	}else{
		echo'<script> location.replace("leaveRequest-list-admin"); </script>';
		die();
	}
	
	
}




?>


	
   
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
		<div class="container-fluid">
	
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

								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn remove_client"value="<?=$row['Id']?>">
								<span class="fas fa-trash-alt" aria-hidden="true"></span>
								حذف</button>
                                <?php if(empty($row["status"]) || $row["status"]==2){ ?>
                                <button type="button" style="background-color:#53c45391" class="btn btn-default btn-green btn-sm quick-action-btn conform" value="<?=$row['Id']?>">
								<span class="	fas fa-check-circle" aria-hidden="true"></span>
								اعتماد</button>
<?php
                                }
                                ?>
								<?php if(empty($row["status"])){ ?>
								<button type="button" style="background-color:#c4535391" class="btn btn-default btn-green btn-sm quick-action-btn deni" value="<?=$row['Id']?>">
								<span class="fa fa-times" aria-hidden="true"></span>
								رفض</button>
                                <?php
                                }
                                ?>
								
								
									


						

								
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن السلفة</strong></a>
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
                                 
                                <tr>
									<td>الحالة</td>
									<td><?= ($row['status']==1)?'تم الاعتماد':'لم يتم الاعتماد'  ?></td>
								</tr>
                               
                                <?php if(!empty($row['leave_start_date']) ):?>
                                <tr>
									<td>تاريخ بداية الاجازة</td>
									<td> <?= date($dateformat, strtotime($row['leave_start_date']))?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['leave_end_date']) ):?>
                                <tr>
									<td>تاريخ انتهاء الاجازة</td>
									<td> <?= date($dateformat, strtotime($row['leave_end_date']))?></td>
								</tr>
                                <?php endif;?>
                                <?php if(!empty($row['day_leave']) ):?>
                                <tr>
									<td>عدد الايام</td>
									<td> <?= $row['day_leave']?>  </td>
								</tr>
                                <?php endif;?>
                                
                                <tr>
									<td>الرفع</td>
                                    <td><?= ($row['Draft']==1)?'تم الرفع':'مسودة' ?></td>
								</tr>
                                

                                <!--  -->
                                <?php if(!empty($row['path']) ):?>
                                <tr>
									<td>الملف</td>
									<td> <a href="<?=(!empty($row['path']) ? $row['path'] :'')?>" download>
                          <button type="button"  class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                                </a></div></td>
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
                                <?php if(!empty($row['description']) ):?>
									<td>اسباب السلفة</td>
									<td><?= $row['description']?></td>
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
			  $('#modal_default .modal-body').load('./hr-app/leaveRequest-remove-admin?id='+id+'',function(){
			});
		}
	}

function confirm_check (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الاعتماد');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/leaveRequest-conform-admin?id='+id+'',function(){
			});
		}
	}
	
function deni_check (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الرفض');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/leaveRequest-deni-admin?id='+id+'',function(){
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
$(document).on('click', '.deni', function(){
	var id = $(this).val();
	deni_check(id);

}); 
// 

});
</script>