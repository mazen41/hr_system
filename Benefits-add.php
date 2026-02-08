  
<?php
$appid  = 'HR';
$page_perm=['إضافة تعويض'];
// $screen = ' التعويضات والمزياء';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة التعويضات والمزياء';
// }else{
// $page_title = 'إضافة تعويض';
// }

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة التعويضات والمزايا ';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 
$form_title = 'إضافة تعويض جديده';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';
 
 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT Id,BranchID,UserID,name,Amount,Currency,Reason,for_what,extionsion,beneft_type,DueDate,beneft_type,AmountType,monthly
 	FROM  tblbenefit 
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
// 
          $flage=false;
if($row['beneft_type']==2)
{
    if($today_date < $row['DueDate'])
        $flage=true;
    elseif(empty($row['Status']))
        $flage=true;
}
else
{
    if(empty($row['Status']))
        $flage=true;
}
 
if($flage){
       // 
       if($row['for_what']==1)
       {
           
           $query_ = "SELECT UserID as ID, CONCAT(FirstName, ' ', LastName) as Name FROM tblusers WHERE BranchID IN ($row[BranchID]) and isemp is not null";
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
           $query_="SELECT 	Id as ID ,Name FROM  tbljobtitle WHERE BranchID IN ($row[BranchID])";
       }
$stmt = $connect_pdo->prepare($query_);
$stmt->execute();
$results = $stmt->fetchAll();

if(!empty($row["extionsion"])) 
{
    $user_ids_E = explode(",", $row['extionsion']);
    $placeholders_E = implode(",", array_fill(0, count($user_ids_E), "?"));
    $query_Q = "SELECT UserID AS ID, CONCAT(FirstName, ' ', LastName) AS Name 
               FROM tblusers WHERE UserID IN ($placeholders_E)";  
    $stmt_E = $connect_pdo->prepare($query_Q);
    $stmt_E->execute($user_ids_E);
    $results_E = $stmt_E->fetchAll();
}
$for_w = !empty($row['UserID']) ? array_unique(explode ( ',', $row['UserID'])) : [];
$for_E = !empty($row['extionsion']) ? array_unique(explode ( ',', $row['extionsion'])) : [];

// 



		$client_id = $row['Id'];
    if(isset($_GET['copy'])){
      $save_btn = 'حفظ واعتماد';
      $save_btn_2 = 'حفظ كمسودة';
     }
      else{
      $save_btn = 'حفظ التغييرات';
      $form_title = 'تعديل مكافئة  ' .$row['name'].'';
      $save_btn_2 = null;
      }
	}
	else{
		echo'<script> location.replace("Benefits-list"); </script>';
		die();
	}
  	}

	else{
		echo'<script> location.replace("Benefits-list"); </script>';
		die();
	}		
}
?>

