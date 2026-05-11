<?php
$appid  = 'HR';
$screen = 'الموظفين';
$page_title = 'تفاصيل الموظفين';
$report_name = 'تفاصيل الموظف';
 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
 $branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);

 $query_ = "SELECT u.UserID,u.lastversion as ID, CONCAT(u.FirstName, ' ', u.LastName) as Name ,s.BranchID
  
 FROM tblusers u 
 left join  tblremewal s ON s.Id  = u.lastversion

 WHERE u.BranchID IN  ($allowed_branch) and u.isemp is not null";
 $stmt = $connect_pdo->prepare($query_);
 $stmt->execute();
 $results = $stmt->fetchAll();

?>
<style>
.modal-dialog .overlay{
	background-color: rgba(255, 255, 255, 0.7);
}
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
/* .filter-advance
{
    height: 200px;
    overflow: scroll;
} */
</style>
    <!-- Content Header (Page header) -->
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title">معلومات الموظف</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	 
        </div>
        </div>
      </div>
    </div>
    <!-- /.content-header -->

    <!-- Search  -->

    
 

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
        <div class="row" id="filter-area" style="display:none_">
		 <div class="col-md-12">
         <div class="invoice mb-3" id="filter-area" style="display:none_">
		<div class="card-header bg-gry">
					<h3 class="card-title">بحث</h3>
				</div>
		<form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">

			
				<div class="card-body card-body pt-0 pb-0">
                <div class="row">
                
                <div class="col-md-6">
				<div class="form-group">
                <label for="branchs_list" class="col-form-label  logindata ">الموظف</label>
                                 <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أختر" id="employer" name="employer" required >
                                <?php
                                if(!empty( $results)){  
                                foreach ($results as $ins) { 
                                  echo'<option value="'.$ins["UserID"].'" >'.$ins["Name"].'</option>';
                                }
                              }
                                ?>
                                
                                </select>
				</div>
				</div>
                
    

                

                
                    
    
               
                </div>
                
                </div>
                

              
				
				<div class="p-1">
				
            <div class="text-left">
			  <button type="submit" class="btn btn-info" name="" ><i class="fas fa-search"></i> بحث</button>
			</div>
			
          </div>
		  

              <!-- /.row -->
			  </form>
			  <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
			</div> 

            <!-- my code -->
             
		
         </div>
         </div>
         </div>
        <div class="container-fluid" id="result-containr" style="display:none">

        <style>
@media print { 
    .table  { 
       width: 100% !important; 
    }
     .table  th { 
        background-color: #f5f5f5 !important; 
    } 
     section,div  { 
        background-color: #FFF !important; 
        padding: 0px !important; 
         margin: 0px !important;
    }
    
   a{
       color:#212529 !important; 
       text-decoration: none !important;
   }
   .invoice .d-none{
       display:block !important;
   }
   
}

</style>


