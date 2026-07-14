<?php
/**
 * Interactive Organizational Chart
 * Hierarchical view with real-time employee presence
 */
$screen = 'الهيكل التنظيمي';
$page_title = 'الهيكل التنظيمي';
include_once('inc/header.php');

require_once 'classes/OrgChartManager.php';
$orgManager = new OrgChartManager($connect_pdo);

// Get org tree data
$orgTree = $orgManager->getOrgTree(true, true);
$presenceSummary = $orgManager->getPresenceSummary();
$statusOptions = $orgManager->getPresenceStatusOptions();

// Get departments for filter
$departments = $orgManager->getDepartmentList();
?>

<!-- OrgChart CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.8.0/css/jquery.orgchart.min.css">
<style>
/* Container & General */
.org-chart-wrapper {
    background: #f4f6f9;
    border-radius: 12px;
    padding: 40px;
    min-height: 700px;
    height: 80vh;
    overflow: auto; /* Allow scrolling to see full chart */
    position: relative;
    border: 1px solid #e2e8f0;
}

#chart-container {
    height: 100%;
    width: 100%;
    text-align: center;
    min-width: max-content; /* Prevent horizontal cutoff */
    padding: 20px 60px; /* Extra padding to prevent edge cutoff */
}

/* OrgChart Node Styling */
.orgchart {
    background: transparent;
    padding: 40px 80px; /* Extra padding for flowlines at edges */
}

.orgchart .node {
    width: 240px;
    height: auto;
    border: none;
    background: transparent;
    padding: 0;
}

.orgchart .node .title {
    background-color: #0d21a5;
    color: #fff;
    height: auto;
    min-height: 44px;
    line-height: 1.4;
    border-radius: 10px 10px 0 0;
    font-size: 15px;
    font-weight: bold;
    white-space: normal; /* Allow text wrapping for long names */
    word-wrap: break-word;
    overflow: visible;
    padding: 10px 12px;
    text-align: center;
}

.orgchart .node .content {
    background-color: #fff;
    border: 2px solid #0d21a5;
    border-top: none;
    border-radius: 0 0 10px 10px;
    padding: 12px;
    height: auto;
    min-height: 90px;
    font-size: 13px;
    color: #333;
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* Node Types Colors - More distinct */
.orgchart .node.department .title { background: linear-gradient(135deg, #0d21a5 0%, #1e3a8a 100%); }
.orgchart .node.division .title { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); }
.orgchart .node.section .title { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); }
.orgchart .node.team .title { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); }

