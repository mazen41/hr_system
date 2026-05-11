<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("report-deductions"); </script>';
	die(); 
} 
$screen = 'الخصم';
$page_title = 'تفاصيل  الخصم';
$report_name='تفاصيل  الخصم';
 include_once('inc/header.php');
 
 if (isset($_GET['id'])) {
    $get_id = (int)$_GET['id'];  // تأكد من أن id هو عدد صحيح
    
    // الاستعلام الأساسي لجلب البيانات
    $query = "SELECT a.Id, a.BranchID, a.UserID, a.name, a.Amount, a.Currency, a.Reason, a.for_what, a.extionsion,a.Status ,
                     a.DueDate, a.CreatedDate, a.LastUpdateDate, a.CreatedBy, u.FirstName, u.LastName, d.branch_name,a.DueDate
              FROM  tbldeductions AS a
              LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
              LEFT JOIN branches AS d ON a.BranchID = d.branch_ref
              WHERE a.Id = :id
              LIMIT 1";
    
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $get_id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();
        // تقسيم القيم في UserID إلى مصفوفة
        $user_ids = explode(",", $row['UserID']);
        $placeholders = implode(",", array_fill(0, count($user_ids), "?"));

        // إعداد الاستعلام بناءً على قيمة for_what
            if ($row['for_what'] == 2) {
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
        elseif ($row['for_what'] == 1) {
            $query_ = "SELECT UserID  AS ID  FROM  tblusers WHERE  UserID IN ($placeholders)";
        } 

        
        else {
            echo 'قيمة for_what غير صالحة.';
            exit;
        }
        $stmt = $connect_pdo->prepare($query_);
        $stmt->execute($user_ids);
        $results = $stmt->fetchAll();


        // all user
        if(!empty($row['for_what']))
        {

            $user_id_1 = array_column($results, 'ID');

            $query_emp = "SELECT u.UserID AS ID, CONCAT(u.FirstName, ' ', u.LastName) AS Name,
                t.BranchID, t.SectionID, t.jobtitleID, t.GroupID, g.name as name_groub,
                b.branch_name, j.Name as job_name, s.Name as scetion_name
                FROM tblusers u 
                LEFT JOIN tblremewal t ON u.lastversion = t.Id
                LEFT JOIN tbljobtitle j ON t.jobtitleID = j.Id 
                LEFT JOIN tblsection s ON t.SectionID = s.Id
                LEFT JOIN tblgroup g ON t.GroupID = g.Id  
                LEFT JOIN branches b ON b.branch_id = t.BranchID
                WHERE 1=1";
            
            if (!empty($row['for_what'])) {
                if ($row['for_what'] == 1) {
                    $placeholders_1 = implode(",", array_fill(0, count($user_ids), "?"));
                    $query_emp .= " AND u.UserID IN ($placeholders_1)";
                    $params = $user_ids;
                } elseif ($row['for_what'] == 2) {
                    $placeholders_1 = implode(",", array_fill(0, count($user_id_1), "?"));
                    $query_emp .= " AND t.GroupID IN ($placeholders_1)";
                    $params = $user_id_1;
                } elseif ($row['for_what'] == 3) {
                    $placeholders_1 = implode(",", array_fill(0, count($user_id_1), "?"));
                    $query_emp .= " AND t.GradeID IN ($placeholders_1)";
                    $params = $user_id_1;
                } elseif ($row['for_what'] == 4) {
                    $placeholders_1 = implode(",", array_fill(0, count($user_id_1), "?"));
                    $query_emp .= " AND t.SectionID IN ($placeholders_1)";
                    $params = $user_id_1;
                } elseif ($row['for_what'] == 5) {
                    $placeholders_1 = implode(",", array_fill(0, count($user_id_1), "?"));
                    $query_emp .= " AND t.jobtitleID IN ($placeholders_1)";
                    $params = $user_id_1;
                }
                if (!empty($row["extionsion"])) {
                    $user_ids_E = explode(",", $row['extionsion']);
                    $placeholders_E = implode(",", array_fill(0, count($user_ids_E), "?"));
                    $query_emp .= " AND u.UserID NOT IN ($placeholders_E)";
                    $params = array_merge($params, $user_ids_E); // دمج المعايير
                }
                
                $stmt_emp = $connect_pdo->prepare($query_emp);
                $stmt_emp->execute($params);
                $results_emp = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);
            }
        }


        // اللي تم استثنائهم هم 
        if(!empty($row["extionsion"])) 
        {
            $user_ids_E = explode(",", $row['extionsion']);
            $placeholders_E = implode(",", array_fill(0, count($user_ids_E), "?"));
            $query_Q = "SELECT u.UserID AS ID, CONCAT(u.FirstName, ' ', u.LastName) AS Name,
                t.BranchID, t.SectionID, t.jobtitleID, t.GroupID, g.name as name_groub,
                b.branch_name, j.Name as job_name, s.Name as scetion_name
                FROM tblusers u 
                LEFT JOIN tblremewal t ON u.lastversion = t.Id
                LEFT JOIN tbljobtitle j ON t.jobtitleID = j.Id 
                LEFT JOIN tblsection s ON t.SectionID = s.Id
                LEFT JOIN tblgroup g ON t.GroupID = g.Id  
                LEFT JOIN branches b ON b.branch_id = t.BranchID
                WHERE u.UserID IN ($placeholders_E)  "; 
                       
                       

            $stmt_E = $connect_pdo->prepare($query_Q);
            $stmt_E->execute($user_ids_E);
            $results_E = $stmt_E->fetchAll();
        }

        // 
		
		

	}else{
		echo'<script> location.replace("report-deductions"); </script>';
		die();
	}
	
	
}




