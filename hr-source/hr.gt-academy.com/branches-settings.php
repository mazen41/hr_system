<?php

//$fix_foot = false;
$screen = 'الفروع';
$page_title = 'إعدادات الفروع';
$only_main_branch = true;

$app = "BRA";
 include_once('inc/header.php');
 //include_once('sheard/settings_fun.php');
 include_once('sheard/settings-fun.php');
 
 /* function get_settings($connect,$account,$app)
	{
			$setting = array();
			$custom_setting = [];
			
			$custom_query = "SELECT * FROM apps_settings_custom  WHERE account = '".$account."' AND app= '".$app."'  ";
			$c_stm = $connect->prepare($custom_query);
			$c_stm->execute();
			if($c_stm->rowCount() > 0){
				$c_rows = $c_stm->fetchAll();
				foreach($c_rows as $row){
						$custom_setting += [$row['option_code'] => $row['value']  ];
				}
			}
			$general_query = "SELECT * FROM apps_settings  WHERE app ='".$app."' AND  stopped is null AND custom_ou is null  ";
			$g_stm = $connect->prepare($general_query);
			$g_stm->execute();
			if($g_stm->rowCount() > 0){
				$g_rows = $g_stm->fetchAll();
				foreach($g_rows as $row){
					if(array_key_exists($row['option_code'], $custom_setting)){
						//$value = array_search($custom_setting, $row['option_code']);
						$setting [$row['option_code']]= ['title' => $row['option_title'],'value' => $custom_setting[$row['option_code']] , 'code' => $row['option_code'] ];
					}else{
						$setting [$row['option_code']]= ['title' => $row['option_title'],'value' => $row['value'] , 'code' => $row['option_code']];
					}

				}
			}
			
		return $setting;

	} */
 
	 
?>

    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">الإعدادت العامة</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	

			<button type="button" class="btn btn-default" onclick="history.back()" id="cancel-bt"><i class="fa fa-times"></i><span class="d-none d-sm-inline"> إلغاء</span></button>
			
			<button type="button" class="btn btn-success"  id="save-data"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> حفظ</span></button>

          </div>
        </div>
      </div>
    </div>
 
	
		
	

    <!-- Main content -->
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
	<div class="card p-3 mb-3 col-md-12" id="">
	<form class="form-horizontal" role="form" action="#" method="post" id="create_fm">
			<div class="row">
				
				<?php
				$app_setting = get_settings($connect_pdo,$branch,$app);
				/*  echo '<pre>';
				print_r ($app_setting);
				echo '</pre>';  */
				foreach($app_setting as $setting){
                        $app_options[$setting['code']]= !empty($setting['value']) ? 1 : 0;
                        
                         /* $app_options[$setting['code']]['code']= $setting['code'];
                        $app_options[$setting['code']]['value']= $setting['value'];
                        $app_options[$setting['code']]['url']= $setting['url']; */
                        echo '';
                        if(!$setting['option_type']){
                        echo '<div class="col-12"><div class="form-group">
                                <label class="switch switch-info switch-md">
								 <input type="checkbox" class="chk_options" value="'.$setting['code'].'"  id="'.$setting['code'].'" '.(!empty($setting['value']) ? 'checked':'').'>
								 <span></span>
							  </label>
						   <label class="control-label" for="'.$setting['code'].'"> '.$setting['title'].'</label>
                           
                           <span class="'.$setting['code'].'">
                           '.(!empty($setting['url']) ? (!empty($setting['value']) ? '<span>'.$setting['url'].'</span>' : '<span style="display:none">'.$setting['url'].'</span>') : '<span></span>').'
                           </div></div>
                           </span>
                           
						   ';
                        }elseif($setting['option_type'] == 'text'){
                             echo '<div class="form-group col-md-12">
                                    <label class="control-label" for="'.$setting['code'].'">'.$setting['title'].'</label>
                                    <textarea class="form-control long_text" rows="3" id="'.$setting['code'].'"  placeholder="" name="'.$setting['code'].'" data-toggle="tooltip" title="'.$setting['title'].'" >'.$setting['value'].'</textarea>
                                   
                                  </div>';
                        }
                        
                        echo '';

				}
				
				//echo $app_setting['multiple_units']['title'];
				?>

					  
			 </div>
             
			 
			</form> 
            <?php
           /*  echo'<pre>';
             print_r($app_options);
             echo'</pre>';  */
            ?>
            <br>
			<div class="overlay" style="display:none" id="add_holdon"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
			</div> 
			
			
	</div>
			
	
    </section>





<?php
 include_once('inc/footer.php');
?>  



<script>


var app_options  = <?php echo json_encode($app_options)?>;
$(document).ready(function(){
console.log(app_options);

$('.chk_options').click(function() {
	if($(this).is(':checked')){
       app_options[$(this).val()] = 1;
       
      /*  if(app_options[$(this).val()]){
           
       } */
      
	}else{
        app_options[$(this).val()] = 0;
       $('.'+app_options[$(this).val()]).hide();
	}
   // alert(app_options)
   console.log(app_options);
   
});

$('.long_text').change(function() {
       app_options[$(this).attr('id')] = $(this).val();
	
   // alert( $(this).attr('id'));
   console.log(app_options);
});


/* $('.long_text').change(function() {
     var lines = $(this).val().split(/\n/);
     var texts = [];
     for (var i=0; i < lines.length; i++) {
       // only push this line if it contains a non whitespace character.
       if (/\S/.test(lines[i])) {
         texts.push($.trim(lines[i]));
       }
     }
   console.log(texts);
}); */




$('#create_fm').on('submit', function(e){  
	e.preventDefault();
   
	//var form_data = $(this).serialize();
	 $.ajax({
		type: 'POST',
		url:"./sheard/update-app-settings",
		//data: $("#create_fm").serialize(),
		data:{ br:<?php echo json_encode($branch)?>, options:app_options,app:<?php echo json_encode($app)?>},
		dataType:"json",
		beforeSend:function(){
			$('#add_holdon').show();
			$('#save-data').prop('disabled', true);
		}, 
		success:function(data){
            if(data !=''){
			if(data.result)
			{
				//toastr.success(data.msg);
				//history.back();
                 location.reload();
			}else{
                toastr.error(data.msg);
                $('#add_holdon').hide();
                $('#save-data').prop('disabled', false);
            }
            }
			
			
		}
	}); 
	
});




$(document).on('click', '#save-data', function(){
	$('#create_fm').trigger('submit');

});


	
	


});
</script>