<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
            <div class="d-print-none text-left mb-2">
                    <button type="button" class="btn btn-default print_repo" onclick="printData()"><i class="fas fa-print"></i> طباعة</button>
                    <button type="button" class="btn btn-primary  download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> PDF</button>
        </div>
                <div class="card shadow-sm border-light" style="overflow:scroll" id="datauser">
       
                    <!--  -->
                    <h3 class="section-title">البيانات الشخصية</h3>
                    <table class="official-table">
                    <tr>
                      <th width="25%">الاسم الكامل</th>
                      <td id="fullName"></td>
                    </tr>
                    <tr>
                <th>البريد الإلكتروني</th>
                <td id="userEmail"></td>
                    </tr>
                    <tr>
                <th>رقم الجوال</th>
                <td id="phone"></td>
                </tr>
                <tr>
                <th>هاتف آخر</th>
                <td id="otherPhone"></td>
            </tr>
            <tr>
                <th>العنوان</th>
                <td id="address"></td>
            </tr>
            <tr>
                <th>الجنس</th>
                <td id="gender"></td>
            </tr>
            <tr>
                <th>الحالة الاجتماعية</th>
                <td id="maritalStatus"></td>
            </tr>
            <tr>
                <th>الحالة الصحية</th>
                <td id="employmenthealth"></td>
            </tr>


            </table>
            <h3 class="section-title">المعلومات المالية</h3>
        <table class="official-table">
            <tr>
                <th width="25%">اسم البنك</th>
                <td id="bankName"></td>
            </tr>
            <tr>
                <th>رقم الحساب البنكي</th>
                <td id="bankAccount"></td>
            </tr>
            <tr>
                <th>شركات التأمين</th>
                <td id="insuranceCompanies"></td>
            </tr>
        </table>
        <!-- السجل الوظيفي -->
        <h3 class="section-title">السجل الوظيفي</h3>
        <table class="official-table" id="employmentHistory">
            <thead>
                <tr>
                    <th >الفترة</th>
                    <th >الوظيفة</th>
                    <th >الدرجة الوظيفية</th>
                    <th >القسم</th>
                    
                    <th >نمط العمل</th>
                    <th >المجموعة</th>
                    <th >فتره العمل</th>
                    <th >جهاز البصمة</th>
                    <th >الراتب</th>
                </tr>
            </thead>
            <tbody>
                <!-- سيتم ملؤها بواسطة JavaScript -->
            </tbody>
        </table>

        <h3 class="section-title">المستندات الرسمية</h3>
        <table class="official-table" id="officialDocuments">
            <thead>
                <tr>
                    <th width="25%">نوع المستند</th>
                    <th width="15%">رقم المستند</th>
                    <th width="20%">تاريخ الإصدار</th>
                    <th width="20%">تاريخ الانتهاء</th>
                    <th width="20%">الحاله</th>
                    <th width="20%">مرفق</th>
                </tr>
            </thead>
            <tbody>
                <!-- سيتم ملؤها بواسطة JavaScript -->
            </tbody>
        </table>
        <div class="section-container">
        <h3 class="section-title">الشهادات</h3>
        <table class="official-table" id="certificates">
            <thead>
                <tr>
                    <th width="30%">اسم الشهادة</th>
                    <th width="25%">الجهة المانحة</th>
                    <th width="20%">تاريخ الإصدار</th>
                    <th width="25%">ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <!-- سيتم ملؤها بواسطة JavaScript -->
            </tbody>
        </table>
</div>
        <div class="exper-container">
        <h3 class="section-title">الخبرات العملية</h3>
        <table class="official-table" id="experiences">
            <thead>
                <tr>
                    <th width="25%">المسمى الوظيفي</th>
                    <th width="25%">الجهة</th>
                    <th width="20%">تاريخ البدء</th>
                    <th width="20%">تاريخ الانتهاء</th>
                    <th width="10%">المدة</th>
                    <th width="10%">الملف</th>
                </tr>
            </thead>
            <tbody>
                <!-- سيتم ملؤها بواسطة JavaScript -->
            </tbody>
        </table>
</div>


                    <!-- النهاية -->
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
<!-- DataTables  & Plugins -->

<!-- DataTables loaded from CDN in footer.php -->


<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>