?>
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid "> 
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
            <span class="page-title"><?= $row['name']?> </span>
           
          </div>
          <style>
          .current_balance{ text-align: right;margin:0;border-left:none;display:none} 
.bsuccess{ border-right:0.3rem solid green; display:block;}
.bdanger{ border-right:0.3rem solid red; display:block;}
@page {
            size: A4;
            margin: 15mm 10mm;
        }
        
        body {
            /* font-family: 'Arial', sans-serif; */
            background-color: #f8f9fa;
            color: #333;
        }
        
        .report-container {
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
        }
        
        .letterhead {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #ddd;
            margin-bottom: 30px;
        }
        
        .ministry-name {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .document-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .employee-photo {
            width: 120px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
            float: left;
            margin-left: 20px;
        }
        
        .official-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .official-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: right;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        .official-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: right;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 25px 0 15px 0;
            padding-right: 10px;
            border-right: 4px solid #3498db;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .status-active {
            background-color: #28a745;
            color: white;
        }
        
        .status-inactive {
            background-color: #dc3545;
            color: white;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 50px;
        }
        
        .no-print {
            display: block;
        }
        
        @media print {
            body {
                background-color: white;
            }
            
            .no-print {
                display: none !important;
            }
            
            .report-container {
                box-shadow: none;
            }
            
        }

