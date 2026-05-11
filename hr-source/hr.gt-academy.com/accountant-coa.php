<?php
//$fix_foot = false;
//$sec_title = 'Dashboard';
//$page_title = 'Corporate Services';
$screen = 'الحسابات العامة';
$page_title = 'دليل الحسابات';
//$search = true;
//$fix_foot = true;
 include_once('inc/header.php');
 $main_account = 0;
 
/*  if(isset($_GET['branch'])){
		$branch_num = (int)$_GET['branch'];
	}else{
		$branch_num = $_SESSION['branch'];
} 
if(empty($branch_num)){
	echo'<script> location.replace("/"); </script>';
	die();
}
*/
 
use Phppot\Coa;
	require_once  'invoices/Model/accountant.php';
	
	$coaModel = new Coa();
	
	//$branchies = $coaModel->branchesList();
	$mainAccounts = $coaModel->selectAccountsByBranchAndParent('0');

	if (! empty($mainAccounts)) {
		
		if(isset($_GET['cat']) && $_GET['cat'] > 0){
			$singleAccount = $coaModel->getSingleAccount($_GET['cat']);
			//$page_title = $singleAccount[0]["AccountName"]. ' #' .$singleAccount[0]["AccountNumber"] ;
			$main_account = $_GET['cat']; 
		}
		
		
		
		

	}else{
		//echo'<script> location.replace("/"); </script>';
		//die();
	}
	
	
	
/* 	
function drowTreeAccounts($topAccounts) 
{
	$tree = "";
	foreach($topAccounts as $row){
		//$mainAccounts[$k]["accHasChild"]
	$tree .='<li id="'.$row['AccountID'].'" data-value="'.$row['AccountName'].'">
			  <a href="#"  class="coa" data-type="'.($row['AccountType'] == 1 ? 'cat': 'account').'" data-id="'.$row['AccountID'].'">
			  
				'.$row['AccountName'].'
			  </a>
			  
			  
			   '.($row['AccountType'] == 1 ? '<ul>'.drowTreeAccounts($coaModel->getBranchAccountsByNum($row['BranchID'],$row['AccountID'])).'</ul>' : '').'
			   
			</li>';
	 
		
	}
	
 return $tree;
} */

function drowTreeAccounts($connect,$parent_num) 
{
	$tree = "";
	$query = " SELECT * FROM  tblaccountguide WHERE  ParentNumber = $parent_num  AND AccountType = 1";
					
	$stm = $connect->prepare($query);
	$stm->execute();
	if($stm->rowCount() > 0){
		$rows = $stm->fetchAll();
		foreach($rows as $row){
		$tree .='<li id="'.$row['AccountID'].'" data-value="'.$row['AccountName'].'">
				  <a href="#"  class="'.($row['AccountType'] == 1 ? 'coa': '').'" data-type="'.($row['AccountType'] == 1 ? 'cat': 'account').'" data-id="'.$row['AccountID'].'">
				  
					'.$row['AccountName'].'
				  </a>
				  
				   '.($row['AccountType'] == 1 ? '<ul>'.drowTreeAccounts($connect,$row['AccountID']).'</ul>' : '').'
				</li>';
		 
			
		}
	}
 return $tree;
}

?>
<!-- DataTables CSS loaded from CDN in header.php -->
<style>

.badge-warning{
	color : white;
}
#accounts_tb td div{
	cursor: pointer;
}
.coa_ul .coa{
	width: 100%;
	cursor: pointer;
}
.fw-bold {
    font-weight: 700!important;
}
.coa > .fa , .sub_account .fa{
	font-size: 25px;
	color:#847bff;
}
.coa > span , .sub_account  > span{
	padding-right: 30px;
}
.d-flex .active{
	background-color: #f5f5f5 !important;
}
td{
	vertical-align: middle !important;
}

.breadcrumb-item{
	cursor: pointer;
    font-size: 14px;
}

