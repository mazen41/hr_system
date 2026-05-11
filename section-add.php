<?php
$appid  = 'HR';
$page_perm = ['اضافة قسم'];
$screen = 'إدارة الموارد البشرية';
$page_title = 'الاعدادات';

include_once('inc/header.php');

// Fetch allowed branches
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches);
$allowed_branch_str = implode(',', $branch_ids);

// Initialize variables
$row = [];
$client_id = 0;
$form_title = 'إضافة قسم جديد';
$save_btn = 'حفظ';

// Handle Edit Mode
if (isset($_GET['id'])) {
    $client_no = (int)$_GET['id'];
    $client_id = $client_no;

    $query = "SELECT Id, Name, ParentID, BranchID FROM tblsection WHERE Id = :id LIMIT 1";
    $st = $connect_pdo->prepare($query);
    $st->execute([':id' => $client_no]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $form_title = 'تعديل قسم: ' . $row['Name'];
        $save_btn = 'حفظ التغييرات';
    } else {
        echo '<script>window.location.href = "section-list";</script>';
        exit;
    }
}

// Helper functions for dependency checks
function GetlastID($connect, $id) {
    $sql = "SELECT SectionID FROM tblremewal WHERE SectionID = :id LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function CheckSectionChild($connect, $id) {
    $sql = "SELECT ParentID FROM tblsection WHERE ParentID = :id LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Fetch Parent Sections (for dropdown)
$sections = [];
if (!empty($allowed_branch_str)) {
    $querySection = "SELECT Id, Name FROM tblsection WHERE BranchID IN ($allowed_branch_str)";
    $stSection = $connect_pdo->prepare($querySection);
    $stSection->execute();
    while ($sec = $stSection->fetch(PDO::FETCH_ASSOC)) {
        $sections[$sec['Id']] = $sec['Name'];
    }
}

$has_employees = isset($_GET['id']) ? GetlastID($connect_pdo, $client_no) : false;
$has_children = isset($_GET['id']) ? CheckSectionChild($connect_pdo, $client_no) : false;
?>

<style>
    /* Custom responsive adjustments */
    .card-header {
        background: aliceblue;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .bootstrap-select .dropdown-toggle .filter-option {
        text-align: right; /* Ensure Arabic alignment */
    }
    
    .form-group label.required:after {
        content: " *";
        color: red;
    }

    @media (max-width: 768px) {
        .card-title {
            font-size: 1.1rem;
        }
        .btn-tool {
            padding: 0.25rem 0.5rem;
        }
        .form-control, .bootstrap-select .dropdown-toggle {
            height: calc(2.25rem + 2px);
        }
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= $form_title ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="Hrdashboard"><?= $screen ?></a></li>
                    <li class="breadcrumb-item active"><?= $form_title ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
        <div class="container-fluid">
            <form class="form-horizontal" role="form" action="" method="post" id="AddSection">
                <input type="hidden" value="" id="section_id" name="section_id">
                
                <div class="row">
                    <div class="col-12">
                        <div class="card invoice mb-3 shadow-none">
                            <div class="card-header" data-card-widget="collapse">
                                <h4 class="card-title m-0">تفاصيل القسم</h4>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body p-3">
                                <div class="row">
                                    <!-- Name -->
                                    <div class="form-group col-12 col-md-6">
                                        <label class="col-form-label required" for="sectiontname">اسم القسم</label>
                                        <input type="text" name="sectiontname" class="form-control" placeholder="ادخل اسم القسم" id="sectiontname" autocomplete="off" value="<?= htmlspecialchars($row['Name'] ?? '') ?>" required>
                                    </div>

                                    <!-- Branch -->
                                    <div class="form-group col-12 col-md-6">
                                        <label class="col-form-label required" for="branchs_list">الفرع</label>
                                        <?php if ($has_employees || $has_children): ?>
                                            <select class="selectpicker form-control" disabled data-style="btn-white border">
                                                <?php if (!empty($row['BranchID']) && isset($allowed_branches[$row['BranchID']])): ?>
                                                    <option selected><?= $allowed_branches[$row['BranchID']] ?></option>
                                                <?php endif; ?>
                                            </select>
                                            <input type="hidden" name="branchs_list" id="branchs_list" value="<?= $row['BranchID'] ?? '' ?>">
                                            <small class="text-danger">
                                                <?= $has_employees ? 'هذا القسم مرتبط بموظفين، لا يمكن تغيير الفرع.' : '' ?>
                                                <?= $has_children ? 'هذا القسم مرتبط بأقسام فرعية، لا يمكن تغيير الفرع.' : '' ?>
                                            </small>
                                        <?php else: ?>
                                            <select class="selectpicker form-control" data-live-search="true" data-style="btn-white border" title="أختر الفرع" id="branchs_list" name="branchs_list" required>
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
                                        <?php endif; ?>
                                    </div>

                                    <!-- Parent Section -->
                                    <div class="form-group col-12 col-md-6">
                                        <label class="col-form-label" for="select_section">القسم الرئيسي (الأب)</label>
                                        <select class="selectpicker form-control" data-live-search="true" data-style="btn-white border" title="بدون (قسم رئيسي)" id="select_section" name="select_section">
                                            <option value="">بدون (قسم رئيسي)</option>
                                            <?php
                                            // Initial population if editing
                                            if (!empty($row['ParentID']) && isset($sections[$row['ParentID']])) {
                                                echo "<option value='{$row['ParentID']}' selected>{$sections[$row['ParentID']]}</option>";
                                            }
                                            // If branch is selected, other sections will be loaded via AJAX
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer text-left">
                                <button type="submit" id="save-section" class="btn btn-success">
                                    <i class="fas fa-save"></i> <?= $save_btn ?>
                                </button>
                                <a href="section-list" class="btn btn-default ml-2">الغاء</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize Plugins
    $('.selectpicker').selectpicker();

    const urlParams = new URLSearchParams(window.location.search);
    const param_id = urlParams.get('id');

    // Trigger Initial State if editing
    if (param_id) {
        // Trigger branch change to load parent sections if branch is set
        // But wait for initial load? Actually, if we are editing, we might need to pre-load parents filtering by branch
        // The PHP code handles the selected value, but the list needs to be populated based on branch
        var initialBranch = $('#branchs_list').val();
        if (initialBranch) {
            loadSections(initialBranch, '<?= $row['ParentID'] ?? '' ?>');
        }
    }

    // Form Submission
    $('#AddSection').on('submit', function(e) {
        e.preventDefault();
        
        if (!$(this).valid()) return;

        var formData = $(this).serialize();
        if(param_id) formData += '&id=' + param_id;

        $.ajax({
            url: "hr-app/index.php?action=section-add",
            method: "POST",
            data: formData,
            dataType: "json",
            beforeSend: function() {
                if(typeof window.showPreloader === 'function') window.showPreloader();
            },
            success: function(data) {
                if (data.result) {
                    toastr.success(data.msg);
                    setTimeout(function() {
                        window.location.href = 'section-view?id=' + data.id;
                    }, 1000);
                } else {
                    toastr.error(data.msg);
                }
            },
            error: function() {
                toastr.error('حدث خطأ غير متوقع');
            },
            complete: function() {
                if(typeof window.hidePreloader === 'function') window.hidePreloader();
            }
        });
    });

    // Validation
    $('#AddSection').validate({
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
            if($(element).hasClass('selectpicker')) {
                $(element).parent().addClass('is-invalid-select');
            }
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
            if($(element).hasClass('selectpicker')) {
                $(element).parent().removeClass('is-invalid-select');
            }
        }
    });

    // Branch Change
    $('#branchs_list').change(function() {
        var selectedValue = $(this).val();
        loadSections(selectedValue, null);
    });

    function loadSections(branchId, selectedId) {
        if (!branchId) return;
        
        $.ajax({
            url: 'info-of-section-and-job-title',
            type: 'POST',
            data: { value: branchId },
            dataType: "json",
            beforeSend: function() { 
                // Optional: show loading indicator on select
            },
            success: function(response) {
                populateSelect('#select_section', response.section, selectedId);
            },
            error: function() {
                toastr.error('حدث خطأ أثناء جلب بيانات الأقسام');
            }
        });
    }

    function populateSelect(selectId, items, selectedValue) {
        var select = $(selectId);
        select.empty();
        select.append('<option value="">بدون (قسم رئيسي)</option>');
        
        if (items && items.length > 0) {
            $.each(items, function(index, item) {
                // Don't list itself as parent if editing
                if (param_id && item.data.id == param_id) return;
                
                var isSelected = (selectedValue && item.data.id == selectedValue) ? 'selected' : '';
                select.append('<option value="' + item.data.id + '" ' + isSelected + '>' + item.data.name + '</option>');
            });
        }
        select.selectpicker('refresh');
    }
});
</script>