function printData() {
    try {
        // إنشاء نافذة طباعة جديدة
        const printWindow = window.open('', '_blank');
        
        // إحضار محتوى القسم المطلوب
        const printContents = document.getElementById("datauser").innerHTML;
        
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








// 

$(document).ready(function(){

    function CreatePDFfromHTML(filename) {
    // إنشاء اسم ملف ديناميكي
    const employeeName = document.getElementById('fullName')?.textContent || 'employee_report';
    const dateStr = new Date().toISOString().slice(0, 10);
    const fileName = filename;

    // إعدادات الجودة
    const options = {
        scale: 2, // زيادة الدقة
        logging: false,
        useCORS: true,
        allowTaint: true,
        letterRendering: true,
        quality: 1 // أعلى جودة
    };

    // عنصر الـ DOM المستهدف
    const element = document.getElementById("result-containr");
    
    html2canvas(element, options).then(canvas => {
        const imgData = canvas.toDataURL('image/png', 1.0);
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210; // عرض A4 بالمليمتر
        const pageHeight = 295; // ارتفاع A4 بالمليمتر
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        // صفحات إضافية إن لزم الأمر
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save(`${fileName}.pdf`);
    });
}

$(document).on('click', '.download_pdf', function(){
    var file_name = $(this).val();
    CreatePDFfromHTML(file_name);
});
 


function get_filter_info(name)
{
    $.ajax({
                url: 'hr-app/index.php?action=report-one-empyer',
                type: 'POST',
                data: { id: name },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
                success: function(response) { 
                    if(response.check)
                {
                    populateReport(response);
                    
                    $('#result-containr').show();
                }
                else
                {
                    toastr.error(response.msg);
                }
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
}




$('#filter-fm').on('submit', function(e){  
	e.preventDefault();
    var name = $('#employer').val();
	get_filter_info(name);
});




function populateReport(data) {
   
            // تعبئة البيانات الأساسية
            const employee = data.employee;
            document.getElementById('fullName').textContent = `${employee.FirstName}  ${employee.LastName}`;
            document.getElementById('userEmail').textContent = employee.UserEmail;
            document.getElementById('phone').textContent = employee.Phone; 
            document.getElementById('otherPhone').textContent = employee.ohter_phone || 'غير متوفر';
            document.getElementById('address').textContent = employee.user_address || 'غير متوفر';
            document.getElementById('gender').textContent = employee.Sex == 1 ? 'ذكر' :employee.Sex == 2 ?'انثى':'غير محدد'; 
            document.getElementById('maritalStatus').textContent = getMaritalStatus(employee.marital_status);
            document.getElementById('employmenthealth').textContent = employee.HealthCondition || 'غير متوفر';
            // تعبئة المعلومات المالية
            document.getElementById('bankName').textContent = employee.user_bank_name || 'غير محدد';
            document.getElementById('bankAccount').textContent = employee.user_account_bank || 'غير محدد';
            document.getElementById('insuranceCompanies').textContent = data.instant_names.join(', ') || 'غير محدد';
            
            // تعبئة السجل الوظيفي
            const employmentTable = document.getElementById('employmentHistory').getElementsByTagName('tbody')[0];
            employmentTable.innerHTML = '';
            
            data.struct.forEach(job => {
                const row = employmentTable.insertRow();
                row.innerHTML = `
                    <td>${job.new_s_date} , ${job.new_e_date}</td>
                    <td>${job.jobtitle_name || 'غير محدد'}</td>
                    <td>${job.name_grade || 'غير محدد'}</td>
                    <td>${job.section_name || 'غير محدد'}</td>
                    <td>${job.name_n || 'غير محدد'}</td>
                    <td>${job.group_name || 'غير محدد'}</td>
                    <td>${data.shift_names.join(', ') || 'غير محدد'}</td>
                    <td>${data.finger_names.join(', ') || 'غير محدد'}</td>
                    <td>${job.Salary} ${job.Currency}</td>
                `;
            });
            
            // تعبئة المستندات الرسمية
            const docsTable = document.getElementById('officialDocuments').getElementsByTagName('tbody')[0];
            docsTable.innerHTML = '';
            
            if (employee.Id_h) {
                const row = docsTable.insertRow();
                row.innerHTML = `
                    <td>الهوية الوطنية</td>
                    <td>${employee.Id_h}</td>
                    <td>${employee.start_date_h}</td>
                    <td>${employee.end_date_h || 'غير محدد'}</td>
                    <td>${isDocumentValid(employee.end_date_h)}</td>
                    <td>${employee.path_h ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            }
            
            if (employee.Id_license) {
                const row = docsTable.insertRow();
                row.innerHTML = `
                    <td>رخصة القيادة</td>
                    <td>${employee.Id_license}</td>
                    <td>${employee.start_date_license}</td>
                    <td>${employee.end_date_license || 'غير محدد'}</td>
                    <td>${isDocumentValid(employee.end_date_license)}</td>
                    <td>${employee.path_license ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            }
            if (employee.Id_passport) {
                const row = docsTable.insertRow();
                row.innerHTML = `
                    <td>جواز سفر</td>
                    <td>${employee.Id_passport}</td>
                    <td>${employee.start_date_passport}</td>
                    <td>${employee.end_date_passport || 'غير محدد'}</td>
                    <td>${isDocumentValid(employee.end_date_passport)}</td>
                    <td>${employee.path_passport ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            }
            if (employee.Id_health) {
                const row = docsTable.insertRow();
                row.innerHTML = `
                    <td> الشهاده الصحية</td>
                    <td>${employee.Id_health}</td>
                    <td>${employee.start_date_health}</td>
                    <td>${employee.end_date_health || 'غير محدد'}</td>
                    <td>${isDocumentValid(employee.end_date_health)}</td>
                    <td>${employee.path_health ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            }
            
            // تعبئة الشهادات
            const certTable = document.getElementById('certificates').getElementsByTagName('tbody')[0];
            certTable.innerHTML = '';

            if (data.certifcate && data.certifcate.length > 0) {
                // إذا كان هناك شهادات، نملأ الجدول
                data.certifcate.forEach(cert => {
                    const row = certTable.insertRow();
                    row.innerHTML = `
                        <td>${cert.Certifacte_name}</td>
                        <td>${cert.Side}</td>
                        <td>${cert.StartDate}</td>
                        <td>${cert.FilePath ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                    `;
                });
                // نضمن أن الجدول مرئي
                document.getElementById('certificates').closest('.section-container').style.display = 'block';
            } else {
                // إذا لم يكن هناك شهادات، نخفي القسم كاملاً
                document.getElementById('certificates').closest('.section-container').style.display = 'none';
            }
            
            // تعبئة الخبرات العملية
            const expTable = document.getElementById('experiences').getElementsByTagName('tbody')[0];
            expTable.innerHTML = '';
            
            if (data.experince && data.experince.length > 0) {
            data.experince.forEach(exp => {
                const row = expTable.insertRow();
                row.innerHTML = `
                    <td>${exp.TitleJob}</td>
                    <td>${exp.side}</td>
                    <td>${exp.StartDate}</td>
                    <td>${exp.EndDate || 'حتى الآن'}</td>
                    <td>${calculateDuration(exp.StartDate, exp.EndDate)}</td>
                    <td>${exp.FilePath ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            });
            document.getElementById('experiences').closest('.exper-container').style.display = 'block';
        }
        else
        {
            document.getElementById('experiences').closest('.exper-container').style.display = 'none';
        }
        }
        
        function getMaritalStatus(status) {
            const statuses = {
                1: 'متزوج',
                2: 'أعزب',
                3: 'أرمل',
                4: 'مطلق'
            };
            return statuses[status] || 'غير محدد';
        }
        
        function isDocumentValid(endDate) {
            if (!endDate) return 'غير محدد';
            
            const today = new Date();
            const expiryDate = new Date(endDate);
            
            return expiryDate > today ? 
                '<span class="text-success">ساري</span>' : 
                '<span class="text-danger">منتهي</span>';
        }
        
        function calculateDuration(startDate, endDate) {
            if (!startDate) return 'غير محدد';
            
            const start = new Date(startDate);
            const end = endDate ? new Date(endDate) : new Date();
            
            const years = end.getFullYear() - start.getFullYear();
            const months = end.getMonth() - start.getMonth();
            
            let duration = '';
            if (years > 0) duration += years + ' سنة ';
            if (months > 0) duration += months + ' شهر';
            
            return duration || 'أقل من شهر';
        }






});
</script>