#accounts_tb .coa,#accounts_tb .sub_account{
display: inline-flex;
    align-items: center;
    /* justify-content: flex-end; */
    width: 100%;
}
#accounts_tb .coa::before {
    content: "\f07b";
    font-family: "Font Awesome 5 Free";
    padding: 0 10px 0 15px;
    font-size: 30px;
     font-weight: 900;
    color: #847bff;
}

#accounts_tb .sub_account::before {
    content: "\f15c";
    font-family: "Font Awesome 5 Free";
    padding: 0 10px 0 15px;
    font-size: 30px;
     font-weight: 900;
    color: #847bff;
}

.stm_date{
    color:#6c757d
}
.scroler::-webkit-scrollbar {
    width: 3px;
}
.dataTable tr:hover{
    background: #e4ebf2 !important;
    cursor: pointer;
}

#nav-tree .nav-link:hover{
    background: #e4ebf2 !important;
}


#accounts_tb td:first-child { width: 100%;  }
</style>
	
	
	
	
 
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          
          <div class="col-md-9">
		 <ol class="breadcrumb" style="margin-bottom:-2px"></ol>
         
            <div class="second-title">
                <span class="page-title">دليل الحسابات</span>
                <div class="btn-group " style="display:none">
                    <button type="button" class="btn  " data-toggle="dropdown" ><span class="fas fa-cog"></span></button>
                    <div class="dropdown-menu dropdown-menu-right account_options" role="menu" data-account=""   style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 28px, 0px);">
                        <button type="button" class="dropdown-item  about_account"><i class="fa fa-exclamation-circle"></i> معلومات الحساب</button>
                        <button type="button" class="dropdown-item  modify_account"><i class="fa fa-edit"></i> تعديل</button>
                        
                        <a href="requisition?no=90&amp;action=edit" class="dropdown-item more_options" targit="_blank"><i class="fas fa-edit"></i> تعيين مركز تكلفة</a>
                        <button type="button" class="dropdown-item close-trash-alt remove_account"><i class="fa fa-trash-alt"></i> حذف</button>
                    </div>
                </div>
            </div>
            
            
          </div><!-- /.col -->
          
          <div class="col-md-3" style="display: flex;justify-content: left;">
          <div class="credit-wap text-left d-inine-block " style="
                border-right: 3px solid #e2e5ed;
            "><div class="px-2 pl-3" style="border-color: rgb(14, 132, 84);line-height: 1.26;"><h1 id="total_balance"></h1><div class="text-muted" id="total_balance_side"></div></div></div>
            
            
            
            <button class="btn btn-primary" type="button" id="account_repo">كشف حساب</button>
            
          
          </div>
      </div>
    </div>
    </div>

    <!-- Main content -->
   <section class="content">
      <div class="container-fluid">

		<!--<table class="paginated">
			<thead>
				<tr>
					<th class="col">Play Id</th>
					<th class="col">Question1</th>  
			   </tr>
		  </thead>
		  <tbody id="myTable">
		 </tbody>
		 </table>-->
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])){?>
			<div class="alert alert-success alert-dismissible" id="result-alert">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <i class="icon fas fa-check fa-2x"></i> <?= $_SESSION['alert'] ?>
                </div>
			<?php $_SESSION['alert'] = '';?>
			<?php }?>
		
		 
        <div class="row ">
        <div class="col-md-11 mx-auto">
         <div class="invoice" >
            <div class="row">
                <div class="col-md-4" style="padding-left:0px">
                    <div class="" style="border-left: 1px solid lightgray;">
                <div class="card-header" style="z-index: 9;">
					<select class="form-control"   id="search_account" name="search_account" data-placeholder="بحث عن حساب" data-iscat="" placeholder="بحث عن حساب" style="width:100%" >
					<option></option>
					
					</select>
                </div>
                <div class=" ml-2 mt-2 scroler right_block" style=" height: 60vh; overflow: auto;">
				
					<ul id="nav-tree" class="nav_coa_ul" style="display:none">
					
					
					  <?php
					//  echo get_coa_tree($connect_pdo,'0') ;
					//$mainAccounts = $coaModel->selectAccountsByBranchAndParent($_SESSION['branch'],'0');
					 // echo drowTreeAccounts($mainAccounts) ;
					  echo drowTreeAccounts($connect_pdo,'0') ;
					  
					 /*  if(){
					  foreach($rows as $row){
						$tree .='<li id="'.$row['id'].'" data-value="'.$row['accTitle'].'">
								  <a href="#"  class="coa" data-type="'.($row['accHasChild'] ? 'cat': 'account').'" data-id="'.$row['id'].'">
								  
									'.$row['accTitle'].'
								  </a>
								  
								   '.($row['accHasChild'] == 1 ? '<ul>'.get_coa_tree($connect,$row['id']).'</ul>' : '').'
								</li>';
						 
							
						}
					  } */
						
						/* if(!empty($mainAccounts)){
							foreach ($mainAccounts as $k => $v){
								echo'<li class="nav-item text-primary coa" data-type="'.($mainAccounts[$k]["accHasChild"] ? 'cat': 'account').'"  data-id="'.$mainAccounts[$k]["id"].'">
										  <i class="fa fa-folder-plus fa-2x"></i>
										  '.$mainAccounts[$k]["accTitle"].'
									  </li>';
							}
						} */
					  ?>
					</ul>
                  
                </div>
                <!-- /.card-body -->
              </div>
              
                </div>
                
                
                
                
                
                
        <div class="col-md-8" style="padding-right:0px">
            <div class="card" style="box-shadow: none; margin-bottom: unset;">
			<div class="card-header ">
                    <div class="row pl-2">
                    
                        <label for="stm_date_range" class="col-form-label col-md-2 accounts_tb_0">الفترة (من-الى)</label>
                            <input type="search"  id="stm_date_range" class="form-control col-md-4  input-date-range accounts_tb_0" placeholder="" autocomplete="off"  />
                    
                        
                        <div class="col-md-1 mx-auto"></div>
                        <label for="branch_stm" class="col-form-label col-md-2 text-left ">فرع القيود</label>
                        <select class="form-control col-md-3 " title="فرع القيود" id="branch_stm">
                            <?php
                           
                            if(!empty($_SESSION['role']) && $_SESSION['role']== 'owner'){
                                 echo '<option value="">كل الفروع</option>';
                            }
                            if(!empty($_SESSION['user']['extraBranchies']) && count($_SESSION['user']['extraBranchies']) > 0){
                                foreach($_SESSION['user']['extraBranchies'] as $k => $v){
                                    echo '<option value='.$k.'>'.$v.'</option>';
                                
                                }
                            }else{
                                 echo '<option value='.$branch.'></option>';
                            }
                            ?>
                           
                        </select>
                    </div>
            </div>
			
				
				
			<div class=" p-2 scroler" style=" height: 60vh; overflow: auto;">
			
				<div class="table-responsive" style="overflow-x: unset;">
					<table id="accounts_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap " width="100%" style="margin-top: -22px !important;">
							<thead style="visibility: hidden;">
								<tr>
									<th scope="col"  width="100%" ></th>
								
									<th scope="col"></th>
									<th scope="col"></th>
								</tr>
							</thead>
							<tbody></tbody>
							
					</table>
                    
                    <table id="accounts_tb_0" class="table dataTable table-hover  dtr-inline table-sm  nowrap " width="100%"  style="display:none;margin-top: 0px !important;">
							<thead style="">
								<tr>
									<th scope="col" data-priority="2" width="100%">العملية</th>
									<th scope="col">مدين</th>
									<th scope="col">دائن</th>
								</tr>
							</thead>
							<tbody></tbody>
                            
							<tfoot class="bg-gry">

                                <tr >
									<th>رصيد سابق : </th>
									<th class="last_balance_d"></th>
									<th class="last_balance_c"></th>
								</tr>

                            </tfoot>
							
					</table>
				</div>
				</div>
                <div class=" p-2"><button data-main="<?=$main_account?>" type="button" data-type="any" class="btn btn-default"  id="add_coa"><i class="fa fa-plus"></i> إضافة حساب
									</button></div>
            <div class="overlay" style="display:none" id="add_holdon"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
			</div>
		 
                </div>
            </div>
         </div>
         
         </div>
         </div>
         
         
         
         
         
         
         
         
		 
    </section>


