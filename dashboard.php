<?php
$screen = 'المبيعات';
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
        
        
        
        <?php
            if($User->userIsAdmin()){?>
        
        <div class="row mb-1">
 
            <div class="col-lg-8 mx-auto mt-2">
              <div class="card mb-4">
                <div class="card-header  d-flex " style="direction: rtl;">
                  <h6 class="m-0 font-weight-bold text-primary" id="chart_title">حركة المبيعات</h6>
                  
                  <div class="dropdown no-arrow mr-auto">
                    <a class="dropdown-toggle btn  btn-sm" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">خيارات</i>
                    </a>
                    <div class="dropdown-menu shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 31px, 0px);">
                    <a class="dropdown-item get_chart active first_chart" data-charttitle="حركة المبيعات" data-chartid="invfactors_weekly" >حركة المبيعات</a>
                    
                      <!-- <a class="dropdown-item get_chart " data-charttitle="فواتير المبيعات بحسب الشهر" data-chartid="cards_by_type">فواتير المبيعات بحسب الشهر</a>-->
                      
                      

                    </div>
                  </div>
                 
                </div>
                <div class="card-body pt-0" style="direction:ltr">
                  <div class="charts_aria" id="charts_aria1" style="height: 200px;">
                 <div class="loader"></div>
                     <canvas  id="canvas"></canvas>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Pie Chart -->
            
            <div class="col-lg-4 mt-2">
              <div class="card mb-4">
			 
                <div class="card-header tab-card-header_  d-flex flex-row align-items-center justify-content-between  rtl" style="border-bottom: 1px solid rgba(0,0,0,.125);">
				 <h6 class="m-0 font-weight-bold text-primary " style="white-space: pre;">هذا الشهر</h6>
                  <ul class="nav nav-tabs card-header-tabs nav-fill" id="myTab" role="tablist">
                   <li class="nav-item">
						<a class="nav-link active" id="two-tab" data-toggle="tab" href="#two" role="tab" aria-controls="Two" aria-selected="true">الإيراد والصرف<sup><span class="badge badge-warning " id="off_wifi"></span></sup></a>
					</li>
					<!--<li class="nav-item">
						<a class="nav-link  " id="one-tab" data-toggle="tab" href="#one" role="tab" aria-controls="One" aria-selected="false">الأرباح<sup><span class="badge badge-success today_wifi_cot"></span></sup>
						</a>
						
					</li>-->
					

				  </ul>
				</div>
				
				<div class="tab-content" id="myTabContent" style="direction:ltr">
                <div class="tab-pane fade show active p-3" id="two" role="tabpanel" aria-labelledby="two-tab" style="">
					<div class="charts_aria" id="charts_aria2" style="display:none-;height: 190px;" >
                    <div class="loader"></div>
							 <canvas  id="profit_chart" ></canvas>
						</div>             
				  </div>
                  
				  <div class="tab-pane fade  " id="one" role="tabpanel" aria-labelledby="one-tab" >
					<div class="charts_aria" id="charts_aria3" style="display:none-;height: 190px;" >
                    <div class="loader"></div>
							 <canvas  id="profit_chart2" ></canvas>
						</div>
					<div class="card-footer text-center">
                  <button type="button" class="btn m-0 btn-sm text-primary wifi_as_table" ><i
                      class="fas fa-table"></i> عرض كجدول</button>
                </div>	
									 
				  </div>
				  
				  

				</div>
                
                <!--<div class="card-footer text-center">
                  <button type="button" class="btn m-0 btn-sm text-primary wifi_as_table" ><i
                      class="fas fa-table"></i> عرض كجدول</button>
                </div>-->
              </div>
            </div>
           
			
        </div>
        
         <?php
            }?>
        <div class="row mb-1">
          <div class="col-sm-10">
            <h4>نظرة عامة</h4>
          </div><!-- /.col -->
          <div class="col-sm-2 text-left">	
            <select  class="form-control " id="main_overview">
                <option value="this_day">هذا اليوم</option>
                <option value="this_month">الشهر الحالي</option>
                <option value="this_quarter">الربع الحالي</option>
                <option value="this_year">العام الحالي</option>
            </select>
          </div><!-- /.col -->
        </div>
      
      <div class="row">

          <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-info">
              <span class="info-box-icon"><i class="fa fa-layer-group"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">فواتير</span>
                <span class="info-box-number invs_cots"></span>

                <div class="progress">
                  <div class="progress-bar refund_cots_bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                  <span>مرتجعة </span><span style="float: left;" class="refund_cots_avg">0 <sup>%</sup></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-success">
              <span class="info-box-icon"><i class="fa fa-dollar-sign"></i></span>

              <div class="info-box-content" title="إجمالي المبيعات بعد خصم المرتجعات">
                <span class="info-box-text bold">إجمالي المبيعات</span>
                <span class="info-box-number invs_amount" id="invoice_totals_"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                  <span>محصل </span><span style="float: left;">0 <sup>%</sup></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-danger">
              <span class="info-box-icon"><i class="fa fa-hand-holding-usd"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">المرتجعات</span>
                <span class="info-box-number refound_amount"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                 <span>مدفوع </span><span style="float: left;">0 <sup>%</sup></span> 
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6 col-12" title="إجمالي الضريبة المحصلة (بعد خصم المرتجعات)">
            <div class="info-box bg-warning">
              <span class="info-box-icon"><i class="fa fa-donate"></i></span>

              <div class="info-box-content">
                <span class="info-box-text bold">إجمالي الضريبة</span>
                <span class="info-box-number invs_vat"></span>

                <div class="progress">
                  <div class="progress-bar" style="width: 0%"></div>
                </div>
                <span class="progress-description">
                    <span>&nbsp;</span><span style="float: left;"></span> 
                </span>
              </div>
            </div>
          </div>
          
          
        </div>
        
        
		   
                
         <div class="row">
          <div class="col-md-7">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title">إجمالي المبيعات مقابل التحصيلات<?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>

                <div class="card-tools">
                  <div class="btn-group ml-2">
                    <select  class="form-control " id="invoices_vs_payments">
                        <option value="this_month">الشهر الحالي</option>
                        <option value="this_quarter">الربع الحالي</option>
                        <option value="this_year">العام الحالي</option>
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
                <canvas class="canvs" id="this_quarter" style="display:none"></canvas>
                <canvas class="canvs"  id="this_year" style="display:none"></canvas>
              </div>
              <!-- ./card-body -->
              <div class="card-footer">
                <div class="row">
                  <div class="col-sm-3 col-6 bold">
                    <div class="description-block border-left">
                      <h5 class="description-header cots_month">0</h5>
                      <span class="description-text">عدد الفواتير</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6 text-success bold">
                    <div class="description-block border-left">
                      <h5 class="description-header"><span class="totals">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">إجمالي المبيعات</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6  text-warning bold">
                    <div class="description-block border-left">
                      <h5 class="description-header"><span class="totals_payed">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">إجمالي التحصيلات</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6">
                    <div class="description-block text-danger bold">
                      <h5 class="description-header"><span class="totals_unpayed">0</span> <?=!empty($currency)? ' <sup>'.$currency.'</sup>' :''?></h5>
                      <span class="description-text">مبالغ غير محصلة</span>
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
          
          <div class="col-md-5">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title">الأكثر مبيعاً (نوفمبر)</h5>

                <div class="card-tools">
                  <div class="btn-group ml-2">
                    <select  class="form-control " id="invoices_vs_payments">
                        <option value="this_month">الشهر الحالي</option>
                        <option value="this_quarter">الربع الحالي</option>
                        <option value="this_year">العام الحالي</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                 <canvas class="" id="pup" style="display:none_"></canvas>
              </div>
              
            </div>
          </div>
          
      
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
    
  
function lineChart(cavas,chart_title,xValues,yValues,zyValues){
    
  Chart.register(ChartDataLabels);
 chart = new Chart(cavas, {
    type: "line",
    data: {
        labels: xValues,
        datasets: [
        {
            //label:'عدد الكروت ', 
           // backgroundColor: xcolor,
             borderColor: '#444444',
            // color:'#fff',
            fill: false,
           
            data: zyValues,
            borderWidth: 2,
           // barThickness: 40,
            // minBarLength: 10,               
        },
          
        ]
    },
         
        options: {
            maintainAspectRatio: false,
             plugins: {
                
                datalabels: { // This code is used to display data values
            
                display: false,
                
                
            },
                legend: {
                    
                display: false,
                labels: {
                    font: {
                        size: 16,
                       
                    }
                }
                },  
               /*  title: {
                    display: true,
                    text: chart_title,
                   font: {
                    size: 14,
                    family: 'Tahoma'
                  }
                } */
            },
            responsive: true,
            /* scales: {
                xValues: [{
                    display: true,
                    ticks: {
                        beginAtZero: true,
                    }
                }]
            }, */
           
        }
        
    });
     
}

