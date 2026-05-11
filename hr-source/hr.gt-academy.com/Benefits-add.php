<?php
$appid  = 'HR';
$page_perm = ['إضافة تعويض'];
$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة التعويضات والمزايا';

include_once('inc/header.php');

// Fetch user's allowed branches
$allowed_branches = $User->allBranches($User->branches);

$form_title = 'إضافة تعويض جديد';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';

// Initialize variables
$row = [];
$results = [];
$results_E = [];
$for_w = [];
$for_E = [];
$client_id = 0;

// Handle Edit Mode
if (isset($_GET['id'])) {
    $client_no = (int)$_GET['id'];
    $client_id = $client_no;

    $query = "SELECT Id, BranchID, UserID, name, Amount, Currency, Reason, for_what, extionsion, beneft_type, DueDate, AmountType, monthly, Status 
              FROM tblbenefit 
              WHERE Id = :id";
    
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $client_no]);
    
    if ($st->rowCount() > 0) {
        $row = $st->fetch(PDO::FETCH_ASSOC);
        
        // Logic to determine if editable based on status/date
        $flage = false;
        if ($row['beneft_type'] == 2) {
            if ($today_date < $row['DueDate']) $flage = true;
            elseif (empty($row['Status'])) $flage = true;
        } else {
            if (empty($row['Status'])) $flage = true;
        }

        if ($flage) {
            $query_ = "";
            if ($row['for_what'] == 1) {
                $query_ = "SELECT UserID as ID, CONCAT(FirstName, ' ', LastName) as Name FROM tblusers WHERE BranchID IN ($row[BranchID]) AND isemp IS NOT NULL";
            } elseif ($row['for_what'] == 2) {
                $query_ = "SELECT Id as ID, Name FROM tblgroup WHERE BranchID IN ($row[BranchID])";
            } elseif ($row['for_what'] == 3) {
                $query_ = "SELECT Id as ID, Name FROM tbljobgrade WHERE BranchID IN ($row[BranchID])";
            } elseif ($row['for_what'] == 4) {
                $query_ = "SELECT c.Id As ID, c.Name as Name FROM tblsection AS c LEFT JOIN tblsection AS d ON c.Id = d.ParentID WHERE c.ParentID IS NOT NULL AND d.Id IS NULL AND c.BranchID IN ($row[BranchID])";
            } elseif ($row['for_what'] == 5) {
                $query_ = "SELECT Id as ID, Name FROM tbljobtitle WHERE BranchID IN ($row[BranchID])";
            }

            if (!empty($query_)) {
                $stmt = $connect_pdo->prepare($query_);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!empty($row["extionsion"])) {
                $user_ids_E = explode(",", $row['extionsion']);
                $user_ids_E = array_map('intval', $user_ids_E); // Sanitize
                if(!empty($user_ids_E)) {
                    $placeholders_E = implode(",", array_fill(0, count($user_ids_E), "?"));
                    $query_Q = "SELECT UserID AS ID, CONCAT(FirstName, ' ', LastName) AS Name FROM tblusers WHERE UserID IN ($placeholders_E)";
                    $stmt_E = $connect_pdo->prepare($query_Q);
                    $stmt_E->execute($user_ids_E);
                    $results_E = $stmt_E->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
        
        $for_w = !empty($row['UserID']) ? array_unique(explode(',', $row['UserID'])) : [];
        $for_E = !empty($row['extionsion']) ? array_unique(explode(',', $row['extionsion'])) : [];
    }
}

// Handle Copy Mode
if (isset($_GET['copy'])) {
    $client_id = 0; // Reset ID for new insert
    $form_title = 'نسخ تعويض';
} elseif (isset($_GET['id'])) {
    $form_title = 'تعديل تعويض';
}
?>

<style>
    /* تحسينات التصميم الحديثة */
    body { background-color: #f4f6f9; }
    .card-custom { border-radius: 12px; border: none; box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important; overflow: hidden; }
    .card-header-custom { background: linear-gradient(90deg, #f8f9fa, #e9ecef) !important; border-bottom: 1px solid #dee2e6; padding: 1rem 1.25rem; }
    .card-title { font-weight: 600; color: #343a40; }
    .form-control { border-radius: 8px !important; border: 1px solid #ced4da; padding: 0.6rem 1rem; height: auto; transition: all 0.3s ease-in-out; }
    .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15); }
    .bootstrap-select > .dropdown-toggle { border-radius: 8px !important; border: 1px solid #ced4da !important; background-color: #fff !important; padding: 0.6rem 1rem; height: auto; }
    .bootstrap-select > .dropdown-toggle:focus { outline: none !important; border-color: #80bdff !important; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15) !important; }
    .bootstrap-select .dropdown-toggle .filter-option { text-align: right !important; }
    .input-group > .form-control:not(:last-child) { border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; border-top-right-radius: 8px !important; border-bottom-right-radius: 8px !important; }
    .input-group > .input-group-append > .form-control { border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; border-top-left-radius: 8px !important; border-bottom-left-radius: 8px !important; background-color: #f8f9fa; }
    .col-form-label { font-weight: 600; color: #495057; margin-bottom: 5px; }
    .form-group label.required:after { content: " *"; color: #e3342f; font-weight: bold; }
    .label-icon { color: #007bff; margin-left: 6px; font-size: 0.9em; }
    .btn-custom { border-radius: 8px; padding: 0.5rem 1.5rem; font-weight: 600; transition: all 0.2s; }
    .btn-custom:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    @media (max-width: 768px) { .bootstrap-select .dropdown-menu { max-width: 100%; } .card-header-custom { padding: 0.75rem 1rem; } }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">
                        <i class="fas fa-hand-holding-usd text-primary ml-2"></i><?= $form_title ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="Hrdashboard"><?= $screen ?></a></li>
                        <li class="breadcrumb-item active"><?= $form_title ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form class="form-horizontal" role="form" action="" method="post" id="AddBenefit">
                <input type="hidden" value="" id="shift_id" name="shift_id">
                <input type="hidden" name="isdraft" id="isdraft" value="0">
                
                <div class="row">
                    <div class="col-12 col-xl-10 m-auto"> 
                        <div class="card card-custom mb-4">
                            <div class="card-header card-header-custom d-flex justify-content-between align-items-center" data-card-widget="collapse" style="cursor: pointer;">
                                <h4 class="card-title m-0"><i class="fas fa-info-circle text-primary ml-2"></i>تفاصيل التعويض</h4>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool text-dark"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label class="col-form-label required" for="name"><i class="fas fa-file-signature label-icon"></i>اسم التعويض</label>
                                        <input type="text" name="name" class="form-control" placeholder="أدخل اسم التعويض..." id="name" autocomplete="off" value="<?= htmlspecialchars($row['name'] ?? '') ?>" required>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label class="col-form-label required" for="branchs_list"><i class="fas fa-code-branch label-icon"></i>الفرع</label>
                                        <select class="selectpicker form-control" data-live-search="true" title="أختر الفرع" id="branchs_list" name="branchs_list" required>
                                            <?php
                                            if (!empty($row['BranchID']) && isset($allowed_branches[$row['BranchID']])) {
                                                echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                            }
                                            foreach ($allowed_branches as $id => $name) {
                                                if (empty($row['BranchID']) || $row['BranchID'] != $id) {
                                                    echo '<option value="' . $id . '">' . $name . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label class="col-form-label required" for="beneft_type"><i class="fas fa-tags label-icon"></i>نوع التعويض</label>
                                        <select class="selectpicker form-control" title="أدخل النوع" id="beneft_type" name="beneft_type" required>
                                            <option value="1" <?= (!empty($row['beneft_type']) && $row['beneft_type'] == '1') ? 'selected' : '' ?>>شهرية تتكرر كل شهر</option>
                                            <option value="2" <?= (!empty($row['beneft_type']) && $row['beneft_type'] == '2') ? 'selected' : '' ?>>شهر محدد</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label id="title" class="col-form-label required" for="amount"><i class="fas fa-money-bill-wave label-icon"></i>المبلغ</label>
                                        <div class="input-group">
                                            <input type="text" step="any" name="amount" class="form-control" placeholder="القيمة..." id="amount" autocomplete="off" value="<?= htmlspecialchars($row['Amount'] ?? '') ?>" required>
                                            <div class="input-group-append">
                                                <select name="AmountType" id="AmountType" class="form-control" style="min-width: 65px; cursor: pointer;">
                                                    <option value="amount" <?= (!empty($row['AmountType']) && $row['AmountType'] == 'amount') ? 'selected' : '' ?>>$</option>
                                                    <option value="avg" <?= (!empty($row['AmountType']) && $row['AmountType'] == 'avg') ? 'selected' : '' ?>>%</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label class="col-form-label required" for="currency"><i class="fas fa-coins label-icon"></i>العملة</label>
                                        <select class="selectpicker form-control" data-live-search="true" title="أدخل العملة" id="currency" name="currency" required>
                                            <?php $selectedCurrency = $row['Currency'] ?? $User->currency; ?>
                                            <option value="<?= $selectedCurrency ?>" selected><?= $selectedCurrency == $User->currency ? 'عملة النظام' : $selectedCurrency ?></option>
                                            <?php if ($selectedCurrency != $User->currency): ?>
                                            <option value="<?= $User->currency ?>">عملة النظام</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4" id="Div_Due_date" style="display:none">
                                        <label class="col-form-label required" for="Due_date"><i class="fas fa-calendar-alt label-icon"></i>تاريخ الاستحقاق <small class="text-muted">(تصرف نهاية الشهر)</small></label>
                                        <input type="text" name="Due_date" class="form-control input-date" placeholder="تاريخ الاستحقاق" id="Due_date" autocomplete="off" value="<?= htmlspecialchars($row['DueDate'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row border-top pt-3 mt-2">
                                    <div class="form-group col-12 col-md-6 col-lg-4">
                                        <label class="col-form-label required" for="for_what"><i class="fas fa-users label-icon"></i>لمن تكون</label>
                                        <select class="selectpicker form-control" data-live-search="true" title="لمن تكون" id="for_what" name="for_what" required>
                                            <?php if (!empty($row['for_what'])): ?>
                                                <option value="<?= $row['for_what'] ?>" selected>
                                                    <?= ($row['for_what'] == 1 ? 'لموظف' : ($row['for_what'] == 2 ? 'لمجموعة' : ($row['for_what'] == 3 ? 'لدرجة وظيفية' : ($row['for_what'] == 5 ? 'لمسمى وظيفي' : ($row['for_what'] == 4 ? 'لقسم محدد' : ''))))) ?>
                                                </option>
                                            <?php endif; ?>
                                            <option value="1">لموظف</option>
                                            <option value="2">لمجموعة</option>
                                            <option value="3">لدرجة وظيفية</option>
                                            <option value="4">لقسم محدد</option>
                                            <option value="5">لمسمى وظيفي</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6 col-lg-4" id="for_W">
                                        <label class="col-form-label required" for="employer"><i class="fas fa-check-square label-icon"></i>اختر</label>
                                        <select class="selectpicker form-control" data-live-search="true" title="أختر من القائمة..." id="employer" name="employer[]" required multiple>
                                            <?php
                                            if (!empty($results)) {
                                                foreach ($results as $ins) {
                                                    $selected = (!empty($for_w) && in_array($ins["ID"], $for_w)) ? 'selected' : '';
                                                    echo '<option value="' . $ins["ID"] . '" ' . $selected . '>' . $ins["Name"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row" id="ess" style="display: <?= (!empty($results_E)) ? 'flex' : 'none' ?>">
                                    <div class="form-group col-12">
                                        <label class="col-form-label" for="extinsion"><i class="fas fa-user-minus label-icon"></i>استثناءات <small class="text-muted">(غير مضاف في قاعدة البيانات)</small></label>
                                        <select class="selectpicker form-control" data-live-search="true" title="أختر الاستثناءات إن وجدت..." id="extinsion" name="extinsion[]" multiple>
                                            <?php
                                            if (!empty($results_E)) {
                                                foreach ($results_E as $ins) {
                                                    $selected = (!empty($for_E) && in_array($ins["ID"], $for_E)) ? 'selected' : '';
                                                    echo '<option value="' . $ins["ID"] . '" ' . $selected . '>' . $ins["Name"] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row border-top pt-3 mt-2">
                                    <div class="form-group col-12">
                                        <label class="col-form-label" for="Reson"><i class="fas fa-comment-dots label-icon"></i>السبب / ملاحظات</label>
                                        <textarea name="Reson" id="Reson" class="form-control" rows="3" placeholder="اكتب السبب أو أي ملاحظات إضافية هنا..."><?= htmlspecialchars($row['Reason'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div id="detials" class="mt-4"></div>
                            </div>
                            
                            <div class="card-footer bg-white border-top d-flex justify-content-end p-3">
                                <a href="Benefits-list" class="btn btn-default btn-custom mr-2">الغاء</a>
                                <button type="button" id="save-draft" class="btn btn-secondary btn-custom mr-2">
                                    <i class="fas fa-save ml-1"></i> <?= $save_btn_2 ?>
                                </button>
                                <button type="button" id="save-benefit" class="btn btn-success btn-custom">
                                    <i class="fas fa-check ml-1"></i> <?= $save_btn ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    $('.selectpicker').selectpicker();
    
    if($.fn.daterangepicker) {
        $('.input-date').daterangepicker({
            singleDatePicker: true, showDropdowns: true, autoUpdateInput: false,
            locale: { format: 'YYYY-MM-DD', cancelLabel: 'مسح', applyLabel: 'تطبيق' }
        });
        $('.input-date').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD'));
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    let param_id = urlParams.get('id');
    if (urlParams.has('copy')) param_id = null;

    setTimeout(function() {
        $('#AmountType').trigger('change');
        $('#beneft_type').trigger('change');
        $('#for_what').trigger('change');
    }, 100);

    $('#save-benefit').on('click', function() { $('#isdraft').val(0); $('#AddBenefit').submit(); });
    $('#save-draft').on('click', function() { $('#isdraft').val(1); $('#AddBenefit').submit(); });

    $('#AddBenefit').on('submit', function(e) {
        e.preventDefault();
        if (!$(this).valid()) return;

        var formData = new FormData(this);
        if(param_id) formData.append('id', param_id);

        $.ajax({
            url: "hr-app/index.php?action=Benefits-add", // لم يتم تغييره بناءً على طلبك
            method: "POST", data: formData, contentType: false, processData: false, dataType: "json",
            beforeSend: function() { if(typeof window.showPreloader === 'function') window.showPreloader(); },
            success: function(data) {
                if (data.result) {
                    toastr.success(data.msg);
                    setTimeout(function() { window.location.href = 'Benefits-list'; }, 1000);
                } else { toastr.error(data.msg); }
            },
            error: function() { toastr.error('حدث خطأ غير متوقع'); },
            complete: function() { if(typeof window.hidePreloader === 'function') window.hidePreloader(); }
        });
    });

    $('#AddBenefit').validate({
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback text-danger d-block mt-1');
            element.closest('.form-group').append(error);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
            if($(element).hasClass('selectpicker')) $(element).parent().addClass('is-invalid-select border-danger');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
            if($(element).hasClass('selectpicker')) $(element).parent().removeClass('is-invalid-select border-danger');
        }
    });

    $('#amount').on('input', function() {
        let value = $(this).val().replace(/[^0-9.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) value = parts[0] + '.' + parts[1];
        $(this).val(value);
        validateAmount();
    });

    function validateAmount() {
        var type = $('#AmountType').val();
        var val = parseFloat($('#amount').val());
        if (type === 'avg' && val > 100) {
            $('#amount').val(''); toastr.warning('النسبة لا يمكن أن تتجاوز 100%');
        }
    }

    $('#AmountType').on('change', function() {
        if ($(this).val() === 'avg') { $('#title').html('<i class="fas fa-percentage label-icon"></i>النسبة (حتى 100%)'); } 
        else { $('#title').html('<i class="fas fa-money-bill-wave label-icon"></i>المبلغ'); }
        validateAmount();
    });

    $('#beneft_type').on('change', function() {
        if ($(this).val() === '2') {
            $('#Div_Due_date').slideDown(300); $('#Due_date').attr('required', true);
        } else {
            $('#Div_Due_date').slideUp(300); $('#Due_date').removeAttr('required').val('');
        }
    });

    $('#branchs_list').change(function() {
        $('#for_what').val('').selectpicker('refresh');
        $('#employer').val('').selectpicker('refresh');
        $('#detials').empty();
    });

    // ----- التعديل هنا: تم تغيير المسار لملف incentive-info.php -----
    $('#for_what').change(function() {
        var selectedValue = $(this).val();
        var branchs = $('#branchs_list').val();
        
        if (['2', '3', '4', '5'].includes(selectedValue)) { $("#ess").slideDown(300); } else { $("#ess").slideUp(300); }

        $('#employer').empty().selectpicker('refresh');
        $('#extinsion').empty().selectpicker('refresh');
        
        if (selectedValue && branchs) {
            $.ajax({
                url: 'incentive-info.php', // التعديل هنا
                type: 'POST',
                data: { value: selectedValue, BranchID: branchs },
                dataType: "json",
                beforeSend: function() { if(typeof window.showPreloader === 'function') window.showPreloader(); },
                success: function(response) {
                    if (response.result) { populateSelect('#employer', response.data); } 
                    else { toastr.error(response.msg); }
                },
                complete: function() { if(typeof window.hidePreloader === 'function') window.hidePreloader(); }
            });
        }
    });

    // ----- التعديل هنا: تم تغيير المسار لملفات المستقلة الأخرى -----
    $('#employer').change(function() {
        var selectedValue = $(this).val(); 
        var branchs = $('#branchs_list').val();
        var parent = $('#for_what').val();

        if (parent != 1 && selectedValue && selectedValue.length > 0) {
            $.ajax({
                url: 'hr-app/incentive-extion.php', // التعديل هنا
                type: 'POST',
                data: { value: selectedValue, BranchID: branchs, parent: parent },
                dataType: "json",
                success: function(response) {
                    if (response.result) { populateAllSelect('#extinsion', response.data, response.data_); }
                }
            });
        }

        if (selectedValue && selectedValue.length > 0) {
            $.ajax({
                url: 'hr-app/benefit-info_show.php', // التعديل هنا
                type: 'POST',
                data: { value: selectedValue, branch: branchs, for_whats: parent },
                dataType: "json",
                success: function(response) {
                    if (response.section && response.section.length > 0) { renderInfoTable(response.section); } 
                    else { $('#detials').html('<div class="alert alert-info rounded-lg"><i class="fas fa-info-circle ml-2"></i>لا توجد بيانات متاحة.</div>'); }
                }
            });
        } else {
            $('#detials').empty();
        }
    });

    function populateSelect(selectId, items) {
        var select = $(selectId); select.empty();
        if (items && items.length > 0) {
            $.each(items, function(index, item) { select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>'); });
        }
        select.selectpicker('refresh');
    }

    function populateAllSelect(selectId, items, fixedItems) {
        var select = $(selectId); select.empty();
        if (fixedItems && fixedItems.length > 0) {
            $.each(fixedItems, function(index, item) { select.append('<option value="' + item.data.id + '" selected disabled>' + item.data.name + '</option>'); });
        }
        if (items && items.length > 0) {
            $.each(items, function(index, item) { select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>'); });
        }
        select.selectpicker('refresh');
    }

    function renderInfoTable(data) {
        let html = `
            <div class="table-responsive shadow-sm rounded-lg">
            <table class="table table-bordered table-striped table-hover text-center align-middle mb-0 bg-white">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="border-0">#</th>
                        <th class="border-0">اسم الموظف</th>
                        <th class="border-0">اسم التعويض</th>
                        <th class="border-0">الفرع</th>
                        <th class="border-0">نوع التعويض</th>
                        <th class="border-0">المبلغ</th>
                        <th class="border-0">الحالة</th>
                    </tr>
                </thead>
                <tbody>
        `;
        data.forEach(function(emp, index) {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="font-weight-bold text-primary">${emp.username ?? '-'}</td>
                    <td>${emp.name ?? '-'}</td>
                    <td><span class="badge badge-light border">${emp.branch ?? '-'}</span></td>
                    <td>${emp.type ?? '-'}</td>
                    <td class="text-success font-weight-bold">${emp.money ?? '-'}</td>
                    <td>${emp.check ?? '-'}</td>
                </tr>
            `;
        });
        html += '</tbody></table></div>';
        $('#detials').html(html);
    }
});
</script>