  <?php
$appid  = 'HR';
$page_perm=['إصدار الرواتب'];

// $screen = 'التعويضات ';
// $page_title = 'إدارة التعويضات ';

 $screen = 'إدارة الموارد البشرية';
$page_title = 'إصدار الرواتب';

 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>


<style>	
.table.dataTable{
	margin-top: 0px !important;
}
.table.dataTable  td, .table.dataTable  th {
	vertical-align: middle;
}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">الرواتب المصروفة</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
						

        <button type="button" class="btn btn-primary"  id="add-client-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة صرف راتب</span></button>		
      
    </div> 
        </div>
      </div>
    </div>
   
	
	

    <section class="content">
	

	<div class="container-fluid">
	<?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
		<div class="alert alert-success alert-dismissible" id="result-alert">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <i class="icon fas fa-check"></i>
                 <?=$_SESSION['alert']?>
                 <?php $_SESSION['alert'] ='';?>
                </div>
	<?php endif;?>

    <div class="invoice mb-5" id="filter-area" >
	
    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">

        
            <div class="card-body card-body pt-0 pb-0">
            <div class="row">
            
            
            <div class="col-md-6">
              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
            </div>

            
            
            <div class="col-md-6">
          <div class="form-group">
              <label for="branchs_list" class="col-form-label">الفرع</label>
                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list"  multiple="multiple" >
                        <?php
                        
                            foreach($allowed_branches as $id => $name){ 
                                echo'<option value="'.$id.'" >'.$name.'</option>';
                            }

                        ?>
                        </select>
            </div>
            </div>


            

            
            </div>
            <div class="text-left">
              <button type="button" class="btn btn-default reset-filter" data-dismiss="">إلغاء الفلتر</button>
              <button type="submit" class="btn btn-success" name="" ><i class="fas fa-eye"></i> بحث</button>
            </div>
            </div>
            
            
        
        
      
 
          <!-- /.row -->
          </form>
          <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
    </div> 

	
	

			
			
		
				
				<div class="row">
                
                <div class="col-md-12">
					 <div class="card">
					<div class="table-responsive">
						<table id="data_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm" width="100%">
							 <thead>
                                 <tr class="bg-gry">
                                  <th>رقم القيد</th>
                                  <th >الشهر</th>
                                  <th >السنة</th>
                                  <th >انشاء بواسطة</th>
                                  <th>الاجراءات</th>

                                </tr>
                            </thead>
							<tbody></tbody>
						</table>
					</div>
					</div>
					</div>
              </div>
              <!-- /.row -->
			

         
	</div>

			
    </section>





<?php
 include_once('inc/footer.php');
?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>

$(document).ready(function(){
//هذه من اجل الغاء الفلتره
$(document).on('click', '.reset-filter', function(){
    $('#filter-fm').each(function() {
    $("input").val('');
    $(".selectpicker").val('');
    $("#branchs_list").val('');
    $(".selectpicker").selectpicker("refresh");
});
});
// هذه من اجل جلب القيم تبع الفلتره وتحويلها لمصفوفه
function get_filter(input_name)
{
    var filter = [];
    $('select[name="'+input_name+'"] option:selected').each(function() {
    filter.push($(this).val());
    });
return filter;
}
// هذه تقوم بتطبيق قيمة الفلتر 
function apply_filters()
{
    var date_range = $('#date_range').val();
    var branchs = get_filter('branchs_list');
    $('#data_tb').DataTable().destroy();
    clients_data('yes',date_range,branchs); // افحص مخرجات الداله
}
// هذه عندما نطبق الفلتر
$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});

$('#add-client-bt').on('click', function(event){
   window.open("Issuing-salaries","_self");
});


// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
 function clients_data(is_date_search, date_range='',branchs=[], states=[])
 {
	//var account = 0;
  var dataTable = $('#data_tb').DataTable({
    "processing" : true,
    "serverSide" : true, 
    "paging": true,
    "lengthChange": true,
    "searching": false,
    "order" : [],
    "ordering": true,
    "info": false,
    "autoWidth": false,
    "responsive": true,
    "pagingType": "numbers",
	language: {
            url: '/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
    url:"hr-app/index.php?action=Issuing-salaries-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            branchs: branchs,
            states: states
	}
	},
   
    aoColumns: [
		  
			{
                data: ['data'],
                render: function(data, type) {
					return data.id_r;
                }
            },  
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.month;
                     
                }
            },
                        {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.year;
                     
                }
            },

            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.user;
                     
                }
            },
			
			  { 
			   data: ['data'],
			  "bSortable": false,
			  render: function(data, type) {

          var options='<div class="btn-group"> <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu">';
                
                    options += '<a href="Issuing-salaries-view?id=' + data.id + '" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a>';
                    options+='</div></div>'
                   return options;    
                }			
			  }		
		],
   
    "dom": "<'row'<'col-sm-12 col-md-7'><'col-sm-12 col-md-1'><'col-sm-12 col-md-4 text-left'>>" +
    
    "<'row'<'col-sm-12 mb-4'tr>>" +
    "<'row'<'col-sm-12 col-md-5'li><'col-sm-12 col-md-7'p>>",  
    
	
	
  });

};
  

 
  
});
 

</script>
