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
		$query = "	SELECT 	Id,UserID, TitleJob, side,	StartDate,EndDate,FilePath,JobTasks
		From user_exper 
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
          <a href="user-certifacte?id=<?=  $_GET['id']; ?>"><button type="button" class="btn btn-success"  id="save-data"><i class="fas fa-save"></i><span class="d-none d-sm-inline">تحديث واغلاق</span></button></a>
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
<!--  -->

<!--  -->

		<!-- <div class="row"> -->
			
	<form class="form-horizontal" role="form" action="#" method="post" id="user_fm_exp" enctype="multipart/form-data">
	<!--  -->
	<div id="tab6" class="tab-content">
		<div class="col-md-12">
		<div class="card ">
			<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
				<h3 class="card-title">تفاصيل  عن خبرات الموظف</h3>
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
				  <!-- text input -->
				  <div class="form-group">
					<label class="col-form-label" for="emp_Job">المسمى الوظيفي</label>
					<input type="text" class="form-control " data-toggle="tooltip" title="ادخل المسمى الوظيفي" id="emp_Job" name="emp_Job" placeholder=""  >
				  </div>
				</div>
				<div class="col-md-5">
				  <div class="form-group">
					<label class="col-form-label " for="emp_JobCom">اسم الجهة</label>
					<input type="text" name="emp_JobCom" class="form-control"  placeholder="اسم الجهة" id="emp_JobCom" autocomplete="off" value="">
				  </div>
				</div>
			</div>
			<div class="row">		  
				<div class="col-md-4">
				  <!-- text input -->
				  <div class="form-group">
					<label class="col-form-label" for="emp_Job_start_date">تاريخ البدء</label>
					<!-- <input type="date" class="form-control  " data-toggle="tooltip" title="تاريخ البدء" id="emp_Job_start_date" name="emp_Job_start_date" placeholder=""  value="" > -->
          <input type="text" name="emp_Job_start_date" class="form-control input-date"  placeholder="تاريخ البدء" id="emp_Job_start_date" autocomplete="off" value="" >
                                
        </div>
				</div>
				<div class="col-md-4">
				  <div class="form-group">
					<label class="col-form-label" for="emp_Job_end_date">تاريخ الانتهاء</label>
          <input type="text" name="emp_Job_end_date" class="form-control input-date"  placeholder="تاريخ الانتهاء" id="emp_Job_end_date" autocomplete="off" value="" >
				  </div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
					  <label class="col-form-label" for="emp_jobFile">شهادة الخبرة</label>
					  <input type="file" name="emp_jobFile" class="form-control"  placeholder="الملف" id="emp_jobFile" autocomplete="off" value="">
					</div>
				  </div>
			</div>
            <input type="hidden" name="id_edite" id="id_edite" value="">
			<div class="row">
				<div class="form-group col-md-12">
					 <label class="col-form-label "  for="JObDeteils">المهام الوظيفية</label><br>
					 <textarea name="JObDeteils" id="JObDeteils" rows="10" style="resize: none; width: -webkit-fill-available;"></textarea>
				  </div>
			</div>
			<button type="button" id="addButton" class="btn btn-primary" name="addButton">إضافة</button>
			<button type="button" id="updateButton__" class="btn btn-success" style="display: none;">تحديث</button>
			

			
			<h3>خبرات الموظف</h3>
			<table id="experienceTable" class="table table-bordered mt-2" >
				<thead>
					<tr>
						<th>المسمى الوظيفي</th>
						<th>اسم الجهة</th>
						<th>تاريخ البدء</th>
						<th>تاريخ الانتهاء</th>
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
                    <td><?php echo htmlspecialchars($row['TitleJob']); ?></td>
                    <td><?php echo htmlspecialchars($row['side']); ?></td>
                    <td><?php echo htmlspecialchars($row['StartDate']); ?></td>
                    <td><?php echo htmlspecialchars($row['EndDate']); ?></td>
                    <td style="display:none;"><input type="hidden" name="task" id="task" value="<?=(!empty($row['JobTasks']) ? $row['JobTasks'] :'')?>"></td>
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
		</form>
		</div>
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


$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const param_id = urlParams.get('id');

    $(document).on('click', '#addButton', function() {
        $('#user_fm_exp').trigger('submit');
    });

    $(document).on('click', '#updateButton__', function() {
        $('#user_fm_exp').trigger('submit');
    });

    $('#user_fm_exp').on('submit', function(e) {
        e.preventDefault();

        var form_data = new FormData(this);
        form_data.append('id', param_id);

        // إذا كانت العملية إضافة أو تعديل
        var action = ($('#addButton').is(':visible')) ? 'add' : 'edit';
        form_data.append('action', action);

        $.ajax({
            type: 'POST',
            url: "hr-app/index.php?action=UserExperince-add",
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
            action: "remove",
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
    $('#experienceTable').on('click', '#update_', function() {
        const row = $(this).closest('tr');
        editIndex_Cer = row.index();
        $('#emp_Job').val(row.find('td').eq(0).text());
        $('#emp_JobCom').val(row.find('td').eq(1).text());
        $('#emp_Job_start_date').val(row.find('td').eq(2).text());
        $('#emp_Job_end_date').val(row.find('td').eq(3).text());
        $('#JObDeteils').val(row.find('td').eq(4).find('input').val()); // تأكد من أن هذه الخلية تحتوي على النص فقط
        
        $('#id_edite').val(row.find('td').eq(5).find('input').val()); // تأكد من أن هذه الخلية تحتوي على النص فقط
        $('#addButton').hide();
        $('#updateButton__').show();
    });
});

 

</script>





