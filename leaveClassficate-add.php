 
<?php
$appid  = 'HR';
$page_perm=['اضافة اجازة عامة'];

// $screen = 'تصنيف الاجازات';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة انواع الاجازات';
// }else{
// $page_title = 'إضافة اجازة';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);


 
$form_title = 'إضافة نوع اجازة جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT *
 	FROM leaveclassification 
	WHERE  Id  = :id 
	LIMIT 1 ";

	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'   => $client_no
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		if($row['for_what']==1)
		{
			
			$query_ = "SELECT UserID as ID, CONCAT(FirstName, ' ', LastName) as Name FROM tblusers WHERE BranchID IN ($row[BranchID])";
		}
		if($row['for_what']==2)
		{
			$query_="SELECT 	Id as ID ,Name FROM  tblgroup WHERE BranchID IN ($row[BranchID])";
		}
		if($row['for_what']==3)
		{
			$query_="SELECT 	Id as ID ,Name FROM  tbljobgrade WHERE BranchID IN ($row[BranchID])";
		}
		if($row['for_what']==4)
		{
			$query_=" SELECT c.Id As ID, c.Name as Name
	FROM tblsection AS c
	LEFT JOIN tblsection AS d ON c.Id = d.ParentID
	WHERE c.ParentID IS NOT NULL
	AND d.Id IS NULL and c.BranchID IN ($row[BranchID]) ";
		}
		if($row['for_what']==5)
		{
			$query_="SELECT Id as ID ,Name FROM  tbljobtitle WHERE BranchID IN ($row[BranchID])";
		}
		if(!empty($row['for_what'])){
 $stmt = $connect_pdo->prepare($query_);
 $stmt->execute();
 $results = $stmt->fetchAll();
		}
 $for_w = !empty($row['chose']) ? array_unique(explode ( ',', $row['chose'])) : [];
		$client_id = $row['Id'];
		
        $save_btn = 'حفظ التغييرات';

		if(isset($_GET['copy'])){
			$save_btn = 'حفظ';
		   }
			else{
			$save_btn = 'حفظ التغييرات';
			$form_title = 'تعديل نوع إجازة ' .$row['Name'].'';
			}
	}
	else{
		echo'<script> location.replace("insurance-list"); </script>';
		die();
	}		
}
?>


	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-shift"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
		 </div>
        </div>
      </div>
    </div>
   
	
	
		
	


    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="AddShift">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل الاجازة </h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-md-12">
							<label class="col-form-label required"  for="leavename">اسم نوع الاجازة</label>
							<input type="text"  value="<?=!empty($row['Name']) ? $row['Name'] : ''?>" class="form-control"  data-toggle="tooltip"  id="leavename" name="leavename"  autocomplete="off" required>
						 </div>

                        
                      </div>
                      
					 
					 <div class="row">
                             <div class="form-group col-md-4">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list" required >
                                    <?php
									if(!empty($row['BranchID'])) {
										echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
									}
                  else
                  {
                                        foreach($allowed_branches as $id => $name){	
                                            echo'<option value="'.$id.'" >'.$name.'</option>';
                                        }
                                      }

                                    ?>
                                    </select>
                              </div> 
                              <div class="form-group col-md-4">
                                <label class="col-form-label required" for="isaccept">هل تحتاج موافقة</label>
                                  <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="تحتاج موافقة" id="isaccept" name="isaccept" required>
                                  <option value="1" <?=!empty($row['isaccept']) && $row['isaccept']=='1' ? 'selected' : ''?>>نعم</option>
                                  <option value="2" <?=!empty($row['isaccept']) && $row['isaccept']=='2' ? 'selected' : ''?>>لا</option>
                                  </select>
                              </div>
							
							  <div class="form-group col-md-4">
                                <label class="col-form-label required" for="RequiresAttachment">تتطلب مرفق</label>
                                  <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="تتطلب مرفق" id="RequiresAttachment" name="RequiresAttachment" required>
                                  <option value="1" <?=!empty($row['RequiresAttachment']) && $row['RequiresAttachment']=='1' ? 'selected' : ''?>>نعم</option>
                                  <option value="2" <?=!empty($row['RequiresAttachment']) && $row['RequiresAttachment']=='2' ? 'selected' : ''?>>لا</option>
                                  </select>
                              </div>
						</div>

                        <!--  -->
                        <div class="row">
                             <div class="form-group col-md-4">
                                <label class="col-form-label required" for="type">حالة الاجازة</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="حالة الاجازة" id="type" name="type" required >
                                <option value="1" <?=!empty($row['type']) && $row['type']=='1' ? 'selected' : ''?>>مدفوعة كليا</option>
                                <option value="2" <?=!empty($row['type']) && $row['type']=='2' ? 'selected' : ''?>>مدفوعة جزئيا</option>
                                <option value="3" <?=!empty($row['type']) && $row['type']=='3' ? 'selected' : ''?>>غير مدفوعة</option>
                                    </select>
                              </div> 

							  <!-- for what -->
							  <div class="form-group col-md-4">
                           <label class="col-form-label " for="for_what"> لمن تكون <span style="color:brown">(إن لم تحدد تكون للجميع)</span></label>
                           <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="لمن تكون" id="for_what" name="for_what" >
                           <?php if(!empty($row['for_what'])){ ?>
                                  <option value="<?= $row['for_what'] ?>" selected>
                                  <?= ($row['for_what']==1 ? 'لموظف':
                                             ($row['for_what']==2 ?'لمجموعة':
                                             ($row['for_what']==3 ?'لدرجة وظيفية':
                                             ($row['for_what']==5 ?'لمسمى وظيفي':
                                             ($row['for_what']==4 ?'لقسم محدد': ''))))) ?> 

                                  </option>
                                <?php }
                                ?> 
                           <option value="1" >لموظف</option>
                            <option value="2" >لمجموعة</option>
                            <option value="3" >لدرجة وظيفية</option>
                            <option value="5" >لمسمى وظيفي</option>
                            <option value="4" >لقسم محدد</option>
                               </select>
                         </div>

							  <!-- end for whats -->
							   <!-- chose after for what -->
							   <div class="form-group col-md-4" id="for_W">
                                <label class="col-form-label " for="employer">اختر </label>
                                 <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أختر" id="employer" name="employer"   multiple>
                                <?php
                                if(!empty( $results)){  
                                foreach ($results as $ins) { 
                                  echo'<option value="'.$ins["ID"].'"  '.(!empty($for_w) && in_array($ins["ID"], $for_w) ? 'selected' : '').'  >'.$ins["Name"].'</option>';
                                }
                              }
                                ?>
                                
                                </select>
                              </div>
							   <!-- end chose -->
							
						</div>
						<div class="form-group col-md-4" id="div_money" style="display:none">
                            <label id="title" class="col-form-label required" for="amount">المبلغ</label>
                            <div class="input-group">
                                <input type="text" step="any" name="amount" class="form-control" placeholder="المبلغ أو النسبة" id="amount" autocomplete="off" value="<?=(!empty($row['Amount'])? $row['Amount'] : '' )?>" >
                                <select name="AmountType" id="AmountType" class="form-control" style="max-width: 80px;">
                                    <!-- <option value="amount" <?= (!empty($row['AmountType']) && $row['AmountType'] == '$') ? 'selected' : '' ?>>$</option> -->
                                    <option value="avg" <?= (!empty($row['AmountType']) && $row['AmountType'] == '%') ? 'selected' : '' ?>>%</option>
                                </select>
                            </div>
                        </div>
							
                        <!--  -->
                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Note">الوصف</label><br>
                         <textarea name="note" id="note" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['Description']) ? $row['Description'] : ''?></textarea>
                      </div>
                </div>
                <div class="row"> 
						   <div class="col-sm-10">
							  <label class="switch switch-danger switch-md">
								 <input type="checkbox" name="stopped" value="1" id="stopped" <?=!empty($row['state']) ? 'checked': ''?>>
								 <span></span>  ايقاف الاجازة
							  </label>
						   </div>
					  </div>

					</div>
				</div>
			</div>
			</div>
			
        
            
			
		</div>
		</form>
      </div>
	  
    </section>
	





