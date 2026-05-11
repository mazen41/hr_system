   
<?php

$screen = 'إدارة الموارد البشرية';
$page_title = 'طلب نسيان البصمة ';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 
$form_title = 'إضافة طلب جديد';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';






if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT o.Id ,o.date,o.num_finger,o.description,o.name_type,s.ShiftName
 	FROM   order_finger_add o

    LEFT JOIN tbshift AS s ON s.ShiftID   = o.num_finger 
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("finger-forget-list"); </script>';
            die(); 
        }

		$client_id = $row['Id'];
		

        if(isset($_GET['copy'])){
          $save_btn = 'حفظ واعتماد';
          $save_btn_2 = 'حفظ كمسودة';
         }
          else{
          $save_btn = 'حفظ التغييرات';
          $form_title = 'تعديل الطلب  ';
          $save_btn_2 = null;
          }
	}
	else{
		echo'<script> location.replace("finger-forget-list"); </script>';
		die();
	}		
}
// get the leaves of user
$query = "SELECT u.UserID,t.shiftID, s.ShiftName,s.ShiftID as sh_id
FROM tblusers AS u
LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
LEFT JOIN tbshift AS s ON FIND_IN_SET(s.ShiftID, t.shiftID)

WHERE u.UserID=$user order by u.UserID";

$stmt = $connect_pdo->prepare($query);
$stmt->execute();

if($stmt->rowCount() > 0){
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
   foreach($results as $rows){
       $leaves[] = array(
           'id' => $rows['sh_id'],
           'name' => $rows['ShiftName']
       );
}
 
}
// 

?>

<style>
  #ess
  {
    display:none;
  }
</style>
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-incentive"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
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
	<form class="form-horizontal" role="form" action="" method="post" id="AddleaveRequesr" enctype="multipart/form-data">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل الطلب</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      

                       <div class="row">
                        <div class="form-group col-md-12">
                            <label class="col-form-label required" for="date">حدد اليوم</label>
                            <input type="text" name="date" class="form-control input-date"  placeholder="حدد اليوم" id="date" autocomplete="off" value="<?=(!empty($row['date'])? $row['date'] : '' )?>" required>
                        </div>
                   </div>

                   <div class="row">
                        <div class="form-group col-md-6">
                        <label class="col-form-label required" for="leaves_name">اسم الفترة</label>
                        <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اسم الفتره" id="leaves_name" name="leaves_name"  required >
                            <?php
                        if(!empty($row['ShiftName'])){
                            
                            echo '<option  selected value="' . $row['num_finger'] . '">' . $row['ShiftName'] .'</option> ';
                        }
                        
                        foreach($leaves as $roww) {
                                    echo '<option value="' . $roww['id'] . '">' . $roww['name'] . '</option>';
                                }
                            
                            ?>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                        <label class="col-form-label required" for="leaves_type">اختر حظور او انصراف</label>
                        <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="حظور وانصراف" id="leaves_type" name="leaves_type"  required >
                            <?php

                            ?>
                            </select>
                            <?php
                        if(isset($_GET['id'])){
                            ?>
                            <label class="col-form-label required" for="name_type" id="name_type">تم اختيار <?php echo $row['name_type']  ?></label>

                            <?php

                        }
                        ?>
                        </div>


                            <input type="hidden"  name="type" id="type"  value="">
                            <input type="hidden"  name="fisrt_time" id="fisrt_time"  value="">
                            <input type="hidden"  name="last_time" id="last_time"  value="">
                            <input type="hidden"  name="isdraft" id="isdraft"  value="">
                    </div>
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">الوصف</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['description']) ? $row['description'] : ''?></textarea>
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
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


  const urlParams = new URLSearchParams(window.location.search);
let param_id = urlParams.get('id');
if (urlParams.has('copy')) {
    param_id = null;
}


$(document).on('click', '#save-incentive', function() {
  $('#isdraft').val(1);
    $('#AddleaveRequesr').trigger('submit');
    
});

$(document).on('click', '#save-draft', function() {
  $('#isdraft').val(null);
    $('#AddleaveRequesr').trigger('submit');
});



$('#AddleaveRequesr').on('submit', function(e){  
    e.preventDefault();

  var form_data = new FormData(this);
  form_data.append('id', param_id);

	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=finger-forget-add",
        method:"POST", 
		data:form_data,
    contentType: false,
    processData: false,
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
				if(data.id > 0){  
                   window.location.href = 'finger-forget-list';
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



	$('#AddleaveRequesr').validate({
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


// جلب بيانات البصمة
$('#leaves_name').change(function() {
    var selectedValue = $(this).val(); 
    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: 'hr-app/index.php?action=get-leaves-info',
            type: 'POST',
            data: { value: selectedValue},
            dataType: "json",
            beforeSend: function() {
                $('#preloading').show();
            },
            success: function(response) { 
    $('#preloading').hide();

    if (response.leaves && response.leaves.length > 0) {
        let shift = response.leaves[0]; // استخدم أول عنصر في المصفوفة
        let html = "";

        if (shift.NumFootprint == "1") {
            html = "<option value='1'>حضور</option>";
            name_type="حضور";  
        } else if (shift.NumFootprint == "2") {
            html = "<option value='1'>حضور</option> <option value='2'>انصراف</option>";
            name_type="حصور او انصراف";   
        }

        $('#leaves_type').html(html);
        $('#name_type').html(name_type);
        $('#leaves_type').selectpicker('refresh');
        $('#type').val(shift.NumFootprint);
        $('#fisrt_time').val(shift.ShiftStartTime);
        $('#last_time').val(shift.ShiftEndTime);

    } else {
        toastr.error('حدث خطأ أثناء جلب البيانات');
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