/* 
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        } */
        /* .header img {
            height: 80px;
            margin-bottom: 10px;
        } */
        /* .document-title {
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0;
        } */
        /* .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        } */
        /* .section-title {
            font-weight: bold;
            font-size: 18px;
            border-right: 3px solid #000;
            padding-right: 10px;
            margin: 20px 0 10px 0;
        } */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            padding: 8px;
            text-align: center;
        }
        td {
            padding: 8px;
        }
        /* .signature-area {
            margin-top: 50px;
        }
        .signature-line {
            width: 300px;
            border-top: 1px solid #000;
            margin-top: 40px;
            display: inline-block;
        } */
        /* .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 10px;
        } */
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
								<a href="#" onclick="printData()" class="btn btn-default btn-white  btn-sm quick-action-btn">
								<span class="fas fa-print" aria-hidden="true"></span>
								طباعه</a>
								
								<button type="button" class="btn btn-default btn-white  btn-sm quick-action-btn download_pdf"value="<?=$report_name?>">
								<span class="fa fa-file-pdf" aria-hidden="true"></span>
								pdf</button>
                                <!-- <button type="button" class="btn btn-primary  download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> PDF</button>	 -->
						
							<div class="clearfix"></div>
						</div>
						</div>
			<div class="card-body p-2">
				<ul class="nav nav-tabs d-print-none" id="custom-content-above-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن الخصم</strong></a>
              </li>            
			</ul>
           
            <div class="tab-content p-2" id="custom-content-above-tabContent" style="border-right: 1px solid #dddfe3;border-left: 1px solid #dddfe3;border-bottom: 1px solid #dddfe3; ">
			<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab" style="">
			  
			<div class="result-containr" style="overflow:scroll" id="dataincentiv">
			<!-- start -->
            
                            
                               <div class="section">
            <div class="section-title">معلومات الخصم</div>
            <table>
                <tbody><tr>
                    <td style="width: 25%; font-weight: bold;">اسم الخصم</td>
                    <td style="width: 25%;"><?= !empty($row['name'])?$row['name']:'غير محدد' ?></td>
                    <td style="font-weight: bold;">حالة التعويض</td>
                    <td><?= !empty($row['Status']) ?'معتمد':'غير معتمد' ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">الفرع</td>
                    <td><?= !empty($row['branch_name'])?$row['branch_name']:'غير محدد' ?></td>
                    <td style="font-weight: bold;">العملة</td>
                    <td><?= !empty($row['Currency'])?$row['Currency']:'غير محدد' ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">تاريخ الاستحقاق</td>
                    <td><?= !empty($row['DueDate'])?$row['DueDate']:(($row['beneft_type']==1)?'نهاية كل شهر':'') ?></td>
                    <td style="font-weight: bold;">المبلغ/النسبة</td>
                    <td><?= !empty($row['Amount']) ?($row['Amount'].' '.$row['Currency']):'غير محدد' ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">الجهة المستلمة</td>
                    <td colspan="3">
    <?php
        if (!empty($row['for_what'])) {
            switch ($row['for_what']) {
                case 1:
                    echo 'لموظف';
                    break;
                case 2:
                    echo 'لمجموعة';
                    break;
                case 3:
                    echo 'لدرجة وظيفية';
                    break;
                case 4:
                    echo  'لقسم محدد';
                    break;
                case 5:
                    echo 'لمسمى وظيفي';
                    break;
                default:
                    echo 'غير محدد';
                    break;
            }
        } else {
            echo 'غير محدد';
        }
    ?>
