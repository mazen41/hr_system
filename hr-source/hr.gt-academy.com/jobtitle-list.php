<?php
$appid  = 'HR';
$page_perm=['اضافة مسمى وظيفي','عرض مسمى وظيفي','تعديل مسمى وظيفي','حذف مسمى وظيفي'];
// $screen = 'المنصب الوظيفي ';
// $page_title = 'إدارة المنصب الوظيفي';
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
$tree=null;
 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);

// 
 $querySection = "SELECT *
FROM  tbljobtitle 
ORDER BY ParentID, Id";
$stSection = $connect_pdo->prepare($querySection);
$stSection->execute();
if($stSection->rowCount() > 0){
    $allowed_section = $stSection->fetchAll();
} 
if(!empty($allowed_section))
$tree = buildTree($allowed_section);
//  
function buildTree($sections, $parentID = null) {
    $tree = [];
    
    foreach ($sections as $section) {
        if ($section['ParentID'] == $parentID) {
            $children = buildTree($sections, $section['Id']);
            if ($children) {
                $section['children'] = $children;
            }
            $tree[] = $section;
        }
    }
    
    return $tree;
}

// 
?>


<style>	
.table.dataTable{
	margin-top: 0px !important;
}
.table.dataTable  td, .table.dataTable  th {
	vertical-align: middle;
}

.btn-group-vertical {
    display: flex;
    flex-direction: column;
    gap: 5px; 
}


.btn-group-vertical .btn {
    background-color: #f0f0f0; 
    border: 1px solid #d1d1d1;
    color: #333; 
    font-size: 0.85rem; 
}


.btn-group-vertical .btn:hover {
    background-color: #e0e0e0;
}

/
@media (max-width: 768px) {
    .btn-group-vertical {
        right: 5%; 
    }
}

.file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.file-icon {
    margin-right: 10px;
}

.dropdown-menu {
    min-width: 200px;
}

.nested {
            display: none;
            padding-left: 20px;
            transition: all 0.3s ease;
        }

        .list-group-item {
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 2px;
            background-color: white;
        }

        /* تغيير اللون عند التمرير */
        .list-group-item:hover {
            background-color:rgb(240, 239, 239);
        }

        /* تخصيص الأيقونات */
        .file-icon {
            margin-right: 10px;
            color: #6c757d;  /* لون رمادي للأيقونة */
        }

        .btn-toggle {
            margin-left: 10px;
            background-color: transparent;
            border: none;
            color: #007bff;
            cursor: pointer;
        }

        .icon-toggle {
            font-size: 18px;
        }

        /* تخصيص الأيقونات والملفات */
        /* .file-item {
            display: flex;
            align-items: center;
        } */

        .file-item i {
            font-size: 18px;
            color: #6c757d;
        }
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">المناصب</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
          <?php if($User->isAllowedPerm(['اضافة مسمى وظيفي'],$appid)){ ?>
            <button type="button" class="btn btn-primary"  id="add-client-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة</span></button>
            <button type="button" class="btn btn-primary"  id="add-client-bt-setting"><i class="fas fa-cog"></i><span class="d-none d-sm-inline"> قائمة الاعدادات</span></button>					
            <?php
         } 
         ?> 
					
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
            
            
            <div class="col-md-3">
              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
            </div>

            
            
            <div class="col-md-3">
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

	
	

			
			
		
				
				<div class="row" style="background-color:white">
                <div class="col-md-4">
                        <h5 class="text-center mb-4">شجرة الأقسام</h5>
                        <?php
                        if(!empty($tree)){
        function displayTree($tree) {
            foreach ($tree as $node) {
                echo '<li class="list-group-item">';
echo '<div class="file-item" data-toggle="section" data-target="section-' . $node['Id'] . '">';

// إضافة أيقونة المجلد وتسمية القسم
echo '<div><i class="fas fa-file-alt file-icon"></i>';
echo "<span>" . $node['Name'] . "</span></div>";

// زر النقاط الثلاث للقائمة المنبثقة (تحويله إلى تصميم عمودي)
echo '<div class="btn-group-vertical" style="width: auto;">';
echo '<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-ellipsis-h"></i></button>';
echo '<div class="dropdown-menu dropdown-menu-left" role="menu">';
echo '<a href="jobtitle-add?id=' . $node['Id'] . '" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a>';
echo '<button type="button" class="dropdown-item remove_client" value="' . $node['Id'] . '"><i class="fa fa-trash-alt"></i> حذف</button>';
echo '</div>';
echo '</div>';

echo '</div>';

// إذا كان للقسم أبناء، يتم إنشاء قائمة فرعية (nested)
if (isset($node['children']) && !empty($node['children'])) {
    echo '<ul class="list-group ms-3 nested" id="section-' . $node['Id'] . '">';
    displayTree($node['children']); // استدعاء نفس الدالة لعرض الأبناء
    echo '</ul>';
}

echo '</li>';
            }
        }

        displayTree($tree);
    }
        ?>
                    </div>
                <div class="col-md-8">
					 <div class="card">
					<div class="table-responsive">
						<table id="data_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm" width="100%">
							 <thead>
                                 <tr class="bg-gry">
                                  <th>اسم المسمى</th>
                                  <th >الفرع</th>
                                  <th >تاريخ الاضافة</th>
                                  <th >من انشاءه</th>
                                  <th>الاجراءات</th>

                                </tr>
                            </thead>
							<tbody></tbody>
						</table>
					</div>
					</div>
					</div>
                    <!--  -->

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

    // tree
    $('[data-toggle="section"]').click(function() {
            var target = $(this).data('target');  // الحصول على الـ ID للقسم
            var section = $('#' + target);         // تحديد القسم بناءً على الـ ID

            // إذا كانت القائمة الفرعية مخفية، نعرضها ونغير الأيقونة
            if (section.is(":hidden")) {
                section.slideDown(100); // التوسع
            } else {
                section.slideUp(100);   // الطي
            }
        });
    // 
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
   window.open("jobtitle-add","_self");
});

