
<?php
$appid  = 'HR';
$page_perm=['اضافة اجازة رسمية'];
// $screen = 'العطلات';
// if(isset($_GET['id'])){
//    $page_title = 'إدارة العطلات';
// }else{
// $page_title = 'إضافة عطلة';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
// 

// 


$form_title = 'إضافة عطلة جديده';
$save_btn = 'حفظ';


if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    $query = " SELECT a.Id,a.Holiday_ID, a.BranchID,a.Name,a.Start_date,a.End_date,a.CreatedDate,a.LastUpdateDate,
 u.FirstName , u.LastName
FROM   holidays AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
LEFT JOIN tblusers AS u ON a.CreatedBy = u.UserID
	WHERE a.Id = :id 
	LIMIT 1 ";
	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $get_id
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        $form_title = 'تعديل عطلة  ' .$row['Name'].'';
        $save_btn = 'حفظ التغييرات';
		// 
        if ($row) {
            $holidayID = $row['Holiday_ID']; 
            $sql2 = "
                SELECT 
                    Description,
                    Date
                FROM 
                    holidays_day
                WHERE 
                    HolidayID = :holidayID;
            ";
    
            $stmt2 = $connect_pdo->prepare($sql2);
            $stmt2->execute([':holidayID' => $holidayID]);
            $holidaysData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
	}else{
		echo'<script> location.replace("holidays-list"); </script>';
		die();
	}	
}
?>


   
   <div class="content-header page-nav" >
     <div class="container-fluid ">
       <div class="row ">
         <div class="col-7">
           <span class="page-title"><?=$form_title?></span>
         </div>
         <div class="col-5 text-left">	
           <button type="button" class="btn btn-success"  id="save-groub"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
        </div>
       </div>
     </div>
   </div>
  
   
   
       
   


   <section class="content">
       <div class="container-fluid">
   <form class="form-horizontal" role="form" action="" method="post" id="AddShift">	
   <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
   <div class="row">
           <div class="col-md-12">
               <div class="invoice card mb-3 shadow-none">
               <div class="card-header"    style="background: aliceblue; cursor: pointer">
                   <h4 class="card-title">تفاضيل العطلة</h4>
                   <span style="float: left;"><div class="card-tools">
                         <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
                           <i class="fas fa-minus"></i>
                         </button>
                       </div></span>
               </div>
               

                   <div class="card-body p-3">

                               <div class="row">
                                   <div class="form-group col-md-6">
                                   <label class="col-form-label required"  for="H_name">اسم العطلة(الاجازة)</label>
                                   <input type="text" id="H_name" name="H_name" value="<?=!empty($row['Name']) ? $row['Name'] : ''?>" class="form-control" placeholder="إجازات رسمية" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list[]"<?php echo !isset($_GET['id']) ? "multiple='multiple'" : ''; ?> required >
                                    <?php
									if(!empty($row['BranchID'])) {
										echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
									}
                                    else
                                    {
                                        foreach($allowed_branches as $id => $name){	
                                            echo'<option value="'.$id.'" >'.$name.'</option>';
                                        }
                                    }

                                    ?>
                                    </select>
                              </div>
                               </div>
                               
                               <div class="row">
                                   <div class="form-group col-md-6">
                                       <label class="col-form-label required" for="form_date">من تاريخ</label>
                                       <input type="date" name="form_date" class="form-control "  placeholder="تاريخ البداء" id="form_date" autocomplete="off" value="<?=!empty($row['Start_date']) ? $row['Start_date'] : ''?>">
                                       </div>

                                       <div class="form-group col-md-6">
                                       <label class="col-form-label required" for="until_date">حتى الان</label>
                                       <input type="date" name="until_date" class="form-control"  placeholder="تاريخ الانتهاء" id="until_date" autocomplete="off" value="<?=!empty($row['End_date']) ? $row['End_date'] : ''?>">
                                       </div>
                               </div>
                               
                               <h5 class="mt-4">إضافة العطلات الأسبوعية</h5>

                               <div class="row" style="display:flex;align-items: flex-end;">
                               <div class="form-group col-md-6">
                                   <label class="col-form-label " for="day">اليوم</label>
                                   <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل اليوم" id="day" name="day"  >
                                <option value="0">الأحد</option>
                                <option value="1">الاثنين</option>
                                <option value="2">الثلاثاء</option>
                                <option value="3">الأربعاء</option>
                                <option value="4">الخميس</option>
                                <option value="5">الجمعة</option>
                                <option value="6">السبت</option>
                                    
                                       </select>
                                 </div>
                                 <div class="form-group col-md-6">
                                   <div class="btn btn-primary" id="add-weekly-holiday">أضف إلى العطلات</div>
                                   </div>
                               </div>
                               <table class="table table-bordered mt-2" id="holidays-table">
               <thead>
                   <tr>
                       <th>#</th>
                       <th>تاريخ *</th>
                       <th>وصف *</th>
                       <th>إجراء</th>
                   </tr>
               </thead>
               <tbody>
               <?php
                    if (!empty($holidaysData)) 
                        foreach ($holidaysData as $holiday) {
                    ?>
                    <tr>
                    <td><input type="checkbox"></td>
                    <td><input type="date" id="date_date" name="date_date" class="form-control" value="<?= $holiday['Date']?>"  required></td>
                    <td><input type="text"id="date_description" name="date_description" class="form-control" value="<?= $holiday['Description']?>" required></td>
                    <td><button class="btn btn-danger btn-sm delete-row">مسح</button></td>
                    </tr>
                    <?php
                        }
                    ?>
               </tbody>
           </table>
           <div class="row" >
        <span class="btn btn-success col-md-2" id="add-row">إضافة صف</span>
