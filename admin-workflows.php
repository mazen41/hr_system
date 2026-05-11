<?php
/**
 * Admin Workflows - Workflow configuration management
 */
$screen = 'سير العمل';
$page_title = 'إعدادات سير العمل'; // Changed for better clarity
$page = 'admin-workflows';
include_once('inc/header.php');

// Get workflow configs
$stmt = $connect_pdo->query("
    SELECT wc.*, 
           (SELECT COUNT(*) FROM workflow_steps WHERE workflow_id = wc.id) as steps_count,
           (SELECT COUNT(*) FROM workflow_instances WHERE workflow_id = wc.id AND status = 'pending') as pending_count
    FROM workflow_configs wc
    ORDER BY wc.entity_type
");
$workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get workflow steps for each workflow
$workflowSteps = [];
foreach ($workflows as $w) {
    $stmt = $connect_pdo->prepare("SELECT * FROM workflow_steps WHERE workflow_id = ? ORDER BY step_order");
    $stmt->execute([$w['id']]);
    $workflowSteps[$w['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all users for "Specific User" approver type
$stmtUsers = $connect_pdo->query("SELECT UserID, FirstName, LastName FROM tblusers WHERE IsDisabled = 0 ORDER BY FirstName");
$allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Fetch all user groups/roles for "Role" approver type
// Fetch all user groups/roles for "Role" approver type
$stmtRoles = $connect_pdo->query("SELECT GroupID, GroupName FROM tblusergroups ORDER BY GroupName");
$allRoles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

// Define entity types (can be fetched from DB or a config file if dynamic)
$entityTypes = [
    'leave_request' => 'طلب إجازة',
    'advance_request' => 'طلب سلفة',
    'promotion_request' => 'طلب ترقية',
    'violation' => 'مخالفة موظف',
    'order' => 'أمر عمل',
    // Add more entity types as needed
];

?>

<style>
/* Responsive Modal Styles */
.modal-dialog {
    margin: 10px auto;
    max-width: 95%;
}
@media (min-width: 576px) {
    .modal-dialog { max-width: 600px; }
}
@media (min-width: 992px) { /* Larger breakpoint for a wider modal */
    .modal-dialog { max-width: 800px; }
}
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
.modal-header {
    background: linear-gradient(135deg, #0d21a5 0%, #1e3a8a 100%);
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
}
.modal-header .close {
    color: #fff;
    opacity: 0.8;
}
.modal-body {
    padding: 20px;
    /* max-height: calc(100vh - 200px); */ /* Removed to allow content to expand if needed */
    overflow-y: auto;
}

/* Workflow Cards */
.workflow-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-right: 4px solid #0d21a5;
    transition: all 0.2s ease-in-out;
}
.workflow-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.workflow-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}
.workflow-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}
.workflow-entity {
    background: #e8f4fd;
    color: #0d21a5;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.workflow-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.workflow-stat {
    text-align: center;
    min-width: 80px; /* Ensure stats don't get too squeezed */
}
.workflow-stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #0d21a5;
}
.workflow-stat-label {
    font-size: 12px;
    color: #6b7280;
}

/* Steps Timeline */
.steps-timeline {
    position: relative;
    padding: 10px 0;
    margin-right: 20px; /* Add margin for the line */
}
.step-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 10px 0;
    position: relative;
}
.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 15px; /* Adjust based on step-number position */
    top: 40px; /* Position below the number */
    bottom: -10px; /* Extend to next item */
    width: 2px;
    background: #e5e7eb;
}
.step-number {
    width: 32px;
    height: 32px;
    background: #0d21a5;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
    z-index: 1; /* Ensure it's above the line */
}
.step-content {
    flex: 1;
}
.step-name {
    font-weight: 600;
    color: #1f2937;
}
.step-type {
    font-size: 13px;
    color: #6b7280;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
    flex-shrink: 0; /* Prevent it from shrinking */
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 26px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .toggle-slider {
    background-color: #10b981; /* Green for active */
}
input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Step Editor in Modal */
.workflow-step-editor {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f9f9f9;
    position: relative;
}
.workflow-step-editor .btn-remove-step {
    position: absolute;
    top: 10px;
    left: 10px;
    color: #dc3545;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
}
.workflow-step-editor .step-order-indicator {
    position: absolute;
    top: 10px;
    right: 15px;
    font-weight: bold;
    color: #0d21a5;
    font-size: 1.1rem;
}
.workflow-step-editor .form-group {
    margin-bottom: 1rem;
}
.workflow-step-editor .form-control {
    border-radius: 6px;
}

/* Custom button styles */
.btn-primary-gradient {
    background: linear-gradient(45deg, #0d21a5 0%, #1e3a8a 100%);
    border: none;
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(13, 33, 165, 0.3);
}
.btn-primary-gradient:hover {
    background: linear-gradient(45deg, #1e3a8a 0%, #0d21a5 100%);
    box-shadow: 0 6px 15px rgba(13, 33, 165, 0.4);
    transform: translateY(-1px);
}
.btn-outline-primary {
    color: #0d21a5;
    border-color: #0d21a5;
}
.btn-outline-primary:hover {
    background-color: #0d21a5;
    color: #fff;
}


@media (max-width: 768px) {
    .workflow-stats {
        gap: 15px;
    }
    .workflow-stat-number {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .workflow-header {
        flex-direction: column;
        align-items: stretch;
    }
    .toggle-switch {
        align-self: flex-end;
    }
    .step-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    .step-item:not(:last-child)::after {
        right: auto; /* Revert to default */
        left: 15px; /* Adjust to align with step-number */
        top: 35px;
    }
}
</style>

    <div class="page-nav d-flex justify-content-between align-items-center">
        <h4 class="page-title"><i class="fas fa-project-diagram ml-2"></i> إعدادات سير العمل</h4>
        <button class="btn btn-primary-gradient" onclick="addWorkflow()">
            <i class="fas fa-plus ml-2"></i> إضافة سير عمل جديد
        </button>
    </div>
    
    <section class="content py-3">
        <div class="container-fluid">
            
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle ml-2"></i>
                سير العمل يحدد مسار الموافقات لكل نوع من الطلبات. يمكنك تفعيل أو تعطيل كل سير عمل حسب الحاجة.
            </div>
            
            <?php if (empty($workflows)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle ml-2"></i>
                    لا توجد سلاسل عمل مهيأة بعد. انقر على "إضافة سير عمل جديد" للبدء.
                </div>
            <?php endif; ?>

            <?php foreach ($workflows as $workflow): ?>
            <div class="workflow-card">
                <div class="workflow-header">
                    <div>
                        <div class="workflow-title"><?= htmlspecialchars($workflow['name_ar']) ?></div>
                        <span class="workflow-entity"><?= $entityTypes[$workflow['entity_type']] ?? htmlspecialchars($workflow['entity_type']) ?></span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" <?= $workflow['is_active'] ? 'checked' : '' ?> 
                               onchange="toggleWorkflow(<?= $workflow['id'] ?>, this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="workflow-stats">
                    <div class="workflow-stat">
                        <div class="workflow-stat-number"><?= $workflow['steps_count'] ?></div>
                        <div class="workflow-stat-label">خطوات</div>
                    </div>
                    <div class="workflow-stat">
                        <div class="workflow-stat-number"><?= $workflow['pending_count'] ?></div>
                        <div class="workflow-stat-label">طلبات معلقة</div>
                    </div>
                </div>
                
                <?php if (!empty($workflowSteps[$workflow['id']])): ?>
                <div class="steps-timeline">
                    <?php foreach ($workflowSteps[$workflow['id']] as $step): ?>
                    <div class="step-item">
                        <div class="step-number"><?= $step['step_order'] ?></div>
                        <div class="step-content">
                            <div class="step-name"><?= htmlspecialchars($step['name_ar']) ?></div>
                            <div class="step-type">
                                <?php
                                $types = [
                                    'direct_manager' => 'المدير المباشر',
                                    'hr_manager' => 'مدير الموارد البشرية',
                                    'department_head' => 'رئيس القسم',
                                    'specific_user' => 'مستخدم محدد',
                                    'role' => 'دور محدد'
                                ];
                                echo $types[$step['approver_type']] ?? $step['approver_type'];

                                if ($step['approver_type'] == 'specific_user' && $step['approver_id']) {
                                    $user = array_filter($allUsers, fn($u) => $u['UserID'] == $step['approver_id']);
                                    if ($user) echo ' (' . htmlspecialchars(reset($user)['FirstName'] . ' ' . reset($user)['LastName']) . ')';
                                } elseif ($step['approver_type'] == 'role' && $step['approver_role']) {
                                    $role = array_filter($allRoles, fn($r) => $r['UserGroupID'] == $step['approver_role']);
                                    if ($role) echo ' (' . htmlspecialchars(reset($role)['Name']) . ')';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">لا توجد خطوات محددة لسير العمل هذا. <br> اضغط "تعديل الخطوات" لإضافتها.</p>
                <?php endif; ?>
                
                <div class="mt-3 text-left">
                    <button class="btn btn-sm btn-outline-primary" onclick="editWorkflow(<?= $workflow['id'] ?>)">
                        <i class="fas fa-edit ml-2"></i> تعديل الخطوات
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
        </div>
    </section>

<!-- Add/Edit Workflow Modal -->
<div class="modal fade" id="workflowModal" tabindex="-1" role="dialog" aria-labelledby="workflowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workflowModalLabel"><i class="fas fa-project-diagram ml-2"></i> إضافة / تعديل سير عمل</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="workflowForm">
                    <input type="hidden" id="workflowId" name="workflow_id">
                    
                    <div class="form-group">
                        <label for="workflowNameAr">اسم سير العمل (عربي)</label>
                        <input type="text" class="form-control" id="workflowNameAr" name="name_ar" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="entityType">نوع الكيان</label>
                        <select class="form-control" id="entityType" name="entity_type" required>
                            <option value="">اختر نوع الكيان</option>
                            <?php foreach ($entityTypes as $key => $value): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($value) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="isActive" name="is_active">
                        <label class="form-check-label" for="isActive">تفعيل سير العمل</label>
                    </div>

                    <hr>
                    <h6>الخطوات <small class="text-muted">(اضغط على <i class="fas fa-trash-alt"></i> لإزالة خطوة)</small></h6>
                    <div id="workflowStepsContainer" class="mb-3">
                        <!-- Workflow steps will be added here dynamically -->
                    </div>
                    
                    <button type="button" class="btn btn-outline-secondary btn-block" onclick="addStepField()">
                        <i class="fas fa-plus ml-2"></i> إضافة خطوة
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary-gradient" id="saveWorkflowBtn" onclick="saveWorkflow()">حفظ</button>
            </div>
        </div>
    </div>
</div>

<script>
    const allUsers = <?= json_encode($allUsers) ?>;
    const allRoles = <?= json_encode($allRoles) ?>;
    const entityTypes = <?= json_encode($entityTypes) ?>; // For displaying Arabic names

    let stepCounter = 0; // To ensure unique IDs for new steps

    // Function to toggle workflow active status
    function toggleWorkflow(id, isActive) {
        $.post('hr-app/index.php?action=save-workflow-config', {
            id: id,
            is_active: isActive ? 1 : 0
        }, function(res) {
            if (res.result) {
                toastr.success(isActive ? 'تم تفعيل سير العمل بنجاح.' : 'تم تعطيل سير العمل بنجاح.');
            } else {
                toastr.error(res.msg);
            }
        }, 'json').fail(function() {
            toastr.error('حدث خطأ أثناء تحديث حالة سير العمل.');
        });
    }

    // Function to add a new step field to the modal
    function addStepField(step = {}) {
        stepCounter++;
        const stepId = step.id || 'new_' + stepCounter;
        const stepName = step.name_ar || '';
        const approverType = step.approver_type || '';
        const approverId = step.approver_id || ''; // For specific_user
        const approverRole = step.approver_role || ''; // For role
        const stepOrder = step.step_order || $('#workflowStepsContainer').children().length + 1;

        const stepHtml = `
            <div class="workflow-step-editor" data-step-id="${stepId}">
                <button type="button" class="btn btn-remove-step" onclick="removeStepField(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <span class="step-order-indicator">${stepOrder}</span>
                <input type="hidden" name="steps[${stepId}][id]" value="${step.id || ''}">
                <input type="hidden" name="steps[${stepId}][step_order]" value="${stepOrder}">
                <div class="form-group">
                    <label for="stepNameAr_${stepId}">اسم الخطوة (عربي)</label>
                    <input type="text" class="form-control" id="stepNameAr_${stepId}" name="steps[${stepId}][name_ar]" value="${htmlspecialchars(stepName)}" required>
                </div>
                <div class="form-group">
                    <label for="approverType_${stepId}">نوع الموافق</label>
                    <select class="form-control" id="approverType_${stepId}" name="steps[${stepId}][approver_type]" onchange="toggleApproverDetails('${stepId}', this.value)" required>
                        <option value="">اختر نوع الموافق</option>
                        <option value="direct_manager" ${approverType === 'direct_manager' ? 'selected' : ''}>المدير المباشر</option>
                        <option value="hr_manager" ${approverType === 'hr_manager' ? 'selected' : ''}>مدير الموارد البشرية</option>
                        <option value="department_head" ${approverType === 'department_head' ? 'selected' : ''}>رئيس القسم</option>
                        <option value="specific_user" ${approverType === 'specific_user' ? 'selected' : ''}>مستخدم محدد</option>
                        <option value="role" ${approverType === 'role' ? 'selected' : ''}>دور محدد</option>
                    </select>
                </div>
                <div id="approverDetails_${stepId}" class="mb-2">
                    <!-- Specific approver details will be loaded here -->
                </div>
            </div>
        `;
        $('#workflowStepsContainer').append(stepHtml);
        toggleApproverDetails(stepId, approverType, approverId, approverRole); // Initialize details
        updateStepOrders();
    }

    // Function to remove a step field
    function removeStepField(button) {
        $(button).closest('.workflow-step-editor').remove();
        updateStepOrders();
    }

    // Function to dynamically show/hide approver details based on type
    function toggleApproverDetails(stepId, approverType, selectedApproverId = '', selectedApproverRole = '') {
        const detailsContainer = $(`#approverDetails_${stepId}`);
        detailsContainer.empty();

        if (approverType === 'specific_user') {
            let userOptions = '<option value="">اختر مستخدم</option>';
            allUsers.forEach(user => {
                userOptions += `<option value="${user.UserID}" ${user.UserID == selectedApproverId ? 'selected' : ''}>${htmlspecialchars(user.FirstName + ' ' + user.LastName)}</option>`;
            });
            detailsContainer.html(`
                <div class="form-group">
                    <label for="approverUser_${stepId}">المستخدم</label>
                    <select class="form-control" id="approverUser_${stepId}" name="steps[${stepId}][approver_id]" required>
                        ${userOptions}
                    </select>
                </div>
            `);
        } else if (approverType === 'role') {
            let roleOptions = '<option value="">اختر دور</option>';
            allRoles.forEach(role => {
                roleOptions += `<option value="${role.GroupID}" ${role.GroupID == selectedApproverRole ? 'selected' : ''}>${htmlspecialchars(role.GroupName)}</option>`;
            });
            detailsContainer.html(`
                <div class="form-group">
                    <label for="approverRole_${stepId}">الدور</label>
                    <select class="form-control" id="approverRole_${stepId}" name="steps[${stepId}][approver_role]" required>
                        ${roleOptions}
                    </select>
                </div>
            `);
        }
    }

    // Function to re-index step orders visually and in hidden fields
    function updateStepOrders() {
        $('#workflowStepsContainer').children('.workflow-step-editor').each(function(index) {
            const stepOrder = index + 1;
            $(this).find('.step-order-indicator').text(stepOrder);
            $(this).find(`input[name$="[step_order]"]`).val(stepOrder);
        });
    }

    // Function to open the modal for adding a new workflow
    function addWorkflow() {
        $('#workflowId').val('');
        $('#workflowForm')[0].reset();
        $('#workflowStepsContainer').empty();
        $('#workflowModalLabel').html('<i class="fas fa-plus ml-2"></i> إضافة سير عمل جديد');
        $('#saveWorkflowBtn').text('إضافة').off('click').on('click', saveWorkflow); // Reset click handler
        addStepField(); // Start with one empty step
        $('#workflowModal').modal('show');
    }

    // Function to open the modal for editing an existing workflow
    function editWorkflow(id) {
        $('#workflowId').val(id);
        $('#workflowForm')[0].reset();
        $('#workflowStepsContainer').empty();
        $('#workflowModalLabel').html('<i class="fas fa-edit ml-2"></i> تعديل سير عمل');
        $('#saveWorkflowBtn').text('حفظ التعديلات').off('click').on('click', saveWorkflow); // Reset click handler

        $.ajax({
            url: 'hr-app/index.php?action=get-workflow-details',
            method: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.result && res.data) {
                    const workflow = res.data;
                    $('#workflowNameAr').val(workflow.name_ar);
                    $('#entityType').val(workflow.entity_type);
                    $('#isActive').prop('checked', workflow.is_active == 1);

                    if (workflow.steps && workflow.steps.length > 0) {
                        workflow.steps.forEach(step => addStepField(step));
                    } else {
                        addStepField(); // Add an empty step if none exist
                    }
                    $('#workflowModal').modal('show');
                } else {
                    toastr.error(res.msg || 'فشل تحميل بيانات سير العمل.');
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء جلب بيانات سير العمل.');
            }
        });
    }

    // Function to save (add/update) a workflow
    function saveWorkflow() {
        const workflowId = $('#workflowId').val();
        const url = workflowId ? 'hr-app/index.php?action=update-workflow' : 'hr-app/index.php?action=add-workflow';
        const method = 'POST';
        
        // Serialize form data, including dynamic steps
        const formData = $('#workflowForm').serializeArray();
        let postData = {};
        formData.forEach(item => {
            if (item.name.endsWith('[]')) { // Handle arrays (if any, though not explicitly used for steps this way)
                const name = item.name.slice(0, -2);
                if (!postData[name]) postData[name] = [];
                postData[name].push(item.value);
            } else if (item.name.includes('[') && item.name.includes(']')) { // Handle steps[id][field]
                const match = item.name.match(/steps\[(.*?)\]\[(.*?)\]/);
                if (match) {
                    const stepIndex = match[1];
                    const fieldName = match[2];
                    if (!postData['steps']) postData['steps'] = {};
                    if (!postData['steps'][stepIndex]) postData['steps'][stepIndex] = {};
                    postData['steps'][stepIndex][fieldName] = item.value;
                } else {
                    postData[item.name] = item.value;
                }
            } else {
                postData[item.name] = item.value;
            }
        });
        
        // Ensure checkbox value is sent even if unchecked
        postData['is_active'] = $('#isActive').prop('checked') ? 1 : 0;

        // Convert steps object to an array for easier backend processing
        if (postData['steps']) {
            postData['steps'] = Object.values(postData['steps']);
        } else {
            postData['steps'] = []; // No steps added
        }

        // Basic form validation
        if (!postData.name_ar) { toastr.error('الرجاء إدخال اسم سير العمل.'); return; }
        if (!postData.entity_type) { toastr.error('الرجاء اختيار نوع الكيان.'); return; }
        if (postData.steps.length === 0) { toastr.warning('يرجى إضافة خطوة واحدة على الأقل لسير العمل.'); }

        let isValid = true;
        postData.steps.forEach(step => {
            if (!step.name_ar) { isValid = false; toastr.error('الرجاء إدخال اسم لجميع الخطوات.'); return false; }
            if (!step.approver_type) { isValid = false; toastr.error('الرجاء اختيار نوع الموافق لجميع الخطوات.'); return false; }
            if (step.approver_type === 'specific_user' && !step.approver_id) { isValid = false; toastr.error('الرجاء اختيار مستخدم محدد لخطوة الموافق.'); return false; }
            if (step.approver_type === 'role' && !step.approver_role) { isValid = false; toastr.error('الرجاء اختيار دور محدد لخطوة الموافق.'); return false; }
        });
        if (!isValid) return;

        $('#saveWorkflowBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ الحفظ...');

        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(postData), // Send as JSON to handle complex data structures easily
            contentType: 'application/json',
            dataType: 'json',
            success: function(res) {
                if (res.result) {
                    toastr.success(res.msg);
                    $('#workflowModal').modal('hide');
                    setTimeout(() => location.reload(), 500); // Reload page to reflect changes
                } else {
                    toastr.error(res.msg || 'حدث خطأ أثناء حفظ سير العمل.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                toastr.error('حدث خطأ غير متوقع: ' + (xhr.responseJSON ? xhr.responseJSON.msg : error));
            },
            complete: function() {
                $('#saveWorkflowBtn').prop('disabled', false).html(workflowId ? 'حفظ التعديلات' : 'إضافة');
            }
        });
    }

    // Helper for HTML escaping
    function htmlspecialchars(str) {
        if (typeof str != 'string') return str;
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

</script>

<?php include_once('inc/footer.php'); ?>