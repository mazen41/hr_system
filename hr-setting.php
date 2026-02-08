<?php
//$fix_foot = false;
$appid  = 'HR';
$page_perm=['الاعدادات','الفترات','اجهزة البصمة','الاقسام','المسميات الوظيفية','شركات التامين','المجموعات','الدرجات الوظيفية','انماط العمل','الاجازات الرسمية','الاجازات العامة'];

// $screen = 'الموارد البشرية';
// $page_title = 'إعدادات الموارد البشرية';

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
include_once('inc/header.php');
	
?>


   
 
	
		
	

    <!-- Main content -->
<section class="content">
	<div class="container-fluid text-center">
		<div class="row">
        <?php if($User->isAllowedPerm(['الفترات'],$appid)){ ?>
			<div class="col-md-4">
				<div class="card p-5">
					<a href="shift-list">
					<h5>الفترات</h5>
					<i class="fa fa-calendar fa-3x text-muted"></i>
					</a>
				</div>
			</div>
            <?php  } if($User->isAllowedPerm(['اجهزة البصمة'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="fingerprint-list">
					<h5>اجهزة البصمة</h5>
					<i class="fas fa-fingerprint fa-3x text-muted"></i>
					</a>
				</div>
				
			</div>
            <?php  } if($User->isAllowedPerm(['الاقسام'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="section-list">
					<h5>الإدارات</h5>
					<i class="fa fa-bars fa-3x text-muted"></i>
					</a>
				</div>
				
			</div>
            <?php  } if($User->isAllowedPerm(['المسميات الوظيفية'],$appid)){ ?>
            <!--  -->
            <div class="col-md-4">
				<div class="card p-5">
					<a href="jobtitle-list">
					<h5>المسميات الوظيفية</h5>
					<i class="fa fa-tasks fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } if($User->isAllowedPerm(['شركات التامين'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="insurance-list">
					<h5>شركات التامين</h5>
					<i class="fa fa-medkit fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } if($User->isAllowedPerm(['المجموعات'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="groub-list">
					<h5> المجموعات</h5>
					<i class="fa fa-users fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <!--  -->
            <?php  } if($User->isAllowedPerm(['الدرجات الوظيفية'],$appid)){ ?>
            <!--  -->
            <div class="col-md-4">
				<div class="card p-5">
					<a href="jobgrade-list">
					<h5> الدرجات الوظيفيه</h5>
					<i class="fas fa-graduation-cap fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } if($User->isAllowedPerm(['انماط العمل'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="empolyment-list">
					<h5>انماط العمل</h5>
					<i class="fa fa-random fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } if($User->isAllowedPerm(['الاجازات الرسمية'],$appid)){ ?>
            <div class="col-md-4">
				<div class="card p-5">
					<a href="holidays-list">
					<h5>الاجازات الرسمية</h5>
					<i class="fas fa-gifts fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <!--  -->
            <?php  } if($User->isAllowedPerm(['الاجازات العامة'],$appid)){ ?>
            <!--  -->
            <div class="col-md-4">
				<div class="card p-5">
					<a href="leaveClassficate-list">
					<h5>الاجازات الخاصة بكل موظف</h5>
					<i class="fas fa-mug-hot fa-3x text-muted"></i>
					</a>
				</div>	
			</div>
            <?php  } ?>
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