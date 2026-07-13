<?php
// $screen = ' استقالة الموظفين';
// if(isset($_GET['id'])){
//  $page_title = 'إدارة استقالة الموظفين';
// }else{
// $page_title = 'إضافة';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';

include_once('inc/header.php'); // Ensure this path is correct
$allowed_branches = $User->allBranches($User->branches);
//
$query = "SELECT UserID ,CONCAT(FirstName, ' ', LastName) as emp_name
FROM  tblusers
WHERE  isemp IS NOT NULL"; // Changed isemp is not null to IS NOT NULL for better SQL practice

$st = $connect_pdo->prepare($query);
$st->execute(
   array());

$emp = []; // Initialize $emp array to prevent errors if no results
if($st->rowCount() > 0){
   $results = $st->fetchAll(PDO::FETCH_ASSOC);

   foreach($results as $rows){
       $emp[] = array(
           'id' => $rows['UserID'],
           'name' => $rows['emp_name']
       );
    }
}
//

$form_title = 'إضافة طلب استقالة جديد';
$save_btn = 'حفظ';

$row = []; // Initialize $row to prevent undefined variable notices
$client_id = null; // Initialize client_id

if(isset($_GET['id'])){
    $client_no = (int)$_GET['id'];

    $query = "SELECT a.Id ,a.UserID,a.DueDate,a.Reason, a.Draft,
     f.FirstName as f_name, f.LastName as l_name, a.Status
    FROM   tblresignation a
    LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
    WHERE  Id  = :id ";


    $st = $connect_pdo->prepare($query);
    $st->execute(array(':id'  => $client_no));

    if($st->rowCount() > 0){
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if(!empty($row["Status"]) && $row["Status"] != 0) // Only redirect if status is not 'draft' (0) or null
        {
            echo'<script> location.replace("resignation-list-add"); </script>';
            die();
        }

        $client_id = $row['Id'];
        $form_title = 'تعديل طلب استقالة الموظف ';
        $save_btn = 'حفظ التغييرات';
    }
    else{
        echo'<script> location.replace("resignation-list-add"); </script>';
        die();
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  /* Custom styles for a cleaner look */
  .content-header.page-nav {
    background: #f8f9fa; /* Light background for header */
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
  }
  .page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #343a40;
  }
  .card.invoice {
    border-radius: .5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
  }
  .card-header {
    background: linear-gradient(to right, #eaf4ff, #d0e7ff); /* Gradient background for card header */
    border-bottom: 1px solid #cce5ff;
    color: #004085;
    font-weight: 600;
    padding: .75rem 1.25rem;
    border-top-left-radius: calc(.5rem - 1px);
    border-top-right-radius: calc(.5rem - 1px);
  }
  .card-title {
    font-size: 1.25rem;
    font-weight: 500;
  }
  .form-group label.required:after {
    content: " *";
    color: #dc3545;
  }
  .select2-container--default .select2-selection--single {
    height: calc(2.25rem + 2px); /* Match Bootstrap input height */
    border-color: #ced4da;
    border-radius: .25rem;
    display: flex;
    align-items: center;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem + 2px);
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem + 2px);
    padding-right: .75rem; /* For RTL */
    padding-left: .75rem;
  }
  .form-control.input-date {
    background-color: #fff;
  }
  textarea#Reson {
    border-radius: .25rem;
    border: 1px solid #ced4da;
    padding: .375rem .75rem;
  }
  .btn-success {
    background-color: #28a745;
    border-color: #28a745;
  }
  .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
  }
  .alert-info {
    background-color: #e2f2fb;
    border-color: #b8daff;
    color: #0c5460;
  }
  /* Ensure preloading is styled correctly if you use it */
  #preloading {
    display: none; /* Hide by default, shown by JS */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }
  #preloading img {
    width: 50px; /* Adjust size of your loading gif */
    height: 50px;
  }
