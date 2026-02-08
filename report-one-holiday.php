   <?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("report-holidays"); </script>';
	die(); 
} 
$screen = 'الإجازات الرسمية';
$page_title = 'تفاصيل الإجازه الرسمية';
$report_name = 'تفاصيل الإجازه الرسمية';
 include_once('inc/header.php');
 
 if (isset($_GET['id'])) {
    $get_id = (int)$_GET['id'];  // تأكد من أن id هو عدد صحيح
    
    // الاستعلام الأساسي لجلب البيانا

$query = " SELECT a.Id,b.branch_name,a.Name,a.Start_date,a.End_date,a.CreatedDate,a.Holiday_ID,
 u.FirstName , u.LastName
FROM   holidays AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
where a.Id =:id
";
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $get_id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();


        $query_emp = "SELECT 
                    Description,
                    Date,
                    CreatedDate,HolidayID
                FROM 
                    holidays_day
                WHERE 
                    HolidayID = :holiday_id";
            
            
                
                
                $stmt_emp = $connect_pdo->prepare($query_emp);
                $stmt_emp->execute(['holiday_id' => $row['Holiday_ID']]);
                $results_emp = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);





        // 
		
		

	}else{
		echo'<script> location.replace("report-holidays"); </script>';
		die();
	}
	
	
}




?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <script type="text/javascript" src="plugins/jspdf/jspdf.min.js"></script>
  <script type="text/javascript" src="plugins/jspdf/html2canvas.js"></script>
   
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
                <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true"><strong>عن نمط العمل</strong></a>
              </li>            
			</ul>
           
            <div class="tab-content p-2" id="custom-content-above-tabContent" style="border-right: 1px solid #dddfe3;border-left: 1px solid #dddfe3;border-bottom: 1px solid #dddfe3; ">
			<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab" style="">
			  
			<div class="result-containr" style="overflow:scroll" id="dataincentiv">
			<!-- start -->
            
                            
                               <div class="section">
            <div class="section-title">معلومات الاجازه الرسمية </div>
            <table>
                <tbody><tr>
                    <td style="width: 25%; font-weight: bold;"> اسم الاجازة</td>
                    <td style="width: 25%;"><?= !empty($row['Name'])?$row['Name']:'غير محدد' ?></td>
                    <td style="font-weight: bold;">الفرع</td>
                    <td><?= !empty($row['branch_name'])?$row['branch_name']:'غير محدد' ?></td>
                </tr>

                
                <tr>
                    <td style="font-weight: bold;">تاريخ البدء</td>
                    <td><?= !empty($row['Start_date'])?$row['Start_date']:'غير محدد' ?></td>
                    <td style="font-weight: bold;">تاريخ الانتهاء</td>
                    <td><?= !empty($row['End_date'])?$row['End_date']:'غير محدد' ?></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">تاريخ الاضافة</td>
                    <td><?= !empty($row['CreatedDate'])?$row['CreatedDate']:'غير محدد' ?></td>
                    <td style="font-weight: bold;">انشاءه بواسطة</td>
                    <td><?= !empty($row['FirstName'])?$row['FirstName'].' '.$row['LastName']:'غير محدد' ?></td>
                </tr>
                  


            </tbody></table>
        </div>
			
   	


        <div class="section">
            <div class="section-title">ايام الاجازة</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">اليوم</th>
                        <th style="width: 10%;">التاريخ</th>
                        <th style="width: 25%;">تاريخ الاضافة</th>

                        
                    </tr>
                </thead>
                <tbody>
                <?php
                    $i=1;
                    foreach ($results_emp as $ins) { ?>
                        <tr>
                            <td><?= $i++ ?></td> 
                            <td><?= $ins["Description"] ?></td>
                            <td><?= $ins["Date"] ?></td>
                            <td><?= $ins["CreatedDate"] ?></td>
                        </tr>
                    <?php } ?> 
                </tbody>
            </table>
        </div>

        

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
        const printContents = document.getElementById("dataincentiv").innerHTML;
        
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
                    <title>تقرير الإجازات </title>
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