<?php

if(isset($_GET['id']) && !empty($_GET['id'])){
    
}else{
	echo'<script> location.replace("Issuing-salaries-list"); </script>';
	die();
}
 $appid  = 'HR';
$page_perm=['إصدار الرواتب'];

 $screen = 'إدارة الموارد البشرية';
$page_title = 'إصدار الرواتب';


 include_once('inc/header.php');
 include_once('sheard/options-fun.php');
 $app_options = get_app_options($connect_pdo,$branch,'SAL');
//  بيانات الشركة
    $query_site = "SELECT s.SiteTitle,s.SiteAddressID,a.AddressTitle,a.Street,a.Block,a.City,a.Building
     FROM  tblsite s
     LEFT JOIN tbladdress AS a ON a.AddressID  = s.SiteAddressID  LIMIT 1";
    $st_site = $connect_pdo->prepare($query_site);
    $st_site->execute();
    if ($st_site->rowCount() > 0) {
        $ou_data = $st_site->fetch();
    }
    
// حقي البيانات

// 
    $query = "SELECT * FROM  salary_registration WHERE registration_id = :id or registration_id_end=:id LIMIT 1";
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $_GET['id']]);
    if ($st->rowCount() > 0) {
        $row = $st->fetch();
    }
    if(!empty($row))
    {
//  اسماء الفروع
    $query_branch = "SELECT branch_name FROM branches WHERE FIND_IN_SET(branch_id, :branch)";
    $st_branch = $connect_pdo->prepare($query_branch);
    $st_branch->execute([':branch' => $row['BranchID']]);
    if ($st_branch->rowCount() > 0) {
        $row_branch = $st_branch->fetchAll();
    }
//  جلب بيانات الموظفين
    $query_emp = "SELECT u.UserID,u.incentive,u.benefit,u.advances,u.deductions,u.absent_salary,u.net_salary,u.end_salary,u.month,u.id_registration,
    t.FirstName,t.LastName,b.branch_name
    FROM  emp_salary u  
    LEFT JOIN tblusers AS t ON t.UserID = u.UserID
    LEFT JOIN branches AS b ON b.branch_id  = t.BranchID
    WHERE u.id_registration = :id";
    $st_emp = $connect_pdo->prepare($query_emp);
    $st_emp->execute([':id' => $row['Id']]);
    if ($st_emp->rowCount() > 0) {
        $row_emp = $st_emp->fetchAll();
    }

    }
    else
    {
     echo'<script> location.replace("Issuing-salaries-list"); </script>';   
     die();
    }
// 


?>
<style>
.badge-warning {
    color: #fff !important;
}
.product-stats i{
	color : #aaaaaa !important;
}

.table th {
	border: none;
	
}
.small_currency{
    color: #8f8f8f;
    font-weight: bold;
    font-size: 12px;
    padding-top: 6px;
    cursor: help;
    border-bottom: 1px dotted;
}




</style>
	<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	<script	src="plugins/charts/Chart.bundle.min.js"></script>

	 <script type="text/javascript" src="plugins/jspdf/jspdf.min.js"></script>
<script type="text/javascript" src="plugins/jspdf/html2canvas.js"></script>
	 
	 <?php 
	 $padding_top = '110px';
        $paystatus = 'مرحل ';
		$payscolor = 'success';
  
    

	 
	 ?>
	
	<div class="content-header page-nav d-print-none" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-md-10">
            <span class="page-title">صرف رواتب الموظفين  #<?= $row['Id']?></span>
            <span class="badge badge-<?=$payscolor?>"> <?= $paystatus?></span>

			<span class="second-page-title">
               
                
            <?= !empty($row['registration_id']) ? '<span class="sub-header">قيد الصرف: <a target="_blank" href="accountant-journals-view?id='.$row['registration_id'].'">'.$row['registration_id'].'</a></span>' : ''?>
            <?= !empty($row['registration_id_end']) ? '<span class="sub-header">قيد الاستحقاق: <a target="_blank" href="accountant-journals-view?id='.$row['registration_id_end'].'">'.$row['registration_id_end'].'</a></span>' : ''?>
            
            </span>

            </div>


        </div>
      </div>
    </div>

    <!-- Main content -->
	<section class="content">
		<div class="container-fluid" >
		<?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
       	<div class="alert alert-<?=!empty($_SESSION['alert_style']) ? $_SESSION['alert_style'] : 'success'?> alert-dismissible" id="result-alert">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <i class="icon fas fa-check"></i>
                 <?=$_SESSION['alert']?>
                 <?php 
                function wh_log($log_msg){
                    $log_filename = "log";
                    if (!file_exists($log_filename)) 
                    {
                        // create directory/folder uploads.
                        mkdir($log_filename, 0777, true);
                    }
                    $log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';
                    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
                    file_put_contents($log_file_data,  $_SESSION['alert'] . "\n", FILE_APPEND);
                } 

                //wh_log("this is my log message");
                 $_SESSION['alert'] ='';
                 $_SESSION['alert_style'] = '';
                
                 
                 ?>
                </div>
        <?php endif;?>

			<div class="row">
			<!-- هذا من اجل زر طباعه و pdf -->
				<div class="col-md-12">
					<div class="card product-view">
						<div class="card-header p-2 d-print-none with-nav-btn" style="">
							<div class=" btn-group dropdown-btn-group" style="direction:rtl">

                                <button class="btn btn-default btn-white  btn-sm quick-action-btn print_inv" onclick="printData()"><span class="fa fa-print" aria-hidden="true"></span>طباعة</button>
                                <button class="btn btn-default btn-white  btn-sm quick-action-btn download_pdf d-none d-sm-inline" ><span class="fa fa-file-pdf" aria-hidden="true"></span>PDF</button>
								
							<div class="clearfix"></div>
					</div>
				</div>


						<div class="card-body p-2">
							<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>كشف الراتب</strong></a>
              </li>
			  
            
			</ul>
           
            <div class="tab-content p-2" id="custom-content-above-tabContent" style="border-right: 1px solid #dddfe3;border-left: 1px solid #dddfe3;border-bottom: 1px solid #dddfe3; ">
              <div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab" style="background: gainsboro;">
			  
			  <div class="container-fluid " id="the_invoice" >
              <div class="row mb-4" >
 <style>
  #inv_header h2{
     font-size : 20px !important; 
 }
 #inv_header h3{
     font-size : 18px !important; 
 }
 