</td>



                </tr>
            </tbody></table>
        </div>
			
        <?php if (!empty($row['for_what']) && $row['for_what']!=1 ) {
            ?>
        <div class="section">
            <div class="section-title">قائمة     <?php
      switch ($row['for_what']) {    case 1:   echo 'لموظف';    break;   case 2:   echo 'لمجموعة';    break;   case 3:   echo 'لدرجة وظيفية';    break;  case 4:   echo 'لمسمى وظيفي';  break; case 5:  echo 'لقسم محدد'; break; default: echo 'غير محدد'; break; }   ?></div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">الاسم</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $i=1;
                    foreach ($results as $ins) { ?>
                        <tr>
                            <td><?= $i++ ?></td> 
                            <td><?= $ins["Name"] ?></td>
                        </tr>
                    <?php } 
        }
                    ?> 
                </tbody>
            </table>
        </div>
			


        <div class="section">
            <div class="section-title">قائمة المستلمين</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">اسم الموظف</th>
                        <th style="width: 10%;">الفرع</th>
                        <th style="width: 25%;">المسمى الوظيفي</th>
                        <th style="width: 20%;">القسم</th>
                        <th style="width: 15%;">المبلغ</th>
                        
                    </tr>
                </thead>
                <tbody>
                <?php
                    $i=1;
                    foreach ($results_emp as $ins) { ?>
                        <tr>
                            <td><?= $i++ ?></td> 
                            <td><?= $ins["Name"] ?></td>
                            <td><?= $ins["branch_name"] ?></td>
                            <td><?= $ins["job_name"] ?></td>
                            <td><?= $ins["scetion_name"] ?></td>
                            <td><?= !empty($row['Amount']) ?($row['Amount'].' '.$row['Currency']):'غير محدد' ?></td>
                        </tr>
                    <?php } ?> 
                </tbody>
            </table>
        </div>

        
                        
        <?php if(!empty($row["extionsion"])) :?>
        <div class="section">
            <div class="section-title">قائمة المستثنون من المكافئة</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">اسم الموظف</th>
                        <th style="width: 10%;">الفرع</th>
                        <th style="width: 25%;">المسمى الوظيفي</th>
                        <th style="width: 20%;">القسم</th>
                        <th style="width: 15%;">المبلغ</th>
                        
                    </tr>
                </thead>
                <tbody>
                <?php
                    $i=1;
                    foreach ($results_E as $ins) { ?>
                        <tr>
                            <td><?= $i++ ?></td> 
                            <td><?= $ins["Name"] ?></td>
                            <td><?= $ins["branch_name"] ?></td>
                            <td><?= $ins["job_name"] ?></td>
                            <td><?= $ins["scetion_name"] ?></td>
                            <td><?= !empty($row['Amount']) ?($row['Amount'].' '.$row['Currency']):'غير محدد' ?></td>
                        </tr>
                    <?php } ?> 
                </tbody>
            </table>
        </div>
        <?php endif;?>


        <?php if(!empty($row["Reason"])) :?>
        <div class="section">
            <div class="section-title">الوصف</div>
            <table>
                <thead>
                </thead>
                <tbody>
                        <tr>
                            <td><?=$row["Reason"]?></td> 

                        </tr>
                </tbody>
            </table>
        </div>
        <?php endif;?>
                <!-- end of our -->
	
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
	
// $("#purظ_nav").addClass('menu-open');
// // $("#top_pur_menu").addClass('active');
// // $("#pur_clients_menu").addClass('active');
    
// const urlParams = new URLSearchParams(window.location.search);
// const param_id = urlParams.get('id');


// function confirm_remove (id) {
// 	if(id !=''){
// 			$('#modal_title').text('تأكيد عملية الحذف');
// 				  $('#modal_default .modal-body').addClass('loader');
// 				  $('#modal_default .modal-dialog').removeClass('modal-lg');
// 				$('#modal_default').modal({show:true});
// 			  $('#modal_default .modal-body').load('./hr-app/incentive-remove?id='+id+'',function(){
// 				 // $('#modal_default .modal-dialog').addClass('modal-md');
// 			});
// 		}
// 	}

// function confirm_check (id) {
// 	if(id !=''){
// 			$('#modal_title').text('تأكيد عملية الحذف');
// 				  $('#modal_default .modal-body').addClass('loader');
// 				  $('#modal_default .modal-dialog').removeClass('modal-lg');
// 				$('#modal_default').modal({show:true});
// 			  $('#modal_default .modal-body').load('./hr-app/incentive-conform?id='+id+'',function(){
// 			});
// 		}
// 	}    
    

// $(document).on('click', '.remove_client', function(){
// 	var id = $(this).val();
// 	confirm_remove(id);

// }); 

// // 
// $(document).on('click', '.conform', function(){
// 	var id = $(this).val();
// 	confirm_check(id);

// }); 
// 




