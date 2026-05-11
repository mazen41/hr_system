 <?php
//$fix_foot = false;
$appid  = 'HR';
$page_perm=['اعدادات الموظفين','إضافة استقاله لموظف','إضافة إجازة لموظف','إضافة سلفة لموظف','فصل موظف','تغير المدير لمجموعة الموظفين'];

// $screen = 'الموارد البشرية';
// $page_title = 'إعدادات الموارد البشرية';

$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
include_once('inc/header.php');
	
?>


   
 
	
		
	

    <!-- Main content -->
<section class="content">
	<div class="container-fluid text-center">
		<div class="row">
        <?php if($User->isAllowedPerm(['إضافة سلفة لموظف'],$appid)){ ?>
			<div class="col-md-4">
				<div class="card p-5">
					<a href="EmpAdvances-list-add">
					<h5>إضافة سلفه لموظف</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>

					</a>
				</div>
			</div>
            <?php  } if($User->isAllowedPerm(['إضافة إجازة لموظف'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="leaveRequest-list-add">
					<h5>إضافة إجازة لموظف</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>
					</a>
				</div>
				
			</div>
            <?php  } if($User->isAllowedPerm(['إضافة استقاله لموظف'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="resignation-list-add">
					<h5>إضافة استقالة لموظف</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>
					</a>
				</div>
				
			</div>
            <?php  } if($User->isAllowedPerm(['فصل موظف'],$appid)){ ?>
            <!--  -->
            <div class="col-md-4">
				<div class="card p-5">
					<a href="dismissal-list">
					<h5>فصل موظف</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  }  if($User->isAllowedPerm(['تغير المدير لمجموعة الموظفين'],$appid)){ ?>
            <!--  -->
            <div class="col-md-4">
				<div class="card p-5">
					<a href="change-manager-emp">
					<h5>تغير المدير لمجموعة الموظفين</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  }  
			?>
			<!-- <?php
			if($User->isAllowedPerm(['ارسال اشعار لموظف'],$appid)){ ?>
            
            <div class="col-md-4">
				<div class="card p-5">
					<a href="send-notifaction-emp">
					<h5>ارسال اشعار لموظف</h5>
					<i class="fa fa-plus-circle fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } ?> -->

            <!--  -->



            <!--  -->

            <!--  -->
		</div>
	</div>
</section>






<?php
 include_once('inc/footer.php');
?>
<script>
$(document).ready(function(){
	
$("#pos_nav").addClass('menu-open');
$("#pos_top_menu").addClass('active');
$("#pos_sitings_menu").addClass('active');
	

});
</script>