<?php
$screen = 'تقرير الموظف';
$page_title = 'مؤشرات';
 $screen = 'تقرير الموظف';
 $page_title = 'مؤشرات';
 include_once('inc/header.php');
 $currency = $User->currency;
 $dashboardAdvanceTitle = $User->userIsEmployee() ? 'إجمالي سلف الموظف' : 'إجمالي سلف الموظفين';
?>
<style>
/* Fix dropdown z-index - MUST appear above content */
.main-header .dropdown-menu {
    z-index: 1100 !important;
    position: absolute !important;
}
.main-header .dropdown-menu.show {
    z-index: 1100 !important;
}
.content {
    position: relative;
    z-index: 1;
}
.progress{
    /* height: 3px !important; */
}
.progress-description{
    font-size: 14px;
}
.fa-donate{
    color: white;
} 
#dashboard_chart_canvas{
    display:block;
    width:100% !important;
    height:320px !important;
}
.dashboard-chart-body{
    min-height:320px;
    content-visibility:auto;
    contain-intrinsic-size:320px;
}
.dashboard-summary-card .info-box,
.content > .container-fluid > .row:nth-of-type(2) .info-box{
    min-height:170px;
}
.dashboard-summary-card .info-box-text,
.content > .container-fluid > .row:nth-of-type(2) .info-box-text{
    white-space: normal;
    line-height: 1.35;
}
.dashboard-summary-card .info-box-number,
.content > .container-fluid > .row:nth-of-type(2) .info-box-number{
    line-height: 1.2;
    word-break: break-word;
}
@media (max-width: 767.98px) {
    .dashboard-mobile-filter{
        margin-top: 10px;
        text-align: right !important;
    }
    .dashboard-summary-card,
    .content > .container-fluid > .row:nth-of-type(2) > [class*="col-"]{
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    .dashboard-summary-card .info-box,
    .content > .container-fluid > .row:nth-of-type(2) .info-box{
        min-height: 132px;
        margin-bottom: 14px;
    }
    .dashboard-summary-card .info-box-icon,
    .content > .container-fluid > .row:nth-of-type(2) .info-box-icon{
        width: 58px !important;
        min-width: 58px !important;
        font-size: 1.15rem !important;
    }
    .dashboard-summary-card .info-box-content,
    .content > .container-fluid > .row:nth-of-type(2) .info-box-content{
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .dashboard-summary-card .info-box-text,
    .content > .container-fluid > .row:nth-of-type(2) .info-box-text{
        font-size: 0.95rem !important;
    }
    .dashboard-summary-card .info-box-number,
    .content > .container-fluid > .row:nth-of-type(2) .info-box-number{
        font-size: 1.8rem !important;
    }
    .dashboard-chart-card .card-header,
    .content > .container-fluid > .row:nth-of-type(3) .card-header{
        display: block;
    }
    .dashboard-chart-card .card-tools,
    .content > .container-fluid > .row:nth-of-type(3) .card-tools{
        margin-top: 12px;
        float: none !important;
        width: 100%;
    }
    .dashboard-chart-card .card-tools .btn-group,
    .dashboard-chart-card .card-tools .form-control,
    .content > .container-fluid > .row:nth-of-type(3) .card-tools .btn-group,
    .content > .container-fluid > .row:nth-of-type(3) .card-tools .form-control{
        width: 100%;
    }
}
</style>
 <!--<link rel="stylesheet" href="plugins/jqcloud2/jqcloud.css">-->
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        
      </div><!-- /.container-fluid -->
    </div>

    <section class="content">
      <div class="container-fluid">

        <div class="row mb-1">
          <div class="col-sm-10">
            <h4>نظرة عامة</h4>
          </div><!-- /.col -->
          <div class="col-sm-2 text-left dashboard-mobile-filter">	
            <select class="form-control" id="main_overview">
                <option value="this_month">الشهر الحالي</option>
                <option value="last_month">الشهر الماضي</option>
                <option value="this_year">هذه السنة</option>
                <?php
                $curYear = (int)date('Y');
                for ($y = $curYear; $y >= $curYear - 4; $y--) {
                    for ($m = 12; $m >= 1; $m--) {
                        if ($y == $curYear && $m > (int)date('n')) continue;
                        $mNames = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
                        echo '<option value="month_'.$y.'_'.$m.'">'.$mNames[$m].' '.$y.'</option>';
                    }
                }
                ?>
            </select>
          </div><!-- /.col -->
        </div>
      
      <div class="row">

          <div class="col-md-3 col-sm-6 col-12 dashboard-summary-card">
            <div class="info-box bg-info">
              <span class="info-box-icon"><i class="fa fa-layer-group"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">الراتب الشهري</span>
                <span class="info-box-number salary"></span>

                <div class="progress">
                  <div class="progress-bar refund_cots_bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                 <span>&nbsp;</span><span style="float: left;"></span> 
                </span>
              </div>
            </div>
          </div>

                    <div class="col-md-3 col-sm-6 col-12 dashboard-summary-card">
            <div class="info-box bg-info">
              <span class="info-box-icon"><i class="fa fa-layer-group"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">عليك من الشهر السابق</span>
                <span class="info-box-number remain_salary"></span>

                <div class="progress">
                  <div class="progress-bar refund_cots_bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                 <span>&nbsp;</span><span style="float: left;"></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12 dashboard-summary-card">
            <div class="info-box bg-success">
              <span class="info-box-icon"><i class="fa fa-dollar-sign"></i></span>

              <div class="info-box-content" title="المكافئات">
                <span class="info-box-text bold">المكافئات</span>
                <span class="info-box-number incentive" id="invoice_totals_"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                  <span>&nbsp; </span><span style="float: left;" class="incentive_real"></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12 dashboard-summary-card">
            <div class="info-box bg-success">
              <span class="info-box-icon"><i class="fa fa-hand-holding-usd"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">التعويضات والمزياء</span>
                <span class="info-box-number benefit"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                 <span>&nbsp; </span><span style="float: left;" class="benefit_real"></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12" title="الخصومات">
            <div class="info-box bg-danger">
              <span class="info-box-icon"><i class="fa fa-donate"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">الخصومات</span>
                <span class="info-box-number decction"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                    <span>&nbsp; </span><span style="float: left;" class=""> </span> 
                </span>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 col-12" title="السلف">
            <div class="info-box bg-danger">
              <span class="info-box-icon"><i class="fa fa-donate"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">سلفه على الراتب</span>
                <span class="info-box-number advance"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                    <span>&nbsp; </span><span style="float: left;" class=""> </span> 
                </span>
              </div>
            </div>
          </div>
          <!--  -->
<div class="col-md-3 col-sm-6 col-12" title="ساعات العمل">
            <div class="info-box bg-info">
              <span class="info-box-icon"><i class="fa fa-donate"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">عدد ساعات العمل</span>
                <span class="info-box-number hour_work"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                    <span>&nbsp; </span><span style="float: left;" class=""> </span> 
                </span>
              </div>
            </div>
          </div>
          <!--  -->
          <div class="col-md-3 col-sm-6 col-12" title="صافي الراتب">
            <div class="info-box bg-warning">
              <span class="info-box-icon"><i class="fa fa-donate"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">صافي الراتب </span>
                <span class="info-box-number end_salary "></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                    <span>&nbsp; </span><span style="float: left;" class=""> </span> 
                </span>
              </div>
            </div>
          </div>
          
          
        </div>
        
        
		   
                



         <div class="row">
         <div class="col-md-6 mx-auto">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title"><?= htmlspecialchars($dashboardAdvanceTitle) ?> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>

                <div class="card-tools">
                  <div class="btn-group ml-2">
                    <select  class="form-control " id="invoices_vs_payments">
                        <option value="this_day">اليوم</option>
                        <option value="this_month">الشهر الحالي</option>
                        <option value="last_month">الشهر الماضي</option>
                        <option value="this_year">هذه السنة</option>
                        <?php
                        for ($y = $curYear; $y >= $curYear - 4; $y--) {
                            for ($m = 12; $m >= 1; $m--) {
                                if ($y == $curYear && $m > (int)date('n')) continue;
                                echo '<option value="month_'.$y.'_'.$m.'">'.$mNames[$m].' '.$y.'</option>';
                            }
                        }
                        ?>
                    </select>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body dashboard-chart-body">
                <canvas class="canvs" id="dashboard_chart_canvas" height="320" aria-label="مخطط السلف" role="img"></canvas>
                <!-- <canvas class="canvs"  id="this_year" style="display:none"></canvas> -->
              </div>
              <!-- ./card-body -->
              <div class="card-footer">
                <div class="row">
                  <div class="col-sm-3 col-6 bold">
                    <div class="description-block border-left">
                      <h5 class="description-header cots_month">0</h5>
                      <span class="description-text">عدد السلف المقبوله</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6 text-success bold">
                    <div class="description-block border-left">
                      <h5 class="description-header"><span class="totals">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">إجمالي المبلغ للسلف على الراتب</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6  text-warning bold">
                    <div class="description-block border-left">
                      <h5 class="description-header"><span class="totals_payed">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">إجمالي المبلغ للسلف خارج الراتب</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6">
                    <div class="description-block text-danger bold">
                      <h5 class="description-header"><span class="totals_unpayed">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">اجمالي قيمة السلف</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                </div>
                <!-- /.row -->
              </div>
              <!-- /.card-footer -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
          




          
      
        </div>
        
        
                
             
     </div><!-- /.container-fluid -->
    </section>

<?php
 include_once('inc/footer.php');
?>
<!--<script	src="plugins/charts/Chart.bundle.min.js"></script>
<script	src="plugins/jqcloud2/jqcloud.js"></script>-->

<script>
var currency = '<?php echo $User->currency?>';
var invfactors = [];
var chart = null;
var xValues = null;
var yValues = null;
var yValuesp = null;
var word_array = [];
var chartLoaded = false;
$(document).ready(function(){
applyDashboardArabicLabels();
$('#invoices_vs_payments option[value="last_month"]').text('\u0627\u0644\u0634\u0647\u0631 \u0627\u0644\u0645\u0627\u0636\u064a');
$('#invoices_vs_payments option[value="this_year"]').text('\u0647\u0630\u0647 \u0627\u0644\u0633\u0646\u0629');

$(document).on('change', '#main_overview', function(){
    overview();
    var selectedFilter = $('#main_overview').val();
    if ($('#invoices_vs_payments').find('option[value="' + selectedFilter + '"]').length) {
        $('#invoices_vs_payments').val(selectedFilter);
    } else {
        $('#invoices_vs_payments').val('this_month');
    }
    chartLoaded = true;
    invoicesVsPayments();
});

$(document).on('change', '#invoices_vs_payments', function(){
   
   xValues = [];
   yValues = [];
   yValuesp = [];
   chartLoaded = true;
    invoicesVsPayments();

});

if ($('#invoices_vs_payments').find('option[value="' + $('#main_overview').val() + '"]').length) {
    $('#invoices_vs_payments').val($('#main_overview').val());
}

overview();
scheduleDeferredChartLoad();

function applyDashboardArabicLabels() {
    $('.content h4').first().text('نظرة عامة');
    $('#main_overview option[value="this_month"]').text('الشهر الحالي');
    $('#main_overview option[value="last_month"]').text('الشهر الماضي');
    $('#main_overview option[value="this_year"]').text('هذه السنة');
    $('#invoices_vs_payments option[value="this_day"]').text('اليوم');
    $('#invoices_vs_payments option[value="this_month"]').text('الشهر الحالي');
    $('#invoices_vs_payments option[value="last_month"]').text('الشهر الماضي');
    $('#invoices_vs_payments option[value="this_year"]').text('هذه السنة');

    var summaryLabels = [
        'الراتب الشهري',
        'عليك من الشهر السابق',
        'المكافآت',
        'التعويضات والمزايا',
        'الخصومات',
        'سلفة على الراتب',
        'عدد ساعات العمل',
        'صافي الراتب'
    ];

    $('.info-box-text.bold').each(function(index) {
        if (summaryLabels[index]) {
            $(this).text(summaryLabels[index]);
        }
    });

    $('#dashboard_chart_canvas').attr('aria-label', 'مخطط السلف');
    $('.card-title').first().contents().filter(function() {
        return this.nodeType === 3;
    }).first().replaceWith('<?= addslashes($dashboardAdvanceTitle) ?> ');

    $('.description-text').eq(0).text('عدد السلف المقبولة');
    $('.description-text').eq(1).text('إجمالي مبلغ السلف على الراتب');
    $('.description-text').eq(2).text('إجمالي مبلغ السلف خارج الراتب');
    $('.description-text').eq(3).text('إجمالي قيمة السلف');
}

function overview(){
        var filter_by= $('#main_overview').val();
		$.ajax({
			url:"hr-app/index.php?action=dashboard-emp",
			method:"POST",
			data:{filter_by:filter_by},
			            dataType:"json",
			success:function(data)
			{
              var currentCurrency = data.currency || currency || 'ر.س';
              $('.salary').html(formatDashboardMoney(data.salary, currentCurrency));
              $('.remain_salary').html(formatDashboardMoney(data.remain_salary, currentCurrency));
              $('.incentive').html(formatDashboardMoney(data.incentive, currentCurrency));
              $('.benefit').html(formatDashboardMoney(data.benefit, currentCurrency));
              $('.decction').html(formatDashboardMoney(data.dections, currentCurrency));
              $('.hour_work').text(data.total_hour || '00:00');
              $('.end_salary').html(formatDashboardMoney(data.end_salary, currentCurrency));
              $('.advance').html(formatDashboardMoney(data.advance, currentCurrency));
            },
            error: function(xhr, status, err) {
                console.error('dashboard-emp error:', status, err);
            }
    });
}
function scheduleDeferredChartLoad() {
    var chartContainer = document.querySelector('.dashboard-chart-body');
    var triggerLoad = function() {
        if (chartLoaded) {
            return;
        }
        chartLoaded = true;
        invoicesVsPayments();
    };

    if ('IntersectionObserver' in window && chartContainer) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    triggerLoad();
                }
            });
        }, { rootMargin: '160px 0px' });
        observer.observe(chartContainer);

        window.setTimeout(function() {
            observer.disconnect();
            triggerLoad();
        }, 2200);
        return;
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(triggerLoad, { timeout: 1400 });
        return;
    }

    window.setTimeout(triggerLoad, 500);
}

