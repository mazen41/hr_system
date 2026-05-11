<?php
// This file is included by the router, so we don't need full page setup
// Just return the modal content directly

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="alert alert-danger">معرف غير صالح</div>';
    exit;
}

// Get employment type details
$stmt = $connect_pdo->prepare("SELECT * FROM tblemploymenttype WHERE Id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    echo '<div class="alert alert-danger">نوع التوظيف غير موجود</div>';
    exit;
}
?>

<div class="alert alert-warning">
    <h5>تأكيد الحذف</h5>
    <p>هل أنت متأكد من حذف نوع التوظيف: <strong><?= htmlspecialchars($emp['Name']) ?></strong>؟</p>
    <p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p>
</div>

<form id="deleteForm" action="/hr-app/index.php" method="post">
    <input type="hidden" name="action" value="empolyment-remove">
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
$(document).ready(function() {
    $('#deleteForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.result) {
                    $('#modal_default').modal('hide');
                    toastr.success(response.msg);
                    // Refresh the DataTable
                    $('#clients_data').DataTable().ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء الحذف');
            }
        });
    });
});
</script>
