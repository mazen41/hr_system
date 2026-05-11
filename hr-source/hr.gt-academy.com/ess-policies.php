<?php
$page_title = 'سياسات الشركة';
$page_perm = [];
$appid = 1;
include_once('inc/header.php');

// Get employee branch
$branchId = $_SESSION['branch'] ?? 0;
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="page-nav">
                <h1 class="page-title"><i class="fas fa-book"></i> سياسات الشركة</h1>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Policy Categories -->
            <div class="row">
                <!-- Attendance Policy -->
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clock"></i> سياسة الحضور والانصراف</h5>
                        </div>
                        <div class="card-body">
                            <div class="policy-content">
                                <h6><i class="fas fa-check-circle text-success"></i> ساعات العمل</h6>
                                <ul>
                                    <li>ساعات العمل الرسمية: 8:00 صباحاً - 5:00 مساءً</li>
                                    <li>فترة الاستراحة: 12:00 - 1:00 مساءً</li>
                                    <li>أيام العمل: الأحد - الخميس</li>
                                </ul>
                                
                                <h6><i class="fas fa-exclamation-triangle text-warning"></i> التأخير</h6>
                                <ul>
                                    <li>يسمح بتأخير 15 دقيقة كحد أقصى</li>
                                    <li>التأخير المتكرر يؤثر على التقييم السنوي</li>
                                    <li>3 تأخيرات = إنذار كتابي</li>
                                </ul>
                                
                                <h6><i class="fas fa-sign-out-alt text-danger"></i> الانصراف المبكر</h6>
                                <ul>
                                    <li>يتطلب موافقة المدير المباشر</li>
                                    <li>يخصم من الراتب إذا تجاوز ساعة</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Policy -->
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> سياسة الإجازات</h5>
                        </div>
                        <div class="card-body">
                            <div class="policy-content">
                                <h6><i class="fas fa-umbrella-beach text-info"></i> الإجازة السنوية</h6>
                                <ul>
                                    <li>21 يوم عمل في السنة</li>
                                    <li>يمكن ترحيل 7 أيام للسنة التالية</li>
                                    <li>يجب طلب الإجازة قبل 7 أيام على الأقل</li>
                                </ul>
                                
                                <h6><i class="fas fa-procedures text-danger"></i> الإجازة المرضية</h6>
                                <ul>
                                    <li>30 يوم مدفوعة بالكامل</li>
                                    <li>60 يوم بنصف الراتب</li>
                                    <li>يتطلب تقرير طبي معتمد</li>
                                </ul>
                                
                                <h6><i class="fas fa-baby text-pink"></i> إجازة الأمومة</h6>
                                <ul>
                                    <li>70 يوم مدفوعة بالكامل</li>
                                    <li>يمكن تمديدها بدون راتب</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Policy -->
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> سياسة الرواتب والمستحقات</h5>
                        </div>
                        <div class="card-body">
                            <div class="policy-content">
                                <h6><i class="fas fa-calendar-check text-success"></i> موعد صرف الراتب</h6>
                                <ul>
                                    <li>يصرف الراتب في 25 من كل شهر</li>
                                    <li>إذا صادف يوم عطلة، يصرف في اليوم السابق</li>
                                </ul>
                                
                                <h6><i class="fas fa-hand-holding-usd text-primary"></i> السلف</h6>
                                <ul>
                                    <li>الحد الأقصى: راتب شهر واحد</li>
                                    <li>يخصم على 3 أشهر كحد أقصى</li>
                                    <li>لا يمكن طلب سلفة جديدة قبل سداد السابقة</li>
                                </ul>
                                
                                <h6><i class="fas fa-gift text-info"></i> المكافآت</h6>
                                <ul>
                                    <li>مكافأة نهاية الخدمة حسب نظام العمل</li>
                                    <li>مكافآت الأداء السنوية</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Code of Conduct -->
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie"></i> قواعد السلوك المهني</h5>
                        </div>
                        <div class="card-body">
                            <div class="policy-content">
                                <h6><i class="fas fa-handshake text-success"></i> الالتزامات</h6>
                                <ul>
                                    <li>الالتزام بمواعيد العمل</li>
                                    <li>احترام الزملاء والمديرين</li>
                                    <li>الحفاظ على سرية المعلومات</li>
                                    <li>الالتزام بالزي الرسمي</li>
                                </ul>
                                
                                <h6><i class="fas fa-ban text-danger"></i> المحظورات</h6>
                                <ul>
                                    <li>استخدام ممتلكات الشركة لأغراض شخصية</li>
                                    <li>إفشاء أسرار العمل</li>
                                    <li>التدخين في أماكن العمل المغلقة</li>
                                    <li>العمل لدى جهة أخرى بدون إذن</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download All Policies -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-lg" onclick="downloadAllPolicies()">
                        <i class="fas fa-download"></i> تحميل جميع السياسات (PDF)
                    </button>
                    <button class="btn btn-outline-primary btn-lg mr-2" onclick="printPolicies()">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
.policy-content h6 {
    margin-top: 15px;
    margin-bottom: 10px;
    font-weight: 700;
    color: #1a1a2e;
}
.policy-content ul {
    padding-right: 20px;
    margin-bottom: 0;
}
.policy-content li {
    margin-bottom: 5px;
    color: #4b5563;
}
</style>

<?php include_once('inc/footer.php'); ?>

<script>
function downloadAllPolicies() {
    window.location.href = 'hr-app/index.php?action=ess-policies-pdf';
}

function printPolicies() {
    window.print();
}
</script>