<style>
  #ess
  {
    display:none;
  }
  .align-flex-center {
    display: flex;
    align-items: center;
}
</style>
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-benefit"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
      <?php 
      if(!empty($save_btn_2))
      { 
        ?>
        <button type="button" class="btn btn-success"  id="save-draft"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn_2?></span></button>
     <?php
      }
     
      ?> 
    
    </div>
        </div>
      </div>
    </div>
   
	
	
		
	


    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="AddBenefit">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل التعويض</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 
					 <div class="row">
           <div class="form-group col-md-4">
                                <label class="col-form-label required" for="name">اسم التعويض</label>
                                <input type="text" name="name" class="form-control"  placeholder="الاسم" id="name" autocomplete="off" value="<?=(!empty($row['name'])? $row['name'] : '' )?>" required>
                            </div>
                             <div class="form-group col-md-4">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list"  required >
                                    <?php
									if(!empty($row['BranchID'])) {
										echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
									}
                                        foreach($allowed_branches as $id => $name){	
                                            echo'<option value="'.$id.'" >'.$name.'</option>';
                                        }

                                    ?>
                                    </select>
                              
						</div>
            <div class="form-group col-md-4">
              <label class="col-form-label required" for="beneft_type">نوع التعويض</label>
              <select class=" selectpicker "   data-container="body" data-size="5" data-width="100%" title="أدخل النوع" id="beneft_type" name="beneft_type"  required>
              <option value="1" <?= (!empty($row['beneft_type']) && $row['beneft_type'] == '1') ? 'selected' : '' ?>>شهرية تتكرر كل شهر </option>
              <option value="2" <?= (!empty($row['beneft_type']) && $row['beneft_type'] == '2') ? 'selected' : '' ?>>شهر محدد </option>
                  </select>
            </div>
            <input type="hidden" name="isdraft" id="isdraft"/>
           </div>

                        <div class="row">
                        <div class="form-group col-md-4">
                            <label id="title" class="col-form-label required" for="amount">المبلغ</label>
                            <div class="input-group">
                                <input type="text" step="any" name="amount" class="form-control" placeholder="المبلغ أو النسبة" id="amount" autocomplete="off" value="<?=(!empty($row['Amount'])? $row['Amount'] : '' )?>" required>
                                <select name="AmountType" id="AmountType" class="form-control" style="max-width: 80px;">
                                    <option value="amount" <?= (!empty($row['AmountType']) && $row['AmountType'] == 'amount') ? 'selected' : '' ?>>$</option>
                                    <option value="avg" <?= (!empty($row['AmountType']) && $row['AmountType'] == 'avg') ? 'selected' : '' ?>>%</option>
                                </select>
                            </div>
                        </div>
                            <div class="form-group col-md-4">
                               <label class="col-form-label required" for="currency">العملة</label>
                               <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل العملة" id="currency" name="currency" required >
                               <?php if(!empty($row['Currency'])){ ?>
                                  <option value="<?= $row['Currency'] ?>" selected><?= $row['Currency'] ?></option>
                                  <option value="<?= $User->currency; ?>">عملة النظام</option>
                                <?php } 
                                else {
                                  ?>
                                <option value="<?= $User->currency; ?>" selected>عملة النظام</option>
                                  <?php
                                } ?>
                                <!-- <option value="SAR" >الريال السعودي</option>
                                <option value="AED" >الدرهم الاماراتي</option>
                                <option value="USDT" >الدولار الامريكي</option>
                                <option value="QAR" >الريال القطري</option>
                                <option value="KWT" >الريال الكويتي</option>
                                <option value="BHD" >الريال البحريني</option> -->
                                   </select>
                             </div>

                             <div class="form-group col-md-4" id="Div_Due_date" style="display:none">
                              <label class="col-form-label required" for="Due_date">حدد الشهر اللي تضاف فيه هذه التعويض<br>تصرف نهاية الشهر</label>
                              <input type="text" name="Due_date" class="form-control input-date"  placeholder="تاريخ الاستحقاق" id="Due_date" autocomplete="off" value="<?=(!empty($row['DueDate'])? $row['DueDate'] : '' )?>" required>
                              </div>

                       </div>
                       <div class="row"> 
                        <div class="form-group col-md-4">
                           <label class="col-form-label required" for="for_what">لمن تكون</label>
                           <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="لمن تكون" id="for_what" name="for_what" required >
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
                            <option value="4" >لقسم محدد</option>
                            <option value="5" >لمسمى وظيفي</option>
                               </select>
                         </div>
                         <div class="form-group col-md-4" id="for_W">
                                <label class="col-form-label required" for="employer">اختر </label>
                                 <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أختر" id="employer" name="employer" required  multiple>
                                <?php
                                if(!empty( $results)){  
                                foreach ($results as $ins) { 
                                  echo'<option value="'.$ins["ID"].'"  '.(!empty($for_w) && in_array($ins["ID"], $for_w) ? 'selected' : '').'  >'.$ins["Name"].'</option>';
                                }
                              }
                                ?>
                                
                                </select>
                              </div>
                              <!-- <div class="col-md-4 align-flex-center" id="div_loop" style="display:none" >
                                <label class="switch switch-info switch-md " for="monthly">
                                  <input type="checkbox"  name="monthly" id="monthly" <?= (!empty($row['monthly'])?'checked' : '')?> >
                                  <span></span> دورية
                                </label>
                              </div> -->

                   </div>
                   <div class="row">
                          
                          <div class="form-group col-md-12" id="ess" style=" display:<?= (!empty( $results_E))?'block' :'none' ?>">
							<label class="col-form-label "  for="extinsion">استثناءات غير مضاف في قاعده البيانات</label>
              <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أختر" id="extinsion" name="extinsion" multiple >
              <?php  
              if(!empty( $results_E)){ 
                foreach ($results_E as $ins) { 
                  echo'<option value="'.$ins["ID"].'"  '.(!empty($for_E) && in_array($ins["ID"], $for_E) ? 'selected' : '').'  >'.$ins["Name"].'</option>';
                }}
?>
              </select>
            </div>
                        
                      </div>


                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">السبب</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['Reason']) ? $row['Reason'] : ''?></textarea>
                      </div>
                </div>
                <div id="detials"></div>

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

$(document).on('click', '#save-benefit', function(){
  $('#isdraft').val(1);
	$('#AddBenefit').trigger('submit');
});

