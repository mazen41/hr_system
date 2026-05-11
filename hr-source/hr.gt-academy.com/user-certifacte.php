<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'اضافة موظف';
 include_once('inc/header.php');

$over = 0;
$all_branches =  $User->allBranches();
$employee=Null;
if(isset($_GET['id'])){
	$id = (int)$_GET['id'];
	
	$parma = array(':id'  => $id, );	
		$query = "	SELECT 	Id,UserID,Certifacte_name,Side,StartDate,FilePath
		From user_cer 
		where UserID  =:id 
		";
		
	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);
	
	if($stm->rowCount() > 0)
    {
		$employee = $stm->fetchAll();
	}

 
	



?>

<script>
</script>
<style>	
label.error{
	color: red;
    font-size: 0.83rem;
}
 .bootstrap-select .dropdown-toggle:focus{
    outline: unset !important;
    outline: unset !important;
    outline-offset: unset !important;
 }
 .tabs {
    display: flex;
}

.tab-button {
    padding: 5px 10px;
    cursor: pointer;
    background-color:rgb(255, 255, 255);
    border: 1px solid #ccc;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.tab-button:hover {
    background-color: #ddd;
}

.tab-content {
    /* display: none; */
    border-top: none;
    margin-top: -1px;
}

.tab-content.active {
    display: block;
}
#updateButton
{display: none;}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title"><?=$page_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">		
			<a href="user-certifacte?id=<?=  $_GET['id']; ?>"><button type="button" class="btn btn-success"  id="save-data"><i class="fas fa-save"></i><span class="d-none d-sm-inline">تحديث وحفظ</span></button></a>
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

<form class="form-horizontal" role="form" action="#" method="post" id="user_fm_cer" enctype="multipart/form-data">
<div id="tab7" class="tab-content">
	<div class="col-md-12">
	<div class="card ">
		<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
			<h3 class="card-title">تفاصيل  عن شهادة الموظف</h3>
			<div class="card-tools">
			  <button type="button" class="btn btn-tool" data-card-widget="collapse">
				<i class="fas fa-minus"></i>
			  </button>
			</div>
		</div>
		
		<div class="card-body" >
		
		<input type="hidden" value="" name="">

		  <div class="row">		  
			<div class="col-md-5">
			  <div class="form-group">
				<label class="col-form-label" for="emp_C">اسم الشهادة</label>
				<input type="text" class="form-control " data-toggle="tooltip" title="ادخل اسم الشهادة" id="emp_C" name="emp_C" placeholder=""  value="" >
			  </div>
			</div>
			<div class="col-md-5">
			  <div class="form-group">
				<label class="col-form-label" for="emp_CerCom">اسم الجهة</label>
				<input type="text" name="emp_CerCom" class="form-control"  placeholder="اسم الجهة " id="emp_CerCom" autocomplete="off" value="">
			  </div>
			</div>
		</div>
		<div class="row">		  
			<div class="col-md-5">
			  <div class="form-group">
				<label class="col-form-label " for="emp_Cer_date">تاريخ الاصدار</label>
				<!-- <input type="date" class="form-control  " data-toggle="tooltip" title="تاريخ الاصدار" id="emp_Cer_date" name="emp_Cer_date" placeholder=""  value="" > -->
                <input type="text" name="emp_Cer_date" class="form-control input-date"  placeholder="تاريخ الاصدار" id="emp_Cer_date" autocomplete="off" value="" >
			  </div>
			</div>
            <input type="hidden" name="id_edite" id="id_edite" value="">
			<div class="col-md-5">
				<div class="form-group">
				  <label class="col-form-label " for="emp_CerFile">مرفق الشهادة</label>
				  <input type="file" name="emp_CerFile" class="form-control"  placeholder="مرفق الشهادة" id="emp_CerFile" autocomplete="off" value="">
				</div>
			  </div>
		</div>
		<button type="button" id="addButton" class="btn btn-primary">إضافة</button>
		<button type="button" id="updateButton__" class="btn btn-success" style="display: none;">تحديث</button>
		

		
		<h3>شهائد الموظف</h3>
		<table id="CertifacteTable" class="table table-bordered mt-2">
			<thead>
				<tr>
					<th>اسم الشهادة</th>
					<th>اسم الجهة</th>
					<th>تاريخ الاصدار</th>
                    <th style="display:none;"></th>
                    <th style="display:none;"></th>
					<th>الملف</th>
                    <th>الاجرات</th>
				</tr>
			</thead>
			<tbody>
            <?php
             if(!empty($employee)){
             foreach ($employee as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Certifacte_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Side']); ?></td>
                    <td><?php echo htmlspecialchars($row['StartDate']); ?></td>
                    <td style="display:none;"><input type="hidden" name="ID_detail" id="ID_detail" value="<?=htmlspecialchars($row['Id']);?>"></td>
                    <td >
                    <a href="<?=(!empty($row['FilePath']) ? $row['FilePath'] :'')?>" download>
                    <button type="button" value="<?=(!empty($row['Id']) ? $row['Id'] :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                    </a>
                       
                    </td> 
                    <td>
                    <button type="button" value="<?=(!empty($row['Id']) ? $row['Id'] :'')?>" class="btn btn-xs btn-default" id="update_">تعديل </button>
					<button type="button" value="<?=(!empty($row['Id']) ? $row['Id'] :'')?>" class="btn btn-xs btn-danger remove_exper">حذف</button>
                   </td> 
                    
                </tr>
            <?php endforeach; 
             }
            ?>
			</tbody>
		</table>
		</div>
		
	</div>

</div>


</div>
			<!--  -->
		</form>
		</div>
				
			
		
		<!-- </div> -->
		
	</div>

			
    </section>





<?php
 include_once('inc/footer.php');
}
else
{
?>
<script>window.location.href = 'dashboard';</script>
<?php
}
?>