function invoicesVsPayments(){
        var filter_by= $('#invoices_vs_payments').val();
       
		$.ajax({
			url:"hr-app/index.php?action=emp-chart",
			method:"POST",
			data:{filter_by:filter_by},
			            dataType:"json",
			success:function(data)
			{
               if (!data || data.result === false) {
                   console.error('emp-chart invalid response:', data);
                   return;
               }
               xValues = data.xy;
               yValues = data.yv;
               yValuesp = data.yvp;
               var barColors = data.style; 
               $('.cots_month').text(data.cots);
               $('.totals').text(data.totals);
               $('.totals_payed').text(data.advance_2);
               $('.totals_unpayed').text(data.advance_1);
           chart_drow('dashboard_chart_canvas',xValues,yValues,yValuesp, data.label_prefix || '');
        },
        error: function(xhr, status, err) {
            console.error('emp-chart error:', status, err);
        }
    });
}

function formatDashboardMoney(value, currencyCode) {
    var safeValue = value || '0.00';
    var safeCurrency = currencyCode || 'ر.س';
    return safeValue + ' <sup>' + safeCurrency + '</sup>';
}

function mostProducts(products){ 
var items = products.length;
var html = ''
$.each(products, function(key, value){
             html += '<div class="progress-group">'+products[key].text+'<div class="progress progress-sm"><div class="progress-bar " style="width: '+products[key].weight+'%; background:'+products[key].style+'"></div></div></div>'; 
			});
            
/*   jQuery(products).each(function(i){
       console.log(products[1].text);
        html += '<div class="progress-group">'+products[i].text+'<span class="float-right"><b>'+products[i].weight+'</b> / </span><div class="progress progress-sm"><div class="progress-bar bg-primary" style="width: '+products[i].weight+'%"></div></div></div>'; 
       
    })  */
       $('#puplur_products').html(html);
}


function chart_drow(cavas,xValues,yValues,yValuesp){
    try {
        if (chart && typeof chart.destroy === 'function') {
            chart.destroy();
        }
        var canvasEl = document.getElementById(cavas);
        if (!canvasEl) { console.warn('Canvas not found:', cavas); return; }
        var ctx = canvasEl.getContext('2d');
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: xValues,
                datasets: [
                    { label: 'سلف على الراتب', backgroundColor: 'green', data: yValues },
                    { label: 'سلف خارج الراتب', backgroundColor: 'orange', data: yValuesp }
                ]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    } catch(e) {
        console.error('Chart draw error:', e);
    }
}


});
</script>