<?php

 include_once('inc/footer.php');

?>
 <script src="plugins/select2_n/dist/js/select2.full.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


	const urlParams = new URLSearchParams(window.location.search);
let param_id = urlParams.get('id');
if (urlParams.has('copy')) {
    param_id = null;
}

$(document).on('click', '#save-shift', function(){
	$('#AddShift').trigger('submit');
});

$('#branchs_list').change(function() {
  $('#for_what').prop('selectedIndex', -1);
  $(".selectpicker").selectpicker("refresh");
});

$('#for_what').change(function() {
        var selectedValue = $(this).val(); 
        if(selectedValue==2 || selectedValue==3 || selectedValue==4 || selectedValue==5)
        {
          
          $("#ess").show();
        }
        else
        {
          $("#ess").hide();
        }
        // if(selectedValue==5)
        // {
        //   $("#for_W").hide();
        // }
        // else 
        // {
        //   $("#for_W").show();
        // }
        $('#employer').prop('selectedIndex', -1);
        $(".selectpicker").selectpicker("refresh");
        var branchs = $('#branchs_list').val();
        if (selectedValue) {
            $.ajax({
                url: '/hr-app/incentive-info',
                type: 'POST',
                data: 
                { 
                  value: selectedValue,
                  BranchID: branchs
                },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
          success: function(response) {
            if(response.result)
					populateSelect('#employer', response.data);
        else
        toastr.error(response.msg);
					
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        } 
    });

	function populateSelect(selectId, items) {
    var select = $(selectId);
    select.empty(); 
    if (items && items.length > 0) {		
        $.each(items, function(index, item) {
            select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
        });
    } 
	select.selectpicker('refresh'); // تحديث SelectPicker
}
function get_filter(input_name)
{
	var filter = [];
		$('select[name="'+input_name+'"] option:selected').each(function() {
		filter.push($(this).val());
	   });
	return filter;
}



$('#AddShift').on('submit', function(e){  
    e.preventDefault();
	var employer=get_filter('employer'); 
	// var form_data = $(this).serialize() + '&id=' + param_id+ '&employer__=' + employer;
	// alert(employer);
	    form_data = $(this).serialize() + '&id=' + param_id +'&employer__=' + employer;
	if($(this).valid()){
	$.ajax({
        url:"./hr-app/leaveClassficate-add",
        method:"POST", 
		data:form_data,
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
				if(data.id > 0){  
                   window.location.href = 'leaveClassficate-view?id='+data.id+'';
				}

			}
            else
            {
				toastr.error(data.msg);
			}
			
			
			
			

			$('#preloading').hide();
					
					
			
		}
	});
	
	}
	
});



	$('#AddShift').validate({
		errorElement: 'span',
		errorPlacement: function (error, element) {
		  error.addClass('invalid-feedback');
		  element.closest('div').append(error);
		},
		highlight: function (element, errorClass, validClass) {
		  $(element).addClass('is-invalid');
		},
		unhighlight: function (element, errorClass, validClass) {
		  $(element).removeClass('is-invalid');
		}
	});
    //  لادخال الارقام فقط
    $('#Cphone').on('keypress', function(e) {
        var charCode = e.which || e.keyCode;
        if ((charCode >= 48 && charCode <= 57) || charCode === 43) {
          return true; 
        } else {
          return false;
        }
    });



	function validateAmount() {
    var type = $('#AmountType').val();
    var val = parseFloat($('#amount').val());
    var $amount = $('#amount');

    // إذا كانت النسبة أكبر من 100 يتم التلوين بالأحمر
    if (type === 'avg' && !isNaN(val) && val > 100) {
        
        $amount.val(null);
    }
}

    // منع الحروف والسماح فقط بالأرقام والفاصلة العشرية
    $('#amount').on('input', function () {
        let value = $(this).val();

        // حذف أي شيء غير رقم أو فاصلة عشرية واحدة
        value = value.replace(/[^0-9.]/g, '');

        // منع وجود أكثر من نقطة عشرية
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts[1];
        }

        $(this).val(value);
        validateAmount();
    });
	$('#AmountType').on('change', function () {
  
  if ($(this).val() === '2') {
	  $('#title').html( 'النسبة (حتى 100%)');
  } else {
	  $('#title').html( 'المبلغ');
  }
  validateAmount();
});

//type

$('#type').on('change', function () {
  
  if ($(this).val() === '2') {
	  $('#div_money').show();
	  $('#amount').attr('required');
  } else {
	  $('#div_money').hide();
	  $('#amount').removeAttr('required');
  }
  validateAmount();
});


$('#AmountType').trigger('change');
$('#type').trigger('change');

});
 

</script>