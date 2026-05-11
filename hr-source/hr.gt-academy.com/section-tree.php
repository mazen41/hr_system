<?php
$screen = 'التقارير';
$report_name = 'الاقسام';
$page_title = $report_name;
include_once('inc/header.php');

$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches);
$allowed_branch = implode(',', $branch_ids);

$sections = [];
$query = "SELECT c.Id, c.Name, c.ParentID, b.branch_name, c.CreatedBy, c.CreatedDate,
                 u.FirstName, u.LastName
          FROM tblsection AS c
          LEFT JOIN branches AS b ON c.BranchID = b.branch_id
          LEFT JOIN tblusers AS u ON c.CreatedBy = u.UserID
          WHERE c.BranchID in ($allowed_branch)
          ORDER BY c.ParentID, c.Id";

$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// بناء الشجرة
$tree = [];
$lookup = [];

foreach ($results as $row) {
    $row['children'] = [];
    $lookup[$row['Id']] = $row;
}

foreach ($lookup as $id => &$node) {
    if (!empty($node['ParentID']) && isset($lookup[$node['ParentID']])) {
        $lookup[$node['ParentID']]['children'][] = &$node;
    } else {
        $tree[] = &$node;
    }
}
unset($node);
?>




<style>
    /* تصميم أساسي */
    body {
        font-family: Tahoma, Arial, sans-serif;
        direction: rtl;
        padding: 20px;
        background-color: #f4f4f4;
    }
    
    .report-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .report-title {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
    }
    
    /* تصميم حاوية الشجرة */
    .tree-container {
        width: 100%;
        overflow: auto;
        margin: 30px 0;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    
    /* تصميم الشجرة */
    .org-tree {
        display: inline-block;
        position: relative;
        margin: 0 auto;
    }
    
    .org-tree ul {
        padding-top: 40px;
        position: relative;
        margin: 0;
        padding-left: 0;
        display: flex;
        justify-content: center;
    }
    
    .org-tree li {
        list-style: none;
        text-align: center;
        position: relative;
        padding: 40px 15px 0 15px;
        vertical-align: top;
    }
    
    /* الخط العمودي من الأب */
    .org-tree li::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 40px;
        background-color: #4a89dc;
    }
    
    /* الخط الأفقي بين الإخوة */
    .org-tree ul::before {
        content: '';
        position: absolute;
        top: 40px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #4a89dc;
    }
    
    /* إخفاء الخطوط غير الضرورية */
    .org-tree li:only-child::before {
        display: none;
    }
    
    .org-tree li:only-child {
        padding-top: 0;
    }
    
    .org-tree > ul > li::before {
        display: none;
    }
    
    .org-tree > ul::before {
        display: none;
    }
    
    /* تصميم العقد */
    .org-tree .node {
        display: inline-block;
        padding: 12px 15px;
        background-color: #ffffff;
        color: #333;
        border: 2px solid #4a89dc;
        border-radius: 8px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        position: relative;
        min-width: 150px;
        max-width: 200px;
        word-wrap: break-word;
        font-weight: bold;
        transition: all 0.3s;
    }
    
    .org-tree .node:hover {
        background-color: #f0f7ff;
        transform: translateY(-3px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.15);
    }
    
    .org-tree .node .node-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .org-tree .node .branch-name {
        font-size: 11px;
        color: #7f8c8d;
        margin-top: 3px;
    }
    
    .org-tree .node .created-by {
        font-size: 10px;
        color: #95a5a6;
        margin-top: 3px;
    }
    
    /* أزرار التحكم */
    .print-controls {
        text-align: center;
        margin: 30px 0;
        padding: 20px;
        background: #f5f7fa;
        border-radius: 8px;
    }
    
    .btn {
        padding: 10px 20px;
        margin: 0 8px;
        border-radius: 6px;
        cursor: pointer;
        border: none;
        font-weight: bold;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .btn-print {
        background: #4a89dc;
        color: white;
    }
    
    .btn-landscape {
        background: #8cc152;
        color: white;
    }
    
    .btn-a3 {
        background: #f6bb42;
        color: white;
    }
    
    .btn-pdf {
        background: #e9573f;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    
    /* الطباعة */
    @media print {
        body {
            padding: 0;
            background: none;
        }
        
        .no-print {
            display: none;
        }
        
        .tree-container {
            transform: none !important;
            padding: 0 !important;
            background: none !important;
            box-shadow: none !important;
        }
        
        .org-tree .node {
            box-shadow: none !important;
        }
    }
</style>

<div class="report-header">
    <div class="report-title">الشجرة التنظيمية للادارات</div>
    <div class="report-date">تاريخ التقرير: <?php echo date('Y-m-d'); ?></div>
</div>

<div class="tree-container" id="treeContainer">
    <div class="org-tree" id="orgTree">
        <?php renderTreeGraphic($tree); ?>
    </div>
</div>

<div class="print-controls no-print">
    <button class="btn btn-print" onclick="printReport('portrait', 'a4')">🖨 طباعة عمودي (A4)</button>
    <button class="btn btn-landscape" onclick="printReport('landscape', 'a4')">🖨 طباعة أفقي (A4)</button>
    <button class="btn btn-a3" onclick="printReport('portrait', 'a3')">🖨 طباعة عمودي (A3)</button>
    <button class="btn btn-a3" onclick="printReport('landscape', 'a3')">🖨 طباعة أفقي (A3)</button>
    <!-- <button class="btn btn-pdf" onclick="exportToPDF()">📄 تصدير PDF</button> -->
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// ضبط حجم الشجرة لتناسب العرض
function adjustTreeSize() {
    const container = document.getElementById('treeContainer');
    const tree = document.getElementById('orgTree');
    const containerWidth = container.clientWidth;
    const treeWidth = tree.scrollWidth;
    
    if (treeWidth > containerWidth) {
        const scale = (containerWidth * 0.95) / treeWidth;
        tree.style.transform = `scale(${scale})`;
    } else {
        tree.style.transform = 'scale(1)';
    }
}

// طباعة التقرير
function printReport(orientation, paperSize) {
    const style = document.createElement('style');
    style.innerHTML = `
        @page {
            size: ${paperSize} ${orientation};
            margin: 10mm;
        }
    `;
    
    document.head.appendChild(style);
    window.print();
    document.head.removeChild(style);
}

// تصدير إلى PDF
function exportToPDF() {
    const element = document.getElementById('treeContainer');
    const opt = {
        margin: 10,
        filename: 'الشجرة_التنظيمية.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().from(element).set(opt).save();
}

window.addEventListener('load', adjustTreeSize);
window.addEventListener('resize', adjustTreeSize);
</script>

<?php
function renderTreeGraphic($nodes) {
    if (empty($nodes)) return;
    
    echo "<ul>";
    foreach ($nodes as $node) {
        echo "<li>";
        echo "<div class='node'>";
        echo "<div class='node-title'>" . htmlspecialchars($node['Name']) . "</div>";
        if (!empty($node['branch_name'])) {
            echo "<div class='branch-name'>" . htmlspecialchars($node['branch_name']) . "</div>";
        }
        if (!empty($node['FirstName']) || !empty($node['LastName'])) {
            echo "<div class='created-by'>أنشئ بواسطة: " . htmlspecialchars($node['FirstName'] . ' ' . $node['LastName']) . "</div>";
        }
        echo "</div>";
        
        if (!empty($node['children'])) {
            renderTreeGraphic($node['children']);
        }
        echo "</li>";
    }
    echo "</ul>";
}

include_once('inc/footer.php');
?>