<?php
 //include_once('inc/footer.php');
?>

<!-- /.content -->
	 <br>
  </div>



<?php
 include_once('inc/footer.php');
?>




<!-- DataTables JS loaded from CDN in footer.php -->





<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
      NavTree.createBySelector("#nav-tree", {
        searchable: true,
        showEmptyGroups: true,

        groupOpenIconClass: "fas",
        groupOpenIcon: "fa-folder-open",

        groupCloseIconClass: "fas",
        groupCloseIcon: "fa-folder",

        linkIconClass: "fas",
        linkIcon: "fa-file-alt",

        searchPlaceholderText: "Search",

        iconPlace: "start"
      });
    });
  </script>

<script>

function changeurl(url)
{
    
       var title   = 'دليل الحسابات : '+$('.page-title').text();
	 if (typeof (history.pushState) != "undefined") {
        var obj = { Title: title, Url: url };
        history.pushState(obj, obj.Title, obj.Url);
		document.title=title;
    }
     var title_height = $('.content-header').height();
                 $('.content').css({ paddingTop : title_height + 25 + 'px' });
/*  var new_url= url;
 window.history.pushState("data","Title",new_url);
 document.title=url; */
}  
function accounts_tb(num)
 {
     $('#add_holdon').show();
	var branch = $("#branch_stm").val();
	//var account = 0;
    $('#add_coa').show();
     $('#accounts_tb').DataTable().destroy();
     $('#accounts_tb').show();
     $('#accounts_tb_0').DataTable().destroy();
     $('#accounts_tb_0').hide();
     $('.accounts_tb_0').hide();
     
  var dataTable = $('#accounts_tb').DataTable({
	  
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
	/* "aoColumns":[
		  null,
		 // { "visible": false },
		  null,
		  null,
		 // { "visible": false },
		  null,
		//  { "visible": false },
		  { "bSortable": false }
		], */
	language: {
            url: '/dist/js/dataTables.coa.json'
        },
   "ajax" : {
    url:"./accountants-app/accounts-coa-tree",
    type:"POST",
	data:{
	num:num, branch:branch

    }

   },
   
    aoColumns: [
		  
			
			
			
			{
                data: ['account'],
			
                render: function(data, type) {
					let thetype ='';
					let icon ='';
					let row_class ='sub_account';
                    if (data.type > 0) {
                       thetype = "cat";
                       icon = "folder";
                       row_class = "coa";
					   
                    }else{
					   thetype = "account";
					   icon = "file-alt";
					}
				//return '<div onClick="window.location.href =\'c-of-a?'+data.id+'\';"><i class="fa fa-'+icon+'"></i><p>' + data.title + '</p> <span>' + data.no + '</span></div>';
				
				//return '<div class="'+row_class+'" data-type="'+thetype+'"  data-id="'+data.id+'"><i class="fa fa-'+icon+'"></i> <b>' + data.name + ' </b><br><span>' + data.no + '#</span></div>';
                
                  return '<div class="'+row_class+'" data-type="'+thetype+'"  data-title="'+data.name+'#' + data.no + '" data-id="'+data.id+'"><div class=""><div class="bold title">' + data.name + '</div><div class="text-muted">#' + data.no + '</div></div></div>';
                  //  return data.date;
                }
            },
			
		 	 {
                 data: ['account'],
				  "bSortable": false,
                render: function(data, type) {
					return '<div class="bold">'+data.balance+'</div><div class="text-muted">'+data.balance_side+'</div>';
                     
                }
            },  
			


			
			  { 
			   //data: "status",
			   // data: ['req_id','status'],
				// data: null,
				  data: ['account'],
			  "bSortable": false,
			  render: function(data, type) {
					//s. url ='#';
					let action = '';
					if (data.type == 0) {
						 action = '<a href="requisition?no='+data.id+'&action=edit" class="dropdown-item run-pos" targit="_blank"><i class="fas fa-edit"></i> تعيين مركز تكلفة</a>';
					}
                   
					
                   return '<div class="btn-group" > <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu" data-account="'+data.id+'" data-branch="'+data.branch+'"><button type="button" class="dropdown-item view-pos about_account"><i class="fa fa-exclamation-circle"></i> معلومات الحساب</button>'+action+'<button type="button" class="dropdown-item view-pos modify_account"><i class="fa fa-edit"></i> تعديل</button><button type="button" class="dropdown-item close-trash-alt remove_account" ><i class="fa fa-trash-alt"></i> حذف</button></div></div>';  
                }
				
			
			  }
			
			
		],
   
   drawCallback:function(settings)
    {
     if(settings.json.not_found !=''){
        alert(settings.json.not_found);
        location.reload();
    }else{ 
        
    var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
    pagination.toggle(this.api().page.info().pages > 1);
     //$('#total_purchases_amount').html(settings.json.total_purchases_amount);
	 if(settings.json.get_path != ''){
			$('.breadcrumb').html(settings.json.get_path );
			$('.page-title').text($('.breadcrumb li:last-child').text());
            var id = $('.breadcrumb li:last-child').data('id');
            var type = $('.breadcrumb li:last-child').data('type');

            $('.account_options').attr('data-account', id );
            if(id > 0){
              $('.account_options').closest('.btn-group').show();
            }else{
              $('.account_options').closest('.btn-group').hide();
            }

            if(type == 'account'){
              $('.account_options .more_options').show();
            }else{
              $('.account_options .more_options').hide();
            }
	 }
     
     if(!settings.json.folders  && !settings.json.files  || (settings.json.folders > 0 && settings.json.files > 0)){
         $('#add_coa').attr('data-type','any');
     }else{
     
	 if(settings.json.files > 0){
			$('#add_coa').attr('data-type','0');
			//$('#add_coa').change();
			//$('#filters_results').html('تم ايجاد <div class="label label-success">'+settings.json.recordsFiltered+'</div> فاتورة');
			 //$('#remove_filters_btn').prop("disabled", false);
		}
        if(settings.json.folders > 0){
			$('#add_coa').attr('data-type','1');
		}
     }
     
      
      
   
        /* if(settings.json.folders > 0 && settings.json.files){
			$('#add_coa').attr('data-type','any');
		} */
        
        
        changeurl('accountant-coa?cat='+settings.json.parent+' ');
		$('#add_coa').attr('data-main',settings.json.parent );
        if(settings.json.account_type == 'account' ){
            $('#add_coa').attr('data-type','0');
            $('#add_coa').hide();
            //alert();
            //accounts_tb_0(num);
        }else{
            $('#add_coa').show();
        }

        
        $('#total_balance').text(settings.json.total_balance);
        $('#total_balance_side').text(settings.json.total_balance_side);
        
        
        
        
        $('#add_holdon').hide();
        
    }
    
    }
  
  
  
	
	
  });

};


