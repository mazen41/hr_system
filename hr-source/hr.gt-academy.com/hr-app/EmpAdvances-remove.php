<?php
session_start();
require_once __DIR__ . '/../inc/config.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="alert alert-danger">معرف غير صالح</div>';
    exit;
}

$stmt = $connect_pdo->prepare("SELECT a.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as EmpName FROM tblempadvances a LEFT JOIN branches b ON a.BranchID = b.branch_id LEFT JOIN tblusers u ON a.UserID = u.UserID WHERE a.Id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo '<div class="alert alert-danger">السلفة غير موجودة</div>';
    exit;
}
?>

<div class="alert alert-warning">
    <h5>تأكيد الحذف</h5>
    <p>هل أنت متأكد من حذف سلفة الموظف: <strong><?= htmlspecialchars($row['EmpName'] ?? '') ?></strong>؟</p>
    <p class="text-muted">المبلغ: <?= htmlspecialchars($row['Amount'] ?? '0') ?></p>
    <p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p>
</div>

<form id="deleteForm">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="text-center">
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash"></i> حذف
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> إلغاء
        </button>
    </div>
</form>

<script>
$('#deleteForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'hr-app/index.php?action=EmpAdvances-remove',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.result) {
                $('#modal_default').modal('hide');
                toastr.success(response.msg);
                if ($.fn.DataTable.isDataTable('#data_tb')) {
                    $('#data_tb').DataTable().ajax.reload();
                }
            } else {
                toastr.error(response.msg);
            }
        },
        error: function() {
            toastr.error('حدث خطأ أثناء الحذف');
        }
    });
});
</script>