<script>



$(document).ready(function(){
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');

$(document).on('click', '#addButton', function() {
    $('#user_fm_cer').trigger('submit');
});

$(document).on('click', '#updateButton__', function() {
    $('#user_fm_cer').trigger('submit');
});
$('#user_fm_cer').on('submit', function(e){  
    e.preventDefault();

var form_data = new FormData(this);
form_data.append('id', param_id);

// إذا كانت العملية إضافة أو تعديل
var action = ($('#addButton').is(':visible')) ? 'add' : 'edit';
form_data.append('action', action);

$.ajax({
    type: 'POST',
    url: "hr-app/index.php?action=UserCertifcate-add",
    data: form_data,
    contentType: false,
    processData: false,
    dataType: "json",
    beforeSend: function() {
        $('#preloading').show();
    },
    success: function(data) {
        if (data.result) {
            $("#user_id").val(data.emp_id);
            window.location.reload(); // لإعادة تحميل الصفحة
        } else {
            toastr.error(data.msg);
            $('#preloading').hide();
        }

        if (data.reload) {
            window.location = document.URL;
        }
    }
});
});

// remove
$(document).on('click', '.remove_exper', function(e) {
    e.preventDefault();
    var id_ = $(this).val();  
    $.ajax({
        type: 'POST',
        url: "hr-app/index.php?action=remove_Exper_cer",
        data: {
            action: "remove_cer",
            id_delete: id_ 
        },
        dataType: "json",
        beforeSend: function() {
            $('#preloading').show();
        },
        success: function(data) {
            if (data.result) {
                window.location.reload(); 
            } else {
                toastr.error(data.msg);
                $('#preloading').hide();
            }

            if (data.reload) {
                window.location = document.URL;
            }
        },
        error: function(xhr, status, error) {
            toastr.error('حدث خطأ أثناء حذف البيانات.');
            $('#preloading').hide();
        }
    });
});

    // edit experience
    $('#user_fm_cer').on('click', '#update_', function() {
        const row = $(this).closest('tr');
        editIndex_Cer = row.index();
        $('#emp_C').val(row.find('td').eq(0).text());
        $('#emp_CerCom').val(row.find('td').eq(1).text());
        $('#emp_Cer_date').val(row.find('td').eq(2).text());        
        $('#id_edite').val(row.find('td').eq(3).find('input').val()); // تأكد من أن هذه الخلية تحتوي على النص فقط
        $('#addButton').hide();
        $('#updateButton__').show();
    });
});
 
 

</script>





