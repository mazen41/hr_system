<?php
/**
 * Employee Evaluation Form - To fill out scores and feedback for an evaluation.
 */
$screen = 'إكمال التقييم';
$page_title = 'نموذج تقييم الأداء';
$page = 'evaluation-form'; // You might want a different 'page' identifier for menu highlighting

include_once('inc/header.php');

require_once 'classes/EvaluationManager.php';
$evalManager = new EvaluationManager($connect_pdo);

// 1. Get the evaluation ID from the URL
$evaluationId = (int)($_GET['id'] ?? 0);

if ($evaluationId === 0) {
    // Redirect or show an error if no ID is provided
    $_SESSION['alert'] = 'معرف التقييم غير صالح.';
    header('Location: admin-evaluations');
    exit;
}

// 2. Fetch the evaluation details
$evaluation = $evalManager->getEvaluation($evaluationId);

if (!$evaluation) {
    // Redirect or show an error if evaluation not found
    $_SESSION['alert'] = 'التقييم المطلوب غير موجود.';
    header('Location: admin-evaluations');
    exit;
}

// 3. Security Check: Ensure current user is the evaluator or an admin
$currentUserId = $_SESSION['UserID'] ?? $_SESSION['user_id'] ?? null; // Adjust based on your actual session key
if (!$currentUserId) {
    $_SESSION['alert'] = 'يرجى تسجيل الدخول.';
    header('Location: login'); // Assuming you have a login page
    exit;
}

// Ensure $User object is loaded and has userIsAdmin/userIsEmployer methods
// It should be loaded via inc/header.php, but good to be explicit
if (!isset($User) || !is_a($User, 'User')) {
    // Fallback or critical error if User class not loaded
    die("User class not initialized.");
}

$isAllowedToEdit = ($evaluation['evaluator_id'] == $currentUserId || $User->userIsAdmin() || $User->userIsEmployer());

// Determine if the form should be read-only (e.g., after submission or approval)
$isReadOnly = in_array($evaluation['status'], ['submitted', 'approved', 'acknowledged']);

// If not allowed to edit and it's not read-only, redirect
if (!$isAllowedToEdit && !$isReadOnly) {
    $_SESSION['alert'] = 'غير مصرح لك بتعديل هذا التقييم.';
    header('Location: admin-evaluations');
    exit;
}

// Variables for display
$employeeName = htmlspecialchars($evaluation['emp_first'] . ' ' . $evaluation['emp_last']);
$evaluatorName = htmlspecialchars($evaluation['evaluator_first'] . ' ' . $evaluation['evaluator_last']);
$periodName = htmlspecialchars($evaluation['period_name']);
$periodType = htmlspecialchars($evaluation['period_type']);
$periodDates = htmlspecialchars($evaluation['start_date'] . ' - ' . $evaluation['end_date']);
$status = htmlspecialchars($evaluation['status']);

// Map status for display
$statusMap = [
    'draft' => '<span class="badge badge-secondary">مسودة</span>',
    'submitted' => '<span class="badge badge-info">قيد المراجعة</span>',
    'reviewed' => '<span class="badge badge-primary">تمت المراجعة</span>', // If you have this status
    'approved' => '<span class="badge badge-success">معتمد</span>',
    'rejected' => '<span class="badge badge-danger">مرفوض</span>',
    'acknowledged' => '<span class="badge badge-dark">تم الإقرار</span>'
];
$displayStatus = $statusMap[$status] ?? $status;

?>