<span class="btn btn-danger col-md-2" id="clear-table">حذف الجدول</span>
                    </div>
</div>
                        
                    
					</div>
				</div>
			</div>
			
			
        
            
			
		</div>
		</form>
      </div>
	  
    </section>
	





<?php

 include_once('inc/footer.php');

?>
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');


$(document).on('click', '#save-groub', function(){
	$('#AddShift').trigger('submit');
});




$('#AddShift').on('submit', function(e) {
    e.preventDefault(); 

    // جمع بيانات الجدول
    let tableData = [];
    $("#holidays-table tbody tr").each(function() {
        let date = $(this).find("td:nth-child(2) input").val(); 
        let description = $(this).find("td:nth-child(3) input").val(); 

        if (date && description) {
            tableData.push({ date: date, description: description });
        }
    });
    var form_data = $(this).serialize() + '&id=' + param_id;
    form_data += '&tableData=' + JSON.stringify(tableData);
    if ($(this).valid()) {
        $.ajax({
            url: "hr-app/index.php?action=hodidays-add",
            method: "POST",
            data: form_data,
            dataType: "json",
            beforeSend: function() {
                $('#preloading').show();
                
            },
            success: function(data) {
                if (data.result) {
                    toastr.success(data.msg); 
                    if (data.id > 0) {
                        window.location.href = 'holidays-view?id=' + data.id; 
                    }
                } else {
                    toastr.error(data.msg); 
                }
                $('#preloading').hide();
            },
            error: function(xhr, status, error) {
    let errorMessage = "حدث خطأ أثناء الاتصال بالخادم!";

    if (status === "timeout") {
        errorMessage = "انتهت مهلة الاتصال بالخادم. يرجى المحاولة مرة أخرى.";
    } else if (status === "error") {
        if (xhr.status === 404) {
            errorMessage = "لم يتم العثور على الصفحة المطلوبة (404).";
        } else if (xhr.status === 500) {
            errorMessage = "حدث خطأ داخلي في الخادم (500).";
        } else {
            errorMessage = "حدث خطأ غير متوقع: " + error;
        }
    } else if (status === "parsererror") {
        errorMessage = "تعذر تحليل الرد من الخادم. يرجى التحقق من البيانات المرسلة.";
    }

    toastr.error(errorMessage); 
    $('#preloading').hide(); 
}
        });
    }
});



	$('#AddShift').validate({
		errorElement: 'span',
		errorPlacement: function (error, element) {
		  error.addClass('invalid-feedback');
		  element.closest('div').append(error);
		},
		highlight: function (element, errorClass, validClass) {
		  $(element).addClass('is-invalid');
		},
		unhighlight: function (element, errorClass, validClass) {
		  $(element).removeClass('is-invalid');
		}
	});

    //من اجل اضافتة تاريخ النهاية
    $("#form_date").on("change", function() {
        let startDate = new Date($(this).val());
        if (!isNaN(startDate.getTime())) {
            let endDate = new Date(startDate);
            endDate.setFullYear(endDate.getFullYear() + 1);
            $("#until_date").val(endDate.toISOString().split('T')[0]);
        }
    });

    // إضافة العطلات الأسبوعية إلى الجدول
    $("#add-weekly-holiday").on("click", function() {
        let startDate = new Date($("#form_date").val());
        let endDate = new Date($("#until_date").val());
        let selectedDay = parseInt($("#day").val());
        let dayName = $("#day option:selected").text();

        // التحقق من صحة التواريخ
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            toastr.error("يرجى تحديد فترة صحيحة (من تاريخ وإلى تاريخ)!"); 
            return;
        }

        // إضافة التواريخ إلى الجدول
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            if (currentDate.getDay() === selectedDay) {
                let formattedDate = currentDate.toISOString().split('T')[0];
                $("#holidays-table tbody").append(`
                    <tr>
                        <td><input type="checkbox"></td>
                        <td><input type="date" id="date_date" name="date_date" class="form-control" value="${formattedDate}"  required></td>
                        <td><input type="text"id="date_description" name="date_description" class="form-control" value="${dayName}" required></td>
                        <td><button class="btn btn-danger btn-sm delete-row">مسح</button></td>
                    </tr>
                `);
            }
            currentDate.setDate(currentDate.getDate() + 1); // الانتقال إلى اليوم التالي
        }
    });

    // حذف صف من الجدول
    $(document).on("click", ".delete-row", function() {
        $(this).closest("tr").remove();
    });
// اضافة صف
$("#add-row").on("click", function() {
                $("#holidays-table tbody").append(`
                    <tr>
                        <td><input type="checkbox"></td>
                        <td><input type="date" id="date_date" name="date_date" class="form-control" value=""  required></td>
                        <td><input type="text"id="date_description" name="date_description" class="form-control" value="" required></td>
                        <td><button class="btn btn-danger btn-sm delete-row">مسح</button></td>
                    </tr>
                `);
            });
            // 
            // حذف الجدول كامل
            $("#clear-table").on("click", function() {
                $("#holidays-table tbody").empty(); // حذف جميع الصفوف
            });
            // 
});
 

</script>
