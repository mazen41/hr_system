<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "sEmptyTable" => "لا توجد بيانات متاحة في الجدول",
    "sInfo" => "عرض _START_ إلى _END_ من أصل _TOTAL_ مدخلات",
    "sInfoEmpty" => "عرض 0 إلى 0 من أصل 0 مدخلات",
    "sInfoFiltered" => "(منتقاة من مجموع _MAX_ مدخلات)",
    "sInfoPostFix" => "",
    "sInfoThousands" => ",",
    "sLengthMenu" => "أظهر _MENU_ مدخلات",
    "sLoadingRecords" => "جارٍ التحميل...",
    "sProcessing" => "جارٍ المعالجة...",
    "sSearch" => "بحث:",
    "sSearchPlaceholder" => "بحث...",
    "sZeroRecords" => "لم يتم العثور على سجلات مطابقة",
    "oPaginate" => [
        "sFirst" => "الأول",
        "sLast" => "الأخير",
        "sNext" => "التالي",
        "sPrevious" => "السابق"
    ],
    "oAria" => [
        "sSortAscending" => ": تفعيل لترتيب العمود تصاعدياً",
        "sSortDescending" => ": تفعيل لترتيب العمود تنازلياً"
    ],
    "buttons" => [
        "copy" => "نسخ",
        "csv" => "CSV",
        "excel" => "Excel",
        "pdf" => "PDF",
        "print" => "طباعة",
        "colvis" => "إظهار الأعمدة"
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