function CreatePDFfromHTML(filename) {
const employeeName = document.getElementById('fullName')?.textContent || 'employee_report';
const dateStr = new Date().toISOString().slice(0, 10);
const fileName = filename;
const options = {
scale: 2,
logging: false,
useCORS: true,
allowTaint: true,
letterRendering: true,
quality: 1
};
const element = document.getElementById("dataincentiv");
html2canvas(element, options).then(canvas => {
const imgData = canvas.toDataURL('image/png', 1.0);
const pdf = new jsPDF('p', 'mm', 'a4');
// تحديد الهوامش
const marginLeft = 10;
const marginRight = 10;
const marginTop = 10;
const marginBottom = 10;

// عرض الصورة داخل الهوامش

const imgWidth = 210 - marginLeft - marginRight; // 190mm
const pageHeight = 297; // ارتفاع A4 بالمليمتر (لاحظ أن 297 هو القياس الصحيح لـ A4 بالطول، لكننا كنا نستخدم 295 سابقاً. سنصححه)
const imgHeight = canvas.height * imgWidth / canvas.width;
let position = marginTop; // بداية الرسم من الهامش العلوي
let heightLeft = imgHeight;

// إضافة الصفحة الأولى

pdf.addImage(imgData, 'PNG', marginLeft, position, imgWidth, imgHeight);

heightLeft -= (pageHeight - marginTop - marginBottom); // نطرح الجزء المرسوم من الصفحة (الصفحة الأولى تبدأ من marginTop وتنتهي قبل marginBottom)

// إذا بقي جزء من الصورة، نضيف صفحات جديدة

while (heightLeft > 0) {
position = -heightLeft + marginTop; // لأننا نرسم الجزء المتبقي من الصورة من الأعلى، لكن في الصفحة الجديدة نبدأ من الأعلى أيضاً. لاحظ أننا نستخدم قيمة سالبة لرفع الصورة لأعلى.
pdf.addPage();
pdf.addImage(imgData, 'PNG', marginLeft, position, imgWidth, imgHeight);
heightLeft -= (pageHeight - marginTop - marginBottom); // نطرح ارتفاع الصفحة (مخصوماً منه الهوامش العلوية والسفلية)
}
pdf.save(`${fileName}.pdf`);
});
}


$(document).on('click', '.download_pdf', function(){
    var file_name = $(this).val();
    CreatePDFfromHTML(file_name);
});
 



});



function printData() {
    try {
        // إنشاء نافذة طباعة جديدة
        const printWindow = window.open('', '_blank');
        
        // إحضار محتوى القسم المطلوب
        const printContents = document.getElementById("main-info").innerHTML;
        
        // أنماط الطباعة المخصصة
        const styles = `
            <style>
                @media print {
                    body { font-family: Arial, sans-serif; line-height: 1.5; color: #000; }
                    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    table, th, td { border: 1px solid #000 !important; }
                    th { background-color: #f2f2f2 !important; text-align: right; padding: 8px; font-weight: bold; }
                    td { padding: 8px; text-align: right; }
                    .section-title { font-weight: bold; font-size: 18px; margin: 20px 0 10px 0; border-bottom: 2px solid #000; padding-bottom: 5px; }
                    .no-print { display: none !important; }
                }
                @page { size: auto; margin: 10mm; }
                @media print {
    body {
        direction: rtl;
        text-align: right;
        font-size: 14pt;
    }
}
            </style>
        `;
        
        // كتابة المحتوى في نافذة الطباعة
        printWindow.document.write(`
            <html>
                <head>
                    <title>تقرير الموظف</title>
                    ${styles}
                </head>
                <body>
                    ${printContents}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                                window.close();
                            }, 200);
                        };
                    <\/script>
                </body>
            </html>
        `);
        
        printWindow.document.close();
    } catch (error) {
        console.error('حدث خطأ أثناء الطباعة:', error);
        alert('حدث خطأ أثناء تحضير الطباعة. الرجاء المحاولة مرة أخرى.');
    }
}

</script>