
<?php
$appid  = 'HR';
$page_perm=['اضافة بصمة'];
$screen = 'الاجهزة';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة اجهزة البصمة';
// }else{
// $page_title = 'إضافة جهاز';
// }

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);



$form_title = 'إضافة اجهزة جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT id as FingerprintID, branch_id as BranchID, name as FingerprintName, 
                vendor as FingerprintType,
                CASE status 
                    WHEN 'active' THEN 1
                    WHEN 'inactive' THEN 2
                    ELSE 2
                END as FingerprintState,
                serial_number as FingerprintSerailnumber, ip_address as ip, port
                FROM fingerprint_devices 
                WHERE id = :id 
                LIMIT 1 ";

	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $client_no
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		$client_id = $row['FingerprintID'];
		$form_title = 'تعديل جهاز ' .$row['FingerprintName'].'';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("fingerprint-list"); </script>';
		die();
	}
	
	
	    function GetlastID($connect, $id) {
        $sql = "SELECT device_id FROM fingerprint_device_users WHERE device_id = :id LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        }
        $sql = "SELECT fingerID FROM tblremewal WHERE FIND_IN_SET(:id, fingerID) LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
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
					<h4 class="card-title">تفاضيل الجهاز</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-12 col-md-6">
							<label class="col-form-label required"  for="devicetname">اسم الجهاز</label>
							<input type="text"  value="<?=!empty($row['FingerprintName']) ? $row['FingerprintName'] : ''?>" class="form-control"  data-toggle="tooltip"  id="devicetname" name="devicetname"  autocomplete="off" required>
						 </div>
						 <div class="form-group col-12 col-md-6">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list[]"<?php echo !isset($_GET['id']) ? "multiple='multiple'" : ''; ?> required >
                                         <?php
                              if (isset($_GET['id']) && GetlastID($connect_pdo,$client_no)) {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">هذا الجهاز تم ربطه بموظفين لايمكن تغير الفرع</label>
                                 
                                 <?php
                              } else {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  foreach ($allowed_branches as $id => $name) {
                                      echo '<option value="' . $id . '">' . $name . '</option>';
                                  }
                                  ?>
</select>
                                  <?php
                              }
                              ?>
                                    
                              </div>
                        
                      </div>
                      
					 
					 <div class="row">
                             
                              <div class="form-group col-12 col-md-6">
                                <label class="col-form-label required" for="decvicestate">حالة الجهاز</label>
                                  <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="حالة الجهاز" id="decvicestate" name="decvicestate" required>
                                  <option value="1" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='1' ? 'selected' : ''?>>شغال</option>
                                  <option value="2" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='2' ? 'selected' : ''?>>موقف</option>
                                  <option value="3" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='3' ? 'selected' : ''?>>جاري الصيانة</option>
                                  </select>
                              </div>
							  <div class="form-group col-12 col-md-6">
							<label class="col-form-label "  for="devicetype">نوع الجهاز(اسم الشركة)</label>
							<input type="text"  value="<?=!empty($row['FingerprintType']) ? $row['FingerprintType'] : ''?>" class="form-control"  data-toggle="tooltip"  id="devicetype" name="devicetype"  autocomplete="off" >
						 </div>
						</div>
                        <div class="row">
                        
                         <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="deviceserialnumber">الرقم التسلسلي حق الجهاز</label>
							<input type="text"  value="<?=!empty($row['FingerprintSerailnumber']) ? $row['FingerprintSerailnumber'] : ''?>" class="form-control"  data-toggle="tooltip"  id="deviceserialnumber" name="deviceserialnumber"  autocomplete="off" >
						 </div>
						 <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="ip">(IP) الجهاز</label>
							<input type="text"  value="<?=!empty($row['ip']) ? $row['ip'] : ''?>" class="form-control"  data-toggle="tooltip"  id="ip" name="ip"  autocomplete="off" placeholder="مثال: 192.168.1.100">
						 </div>
						 <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="port">(port) البوابة</label>
							<input type="text"  value="<?=!empty($row['port']) ? $row['port'] : '4370'?>" class="form-control"  data-toggle="tooltip"  id="port" name="port"  autocomplete="off" placeholder="4370">
						 </div>
                           
                       </div>

                       <?php if(isset($_GET['id'])): ?>
                       <!-- Device Actions (only show when editing) -->
                       <div class="row mt-3">
                           <div class="col-12">
                               <div class="card card-outline card-info">
                                   <div class="card-header">
                                       <h5 class="card-title m-0"><i class="fas fa-cogs"></i> إجراءات الجهاز</h5>
                                   </div>
                                   <div class="card-body">
                                       <div class="row">
                                           <div class="col-12 col-md-6 col-lg-4 mb-2">
                                               <button type="button" class="btn btn-info btn-block" id="test-connection">
                                                   <i class="fas fa-plug"></i> اختبار الاتصال
                                               </button>
                                           </div>
                                           <div class="col-12 col-md-6 col-lg-4 mb-2">
                                               <button type="button" class="btn btn-primary btn-block" id="sync-attendance">
                                                   <i class="fas fa-sync"></i> مزامنة الحضور
                                               </button>
                                           </div>
                                           <div class="col-12 col-md-6 col-lg-4 mb-2">
                                               <button type="button" class="btn btn-secondary btn-block" id="view-sync-history">
                                                   <i class="fas fa-history"></i> سجل المزامنة
                                               </button>
                                           </div>
                                       </div>
                                       <div id="connection-status" class="mt-3" style="display:none;">
                                           <div class="alert" role="alert"></div>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                       <?php endif; ?>

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

$(document).ready(function(){


const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');

$(document).on('click', '#save-shift', function(){
	$('#AddShift').trigger('submit');
});




$('#AddShift').on('submit', function(e){  
    e.preventDefault();
	var form_data = $(this).serialize() + '&id=' + param_id;
	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=fingerprint-add",
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
                   window.location.href = 'fingerprint-view?id='+data.id+'';
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

	$('#ip').on('input', function() {
    let value = $(this).val();
    
    // إزالة أي أحرف غير رقمية أو نقاط
    value = value.replace(/[^0-9.]/g, '');
    
    // تقسيم العنوان إلى أجزاء
    let parts = value.split('.');
    
    // تقييد كل جزء بـ 3 خانات كحد أقصى وتعديل القيم > 255
    parts = parts.map(part => {
        if (part.length > 3) part = part.substring(0, 3); // لا يزيد عن 3 خانات
        if (parseInt(part) > 255) part = "255"; // لا يتجاوز 255
        return part;
    });
    
    // منع أكثر من 4 أجزاء (مثل 192.168.1.1.1)
    if (parts.length > 4) parts = parts.slice(0, 4);
    
    // إعادة تجميع الأجزاء
    $(this).val(parts.join('.'));
});
$('#port').on('input', function() {
    let value = $(this).val();
    value = value.replace(/[^0-9]/g, '');
    $(this).val(value);
});

// Device Action Buttons (only when editing)
if (param_id) {
    // Test Connection
    $('#test-connection').on('click', function() {
        var btn = $(this);
        var statusDiv = $('#connection-status');
        var alertDiv = statusDiv.find('.alert');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الاختبار...');
        
        $.ajax({
            url: "hr-app/index.php?action=fingerprint-test",
            method: "POST",
            data: { device_id: param_id },
            dataType: "json",
            success: function(data) {
                statusDiv.show();
                if (data.result) {
                    alertDiv.removeClass('alert-danger').addClass('alert-success');
                    alertDiv.html('<i class="fas fa-check-circle"></i> ' + data.msg + 
                        '<br><small>زمن الاستجابة: ' + data.data.latency_ms + ' مللي ثانية</small>');
                    toastr.success(data.msg);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert-danger');
                    alertDiv.html('<i class="fas fa-times-circle"></i> ' + data.msg);
                    toastr.error(data.msg);
                }
            },
            error: function() {
                statusDiv.show();
                alertDiv.removeClass('alert-success').addClass('alert-danger');
                alertDiv.html('<i class="fas fa-times-circle"></i> حدث خطأ غير متوقع');
                toastr.error('حدث خطأ غير متوقع');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> اختبار الاتصال');
            }
        });
    });

    // Sync Attendance
    $('#sync-attendance').on('click', function() {
        var btn = $(this);
        
        Swal.fire({
            title: 'مزامنة الحضور',
            html: '<div class="form-group text-right">' +
                '<label>من تاريخ:</label>' +
                '<input type="date" id="sync-from" class="form-control" value="' + 
                new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0] + '">' +
                '</div>' +
                '<div class="form-group text-right">' +
                '<label>إلى تاريخ:</label>' +
                '<input type="date" id="sync-to" class="form-control" value="' + 
                new Date().toISOString().split('T')[0] + '">' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-sync"></i> بدء المزامنة',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#007bff',
            preConfirm: () => {
                return {
                    from_date: document.getElementById('sync-from').value,
                    to_date: document.getElementById('sync-to').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري المزامنة...');
                
                $.ajax({
                    url: "hr-app/index.php?action=fingerprint-sync",
                    method: "POST",
                    data: { 
                        device_id: param_id,
                        from_date: result.value.from_date,
                        to_date: result.value.to_date
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.result) {
                            toastr.success(data.msg);
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بدء المزامنة',
                                text: data.data.message,
                                confirmButtonText: 'حسناً'
                            });
                        } else {
                            toastr.error(data.msg);
                        }
                    },
                    error: function() {
                        toastr.error('حدث خطأ غير متوقع');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> مزامنة الحضور');
                    }
                });
            }
        });
    });

    // View Sync History
    $('#view-sync-history').on('click', function() {
        $.ajax({
            url: "hr-app/index.php?action=fingerprint-info",
            method: "POST",
            data: { device_id: param_id },
            dataType: "json",
            success: function(data) {
                if (data.result && data.data.sync_history) {
                    var history = data.data.sync_history;
                    var html = '<table class="table table-sm table-bordered"><thead><tr>' +
                        '<th>التاريخ</th><th>النوع</th><th>من</th><th>إلى</th><th>الحالة</th></tr></thead><tbody>';
                    
                    if (history.length > 0) {
                        history.forEach(function(item) {
                            var statusBadge = item.status === 'completed' ? 
                                '<span class="badge badge-success">مكتمل</span>' :
                                item.status === 'failed' ? 
                                '<span class="badge badge-danger">فشل</span>' :
                                '<span class="badge badge-warning">قيد الانتظار</span>';
                            
                            html += '<tr><td>' + item.created_at + '</td>' +
                                '<td>' + item.sync_type + '</td>' +
                                '<td>' + (item.from_date || '-') + '</td>' +
                                '<td>' + (item.to_date || '-') + '</td>' +
                                '<td>' + statusBadge + '</td></tr>';
                        });
                    } else {
                        html += '<tr><td colspan="5" class="text-center">لا يوجد سجل مزامنة</td></tr>';
                    }
                    html += '</tbody></table>';
                    
                    Swal.fire({
                        title: 'سجل المزامنة',
                        html: html,
                        width: '600px',
                        confirmButtonText: 'إغلاق'
                    });
                } else {
                    toastr.info('لا يوجد سجل مزامنة لهذا الجهاز');
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء جلب السجل');
            }
        });
    });
}

});
 

</script>