function doughnutChart(cavas,chart_title,xValues,yValues,xcolor,currency = ''){
   Chart.register(ChartDataLabels);
 chart = new Chart(cavas, {
    type: "doughnut",
    data: {
        labels: xValues,
        datasets: [
        {
            //label:'', 
            // borderWidth:2,
           backgroundColor: xcolor,
           //backgroundColor: 'rgb('+Math.floor(Math.random() * 255)+')',
            // borderColor: barColors,
            // color:'#fff',
            fill: false,
            data: yValues,
            //borderWidth: 1,
           // barThickness: 40                
        },
          
        ]
    },
         
    options: {
         maintainAspectRatio: false,
        // cutout:80,
       
    responsive: false,
    plugins: {
        tooltip: { 
      //  rtl:true,
    callbacks: {
     label: function (context) {
        return context.label + ': ' + context.formattedValue + currency;
     }
   }
 },
      datalabels: { // This code is used to display data values
       
            anchor: 'center',
            align: 'center',
            color: 'white',
            font: {
                 weight: 'bold',
                size: 14
            }
        },
       legend: {
         maxWidth:200,
        position: 'top',
        labels: {
            font: {
                size: 14,
               
            }
        }
      }, 
       /* title: {
        display: true,
        text: chart_title,
        font: {
            size: 14,
            family: 'Tahoma'
        }
      } */ 
    }
  },
        
    });
   
   
}


