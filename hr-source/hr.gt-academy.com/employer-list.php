<?php
$appid  = 'HR';
$screen = 'إدارة الموارد البشرية';
$page_title = 'قائمة الموظفين';

$page_perm = ['إضافة موظف', 'عرض موظف', 'تعديل موظف'];
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
?>

<style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
    }

    .page-nav {
        background: #fff;
        padding: 1.5rem;
        border-bottom: 1px solid #e3e6f0;
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #5a5c69;
    }

    .filter-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }

    .filter-advance {
        display: none;
        padding-top: 15px;
        border-top: 1px solid #eee;
        margin-top: 15px;
    }

    #userstb {
        border-radius: 8px;
        overflow: hidden;
        border: none !important;
    }

    #userstb thead th {
        background-color: #f8f9fc;
        color: #4e73df;
        font-weight: 600;
        text-transform: uppercase;
        border-bottom: 2px solid #e3e6f0;
    }

    .table-responsive {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
    }

    /* Fix Pagination Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin-left: 2px;
        border-radius: 5px !important;
        border: 1px solid #ddd !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--primary-color) !important;
        color: white !important;
        border: 1px solid var(--primary-color) !important;
    }
    
    .btn-filter-toggle {
        background: #f8f9fc;
        border: 1px solid #d1d3e2;
        color: #6e707e;
    }
</style>

<!-- Content Header -->
<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <span class="page-title"><i class="fas fa-users-cog mr-2"></i> إدارة الموارد البشرية</span>
            </div>
            <div class="col-sm-6 text-left">
                <?php if ($User->isAllowedPerm(['إضافة موظف'], $appid)) { ?>
                    <button type="button" class="btn btn-primary btn-icon-split" id="add-bt">
                        <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                        <span class="text">إضافة موظف جديد</span>
                    </button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])): ?>
            <div class="alert alert-success alert-dismissible fade show" id="result-alert">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-check"></i> <?= $_SESSION['alert'] ?>
                <?php $_SESSION['alert'] = ''; ?>
            </div>
        <?php endif; ?>

        <div class="card filter-card">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search fa-sm"></i> محرك البحث المتقدم</h6>
            </div>
            <div class="card-body">
                <form id="filter-fm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>اسم الموظف</label>
                                <input type="text" class="form-control" id="emp_name" name="emp_name" placeholder="ادخل الاسم...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الفرع</label>
                                <select class="selectpicker form-control" id="branchs_list" name="branchs_list" multiple data-live-search="true" title="كل الفروع">
                                    <?php
                                    foreach ($allowed_branches as $id => $name) {
                                        echo '<option value="' . $id . '">' . $name . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>القسم</label>
                                <select class="selectpicker form-control" id="user_section" name="user_section" multiple data-live-search="true" title="كل الأقسام"></select>
                            </div>
                        </div>
                    </div>

                    <!-- Advance Filters -->
                    <div class="row filter-advance">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>المسمى الوظيفي</label>
                                <select class="selectpicker form-control" id="user_jobtitle" name="user_jobtitle" multiple data-live-search="true"></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>الدرجة الوظيفية</label>
                                <select class="selectpicker form-control" id="user_grade" name="user_grade" multiple data-live-search="true"></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>فترات العمل</label>
                                <select class="selectpicker form-control" id="user_shift" name="user_shift[]" multiple data-live-search="true"></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>المجموعة الوظيفية</label>
                                <select class="selectpicker form-control" id="user_groub" name="user_groub[]" multiple data-live-search="true"></select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-filter-toggle show-advance">
                            <i class="fa fa-sliders-h"></i> خيارات إضافية
                        </button>
                        <div>
                            <button type="button" class="btn btn-light reset-filter">إعادة تعيين</button>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i> تنفيذ البحث</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table id="userstb" class="table table-hover" width="100%">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>القسم</th>
                        <th>الراتب</th>
                        <th>الفرع</th>
                        <th>الشهادات والخبرات</th>
                        <th>الحالة</th>
                        <th width="100px">إجراءات</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    // Toggle Advance Search
    $(document).on('click', '.show-advance', function() {
        $('.filter-advance').slideToggle();
    });

    // Load Select Data
    get_filter_info();

    function get_filter_info() {
        $.ajax({
            url: 'hr-app/index.php?action=allUserinfo_Search',
            type: 'POST',
            data: { value: 1 },
            dataType: "json",
            success: function(response) {
                populateSelect('#user_section', response.section);
                populateSelect('#user_jobtitle', response.jobtitle);
                populateSelect('#user_grade', response.JobGrade);
                populateSelect('#user_shift', response.Shift);
                populateSelect('#user_groub', response.groub_list);
            }
        });
    }

    function populateSelect(selectId, items) {
        var select = $(selectId);
        select.empty();
        if (items && items.length > 0) {
            $.each(items, function(index, item) {
                select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
            });
        }
        select.selectpicker('refresh');
    }

    // Initialize DataTable ONCE
    var dataTable = $('#userstb').DataTable({
        "processing": true,
        "serverSide": true,
        "paging": true,
        "pageLength": 10,
        "lengthChange": true,
        "searching": false,
        "ordering": false,
        "responsive": true,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json"
        },
        "ajax": {
            "url": "hr-app/index.php?action=employer-list",
            "type": "POST",
            "data": function(d) {
                // This function grabs the current filter values every time the table draws
                d.is_date_search = 'yes';
                d.name = $('#emp_name').val();
                d.branchs = $('#branchs_list').val();
                d.section = $('#user_section').val();
                d.jobtitle = $('#user_jobtitle').val();
                d.grade = $('#user_grade').val();
                d.shift = $('#user_shift').val();
                d.groub = $('#user_groub').val();
            }
        },
        "columns": [
            { "data": "0" }, // Employee
            { "data": "1" }, // Section
            { "data": "2" }, // Salary
            { "data": "3" }, // Branch
            { "data": "4" }, // Certificates
            { "data": "5" }, // Status
            { "data": "6" }  // Actions
        ]
    });

    // Handle Form Submit (Search)
    $('#filter-fm').on('submit', function(e) {
        e.preventDefault();
        dataTable.draw(); // This triggers the ajax call with new parameters
    });

    // Reset Filters
    $(document).on('click', '.reset-filter', function() {
        $('#filter-fm')[0].reset();
        $('.selectpicker').val('').selectpicker('refresh');
        dataTable.draw();
    });

    // Navigation Add button
    $('#add-bt').on('click', function() {
        window.location.href = "/employer-add";
    });

    // Action: Stop Employee
    $(document).on('click', '.stop_emp', function() {
        var id = $(this).val();
        if (id != '') {
            $('#modal_default').modal('show');
            $('#modal_default .modal-body').load('stop_emp.php?id=' + id);
        }
    });

    // Action: Change Password
    $(document).on('click', '.change_pass', function() {
        var user = $(this).val();
        var user_name = $(this).data('user');
        var new_pass = prompt("تعديل كلمة مرور المستخدم '" + user_name + "' : ", "");
        
        if (new_pass) {
            $.ajax({
                type: 'POST',
                url: "users-app/index.php?action=change-user-pass",
                data: { user: user, new_pass: new_pass },
                dataType: "json",
                success: function(data) {
                    if (data.error > 0) toastr.error(data.msg);
                    else toastr.success(data.msg);
                }
            });
        }
    });
});
</script>