$('#add-client-bt-setting').on('click', function(event){
   window.open("hr-setting","_self");
});

// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
 function clients_data(is_date_search, date_range='',branchs=[])
 {
	//var account = 0;
  var dataTable = $('#data_tb').DataTable({
	"processing" : true,
	"serverSide" : true,
	"paging": true,
	"lengthChange": false,
	"searching": false,
	"order" : [],
	"ordering": false,
	"info": false,
	"autoWidth": false,
	"responsive": true,
	"pagingType": "numbers",
	language: {
            url: '/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
    url:"hr-app/index.php?action=jobtitle-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            branchs: branchs
	}
	},
   
    aoColumns: [
		  
			{
                data: ['data'],
                render: function(data, type) {
					return data.name_;
                }
            },  
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.branch;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.createddate;
                     
                }
            },
                        {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.name;
                     
                }
            },
			
			  { 
			   data: ['data'],
			  "bSortable": false,
			  render: function(data, type) {
				 
                var options='<div class="btn-group"> <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu">';
                <?php if ($User->isAllowedPerm(['عرض مسمى وظيفي'], $appid)) { ?>
                    options += '<a href="jobtitle-view?id=' + data.id + '" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['تعديل مسمى وظيفي'], $appid)) { ?>
                    options += '<a href="jobtitle-add?id=' + data.id + '" type="button" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['حذف مسمى وظيفي'], $appid)) { ?>
                    options += '<button type="button" class="dropdown-item remove_client" value="' + data.id + '"><i class="fa fa-trash-alt"></i> حذف</button>';
                <?php } ?>

                options+='</div></div>'
                   return options;      
                }
				
			
			  }
			
			
		],
   
   
    
	
	
  });

};
  
function confirm_remove (id) {
		if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/jobtitle-remove?id='+id+'',function(){
				 // $('#modal_default .modal-dialog').addClass('modal-md');
			});
		}
	}
    
     

$(document).on('click', '.remove_client', function(){
	var id = $(this).val();
	confirm_remove(id);

});
 
  
});
 

</script>