/*  @page :left {
  margin-left: 0.5cm;
  margin-right: 0.5cm;
 
} */

@media print {
     #inv_header h2{
     font-size : 27px !important; 
 }
  @page  {
  margin: 0.5cm !important;
  padding: 0 !important;
 
 
}
 
 #inv_header h3{
     font-size : 22px !important; 
 }
    .table thead td { 
        background-color: #f5f5f5 !important; 
        
    } 
   
    #product_info_table .bg-gry td{
        font-size : 20px !important; 
    }
    .inv_table  td{
        font-size : 22px !important; 
    }
    .table td{
        border-color : #838080 !important; 
    }
    h3{
        font-size:25px;
    }
    
    
}
 
</style>               
                <div class="col-md-'12'?> col-sm-12 mx-auto mt-2">
                <div class="invoice p-3 mb-3 embed-responsive" style="border: none;min-height: 600px;">
<?php
{
    $company = !empty($ou_data["AddressTitle"]) && $ou_data["AddressTitle"] != $_SESSION['account']['title'] ? $_SESSION['account']['title'].'<br>' :'';
$site_vars = array(
    '{%Site_Logo%}'            => file_exists('uploads/basics/'.$_SESSION['account']['logo'].'') ? '<img src="../uploads/basics/'.$_SESSION['account']['logo'].'" style="max-width: 120px;">' : $ou_data["AddressTitle"],
    '{%Site_Title%}'            => $company.$ou_data["AddressTitle"],
    '{%Site_Street%}'           => $ou_data["Street"],
    '{%Site_City%}'             => $ou_data["City"],
    '{%Site_Block%}'            => $ou_data["Block"],
    '{%Site_Building%}'         => $ou_data["Building"],

    
    '{%Inv_Title%}' => "رواتب الموظفين لشهر {$row['month']} سنة {$row['year']}",

    '{%entries_id%}'              => $row['registration_id'].'-'.$row['registration_id_end'],
    '{%Inv_Date2%}'              => $row['created_date'],

   '{%Client_Address%}' => "موظفين الشركة فروع:<br>" . implode('<br>', array_map(function($row) {
    return $row['branch_name'];
}, $row_branch)),

);

    echo'<div id="inv_header">';
    echo strtr($app_options['inv_header_timp'] , $site_vars);
    echo'</div>';


    
   include 'barcode128.php';
   
include_once $folder.'templates/print_salay.php';
}


?>
 

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
			
		</div>
	</section>


<?php
 include_once('inc/footer.php');
?>




<script src="plugins/select2/js/select2.full.min.js"></script>
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

function printmodal(){
	var invoice = document.getElementById("invoice-to-print").contentWindow;
	invoice.focus();
	invoice.print();
	return false;	
}

function printData(){
    var printContents = document.getElementById("the_invoice").innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
} 



// 


$(document).ready(function(){



 












 
 
 
 function CreatePDFfromHTML(file_name) {
    var HTML_Width = $("#the_invoice").width();
    var HTML_Height = $("#the_invoice").height();
    var top_left_margin = 15;
    var PDF_Width = HTML_Width + (top_left_margin * 2);
    var PDF_Height = (PDF_Width * 1.5) + (top_left_margin * 2);
    var canvas_image_width = HTML_Width;
    var canvas_image_height = HTML_Height;

    var totalPDFPages = Math.ceil(HTML_Height / PDF_Height) - 1;

    html2canvas($("#the_invoice")[0]).then(function (canvas) {
        var imgData = canvas.toDataURL("image/jpeg", 1.0);
        var pdf = new jsPDF('p', 'pt', [PDF_Width, PDF_Height]);
        pdf.addImage(imgData, 'JPG', top_left_margin, top_left_margin, canvas_image_width, canvas_image_height);
        for (var i = 1; i <= totalPDFPages; i++) { 
            pdf.addPage(PDF_Width, PDF_Height);
            pdf.addImage(imgData, 'JPG', top_left_margin, -(PDF_Height*i)+(top_left_margin*4),canvas_image_width,canvas_image_height);
        }
        pdf.save(file_name+".pdf");
        //$("#the_invoice").hide();
    });
}

$(document).on('click', '.download_pdf', function(){
    var file_name = $(this).val();
    CreatePDFfromHTML(file_name);
});












});
</script>