<style>
/* Add your custom styles here, or reuse from admin-evaluations.php */
.card-header h5 {
    display: flex;
    align-items: center;
    gap: 10px;
}
.evaluation-info {
    font-size: 0.9em;
    color: #555;
    margin-bottom: 15px;
}
.evaluation-info strong {
    color: #333;
}
.score-input {
    width: 80px; /* Adjust width as needed */
    display: inline-block;
}
.score-comment {
    width: calc(100% - 90px); /* Adjust width */
    display: inline-block;
    vertical-align: top;
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4 class="page-title"><i class="fas fa-clipboard-check ml-2"></i> نموذج تقييم الأداء</h4>
            </div>
            <div class="col-sm-6 text-left">
                <?php if (!$isReadOnly && $isAllowedToEdit): ?>
                <button type="submit" form="evaluationForm" class="btn btn-success" data-action="submit">
                    <i class="fas fa-paper-plane"></i> تقديم للمراجعة
                </button>
                <button type="submit" form="evaluationForm" class="btn btn-primary" data-action="save">
                    <i class="fas fa-save"></i> حفظ كمسودة
                </button>
                <?php elseif ($isReadOnly && $evaluation['status'] == 'approved' && $evaluation['employee_id'] == $currentUserId): ?>
                <button type="button" class="btn btn-info" id="acknowledgeEvaluationBtn">
                    <i class="fas fa-check-circle"></i> إقرار التقييم
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
            <div class="alert alert-danger alert-dismissible" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-exclamation-triangle"></i>
                <?=$_SESSION['alert']?>
                <?php $_SESSION['alert'] ='';?>
            </div>
        <?php endif;?>

        <div class="card">
            <div class="card-header">
                <h5>
                    <span>تقييم الموظف: <strong><?= $employeeName ?></strong></span>
                    <span>الفترة: <strong><?= $periodName ?> (<?= $periodType ?>)</strong></span>
                    <span>الحالة: <?= $displayStatus ?></span>
                </h5>
                <div class="evaluation-info">
                    <span>مقيم بواسطة: <strong><?= $evaluatorName ?></strong></span>
                    <span>تواريخ الفترة: <strong><?= htmlspecialchars($evaluation['period_type']) ?> من <?= $evaluation['start_date'] ?> إلى <?= $evaluation['end_date'] ?></strong></span>
                </div>
            </div>
            <form id="evaluationForm" method="post">
                <input type="hidden" name="evaluation_id" value="<?= $evaluationId ?>">
                <div class="card-body">
                    <?php if (!empty($evaluation['scores'])): ?>
                        <h6>معايير التقييم والدرجات</h6>
                        <hr>
                        <?php foreach ($evaluation['scores'] as $scoreData): ?>
                            <div class="form-group row">
                                <label class="col-sm-12 col-md-4 col-form-label">
                                    <?= htmlspecialchars($scoreData['name_ar']) ?>
                                    <small class="text-muted">(الفئة: <?= htmlspecialchars($scoreData['category']) ?>, الوزن: <?= $scoreData['weight'] ?>, الحد الأقصى: <?= $scoreData['max_score'] ?>)</small>
                                    <?php if (!empty($scoreData['description'])): ?>
                                        <br><small class="text-info"><?= htmlspecialchars($scoreData['description']) ?></small>
                                    <?php endif; ?>
                                </label>
                                <div class="col-sm-12 col-md-3">
                                    <input type="number" name="scores[<?= $scoreData['criteria_id'] ?>][score]" class="form-control score-input" 
                                        value="<?= htmlspecialchars($scoreData['score']) ?>" min="0" max="<?= $scoreData['max_score'] ?>" 
                                        <?= $isReadOnly ? 'disabled' : '' ?> required>
                                </div>
                                <div class="col-sm-12 col-md-5">
                                    <textarea name="scores[<?= $scoreData['criteria_id'] ?>][comment]" class="form-control score-comment" rows="2" placeholder="ملاحظات حول هذا المعيار" 
                                        <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($scoreData['comment'] ?? '') ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">لا توجد معايير تقييم لهذه الفترة.</p>
                    <?php endif; ?>

                    <h6 class="mt-4">ملاحظات عامة وتوصيات</h6>
                    <hr>
                    <div class="form-group">
                        <label for="strengths">نقاط القوة</label>
                        <textarea id="strengths" name="strengths" class="form-control" rows="3" 
                            <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($evaluation['strengths'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="weaknesses">نقاط الضعف ومجالات التحسين</label>
                        <textarea id="weaknesses" name="weaknesses" class="form-control" rows="3" 
                            <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($evaluation['weaknesses'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="recommendations">التوصيات وخطط التطوير</label>
                        <textarea id="recommendations" name="recommendations" class="form-control" rows="3" 
                            <?= $isReadOnly ? 'disabled' : '' ?>><?= htmlspecialchars($evaluation['recommendations'] ?? '') ?></textarea>
                    </div>

                    <?php if ($isReadOnly && $evaluation['status'] == 'approved' && $evaluation['employee_id'] == $currentUserId): ?>
                        <h6 class="mt-4">إقرار الموظف</h6>
                        <hr>
                        <div class="form-group">
                            <label for="employee_comment">تعليق الموظف</label>
                            <textarea id="employee_comment" name="employee_comment" class="form-control" rows="3" 
                                <?= ($evaluation['status'] == 'acknowledged') ? 'disabled' : '' ?>><?= htmlspecialchars($evaluation['employee_comment'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <?php if (!$isReadOnly && $isAllowedToEdit): ?>
                        <button type="submit" class="btn btn-secondary" data-action="cancel">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary" data-action="save">
                            <i class="fas fa-save"></i> حفظ كمسودة
                        </button>
                        <button type="submit" class="btn btn-success" data-action="submit">
                            <i class="fas fa-paper-plane"></i> تقديم للمراجعة
                        </button>
                    <?php elseif ($isReadOnly && $evaluation['status'] == 'approved' && $evaluation['employee_id'] == $currentUserId): ?>
                        <button type="button" class="btn btn-info" id="acknowledgeEvaluationBtn"
                            <?= ($evaluation['status'] == 'acknowledged') ? 'disabled' : '' ?>>
                            <i class="fas fa-check-circle"></i> إقرار التقييم
                        </button>
                    <?php else: ?>
                        <a href="admin-evaluations" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    // Function to calculate and update total score/percentage (optional frontend calculation)
    function updateOverallScore() {
        // This is a basic client-side sum. Server will do official calculation.
        let totalWeightedScore = 0;
        let maxPossibleWeightedScore = 0;

        $('#evaluationForm input[name^="scores"]').each(function() {
            const criteriaId = $(this).attr('name').match(/\[(\d+)\]\[score\]/)[1];
            const score = parseFloat($(this).val()) || 0;
            const maxScore = parseFloat($(this).attr('max'));
            // Get weight from PHP generated data or hidden fields if needed
            // For now, let's assume weight is 1.0 for simplicity, or get from a data attribute
            const weight = 1.0; // Placeholder, ideally fetched from PHP data

            totalWeightedScore += score * weight;
            maxPossibleWeightedScore += maxScore * weight;
        });

        if (maxPossibleWeightedScore > 0) {
            const percentage = (totalWeightedScore / maxPossibleWeightedScore) * 100;
            // You can display this somewhere on the page if you add elements for it.
            // console.log(`Current Score: ${totalWeightedScore} / ${maxPossibleWeightedScore} (${percentage.toFixed(1)}%)`);
        }
    }

    // Call on load and on input change if you want live update
    // updateOverallScore();
    // $('#evaluationForm input[name^="scores"]').on('input', updateOverallScore);

    $('#evaluationForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        var form = $(this);
        var submitButton = $(e.originalEvent.submitter); // Get the button that triggered the submit
        var actionType = submitButton.data('action'); // 'save' or 'submit'
        
        var url = 'hr-app/index.php?action=save-evaluation-scores';
        var postData = form.serializeArray();

        // If the submit button was "تقديم للمراجعة", change the action
        if (actionType === 'submit') {
            url = 'hr-app/index.php?action=submit-evaluation';
            // For submit, we send the whole form and let backend update scores and then change status
            // The saveScores action also includes calculateTotalScore() and other updates.
            // A simpler approach for submit might just be to update the status, assuming save was done beforehand
            // But for robustness, passing all data is better.
        } else if (actionType === 'cancel') {
             window.location.href = 'admin-evaluations';
             return;
        }
        
        // Add evaluation_id to the data
        postData.push({name: 'evaluation_id', value: form.find('input[name="evaluation_id"]').val()});

        $.post(url, postData, function(res) {
            if(typeof res === 'string') {
                try { res = JSON.parse(res); } catch(e) {}
            }

            if (res.result) {
                toastr.success(res.msg || 'تم الحفظ بنجاح.');
                // For 'save', stay on page. For 'submit', redirect.
                if (actionType === 'submit') {
                    setTimeout(function() {
                        window.location.href = 'admin-evaluations';
                    }, 1000);
                } else {
                    // Just reload the page to show latest draft status/data
                    setTimeout(function() {
                        location.reload(); 
                    }, 1000);
                }
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء حفظ التقييم.');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
            console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
        });
    });

    // Handle Employee Acknowledgment (only if it's an approved evaluation for the employee)
    $('#acknowledgeEvaluationBtn').on('click', function() {
        const evaluationId = $('input[name="evaluation_id"]').val();
        const employeeComment = $('#employee_comment').val();

        Swal.fire({
            title: 'تأكيد إقرار التقييم',
            text: 'هل أنت متأكد أنك تريد إقرار هذا التقييم؟ لن تتمكن من تعديل تعليقك بعد الإقرار.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، أقر التقييم',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('hr-app/index.php?action=acknowledgeEvaluation', {
                    evaluation_id: evaluationId,
                    employee_comment: employeeComment
                }, function(res) {
                    if(typeof res === 'string') {
                        try { res = JSON.parse(res); } catch(e) {}
                    }
                    if (res.result) {
                        toastr.success(res.msg || 'تم إقرار التقييم بنجاح.');
                        setTimeout(function() {
                            location.reload(); // Reload to show acknowledged status and disable fields
                        }, 1000);
                    } else {
                        toastr.error(res.msg || 'فشل إقرار التقييم.');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    toastr.error('فشل في الاتصال بالخادم: ' + textStatus);
                    console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                });
            }
        });
    });
});
</script>