function barChart(cavas,chart_title,xValues,yValues,xcolor){
   //Chart.register(ChartDataLabels);
 chart = new Chart(cavas, {
    type: "bar",
    data: {
        labels: xValues,
        datasets: [
        {
            //label:'عدد الكروت ', 
            backgroundColor: xcolor,
            // borderColor: barColors,
            // color:'#fff',
            fill: false,
            data: yValues,
            //borderWidth: 1,
           // barThickness: 40,
             minBarLength: 10,               
        },
          
        ]
    },
         
        options: {
            maintainAspectRatio: false,
             plugins: {
                
                datalabels: { // This code is used to display data values
                anchor: 'center',
                align: 'middle',
                color: 'white',
                formatter: Math.round,
                font: {
                    /* weight: 'bold', */
                    size: 11
                }
            },
                legend: {
                    
                display: false,
                labels: {
                    font: {
                        size: 16,
                       
                    }
                }
                },  
               /*  title: {
                    display: true,
                    text: chart_title,
                   font: {
                    size: 14,
                    family: 'Tahoma'
                  }
                } */
            },
            responsive: true,
            scales: {
                yAxes: [{
                    display: true,
                    ticks: {
                        beginAtZero: true,
                    }
                }]
            },
        }
        
    });
     
}

  function invsfactors(){
		 var type = 'weekly';
		$.ajax({
		type: "post",
		url: "sal-app/invs-factors",
		data: {type:type},
        dataType: "json",
		beforeSend:function()
			{
				//$('.loading').html('<div class="loader"></div>');
				//$('.abstract_note').hide();
			},
		success: function(data){
            var cots = [];
            var dates = [];
            var prices = []; 
            
            for(var i=0; i < data.data.length; i++){
                //invfactors.push( data.data[i] );
                  cots.push( data.data[i]['total_cots'] );
                  dates.push( data.data[i]['the_date'] );
                  prices.push( data.data[i]['total_prices'] );
            }
            invfactors['cots'] = cots;
            invfactors['dates'] = dates;
            invfactors['prices'] = prices;

                //console.log(invfactors_cots);    
                //console.log(invfactors_dates);    
                //console.log(invfactors_prices);    
           // invfactors = data.data;
            console.log(invfactors);  
			getChart('invfactors_weekly');
            //$('#charts_aria2').html('<div class="loader"></div><canvas id="wifi_chart"></canvas>');
             // doughnutChart('wifi_chart','الاجهزة الأنشط',wifi['device'],wifi['price'],wifi['color'],currency );
             //  $('#charts_aria2 .loader') .hide();
		}				
		}); 
	}
    
    function netProfit(){
		$.ajax({
		type: "post",
		url: "sheard/net-profit",
		//data: {type:type},
        dataType: "json",
		beforeSend:function(){},
		success: function(data){
            if(data.data){
               // console.log(data.data['in']['total_in']);
                $('#charts_aria2 .loader') .hide();
 doughnutChart('profit_chart2','rrr',data.data['label'],data.data['values'],data.data['colors'],'SAR');
 barChart('profit_chart','rrr',['الإيرادات','المصروفات'],[data.docs['in']['total'], data.docs['out']['total']],data.data['colors']);
            }
		}				
		}); 
	}
    
 function getChart(chart_id){
 $('#'+chart_id).html('<div class="loader"></div><canvas id="canvas"></canvas>');

 
    var chart_title = $('[data-chartid='+chart_id+']').text();
    if(chart_id == 'invfactors_weekly'){
      lineChart('canvas',chart_title,invfactors['dates'],invfactors['cots'],invfactors['prices']);
    }else if(chart_id == 'this_time_chart'){
        //barChart('canvas',chart_title,xValues,yValues,xcolor);
        
         chart_title = chart_title+' '+$('#the_time').val();
        stakedBarChart('canvas',chart_title,this_time_date,this_time_cots,this_time_price);
       // barChart('canvas',chart_title,this_time_date,this_time_cots,xcolor);
    }else if(chart_id == 'this_week_chart'){
        
        lineChart('canvas',chart_title,last_days_dates,last_days_cards,last_days_price);
        //stakedBarChart('canvas',chart_title,last_days_dates,last_days_cards,last_days_price);
    }else{
        lineChart('canvas',chart_title,last_days_dates,last_days_cards,last_days_price);
        doughnutChart('canvas',chart_title,xValues,yValues,xcolor);
    } 

    $('#chart_title').html(chart_title);
    $('#charts_aria1 .loader') .hide();
   

}