//
$(document).on('click', '#save-draft', function() {
  $('#isdraft').val(null);
    $('#AddBenefit').trigger('submit');
});
//

 
$('#AddBenefit').on('submit', function(e){  
    e.preventDefault();
    var extinsion = get_filter('extinsion'); 
    var employer=get_filter('employer'); 
	// var form_data = $(this).serialize() + '&id=' + param_id;
  form_data = $(this).serialize() + '&id=' + param_id + '&extinsion__=' + extinsion+ '&employer__=' + employer;

	if($(this).valid()){
	$.ajax({
        url:"./hr-app/Benefits-add",
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
                   window.location.href = 'Benefits-list';
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



	$('#AddBenefit').validate({
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

$('#amount').on('keypress', function(e) {
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode < 48 || charCode > 57) {
        e.preventDefault();  
    }
});
// جلب بيانات الحديد وعرضها
function get_filter(input_name)
{
	var filter = [];
		$('select[name="'+input_name+'"] option:selected').each(function() {
		filter.push($(this).val());
	   });
	return filter;
}
$('#branchs_list').change(function() {
  $('#for_what').prop('selectedIndex', -1);
  $('#employer').prop('selectedIndex', -1);
  $(".selectpicker").selectpicker("refresh");
});

// 
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
        $('#extinsion').prop('selectedIndex', -1);
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
    // for get extinsion
    $('#employer').change(function() {
        var selectedValue = get_filter('employer');
        var branchs = $('#branchs_list').val();
        var parent = $('#for_what').val(); 
        if(parent !=1){
        if (selectedValue) {
            $.ajax({
                url: '/hr-app/incentive-extion',
                type: 'POST',
                data: { value: selectedValue , BranchID: branchs , parent:parent},
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
          success: function(response) {
            if(response.result){
populateAllSelect('#extinsion', response.data, response.data_);
            }
        else
        toastr.error(response.msg);
					
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        }
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

function populateAllSelect(selectId, items, fixedItems) {
    var select = $(selectId);
    select.empty(); 

    // أضف العناصر الثابتة أولًا
    if (fixedItems && fixedItems.length > 0) {
        $.each(fixedItems, function(index, item) {
            select.append('<option value="' + item.data.id + '" selected disabled>' + item.data.name + '</option>');
        });
    }

    // أضف باقي العناصر
    if (items && items.length > 0) {
        $.each(items, function(index, item) {
            select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
        });
    }

    select.selectpicker('refresh');
}


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
  
        if ($(this).val() === 'avg') {
            $('#title').html( 'النسبة (حتى 100%)');
        } else {
            $('#title').html( 'المبلغ');
        }
        validateAmount();
    });

    $('#beneft_type').on('change', function () {

        if ($(this).val() === '2') {
            $('#Div_Due_date').show();
            $('#div_loop').show();
            $('#Due_date').attr('required');
        } else {
          $('#Div_Due_date').hide();
          $('#div_loop').hide();
          $('#Due_date').removeAttr('required');
          
        }
    });




    // تشغيل التحقق عند تحميل الصفحة
    $('#AmountType').trigger('change');
    $('#beneft_type').trigger('change');







// 
$('#employer').change(function() {
    var selectedValue = $(this).val(); 
    var branche=$('#branchs_list').val();
    var for_what=$('#for_what').val();

    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: '/hr-app/benefit-info_show',
            type: 'POST',
            data: { value: selectedValue, branch:branche,for_whats:for_what },
            dataType: "json",
            beforeSend: function() {
                $('#preloading').show();
            },
            success: function(response) { 
                $('#preloading').hide();

                if (response.section && response.section.length > 0) {
                    let html = `
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>اسم التعويض</th>
                                    <th>الفرع</th>
                                    <th>نوع التعويض</th>
                                    <th>دورية</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>المزيد</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    response.section.forEach(function(emp, index) {
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${emp.username ?? '-'}</td>
                                <td>${emp.name ?? '-'}</td>
                                <td>${emp.branch ?? '-'}</td>
                                <td>${emp.date ?? '-'}</td>
                                <td>${emp.type ?? '-'}</td>
                                <td>${emp.money ?? '-'}</td>
                                <td>${emp.check ?? '-'}</td>
                                <td>${emp.link ?? '-'}</td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                        </div>
                    `;

                    $('#detials').html(html);
                } else {
                    $('#detials').html('<div class="alert alert-info">لا توجد بيانات متاحة لهذا الموظف.</div>');
                }
            },
            error: function() {
                $('#preloading').hide();
                toastr.error('حدث خطأ أثناء جلب البيانات');
            }
        });
    } else {
        $('#detials').html('');
    }
});
// 




});
 

</script>