</style>

    <div class="content-header page-nav">
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">
            <button type="button" class="btn btn-success"  id="save-incentive"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
         </div>
        </div>
      </div>
    </div>


    <section class="content">
        <div class="container-fluid">
            <form class="form-horizontal" role="form" action="" method="post" id="AddIncentive">
                <input type="hidden" value=""  class="" id="shift_id" name="shift_id">
                <div class="row justify-content-center"> <!-- Center the form content -->
                    <div class="col-md-9"> <!-- Adjust column width for better readability -->
                        <div class="invoice card mb-3 shadow-none">
                            <div class="card-header" style="cursor: pointer">
                                <h4 class="card-title">تفاصيل الاستقالة</h4>
                                <div class="card-tools" style="float: left;">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" style="margin: 0; padding: 2px 5px;">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label class="col-form-label required" for="emp_list">اسم الموظف</label>
                                        <select class="form-control select2" data-live-search="true" data-container="body" data-width="100%" title="اسم الموظف" id="emp_list" name="UserID" required> <!-- Changed name to UserID -->
                                            <?php
                                            if(!empty($row['UserID'])){
                                                echo '<option selected value="' . $row['UserID'] . '">' . $row['f_name'] .' '. $row['l_name'].'</option> ';
                                            } else {
                                                echo '<option value="">اختر موظف...</option>'; // Placeholder for Select2
                                                foreach($emp as $emp_row) { // Renamed $roww to $emp_row to avoid conflict with $row
                                                    echo '<option value="' . $emp_row['id'] . '">' . $emp_row['name'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-12">
                                        <label class="col-form-label required" for="Due_date">تاريخ الاستقالة</label>
                                        <input type="text" name="DueDate" class="form-control input-date"  placeholder="تاريخ الاستقالة" id="Due_date" autocomplete="off" value="<?=(!empty($row['DueDate'])? $row['DueDate'] : '' )?>" required> <!-- Changed name to DueDate -->
                                        <input type="hidden" name="type" id="type" value="1">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                         <label class="col-form-label"  for="Reson">السبب</label><br>
                                         <textarea name="Reason" id="Reson" rows="5" class="form-control" style="resize: vertical;"><?=!empty($row['Reason']) ? $row['Reason'] : ''?></textarea> <!-- Changed name to Reason, added form-control class, adjusted rows -->
                                    </div>
                                </div>

                                <div id="detials" class="mt-4">
                                    <?php if (!empty($row['UserID'])): ?>
                                    <!-- This section will be populated via AJAX, but for an initial view on edit, you might want to show previous info -->
                                    <div class="alert alert-info text-center">
                                        تم تحميل بيانات استقالات سابقة للموظف (سيتم تحديثها عند التغيير)
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

<?php
 include_once('inc/footer.php'); // Ensure this path is correct
?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/localization/messages_ar.min.js"></script>
<!-- You might need a datepicker library if 'input-date' is a custom class -->
<!-- Example: jQuery UI Datepicker (ensure jQuery UI CSS is also included if you use this) -->
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->
<!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"> -->


<script>
$(document).ready(function(){

    // Initialize Select2
    $('.select2').select2({
        placeholder: "اختر موظف...",
        allowClear: true // Option to clear selection
    });

    // Initialize Datepicker (assuming you have one, e.g., jQuery UI or similar)
    // If you are using a specific datepicker library, make sure its JS/CSS are included.
    // Example for jQuery UI Datepicker:
    // $(".input-date").datepicker({
    //     dateFormat: 'yy-mm-dd', // Or your desired format
    //     changeMonth: true,
    //     changeYear: true
    // });
    // If not jQuery UI, adapt this to your datepicker library.

    const urlParams = new URLSearchParams(window.location.search);
    const param_id = urlParams.get('id');

    // Trigger employee info load if we are in edit mode and an employee is pre-selected
    if ($('#emp_list').val() && param_id) {
        $('#emp_list').trigger('change');
    }

    $(document).on('click', '#save-incentive', function(){
        $('#AddIncentive').trigger('submit');
    });

    $('#AddIncentive').on('submit', function(e){
        e.preventDefault();

        // Fix for form_data: serialize() should now correctly pick up 'UserID'
        let form_data = $(this).serialize();
        if (param_id) {
            form_data += '&id=' + param_id;
        }

        if($(this).valid()){
            $.ajax({
                url:"hr-app/index.php?action=resignation-add-add",
                method:"POST",
                data:form_data,
                dataType:"json",
                beforeSend:function(){
                    $('#preloading').show();
                },
                success:function(data){
                    if(data.result){
                        toastr.success(data.msg);
                        if(data.id > 0){
                           window.location.href = 'resignation-list-add'; // Redirect to the list or fresh add page
                        }
                    } else {
                        toastr.error(data.msg);
                    }
                    $('#preloading').hide();
                }
            });
        }
    });

    $('#AddIncentive').validate({
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            // Place error message correctly for Select2
            if (element.hasClass('select2')) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                element.closest('.form-group').append(error); // Append to form-group for better layout
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection--single').addClass('is-invalid');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection--single').removeClass('is-invalid');
            }
        },
        rules: {
            UserID: { // Rule for the employee select (using its new name)
                required: true
            },
            DueDate: {
                required: true
            }
            // Add other rules as needed
        },
        messages: {
            UserID: {
                required: "الرجاء اختيار الموظف"
            },
            DueDate: {
                required: "الرجاء تحديد تاريخ الاستقالة"
            }
        }
    });

    $('#emp_list').on('change', function() { // Use on('change') for Select2
        var selectedValue = $(this).val();
        var employeeName = $(this).find('option:selected').text(); // Get the selected employee name

        if (selectedValue && selectedValue.length > 0) {
            $.ajax({
                url: 'hr-app/index.php?action=emp-info-resignation', // This URL should remain as it is if it's correct for fetching employee info
                type: 'POST',
                data: { value: selectedValue, type: 2 },
                dataType: "json",
                beforeSend: function() {
                    // Show preloading specifically for this section
                    $('#detials').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">جاري تحميل بيانات الموظف...</p></div>');
                },
                success: function(response) {
                    if (response.section && response.section.length > 0) {
                        let html = `
                            <div class="card card-outline card-primary mt-4">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="card-title">سجل استقالات الموظف: ${employeeName}</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-center align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>اسم الموظف</th>
                                                    <th>حالة الاستقالة</th>
                                                    <th>مسودة أو تم الرفع</th>
                                                    <th>تاريخ الاستقالة</th>
                                                    <th>أنشئ بواسطة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                        `;

                        response.section.forEach(function(emp_data, index) { // Renamed 'emp' to 'emp_data'
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${emp_data.name ?? '-'}</td>
                                    <td>${emp_data.statedevice ?? '-'}</td>
                                    <td>${emp_data.draft ?? '-'}</td>
                                    <td>${emp_data.date ?? '-'}</td>
                                    <td>${emp_data.name_add ?? '-'}</td>
                                </tr>
                            `;
                        });

                        html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#detials').html(html);
                    } else {
                        $('#detials').html('<div class="alert alert-info text-center mt-4"><i class="fas fa-info-circle"></i> لا توجد طلبات استقالة سابقة لهذا الموظف.</div>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#detials').html('<div class="alert alert-danger text-center mt-4"><i class="fas fa-exclamation-triangle"></i> حدث خطأ أثناء جلب البيانات: ' + textStatus + '</div>');
                    toastr.error('حدث خطأ أثناء جلب بيانات الموظف.');
                }
            });
        } else {
            $('#detials').html('<div class="alert alert-info text-center mt-4">الرجاء اختيار موظف لعرض سجل الاستقالات.</div>');
        }
    });

});
</script>