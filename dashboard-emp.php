<?php
$screen = 'تقرير الموظف';
$page_title = 'مؤشرات';
 include_once('inc/header.php');
 $currency = $User->currency;
?>
<style>
.progress{
    /* height: 3px !important; */
}
.progress-description{
    font-size: 14px;
}
.fa-donate{
    color: white;
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
          <div class="col-sm-2 text-left">	
            <select  class="form-control " id="main_overview">
                <!-- <option value="this_day">هذا اليوم</option> -->
                <option value="this_month">الشهر الحالي</option>
            </select>
          </div><!-- /.col -->
        </div>
      
      <div class="row">

          <div class="col-md-3 col-sm-6 col-12">
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

                    <div class="col-md-3 col-sm-6 col-12">
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
          
          <div class="col-md-3 col-sm-6 col-12">
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
          
          <div class="col-md-3 col-sm-6 col-12">
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
                <h5 class="card-title">إجمالي سلف  الموظف <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>

                <div class="card-tools">
                  <div class="btn-group ml-2">
                    <select  class="form-control " id="invoices_vs_payments">
                        <option value="this_day">اليوم</option>
                        <option value="this_month">الشهر الحالي</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <canvas class="canvs" id="this_month" style="display:none"></canvas>
                <canvas class="canvs" id="this_day" style="display:none"></canvas>
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
                      <span class="description-text">إجمالي الملبغ للسبف على الراتب</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6  text-warning bold">
                    <div class="description-block border-left">
                      <h5 class="description-header"><span class="totals_payed">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">إجمالي المبلغ للسف خارج الراتب</span>
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

 <script src="plugins/chart_3_js/chart_3.0.js"></script>
   <script src="plugins/chart_3_js/chartjs_plugin.js"></script>
<script>
var currency = '<?php echo $User->currency?>';
var invfactors = [];
var chart = null;
var xValues = null;
var yValues = null;
var yValuesp = null;
var word_array = [];
$(document).ready(function(){
    


$(document).on('change', '#main_overview', function(){
    overview();
});

$(document).on('change', '#invoices_vs_payments', function(){
   
   xValues = [];
   yValues = [];
   yValuesp = [];
    invoicesVsPayments();

});

overview();
function overview(){
        var filter_by= $('#main_overview').val();
		$.ajax({
			url:"./hr-app/dashboard-emp",
			method:"POST",
			data:{filter_by:filter_by},
			dataType:"JSON",
			success:function(data)
			{
              $('.salary').text(data.salary); 
              $('.remain_salary').text(data.remain_salary);        


                $('.incentive').html(data.incentive +' <sup>'+currency+'</sup>');
                

                $('.benefit').html(data.benefit +' <sup>'+currency+'</sup>');
                

                $('.decction').html(data.dections +' <sup>'+currency+'</sup>');
                $('.hour_work').html(data.total_hour +' <sup>'+currency+'</sup>');
                $('.end_salary').html(data.end_salary +' <sup>'+currency+'</sup>');
                $('.advance').html(data.advance +' <sup>'+currency+'</sup>');
                
                
                
				console.log(data)
               
            }
    });
}



invoicesVsPayments();
function invoicesVsPayments(){
        var filter_by= $('#invoices_vs_payments').val();
       
		$.ajax({
			url:"./hr-app/emp-chart",
			method:"POST",
			data:{filter_by:filter_by},
			dataType:"JSON",
			success:function(data)
			{
                
       
				 
               xValues = data.xy;
               yValues = data.yv;
               yValuesp = data.yvp;
               var barColors = data.style; 
               $('.cots_month').text(data.cots);
                $('.totals').text(data.totals);
                $('.totals_payed').text(data.advance_2);
                $('.totals_unpayed').text(data.advance_1);
                
          
           $('.canvs').hide();
           $('#'+filter_by).show();
           chart_drow(filter_by,xValues,yValues,yValuesp);

           console.log(data.pup);
            // mostProducts(data.pup); // هاني
   
            
        }
    });
}

function mostProducts(products){ 
var items = products.length;
 console.log(products);
 console.log(items);
var html = ''
$.each(products, function(key, value){
			//dataItems += key + ": " + value + "\n";
             console.log(products[key].text);
             html += '<div class="progress-group">'+products[key].text+'<div class="progress progress-sm"><div class="progress-bar " style="width: '+products[key].weight+'%; background:'+products[key].style+'"></div></div></div>'; 
			});
            
/*   jQuery(products).each(function(i){
       console.log(products[1].text);
        html += '<div class="progress-group">'+products[i].text+'<span class="float-right"><b>'+products[i].weight+'</b> / </span><div class="progress progress-sm"><div class="progress-bar bg-primary" style="width: '+products[i].weight+'%"></div></div></div>'; 
       
    })  */
       $('#puplur_products').html(html);
}


function chart_drow(cavas,xValues,yValues,yValuesp){
  
     chart = new Chart(cavas, {
              type: "bar",
              data: {
                
            labels: xValues,
                datasets: [
                
                {
                  label:'سلف على الراتب',
                  backgroundColor: 'green',
                  fill: false,
                  data: yValues,
    
                },
                {
                  label:'سلف خارج الراتب',
                  backgroundColor: 'orange',
                  fill: false,
                  data: yValuesp,
    
                },
                ]
              },
              options: {
                responsive:true,
                 scales: {
                  yAxes: {
                    ticks: {
                      beginAtZero: true
                    }
                  }
                } 
                }
            });
}


});
</script>