function accounts_tb_0(num)
 {
     $('#add_holdon').show();
	var branch = $("#branch_stm").val();
	var date_range = $("#stm_date_range").val();
     var last_id = $('.breadcrumb li:last-child').data('id');
    
     $('#add_holdon').show();
     $('#add_coa').hide();
     $('#accounts_tb').DataTable().destroy();
     $('#accounts_tb').hide();
     $('#accounts_tb_0').DataTable().destroy();
     $('#accounts_tb_0').show();
     $('.accounts_tb_0').show();
     
  var dataTable = $('#accounts_tb_0').DataTable({
	  
	"processing" : true,
	"serverSide" : true,
	"paging": true,
	"lengthChange": false,
	"searching": false,
	"order" : [],
	"ordering": true,
	"info": false,
	"autoWidth": false,
	"responsive": true,
	"pagingType": "numbers",
	/* "aoColumns":[
		  null,
		 // { "visible": false },
		  null,
		  null,
		 // { "visible": false },
		  null,
		//  { "visible": false },
		  { "bSortable": false }
		], */
	language: {
            url: '/dist/js/dataTables.coa.json'
        },
   "ajax" : {
     url:"./accountants-app/account-statment-tb",
    type:"POST",
	data:{
	num:num, date_range:date_range, branch:branch,last_id:last_id

    }

   },
   
    aoColumns: [
		  
			{
                data: ['account'],
			
                render: function(data, type) {
	                
                  return '<div class="stm_detail "  data-source="'+data.source_stm+'" data-toggle="modal" data-target="#modal_default"><span class="bold stm_date">'+data.date+' ('+data.source_stm+'#)</span><br><div class="text-muted text-sm">'+data.source_doc+' #'+data.source_no+' </div><small class="branch_icon">'+data.branch_name+'</small></div>';
                }
            },
			
		 	 {
                 data: ['account'],
				  "bSortable": true,
                  "className": "bold",
                render: function(data, type) {
					return data.debit;
                     
                }
            }, 
            
            {
                 data: ['account'],
				  "bSortable": true,
				  "className": "bold",
                render: function(data, type) {
					return data.credit;
                     
                }
            }
			
			
		],
   
    drawCallback:function(settings)
    {
    if(settings.json.not_found !=''){
        alert(settings.json.not_found);
        //location.reload();
    }else{
    var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
    pagination.toggle(this.api().page.info().pages > 1);
    
    
        if(settings.json.get_path != ''){
                $('.breadcrumb').html(settings.json.get_path );
                
         }
         
         if(settings.json.total_balance != ''){
              $('#total_balance').text(settings.json.total_balance['amount']);
              $('#total_balance_side').text(settings.json.total_balance['side']);
         }
         if(settings.json.last_balance['side'] == 'مدين'){
              $('#accounts_tb_0 .last_balance_d').text(settings.json.last_balance['amount']);
         }else if(settings.json.last_balance['side'] == 'دائن'){
              $('#accounts_tb_0 .last_balance_c').text(settings.json.last_balance['amount']);
         }else{
             $('#accounts_tb_0 .last_balance_d').text('');
             $('#accounts_tb_0 .last_balance_c').text('');
         }
         
        
          $('.page-title').text($('.breadcrumb li:last-child').text());
            var id = $('.breadcrumb li:last-child').data('id');
            $('.account_options').attr('data-account', id );
            $('.account_options').closest('.btn-group').show();
            $('.account_options .more_options').show();
        
        
        
      changeurl('accountant-coa?account='+num+' ');   
         $('#add_holdon').hide();
         
        }
         
    }
		
	
  });

};