invsfactors();
netProfit();

$(document).on('change', '#main_overview', function(){
    overview();
});

$(document).on('change', '#invoices_vs_payments', function(){
   
   xValues = [];
   yValues = [];
   yValuesp = [];
   // chart.update();
    invoicesVsPayments();
});

overview();
function overview(){
        var filter_by= $('#main_overview').val();
		$.ajax({
			url:"./sal-app/invoices-overview",
			method:"POST",
			data:{filter_by:filter_by},
			dataType:"JSON",
			success:function(data)
			{
                
               // $('.invs_amount').text(data.invs_amount);
               // $('.invs_vat').text(data.invs_vat);
                $('.invs_amount').html(data.invs_amount_net +' <sup>'+currency+'</sup>');
                $('.invs_vat').html(data.invs_vat_net +' <sup>'+currency+'</sup>');
                $('.invs_cots').text(data.invs_cots);
                $('.refund_cots_bar').width(data.refund_cots_avg);
                $('.refund_cots_avg').html(data.refund_cots_avg+' <sup>%</sup>');
                
               // $('.refound_amount').text(data.refund_amount);
                 $('.refound_amount').html(data.refund_amount_net +' <sup>'+currency+'</sup>');
               // $('.').text(data.refund_vat);
              //  $('.').text(data.refund_cots);
                
                
				console.log(data)
               
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

invoicesVsPayments();
function invoicesVsPayments(){
        var filter_by= $('#invoices_vs_payments').val();
       
		$.ajax({
			url:"./sal-app/sal-chart",
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
                $('.totals_payed').text(data.totals_payed);
                $('.totals_unpayed').text(data.totals_unpayed);
                
          
           $('.canvs').hide();
           $('#'+filter_by).show();
           chart_drow(filter_by,xValues,yValues,yValuesp);
           
           chart_drow2('pup',data.pup.xy,data.pup.yv,data.pup.style);
           // chart.update();
           /* 
           word_array = highest(data.pup);
           word_array = highest(data.pup); */
           console.log(data.pup);
            mostProducts(data.pup); // هاني
            
            
         /*  word_array2 = [
              {
                text: 'ddd',
                weight: 7.5
                //link: 'http://themicon.co'
              }, {
                text: 'cc',
                weight: 7.8
              }
            ]; */ 
            /* console.log(word_array2);
            console.log( [...data.pup].sort()); */
          // mostProducts();
           // console.log(word_array);
          
           
            
        }
    });
}


function chart_drow(cavas,xValues,yValues,yValuesp){
     chart = new Chart(cavas, {
              type: "bar",
              data: {
                
            labels: xValues,
                datasets: [
                
                {
                  label:'المبيعات',
                  backgroundColor: 'green',
                 // borderColor: barColors,
                 // color:'#fff',
                  fill: false,
                  data: yValues,
    
                },
                {
                  label:'التحصيلات',
                  backgroundColor: 'orange',
                 // borderColor: barColors,
                 // color:'#fff',
                  fill: false,
                  data: yValuesp,
    
                },
                ]
              },
              options: {
               // legend: {display: false},
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

function chart_drow2(cavas,xValues,yValues,styles){
     chart = new Chart(cavas, {
             // type: "horizontalBar",
              type: "bar",
              data: {
                
            labels: xValues,
                datasets: [
                
                {
                  //label:'المبيعات',
                  backgroundColor: styles,
                 // borderColor: barColors,
                 // color:'#fff',
                  fill: false,
                  data: yValues,
    
                }
                ]
              },
              
              options: {
               legend: {display: false},
                responsive:true,
                /* indexAxis: 'y',
                legend: {
                  position: "bottom",
                  rtl: true,
                } */
                
                   
                }
            });
}

//mostProducts();
/* function mostProducts(){
   // var word_array = arry;
     


    $("#puplur_products").jQCloud(word_array, {
     // width: 240,
      height: 200,
      steps: 7
    });
     
} */

});
</script>