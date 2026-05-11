
<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'كشف الحضور والانصراف';

include_once('inc/header.php');

$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);


$query = "SELECT UserID ,CONCAT(FirstName, ' ', LastName) as emp_name
FROM  tblusers 
WHERE  isemp is not null and BranchID in ($allowed_branch)  ";

$st = $connect_pdo->prepare($query);
$st->execute(
   array());

if($st->rowCount() > 0){
   $results = $st->fetchAll(PDO::FETCH_ASSOC);

   foreach($results as $row){
       $emp[] = array(
           'id' => $row['UserID'],
           'name' => $row['emp_name']
       );
}

}

$form_title = 'تحضير موظف';
$save_btn = 'حفظ';

 

?>
<style>
body {
      direction: rtl;
      background-color: #f8f9fa;
    }


    .dropdown-scroll {
      position: relative;
    }

    .dropdown-menu-custom {
      position: absolute;
      top: 100%;
      right: 0;
      left: 0;
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 10px;
      background-color: white;
      z-index: 9999;
      display: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .dropdown-menu-custom.show {
      display: block;
    }

    .dropdown-menu-custom div {
      padding: 10px;
      cursor: pointer;
    }

    .dropdown-menu-custom div:hover {
      background-color: #f1f1f1;
    }

    .dropdown-toggle-custom {
      width: auto;
      padding: 5px;
      border: 1px solid #ced4da;
      border-radius: 2px;
      background-color: white;
      text-align: center;
      cursor: pointer;
    }

    .attendance-emp-card,
    .attendance-emp-card .card-body,
    .attendance-emp-card .row,
    .attendance-emp-card .form-group,
    .attendance-emp-card .dropdown-scroll {
      overflow: visible !important;
    }

    .attendance-emp-card .dropdown-scroll {
      position: relative;
      z-index: 20;
    }

    .attendance-emp-card .dropdown-menu-custom {
      z-index: 2055;
    }

    .daterangepicker {
      z-index: 2060 !important;
    }
  </style>
	
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
				<div class="invoice card mb-3 shadow-none attendance-emp-card">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">بيانات الموظف</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">

                      
					 
					 <div class="row">
                             <div class="form-group col-md-6">
                                <label class="col-form-label required" for="date">حدد التاريخ</label>
                                <input type="text" name="date" class="form-control input-date"  placeholder="حدد التاريخ" id="date" autocomplete="off" value="">
                              </div>
                          <div class="form-group col-md-6">
                                <label class="col-form-label required" for="emp_list">اسم الموظف</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اسم الموظف" id="emp_list" name="emp_list[]" multiple required >
                                    <?php
								
                                foreach($emp as $row) {
                                            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                        }

                                    ?>
                                    </select>
                              </div>
							
						</div>
           





						<div class="row">
							<div class="form-group col-md-6">
							<label class="col-form-label required" for="time">وقت بداية الدوام</label>
							<div class="row gx-2 mb-3">
							<div class="col">
							<label>الساعة</label>
							<div class="dropdown-scroll">
								<div id="hourToggle" class="dropdown-toggle-custom">--</div>
								<div id="hourMenu" class="dropdown-menu-custom"></div>
							</div>
							</div>
							<div class="col">
							<label>الدقيقة</label>
							<div class="dropdown-scroll">
								<div id="minuteToggle" class="dropdown-toggle-custom">--</div>
								<div id="minuteMenu" class="dropdown-menu-custom"></div>
							</div>
							</div>
						</div>
                        <input type="hidden"  id="time_start" class="form-control"  name="time_start" readonly>
					</div>

          	<div class="form-group col-md-6">
							<label class="col-form-label required" for="time">وقت انتهاء الدوام</label>
							<div class="row gx-2 mb-3">
							<div class="col">
							<label>الساعة</label>
							<div class="dropdown-scroll">
								<div id="hourToggle_e" class="dropdown-toggle-custom">--</div>
								<div id="hourMenu_e" class="dropdown-menu-custom"></div>
							</div>
							</div>
							<div class="col">
							<label>الدقيقة</label>
							<div class="dropdown-scroll">
								<div id="minuteToggle_e" class="dropdown-toggle-custom">--</div>
								<div id="minuteMenu_e" class="dropdown-menu-custom"></div>
							</div>
							</div>
						</div>
                        <input type="hidden"  id="time_end" class="form-control"  name="time_end" readonly>
					</div>
          




					
						</div>
 <div id="detials"></div>




 
						<!--  -->
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

$(document).on('click', '#save-shift', function(){
	$('#AddShift').trigger('submit');
});

if($.fn.daterangepicker){
	$('.input-date').daterangepicker({
		singleDatePicker: true,
		showDropdowns: true,
		autoUpdateInput: false,
		parentEl: 'body',
		drops: 'auto',
		locale: {
			format: 'YYYY-MM-DD',
			cancelLabel: 'مسح',
			applyLabel: 'تطبيق'
		}
	});

	$('.input-date').on('apply.daterangepicker', function(ev, picker) {
		$(this).val(picker.startDate.format('YYYY-MM-DD')).trigger('change');
	});

	$('.input-date').on('cancel.daterangepicker', function() {
		$(this).val('');
	});
}




$('#AddShift').on('submit', function(e){  
    // alert($().val())
    	e.preventDefault();
	var sup_id = $('#shift_id').val();
	var form_data = $(this).serialize();
	if($(this).valid()){
	//var chk = $('#store_stoped').is(':checked');  
	$.ajax({
        url:"hr-app/index.php?action=attendancet-emp",
        method:"POST", 
		data:form_data,
		// data:new FormD?ata(this), 
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg); 
                   window.location.href = 'reveal-attendance?id='+data.id+'';


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












	// 
	$(function() {
  const $hourToggle = $('#hourToggle');
  const $hourMenu = $('#hourMenu');
  const $minuteToggle = $('#minuteToggle');
  const $minuteMenu = $('#minuteMenu');
  const $timeInput = $('#time_start');

  const $hourToggle_e = $('#hourToggle_e');
  const $hourMenu_e = $('#hourMenu_e');
  const $minuteToggle_e = $('#minuteToggle_e');
  const $minuteMenu_e = $('#minuteMenu_e');
  const $timeInput_e = $('#time_end');

  function generateOptions($container, $toggle, range, step = 1) {
    for(let i = 0; i < range; i += step) {
      const val = i.toString().padStart(2, '0');
      const $option = $('<div>').text(val);
      $option.on('click', function() {
        $toggle.text(val);
        $container.hide();
        updateTime();
      });
      $container.append($option);
    }
  }

  function updateTime() {
    const h1 = $hourToggle.text();
    const m1 = $minuteToggle.text();
    if (h1 !== '--' && m1 !== '--') {
      $timeInput.val(`${h1}:${m1}`);
    } else {
      $timeInput.val('');
    }

    const h2 = $hourToggle_e.text();
    const m2 = $minuteToggle_e.text();
    if (h2 !== '--' && m2 !== '--') {
      $timeInput_e.val(`${h2}:${m2}`);
    } else {
      $timeInput_e.val('');
    }
  }

  generateOptions($hourMenu, $hourToggle, 25);
  generateOptions($minuteMenu, $minuteToggle, 65, 5);
  generateOptions($hourMenu_e, $hourToggle_e, 25);
  generateOptions($minuteMenu_e, $minuteToggle_e, 65, 5);

  $hourToggle.on('click', function(e) {
    e.stopPropagation();
    $hourMenu.css('display', $hourMenu.css('display') === 'none' ? 'block' : 'none');
    $minuteMenu.css('display', 'none');
    $hourMenu_e.css('display', 'none');
    $minuteMenu_e.css('display', 'none');
  });

  $minuteToggle.on('click', function(e) {
    e.stopPropagation();
    $minuteMenu.css('display', $minuteMenu.css('display') === 'none' ? 'block' : 'none');
    $hourMenu.css('display', 'none');
    $hourMenu_e.css('display', 'none');
    $minuteMenu_e.css('display', 'none');
  });

  $hourToggle_e.on('click', function(e) {
    e.stopPropagation();
    $hourMenu_e.css('display', $hourMenu_e.css('display') === 'none' ? 'block' : 'none');
    $minuteMenu_e.css('display', 'none');
    $hourMenu.css('display', 'none');
    $minuteMenu.css('display', 'none');
  });

  $minuteToggle_e.on('click', function(e) {
    e.stopPropagation();
    $minuteMenu_e.css('display', $minuteMenu_e.css('display') === 'none' ? 'block' : 'none');
    $hourMenu_e.css('display', 'none');
    $hourMenu.css('display', 'none');
    $minuteMenu.css('display', 'none');
  });

  $(document).on('click', function() {
    $hourMenu.css('display', 'none');
    $minuteMenu.css('display', 'none');
    $hourMenu_e.css('display', 'none');
    $minuteMenu_e.css('display', 'none');
  });
});



// اظهار معلومات الموظفين بالنسبة للفترات
$('#emp_list').change(function() {
    var selectedValue = $(this).val(); 
    var date=$("#date").val();
    if(date){
    if (selectedValue && selectedValue.length > 0 ) {
        $.ajax({
            url: 'hr-app/index.php?action=emp-info-shift',
            type: 'POST',
            data: { value: selectedValue ,date:date}, 
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
                                    <th>اسم الفترة</th>
                                    <th>بداية الفترة</th>
                                    <th>نهاية الفترة</th>
                                    <th>عدد البصمات المطلوبة</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    response.section.forEach(function(emp, index) {
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${emp.name ?? '-'}</td>
                                <td>${emp.shiftname ?? '-'}</td>
                                <td>${emp.ShiftStartTime ?? 'الموظف ليس لدية فتره خلال هذا التاريخ'}</td>
                                <td>${emp.ShiftEndTime ?? 'الموظف ليس لدية فتره خلال هذا التاريخ'}</td>
                                <td>${emp.NumFootprint ?? '-'}</td>
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
    } 
    else {
        $('#detials').html('');
    }
  }
  else
  {
    toastr.error('حدد التاريخ اولاً');
  }
});




});
 

</script>