.orgchart .node .content { border-color: inherit; }
.orgchart .node.department .content { border-color: #0d21a5; }
.orgchart .node.division .content { border-color: #4f46e5; }
.orgchart .node.section .content { border-color: #2563eb; }
.orgchart .node.team .content { border-color: #0891b2; }

/* Manager Info inside Node */
.node-manager {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #e5e7eb;
}

.node-manager img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}

.node-manager .info {
    text-align: right;
    flex: 1;
    overflow: hidden;
}

.node-manager .name {
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1f2937;
}

.node-manager .role {
    font-size: 11px;
    color: #6b7280;
}

/* Stats inside Node */
.node-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #4b5563;
    margin-top: auto;
    padding-top: 5px;
    border-top: 1px solid #f3f4f6;
}

.node-stats span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Action Buttons Overlay */
.node-actions {
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 6px;
    border-radius: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    display: none;
    gap: 6px;
    z-index: 10;
}

.orgchart .node:hover .node-actions {
    display: flex;
}

.node-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    color: white;
}

.node-action-btn:hover {
    transform: scale(1.1);
}

.btn-add-child { background: #10b981; }
.btn-edit { background: #f59e0b; }
.btn-delete { background: #ef4444; }
.btn-details { background: #3b82f6; }

/* Presence Cards */
.presence-summary {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.presence-card {
    background: white;
    border-radius: 10px;
    padding: 15px 20px;
    min-width: 120px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-right: 4px solid;
    flex: 1;
}

.presence-card .count { font-size: 24px; font-weight: 700; }
.presence-card .label { font-size: 12px; color: #6b7280; }

/* Connectors - Thicker and more visible */
.orgchart .lines .downLine { 
    background-color: #94a3b8; 
    width: 3px;
}
.orgchart .lines .topLine { 
    border-top: 3px solid #94a3b8; 
}
.orgchart .lines .rightLine { 
    border-right: 3px solid #94a3b8; 
}
.orgchart .lines .leftLine { 
    border-left: 3px solid #94a3b8; 
}

/* Ensure lines extend properly */
.orgchart > table {
    margin: 0 auto;
}

.orgchart .lines {
    height: 25px;
}

/* Controls */
.chart-controls {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 1000;
}

.chart-controls button {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #4a5568;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.2s;
}

.chart-controls button:hover {
    background: #0d21a5;
    color: white;
    transform: scale(1.05);
}

/* Root node special styling */
.orgchart .node.root-node .title {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .org-chart-wrapper {
        padding: 15px;
        height: 60vh;
    }
    
    .orgchart .node {
        width: 180px;
    }
    
    .orgchart .node .title {
        font-size: 13px;
        padding: 8px;
    }
    
    .presence-summary {
        gap: 10px;
    }
    
    .presence-card {
        min-width: 80px;
        padding: 10px;
    }
    
    .presence-card .count {
        font-size: 18px;
    }
}
</style>

<section class="content">
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1"><i class="fas fa-sitemap text-primary"></i> الهيكل التنظيمي</h4>
            <p class="text-muted mb-0">عرض تفاعلي للهيكل التنظيمي مع حالة تواجد الموظفين</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($User->userIsAdmin()): ?>
            <button class="btn btn-outline-primary" onclick="syncSections()" title="استيراد الأقسام من النظام">
                <i class="fas fa-sync"></i> استيراد الأقسام
            </button>
            <button class="btn btn-primary" onclick="openAddNodeModal()">
                <i class="fas fa-plus"></i> إضافة جذر جديد
            </button>
            <?php endif; ?>
            <button class="btn btn-default" onclick="exportChart()">
                <i class="fas fa-download"></i> تصدير
            </button>
        </div>
    </div>

    <!-- Presence Summary -->
    <div class="presence-summary">
        <div class="presence-card" style="border-color: #10b981;">
            <div class="count" style="color: #10b981;"><?= $presenceSummary['present'] ?? 0 ?></div>
            <div class="label">حاضر</div>
        </div>
        <div class="presence-card" style="border-color: #ef4444;">
            <div class="count" style="color: #ef4444;"><?= $presenceSummary['absent'] ?? 0 ?></div>
            <div class="label">غائب</div>
        </div>
        <div class="presence-card" style="border-color: #f59e0b;">
            <div class="count" style="color: #f59e0b;"><?= $presenceSummary['late'] ?? 0 ?></div>
            <div class="label">متأخر</div>
        </div>
        <div class="presence-card" style="border-color: #8b5cf6;">
            <div class="count" style="color: #8b5cf6;"><?= $presenceSummary['on_leave'] ?? 0 ?></div>
            <div class="label">في إجازة</div>
        </div>
        <div class="presence-card" style="border-color: #3b82f6;">
            <div class="count" style="color: #3b82f6;"><?= $presenceSummary['in_meeting'] ?? 0 ?></div>
            <div class="label">في اجتماع</div>
        </div>
        <div class="presence-card" style="border-color: #6b7280;">
            <div class="count" style="color: #6b7280;"><?= $presenceSummary['total_employees'] ?? 0 ?></div>
            <div class="label">إجمالي</div>
        </div>
    </div>

    <!-- Chart Area -->
    <div class="org-chart-wrapper">
        <div id="chart-container"></div>
        
        <div class="chart-controls">
            <button onclick="manualZoom(0.1)" title="تكبير"><i class="fas fa-plus"></i></button>
            <button onclick="manualZoom(-0.1)" title="تصغير"><i class="fas fa-minus"></i></button>
            <button onclick="resetChartZoom()" title="توسط"><i class="fas fa-compress-arrows-alt"></i></button>
            <button onclick="toggleFullscreen()" title="ملء الشاشة"><i class="fas fa-expand"></i></button>
        </div>
    </div>

</div>
</section>

<!-- Add/Edit Node Modal -->
<div class="modal fade" id="addNodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle"></i> إضافة قسم/إدارة</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="nodeForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="nodeId">
                    <input type="hidden" name="parent_id" id="parentId">
                    
                    <div class="form-group">
                        <label>النوع <span class="text-danger">*</span></label>
                        <select name="node_type" id="nodeType" class="form-control" required>
                            <option value="division">قطاع</option>
                            <option value="department" selected>إدارة</option>
                            <option value="section">قسم</option>
                            <option value="team">فريق</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="nameAr" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" id="nameEn" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>المدير</label>
                        <select name="manager_id" id="managerId" class="form-control select2" style="width: 100%;">
                            <option value="">-- اختر المدير --</option>
                            <?php
                            $managers = $connect_pdo->query("SELECT UserID, FirstName, LastName FROM tblusers WHERE isemp = 1 ORDER BY FirstName")->fetchAll();
                            foreach ($managers as $mgr) {
                                echo '<option value="' . $mgr['UserID'] . '">' . htmlspecialchars($mgr['FirstName'] . ' ' . $mgr['LastName']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>ربط بقسم موجود (لجلب الموظفين)</label>
                        <select name="section_id" id="sectionId" class="form-control select2" style="width: 100%;">
                            <option value="">-- اختر القسم --</option>
                            <?php
                            foreach ($departments as $dept) {
                                echo '<option value="' . $dept['Id'] . '">' . htmlspecialchars($dept['Name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Employee List Modal -->
<div class="modal fade" id="nodeEmployeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">موظفي القسم</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="employeeListLoader" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
                <div id="employeeListContent"></div>
            </div>
        </div>
    </div>
</div>

<?php include_once('inc/footer.php'); ?>

<!-- OrgChart JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.8.0/js/jquery.orgchart.min.js"></script>
<!-- html2canvas for export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
$(function() {
    // Move modals to body to prevent z-index/focus issues
    $('#addNodeModal').appendTo('body');
    $('#nodeEmployeesModal').appendTo('body');

    // Initial Data
    var orgData = <?= json_encode($orgTree) ?>;
    
    // Transform data for OrgChart if needed
    // OrgChart expects a single root, but we might have multiple top-level nodes
    var chartDataSource = {
        'id': 'root',
        'name': 'الشركة',
        'title': 'الإدارة العليا',
        'className': 'root-node',
        'children': orgData.map(transformNode)
    };

    function transformNode(node) {
        return {
            'id': node.id,
            'name': node.name_ar,
            'title': node.node_type,
            'node_type': node.node_type,
            'manager_name': node.manager_first ? node.manager_first + ' ' + node.manager_last : null,
            'manager_photo': node.manager_photo,
            'employee_count': node.employee_count,
            'section_id': node.section_id,
            'manager_id': node.manager_id,
            'name_en': node.name_en,
            'children': (node.children && node.children.length) ? node.children.map(transformNode) : []
        };
    }

    // Initialize Chart
    var oc = $('#chart-container').orgchart({
        'data': chartDataSource,
        'nodeContent': 'title',
        'pan': true,
        'zoom': true,
        'zoominLimit': 3,
        'zoomoutLimit': 0.5,
        'direction': 't2b', // Top to Bottom
        'verticalLevel': 3, // Align vertically after depth 3
        'draggable': <?= $User->userIsAdmin() ? 'true' : 'false' ?>, // Enable Drag & Drop for admins
        'dropCriteria': function($draggedNode, $dragZone, $dropZone) {
            // Prevent dropping a parent into its own child (handled by library usually, but good to be safe)
            return true;
        },
        'createNode': function($node, data) {
            // Custom Node Content
            var contentHtml = `
                <div class="content">
                    ${data.manager_name ? `
                    <div class="node-manager">
                        <img src="${data.manager_photo || 'dist/img/default-user.svg'}" alt="">
                        <div class="info">
                            <div class="name">${data.manager_name}</div>
                            <div class="role">المدير</div>
                        </div>
                    </div>
                    ` : '<div class="text-muted text-center mb-2" style="font-size:10px">لا يوجد مدير</div>'}
                    
                    <div class="node-stats">
                        <span><i class="fas fa-users"></i> ${data.employee_count || 0}</span>
                        <span>${getNodeTypeLabel(data.node_type || 'department')}</span>
                    </div>

                    <?php if ($User->userIsAdmin()): ?>
                    <div class="node-actions">
                        <button class="node-action-btn btn-add-child" onclick="addChildNode(${data.id})" title="إضافة فرع"><i class="fas fa-plus"></i></button>
                        <button class="node-action-btn btn-edit" onclick="editNode(${data.id})" title="تعديل"><i class="fas fa-edit"></i></button>
                        <button class="node-action-btn btn-details" onclick="viewEmployees(${data.id}, '${data.name}')" title="الموظفين"><i class="fas fa-list"></i></button>
                        <button class="node-action-btn btn-delete" onclick="deleteNode(${data.id})" title="حذف"><i class="fas fa-trash"></i></button>
                    </div>
                    <?php else: ?>
                    <div class="node-actions">
                         <button class="node-action-btn btn-details" onclick="viewEmployees(${data.id}, '${data.name}')" title="الموظفين"><i class="fas fa-list"></i></button>
                    </div>
                    <?php endif; ?>
                </div>
            `;
            
            // Append content to node (jquery-orgchart creates .title div, we append .content)
            $node.find('.content').remove(); // Remove default if any
            $node.append(contentHtml);
            
            // Add custom class based on type
            $node.addClass(data.node_type || 'department');
        }
    });
    
    // Store chart instance
    window.chart = oc;

    // Handle Drag & Drop Event
    oc.$chart.on('nodedrop.orgchart', function(event, extraData) {
        var draggedNodeId = extraData.draggedNode.data('nodeData').id;
        var dropZoneId = extraData.dropZone.data('nodeData').id;
        
        // Handle dropping on root node
        if (dropZoneId === 'root') {
            dropZoneId = ''; // Set to empty to indicate top-level (NULL in DB)
        }

        // Confirm move
        if (!confirm('هل أنت متأكد من نقل هذا القسم؟')) {
            location.reload(); // Revert UI if cancelled
            return;
        }

        $.post('hr-app/index.php?action=move-org-node', {
            node_id: draggedNodeId,
            parent_id: dropZoneId
        }, function(res) {
            if (res.result) {
                toastr.success('تم نقل القسم بنجاح');
            } else {
                toastr.error(res.msg || 'فشل نقل القسم');
                setTimeout(() => location.reload(), 1000); // Revert on error
            }
        }, 'json');
    });

    // Handle Window Resize
    $(window).resize(function() {
        // Debounce resize
        clearTimeout(window.resizedFinished);
        window.resizedFinished = setTimeout(function(){
           // Optional: recenter or adjust
        }, 250);
    });
    
    // Initialize Select2 in Modals
    $('.select2').select2({
        dropdownParent: $('#addNodeModal'),
        width: '100%',
        dir: 'rtl'
    });
});

// Helper Functions
function getNodeTypeLabel(type) {
    const labels = {
        'company': 'الشركة',
        'division': 'قطاع',
        'department': 'إدارة',
        'section': 'قسم',
        'team': 'فريق'
    };
    return labels[type] || type;
}

// Modal Actions
window.openAddNodeModal = function() {
    $('#modalTitle').html('<i class="fas fa-plus-circle"></i> إضافة جذر جديد');
    $('#nodeForm')[0].reset();
    $('#nodeId').val('');
    $('#parentId').val('');
    $('#managerId').val('').trigger('change');
    $('#sectionId').val('').trigger('change');
    $('#addNodeModal').modal('show');
}

window.addChildNode = function(parentId) {
    event.stopPropagation();
    $('#modalTitle').html('<i class="fas fa-plus-circle"></i> إضافة فرع');
    $('#nodeForm')[0].reset();
    $('#nodeId').val('');
    $('#parentId').val(parentId);
    $('#managerId').val('').trigger('change');
    $('#sectionId').val('').trigger('change');
    $('#addNodeModal').modal('show');
}

window.editNode = function(id) {
    event.stopPropagation();
    // Fetch node details via AJAX
    $.get('hr-app/index.php?action=get-org-node&id=' + id, function(res) {
        if(res.result) {
            var data = res.data;
            $('#modalTitle').html('<i class="fas fa-edit"></i> تعديل');
            $('#nodeId').val(data.id);
            $('#parentId').val(data.parent_id);
            $('#nodeType').val(data.node_type);
            $('#nameAr').val(data.name_ar);
            $('#nameEn').val(data.name_en);
            $('#managerId').val(data.manager_id).trigger('change');
            $('#sectionId').val(data.section_id).trigger('change');
            $('#addNodeModal').modal('show');
        } else {
            toastr.error('فشل جلب بيانات العقدة');
        }
    });
}

window.deleteNode = function(id) {
    event.stopPropagation();
    if(confirm('هل أنت متأكد من حذف هذا القسم؟ لا يمكن حذف قسم يحتوي على فروع.')) {
        $.post('hr-app/index.php?action=delete-org-node', {id: id}, function(res) {
            if(res.result) {
                toastr.success(res.message || 'تم الحذف بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(res.message || 'حدث خطأ');
            }
        }, 'json');
    }
}

window.syncSections = function() {
    if(confirm('سيتم استيراد الأقسام من النظام وإضافتها كأقسام رئيسية. هل أنت متأكد؟')) {
        $.post('hr-app/index.php?action=sync-org-from-sections', function(res) {
            if(res.result !== false) { // Logic matches the backend response format
                var message = res.msg;
                if (res.data && res.data.sections && res.data.sections.length > 0) {
                    message += '\nالأقسام المضافة: ' + res.data.sections.join(', ');
                }
                toastr.success(message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(res.msg || 'حدث خطأ أثناء المزامنة');
            }
        }, 'json');
    }
}

window.manualZoom = function(amount) {
    var currentScale = parseFloat($('.orgchart').css('transform').replace(/[^0-9\-.,]/g, '').split(',')[3]) || 1;
    var newScale = currentScale + amount;
    if (newScale > 0.1 && newScale < 5) {
        $('.orgchart').css('transform', 'scale(' + newScale + ')');
    }
}

window.resetChartZoom = function() {
    $('.orgchart').css('transform', '');
}

window.viewEmployees = function(id, name) {
    event.stopPropagation();
    $('#nodeEmployeesModal .modal-title').text('موظفي: ' + name);
    $('#nodeEmployeesModal').modal('show');
    $('#employeeListContent').hide();
    $('#employeeListLoader').show();
    
    $.get('hr-app/index.php?action=get-node-employees&node_id=' + id, function(res) {
        $('#employeeListLoader').hide();
        $('#employeeListContent').show();
        
        if(res.result && res.data.length > 0) {
            var html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>الموظف</th><th>المسمى الوظيفي</th><th>الحالة</th></tr></thead><tbody>';
            res.data.forEach(function(emp) {
                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="${emp.Photo || 'dist/img/default-user.svg'}" class="rounded-circle mr-2" style="width:30px;height:30px;object-fit:cover;border:1px solid #eee;">
                                <span>${emp.FirstName} ${emp.LastName}</span>
                            </div>
                        </td>
                        <td>${emp.job_title || '-'}</td>
                        <td><span class="badge badge-info">${emp.current_status || 'خارج الدوام'}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table></div>';
            $('#employeeListContent').html(html);
        } else {
            $('#employeeListContent').html('<div class="alert alert-info text-center">لا يوجد موظفين في هذا القسم</div>');
        }
    });
}

// Form Submission
$('#nodeForm').submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.post('hr-app/index.php?action=save-org-node', formData, function(res) {
        if(res.result) {
            toastr.success('تم الحفظ بنجاح');
            $('#addNodeModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(res.message || 'حدث خطأ أثناء الحفظ');
        }
    }, 'json');
});

// Export
window.exportChart = function() {
    var $chartContainer = $('#chart-container');
    html2canvas($chartContainer[0], {
        scale: 2,
        backgroundColor: '#f4f6f9'
    }).then(canvas => {
        var link = document.createElement('a');
        link.download = 'org-chart.png';
        link.href = canvas.toDataURL();
        link.click();
    });
}

// Fullscreen
window.toggleFullscreen = function() {
    var elem = document.querySelector('.org-chart-wrapper');
    if (!document.fullscreenElement) {
        elem.requestFullscreen().catch(err => {
            alert(`Error attempting to enable fullscreen: ${err.message} (${err.name})`);
        });
    } else {
        document.exitFullscreen();
    }
}
</script>