var iscat = 1;

 

$(document).ready(function(){
	//data-widget
	
var title_height = $('.content-header').height();
			 $('.content').css({ paddingTop : title_height + 30 + 'px' });
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('cat');
const account_id = urlParams.get('account');

 if(account_id){
     
     $("#stm_date_range").val('');
    accounts_tb_0(account_id);
}else{
    accounts_tb(param_id);
}



/* function changeHeader(title){
	   $('.page-title').text(title);
} */

 


$('#search_account').select2({
	//dropdownParent: $('#modal_default'),
	placeholder: {
		//id: '', // the value of the option
		text: 'الكل'
		
	  }
	//  allowClear: true
});



$(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
});

    $("#search_account").on('select2:selecting', function(){
     iscat =  document.querySelector('.account_data').getAttribute('data-iscat');
     
});

//$(document).on("change", "#search_account", function(){
$("#search_account").on('change', function(){
     var account = $('#search_account').val();
     if(account > 0){
         if(iscat > 0){
            accounts_tb(account);
        }else{
            accounts_tb_0(account);
        }
     }
    
	/* $('#accounts_tb').DataTable().destroy();
	accounts_tb(account); */
   
});

/* function show_filter(){
	//$("#inv-filter-tab").trigger('click');
	$("#filter_options").toggle('slow');
} */
 
/* if(param_id > 0){
	accounts_tb(param_id);
} */

 

   //accounts_tb(param_id);

/* $(window).resize(function() {
	$('.content').css("padding-top", 
	$(".content-header").height() + 30 + 'px');
	//var title_height = $('.content-header').height();
	// $('.content').css({ paddingTop : title_height + 30 + 'px' });
}).resize(); */
			
$("#branch_coa").on('change', function(){
	$('#accounts_tb').DataTable().destroy();
	accounts_tb(param_id);
	
});

var Toast = Swal.mixin({
	toast: true,
	position: 'top-end',
	showConfirmButton: false,
	timer: 5000
});


	






   
 
$(document).on("change", "#branch_stm , #stm_date_range", function(){ 
  var id =   $('.breadcrumb li:last-child').data('id');
  var type =   $('.breadcrumb li:last-child').data('type');
 if(type == 'account'){
    accounts_tb_0(id);
}else{
    accounts_tb(id);
}
});

$(document).on("click", "#account_repo", function(){ 
  var id =   $('.breadcrumb li:last-child').data('id');
  
    if(id > 0){
    window.open('reports-account-statement?ac_id='+id+'', "_blank");
    window.focus();
    }else{
        window.open('reports-account-statement', "_blank");
        window.focus();
    }
 
}); 


$(document).on("click", ".coa", function(){ 
//$('.coa').on('click', function(event){
	var id   = $(this).attr('data-id');
	$('.d-flex').removeClass('active');
	$(this).closest('div').addClass('active');
	//changeurl('accountant-coa?'+type+'='+id+' ');
    

		//$('#add_coa').attr('data-main',id);
		$('#accounts_tb').DataTable().destroy();
		accounts_tb(id);
        
        $('#ntc-'+id).addClass('show');
		 $('.d-flex').removeClass('active');
        $('#'+id+'.d-flex').addClass('active');
});


$(document).on("click", ".sub_account", function(){
//	var id   = $(this).attr('data-id');
    var id   = $(this).attr('data-id');
    $('.page-title').text($(this).attr('data-title'));
    $("#stm_date_range").val('');
    accounts_tb_0(id);
   // changeurl('accountant-coa?account='+id+' ');
	
});

$(document).on("click", ".stm_detail", function(){ 
	var id   = $(this).attr('data-source');
	if(id >= 0){
		  $('#modal_default #modal_title').text('القيد #'+id+'').show();
		
		  $('#modal_default .modal-body').load('accountant-journals-view?id='+id+'&simple=1',function(){
			  $('#modal_default .modal-dialog').addClass('modal-lg');
			//$('#modal_default').modal({show:true});
		});
	}
});


$(document).on("click", "#add_coa", function(){ 
	var id   = $(this).attr('data-main');
	var type   = $(this).attr('data-type');
	//var branch   = $('#branch_coa').val();
	if(id >= 0){
		  $('#modal_default #modal_title').text('اضافة حساب جديد').show();
		
		
		  $('#modal_default .modal-body').load('cofa-add?account='+id+'&type='+type+'',function(){
			  $('#modal_default .modal-dialog').addClass('modal-lg');
			$('#modal_default').modal({show:true});
		});
	}
});


$(document).on("click", ".remove_account", function(){ 
	var type   = 'coa';
	var account_id   = $(this).closest('div').attr('data-account');
	//var account_branch   = $(this).closest('div').attr('data-branch');
		  $('#modal_title').text('حذف الحساب');
		   
		  $('#modal_default .modal-body').load('accounts-remove?remove='+type+'&account='+account_id+'',function(){
			  $('#modal_default .modal-dialog').removeClass('modal-lg');
			 // $('#modal_default .modal-dialog').addClass('modal-md');
			  $('#modal_default .modal-body').addClass('loader');
			$('#modal_default').modal({show:true});
		});

});


$(document).on("click", ".modify_account", function(){ 
	var type   = 'coa';
	var account_id   = $(this).closest('div').attr('data-account');
	//var account_branch   = $(this).closest('div').attr('data-branch');
		  $('#modal_title').text('تعديل الحساب ');
		   
		  $('#modal_default .modal-body').load('accounts-modify?modify='+type+'&account='+account_id+'',function(){
			  $('#modal_default .modal-dialog').removeClass('modal-lg');
			  $('#modal_default .modal-dialog').addClass('modal-md');
			  $('#modal_default .modal-body').addClass('loader');
			$('#modal_default').modal({show:true});
		});

});

$(document).on("click", ".about_account", function(){ 
	//var type   = 'coa';
	var account_id   = $(this).closest('div').attr('data-account');
	//var account_branch   = $(this).closest('div').attr('data-branch');
		  $('#modal_title').text('معلومات الحساب');
		   
		  $('#modal_default .modal-body').load('accounts-info?account='+account_id+'',function(){
			  $('#modal_default .modal-dialog').removeClass('modal-lg');
			 // $('#modal_default .modal-dialog').addClass('modal-md');
			  $('#modal_default .modal-body').addClass('loader');
			$('#modal_default').modal({show:true});
		});

});



//var branch = $("#branch_coa").val();


search_account();
function search_account(){

$("#search_account").select2({
	
	//$(".js-select2-items-ajax_"+row_id).focus();
  ajax: {
	//url: 'ajax/select2_products.php',
	url: './accountants-app/accounts-menu',
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term, // search term
        s_disabled: 'n', // disable disabled accounts
       // s_branch: $("#branch_coa").val(), // disable disabled accounts
        //s_type: 'all', // disable disabled accounts
        page: params.page
      };
    },
    processResults: function (data, params) {
      // parse the results into the format expected by Select2
      // since we are using custom formatting functions we do not need to
      // alter the remote JSON data, except to indicate that infinite
      // scrolling can be used
      params.page = params.page || 1;

      return {
        results: data.accounts,
        pagination: {
          more: (params.page * 10) < data.total_count
         // more: (params.page) < 3
        }
      };
    },
    cache: true
  },
  allowClear: true,
  placeholder: 'بحث عن حساب',
  minimumInputLength: 1,
  templateResult: formatRepo,
  templateSelection: formatRepoSelection
});

function formatRepo (repo) {
  if (repo.loading) {
    return repo.text;
  }
  

  var $container = $(
	 "<div class='select2-result-repository clearfix'>" +
     
        "<div class='account_data'>" +
        "<div class='select2-result-repository__path'></div>" +
        "<div class='select2-result-repository__title bold_sm'></div>" +
      "</div>" +
    
      "</div>" 
  );

  $container.find(".account_data").attr('data-iscat',repo.iscat);
  $container.find(".account_data").attr('data-id',repo.id);
  $container.find(".select2-result-repository__path").text(repo.path);
  $container.find(".select2-result-repository__title").text(repo.name);
  //$container.find(".select2-result-repository__title").attr('data-side',repo.name);
  /* $container.find(".badge").append(repo.code);
  $container.find(".select2-result-repository__stargazers").append(repo.code);
  $container.find(".select2-result-repository__price").append("<span id='item_price'>"+repo.code+"</span>"); */


  return $container;
}

function formatRepoSelection (repo) {
  return repo.name || repo.text;
}
}




  
  

});
</script>
