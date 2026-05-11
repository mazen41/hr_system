<?php
//$fix_foot = false;
$appid  = 'HR';
// Added 'الفروع' to the permissions array
$page_perm=['الاعدادات','الفترات','اجهزة البصمة','الاقسام','المسميات الوظيفية','شركات التامين','المجموعات','الدرجات الوظيفية','انماط العمل','الاجازات الرسمية','الاجازات العامة','الفروع'];

// $screen = 'الموارد البشرية';
// $page_title = 'إعدادات الموارد البشرية';

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
include_once('inc/header.php');
	
?>

<style>
/* Responsive Settings Cards */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.setting-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}

.setting-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: #3b82f6;
}

.setting-card a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.setting-card h5 {
    color: #1f2937;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.setting-card i {
    color: #6b7280;
    margin-top: 10px;
    transition: color 0.3s ease;
}

.setting-card:hover i {
    color: #3b82f6;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        padding: 15px;
    }
    
    .setting-card {
        padding: 20px 15px;
    }
    
    .setting-card h5 {
        font-size: 0.95rem;
    }
    
    .setting-card i {
        font-size: 2rem !important;
    }
}

@media (max-width: 480px) {
    .settings-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .setting-card {
        padding: 25px 20px;
    }
}
</style>

<!-- Main content -->
<section class="content">
	<div class="container-fluid">
		<div class="settings-grid">
            <?php if($User->isAllowedPerm(['الفترات'],$appid)){ ?>
            <div class="setting-card">
                <a href="shift-list">
                    <h5>الفترات</h5>
                    <i class="fa fa-calendar fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['اجهزة البصمة'],$appid)){ ?>
            <div class="setting-card">
                <a href="fingerprint-list">
                    <h5>اجهزة البصمة</h5>
                    <i class="fas fa-fingerprint fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['الاقسام'],$appid)){ ?>
            <div class="setting-card">
                <a href="section-list">
                    <h5>الإدارات</h5>
                    <i class="fa fa-bars fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['المسميات الوظيفية'],$appid)){ ?>
            <div class="setting-card">
                <a href="jobtitle-list">
                    <h5>المسميات الوظيفية</h5>
                    <i class="fa fa-tasks fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['شركات التامين'],$appid)){ ?>
            <div class="setting-card">
                <a href="insurance-list">
                    <h5>شركات التامين</h5>
                    <i class="fa fa-medkit fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['المجموعات'],$appid)){ ?>
            <div class="setting-card">
                <a href="groub-list">
                    <h5>المجموعات</h5>
                    <i class="fa fa-users fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['الدرجات الوظيفية'],$appid)){ ?>
            <div class="setting-card">
                <a href="jobgrade-list">
                    <h5>الدرجات الوظيفية</h5>
                    <i class="fas fa-graduation-cap fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['انماط العمل'],$appid)){ ?>
            <div class="setting-card">
                <a href="empolyment-list">
                    <h5>انماط العمل</h5>
                    <i class="fa fa-random fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['الاجازات الرسمية'],$appid)){ ?>
            <div class="setting-card">
                <a href="holidays-list">
                    <h5>الاجازات الرسمية</h5>
                    <i class="fas fa-gifts fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            
            <?php if($User->isAllowedPerm(['الاجازات العامة'],$appid)){ ?>
            <div class="setting-card">
                <a href="leaveClassficate-list">
                    <h5>الاجازات الخاصة بكل موظف</h5>
                    <i class="fas fa-mug-hot fa-3x"></i>
                </a>
            </div>
            <?php } ?>

            <!-- New Branches Card -->
            <?php if($User->isAllowedPerm(['الفروع'],$appid)){ ?>
            <div class="setting-card">
                <a href="branches-list">
                    <h5>الفروع</h5>
                    <i class="fas fa-building fa-3x"></i>
                </a>
            </div>
            <?php } ?>
            <!-- End New Branches Card -->

            <?php if($User->userIsAdmin() || $User->userIsEmployer()){ ?>
            <div class="setting-card">
                <a href="admin-mail-settings">
                    <h5>إعدادات البريد الإلكتروني</h5>
                    <i class="fas fa-envelope-open-text fa-3x"></i>
                </a>
            </div>
            <?php } ?>
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