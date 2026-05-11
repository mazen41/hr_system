<?php
$screen = 'المستخدمين';
$page_title = 'الأدوار الوظيفية';
 include_once('inc/header.php');
 $only_active = true;
if(isset($_GET['id']) && !empty($_SESSION['branch'])){
	$id = (int)$_GET['id'];
	$only_active = false;
	$parma = array(
				':id'  => $id, 
				//':branch' => (isset($_GET['branch']) && !empty($_GET['branch']) ?  (int)$_GET['branch'] : $_SESSION['branch'] )
			);
	//$query = "SELECT * FROM tblsimpleentries WHERE SimpleEntryID = :id and BranchID = :branch LIMIT 1 ";
			
		$query = "SELECT g.GroupID, g.GroupNumber,g.FullAccess, g.GroupName, g.GroupDesc, g.IsDisabled, g.BranchID , b.branch_name
		FROM tblusergroups g
		JOIN branches b on b.branch_id = g.BranchID
		WHERE g.IsSystem is null AND  g.GroupID= :id 
		
		";
      //  if($_SESSION['role'] != 'owner' ){
            $in = "";
			$i = 0;
        foreach($User->allBranches($User->branches) as $bran){
            
                $key =":allowedBranchies".$i++;
                $in .="$key,";
                $in_params[$key] = $bran;
			}
            
			$in = rtrim($in,",");
			
			$query .= "  OR b.branch_id IN ($in) ";
			$parma = array_merge($parma,$in_params);
            
     //   }    
        
		/* if($_SESSION['role'] != 'owner' ){
		 
		 $query .= " AND  (g.BranchID = :userbranch  ";
		 $parma += ["userbranch" => $_SESSION['branch']];
		 
		if(!empty($_SESSION['user']['extraBranchies'])){
			//$allowedBranchies = implode("','", $extra_branchies);
			$in = "";
			$i = 0;
			foreach($_SESSION['user']['extraBranchies'] as $bran)
			{
					$key =":allowedBranchies".$i++;
					$in .="$key,";
					$in_params[$key] = $bran;
			}
			$in = rtrim($in,",");
			
			$query .= "  OR b.branch_id IN ($in) ";
			$parma = array_merge($parma,$in_params);
			
		}
			$query .= "  ) ";
		
	} */
	
	$query .= "  LIMIT 1 ";
		

	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);
	
	if($stm->rowCount() > 0){
		$group = $stm->fetch();
		 $group_id = $group['GroupID'];

		
		
	}else{
		//echo'<script> location.replace("users-group-list"); </script>';
		//die();
		
	}
	
}


// Get Branch Apps

	$q_apps= " SELECT DISTINCT
	tblbranchesapps.BranchID,tblbranchesapps.AppID, apps.AppName, apps.AppIcon
	FROM tblbranchesapps 
	LEFT JOIN apps  ON apps.AppID = tblbranchesapps.AppID  
	LEFT JOIN branches  ON branches.branch_id = tblbranchesapps.BranchID
	WHERE branches.isdefault is not null
	AND apps.IsRrequred is null 
	AND apps.Disabled is null
	order by apps.Sort ";
	$stm_apps = $connect_pdo->prepare($q_apps);
	$stm_apps->execute();
	
	if($stm_apps->rowCount() > 0){
		$ou_apps = $stm_apps->fetchAll();
	}
	
	

	
function getActions ($account,  $only_active = null){
	
	$actions = [];
		$q = "SELECT PermID, AppID, PermName, Menus,Parent FROM tblpermission where 1=1 ";
        if(!empty($only_active)){
            $q .= "AND IsDisabled is null";
        }
		
		
		  $stmt = $account->prepare($q);
		  $stmt->execute();
		  $rows = $stmt->fetchAll();
		  /* if (!empty($rows))
			{
												 
				foreach($rows as $row){
					//$branches .= (int)$row['branch_id'].",";
					//$branches []= (int)$row['branch_id'];
					$actions[$row['link']]= [];
					$actions[$row['link']][]= $row['ActionCode'];
				}
				//return implode(',',$branches);
			} */
		  return $rows;
		 // return $actions;
}

$actionsRole = getActions($connect_pdo,$only_active);




function getCureGroupRoles ($connect, $group_id){
	
	$actions = [];
		//$q = "SELECT ActionID, menu_id FROM roles2 where group_id = :group_id ";
		$q = "SELECT PermID FROM tblgroupsperm where GroupID = :group_id ";
		
			$actions = [];
		  $stmt = $connect->prepare($q);
		  $stmt->execute(['group_id' => (int)$group_id]);
		  $rows = $stmt->fetchAll();
		   if (!empty($rows))
			{
												 
				foreach($rows as $row){
					$actions['actions'][]= $row['PermID'];
					//$actions['actions']= $row['PermID'];
					//$actions['menu'][]= $row['menu_id'];
				}
                return $actions;
				//return implode(',',$branches);
			} 
		  return $actions;
		 // return $actions;
}



?>
<style>
.card-body .custom-checkbox{
    display: inline-block;
    /* margin-left: 30px; */
    min-width: 49%;
    max-width: 49%;
    padding-top: 20px;
}
.custom-checkbox .fa{
    color:#e0e0e0;
    font-size: small;
}
.tree-item { position: relative; padding-left: 20px; margin: 5px 0; }
.tree-children { margin-left: 25px; border-left: 1px dashed #ccc; }

.tree-toggle {
    position: absolute;
    left: 0;
    top: 16px;
    cursor: pointer;
    font-weight: bold;
    width: 15px;
    font-size: 20px;
}
</style>
 <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-md-7">
            <span class="page-title"><?=(!empty($group['GroupID'])? 'تعديل دور وظيفي | '.$group['GroupName'].' ' : 'إنشاء دور وظيفي')?> </span>
			<?=(!empty($group['IsDisabled']) ? '<span class="badge badge-warning"> مُوقف </span>': '' );?>
          </div><!-- /.col -->
		  
		 
          <div class="col-md-5 text-left">	
			
			<button type="button" class="btn btn-default" onclick="history.back()" id="cancel-bt"><i class="fa fa-share"></i><span class="d-none d-sm-inline"> عودة</span></button>
			
			<button type="button" class="btn btn-success"  id="save_data"><i class="fa fa-save"></i><span class="d-none d-sm-inline"> حفظ</span></button>

          </div>
        </div>
      </div>
    </div>
	
<section class="content">
		<div class="container-fluid">
    <!-- Content Header (Page header) -->
	

	
	
	<?php
	
		/* if(!empty($_POST) && 1 == 2){
		
		//$action = (int) $_POST['action'];
	echo '<br>-------------<br>';
	
	$group_id = (int) $_POST['group_id'];
	$msg = '';	
			
	//function addnewrole ($connect, $group_id,$menu_id,$action_id){
	function addnewrole ($connect, $group_id,$action_id){
		
		$parma = array(
			'group_id'	=>	(int) $group_id,
			//'menu_id'	=>	(int) $menu_id,
			'action_id'	=>	(int) $action_id
		);
		$actions = [];
            $q = "insert into tblgroupsperm SET
					GroupID = :group_id,
                   // menu_id = :menu_id,
					PermID = :action_id
			";

			  $stmt = $connect->prepare($q);
			  $stmt->execute($parma);
			//  $stm->rowCount() > 0
			  return $stmt->rowCount();
	}
	
	function removeRoles ($connect, $group_id){
		global $connect_pdo;
		
		$parma = array(
			'group_id'	=>	(int) $group_id
		);
			//$q = " DELETE FROM roles2 WHERE group_id = :group_id ";
			$q = " DELETE FROM tblgroupsperm WHERE GroupID = :group_id ";
			$stmt = $connect->prepare($q);
			$stmt->execute($parma);
			
			//remove Group Sessions;
			$query_session = " DELETE FROM tblsessions WHERE GroupID = :group_id ";
			$rem = $connect->prepare($query_session);
			$rem->execute(['group_id'	=>	(int) $group_id]);
	}
		
		
		if(!empty($_POST['group_name']) && !empty($_POST['group_id']) && !empty($_POST['action']))
		{
			$error = '';
			try {
				$connect_pdo->beginTransaction();

				$remove_roles = removeRoles($connect_pdo, $group_id);
				
				foreach($_POST['action'] as $value){
					
					echo "value : ".$value.'<br/>';
					$action = getActions($connect_pdo, $value);

					if(!empty($action)){
						//echo'<pre>';
						//	print_r($action);
						//echo'</pre>';
						
					//	$insert_role = addnewrole($connect_pdo, $group_id, $action[0]['MenuID'],$action[0]['ActionID']);
						$insert_role = addnewrole($connect_pdo, $group_id, $action[0]['PermID']);
						
						if(empty($insert_role)){
							$error .= 'حدث خطاء في حفظ البيانات';
							end;
						}

					}else{
						$error .= '<br>حدث خطاء في تحديد الصلاحيات';
					} 
				}
				
				if(empty($error)){
					$connect_pdo->commit();
					$msg = 'تم الحفظ';
				}else{
					$connect_pdo->rollBack();
					$msg = $error;
				}
			}catch (\PDOException $e){
				$connect_pdo->rollBack();
				$msg .= 'Database query Error';
			}
		
		echo $msg;

		}
	} */
	
		if(!empty($group_id)){
			$group_roles = getCureGroupRoles($connect_pdo, $group['GroupID']);
		}
	?>
	
	<form class="form-horizontal pb-3" role="form" action="" method="post" id="group_fm">
			<input type="hidden" id="group_id" name="group_id" value="<?=(!empty($group['GroupID']) ? $group['GroupID'] :'')?>" />
		<div class=" invoice p-3 ">
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="group_name" class="col-form-label required">اسم الدور الوظيفي</label>
							<input type="text" class="form-control "   title="ادخل اسم الدور الوظيفي" id="group_name" name="group_name" placeholder="بائع مثلاً" autocomplete="off" required value="<?=(!empty($group['GroupName']) ? $group['GroupName'] :'')?>">
					</div>
				</div>
				
				<div class="col-md-6">
					<div class="form-group">
						<label for="group_desc" class="col-form-label ">وصف</label>
							<input type="text" class="form-control "   title="يمكنك كتابة اي وصف توضيحي للتذكر"  name="group_desc" placeholder="يمكنك كتابة اي وصف توضيحي للتذكر" autocomplete="off" value="<?=(!empty($group['GroupDesc']) ? $group['GroupDesc'] :'')?>" />
					</div>
				</div>
			</div>
			
			
			
			
			<div class="row">
			
				<div class="col-md-4">
					<div class="form-group">
						<label class="switch switch-info ml-auto  " title="منح الدور الوظيفي كل الصلاحيات">
							  <label class="control-label " for="admin_role"></label>
								 <input type="checkbox" name="admin_role" id="admin_role"  value="" <?=(!empty($group['FullAccess']) ? 'checked' :'')?> >
								 <span></span> صلاحيات كاملة <i class="fa fa-info-circle text-muted"></i>
						  </label>
					</div>
				</div>
			</div>

		
			
			
			
			
			
		
		<!--   -->
		</div>
		
		
		<div class="row mt-3">
				
		
		<?php
        $action_munus = [];
		foreach($ou_apps as $app){?>
			<div class="col-md-6 mb-3">
			<div class="invoice allroles" style="display:<?=(!empty($group['FullAccess']) ? 'none' :'block')?>">
				<div class="card-header" style="background: aliceblue;">
					<h5 class="card-title"><i class="fa fa-<?=$app['AppIcon']?> text-muted"></i> <?=$app['AppName']?></h5>
                            <span style="float: left;">
                            <label class="switch switch-info switch-sm " style="margin-bottom: 0;">
                              <input type="checkbox"  class="checkAll" name="follw_qty" value="<?=$app['AppID']?>"  >
								 <span></span> الكل
							  </label>
                             </span> 
				</div>
			
				<div class="card-body">
				  <div class="form-group">
					  <?php
                        
						// foreach($actionsRole as $action) 
						// {
                        //      $munus = explode ( ',', $action['Menus']);
                        //         $action_munus [$action['PermID']]= $munus;
						// 	if($action['AppID'] == $app['AppID'])
						// 	{
						// 		echo '
						// 		<div class="custom-control custom-checkbox FIN '.$app['AppID'].'">
						// 		  <input class="custom-control-input FIN " type="checkbox" name="action[]" id="'.$action['PermID'].'" data-parent="'.$action['Parent'].'" data-menu="'.$action['Menus'].'" value="'.$action['PermID'].'" '.(!empty($group_roles['actions']) && in_array($action['PermID'], $group_roles['actions']) ? 'checked' : '').' >
						// 		  <label for="customCheckbox1" class="custom-control-label "> '.$action['PermName'].' '.(!empty($action['Parent'])?'<i class="fa fa-arrows-alt-h"></i>':'').'</label>
						// 		</div>';
						// 	}
						// }

						$tree = [];
						foreach ($actionsRole as $action) {
							$munus = explode(',', $action['Menus']);
							$action_munus[$action['PermID']] = $munus;
							
							if ($action['AppID'] == $app['AppID']) {
								$parentId = $action['Parent'] ?: 0;
								$tree[$parentId][] = $action;
							}
						}

							
					  ?>
<div class="permissions-tree">
  <?php
  $renderTree = function($parentId, $tree, $group_roles, $app, $level = 0) use (&$renderTree) {
      if (!isset($tree[$parentId])) return;
      
      foreach ($tree[$parentId] as $action) {
          $hasChildren = isset($tree[$action['PermID']]);
          $isChecked = !empty($group_roles['actions']) && in_array($action['PermID'], $group_roles['actions']);
          $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level * 2);
          ?>
          <div class="tree-item" data-id="<?= $action['PermID'] ?>">
            <?php if ($hasChildren): ?>
              <span class="tree-toggle">+</span>
            <?php endif; ?>
            
            <div class="custom-control custom-checkbox FIN <?= $app['AppID'] ?>">
              <input class="custom-control-input FIN" type="checkbox" name="action[]"
                     id="<?= $action['PermID'] ?>"
                     data-parent="<?= $action['Parent'] ?>"
                     data-menu="<?= $action['Menus'] ?>"
                     value="<?= $action['PermID'] ?>" <?= $isChecked ? 'checked' : '' ?>>
              <label for="<?= $action['PermID'] ?>" class="custom-control-label">
                <?= $indent . $action['PermName'] ?> <?= !empty($action['Parent']) ? '<i class="fa fa-arrows-alt-h"></i>' : '' ?>
              </label>
            </div>
            
            <?php if ($hasChildren): ?>
              <div class="tree-children" style="display:none; margin-left:30px">
                <?php $renderTree($action['PermID'], $tree, $group_roles, $app, $level + 1); ?>
              </div>
            <?php endif; ?>
          </div>
          <?php
      }
  };
  
  $renderTree(0, $tree, $group_roles, $app);
  ?>
</div>
				  </div>
				</div>
			</div>
			</div>
		
		<?php 	}	
         //   echo '<pre>'; print_r($action_munus);echo'</pre>';
        ?>
			</div>
		
		
		
		<!--<div class="invoice mt-3">
				<div class="card-header" style="padding-bottom: 0.2rem;">
					<h5 class="card-title">الصلاحيات تجريبي</h5>
					<div class="card-tools ml-2">
					  <label class="switch switch-info" >
					  <label class="control-label " for="all_apps" >كل التطبيقات</label>
						 <input type="checkbox" name="all_apps" id="all_apps" title="" checked>
						 <span></span>
					  </label>
					</div>
				</div>
			
				<div class="card-body  " id="apps_container">

					<div class="row">		  
						<div class="col-md-12">
						  <div class="form-group">
							  <?php
								/* foreach($ou_apps as $app){
									echo '
								<div class="custom-control custom-checkbox">
								  <input class="custom-control-input" type="checkbox" name="apps[]" id="'.$app['AppID'].'" value="'.$app['AppID'].'">
								  <label for="customCheckbox1" class="custom-control-label "> '.$app['AppName'].'</label>
								</div>';
								} */
							  ?>
							</div>
						</div>
						

					  </div>
						
				</div>
		</div>-->
		
			
		</form>
			

		
      </div><!-- /.container-fluid -->
	  
    </section>
<?php
 include_once('inc/footer.php');
?>
<script>

var selectedArry = [];
$(document).ready(function(){
	
	
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');

$(document).on('click', '#save_data', function(){
	$('#group_fm').trigger('submit');
});

//selectedArry.push(id);
//selectedArry.splice($.inArray(row_id, selectedArry), 1);

$(document).on('click', 'input[name$="admin_role"]', function(){
    if($(this).is(':checked')){
        $('.allroles').hide();
    }else{
         $('.allroles').show();
    }
});



$(document).on('click', '.card-body input[name$="action[]"]', function(){
    var parent = $(this).data('parent');
    if($(this).is(':checked') && parent > 0){
        //alert(parent);
        $('#'+parent).prop( "checked", true );
    }else{
       if(!$(this).is(':checked')){
           var child =$(this).val();
         $('input[data-parent="'+child+'"]').prop( "checked", false );
       }
    }
});

$(document).on('click', '.checkAll', function(){
    var container = $(this).val();
    if($(this).is(':checked')){
        //alert(container);
        $('.'+container+' input[name="action[]"]').prop( "checked", true );
    }else{
        $('.'+container+' input[name="action[]"]').prop( "checked", false );
    }
});


$('#group_fm').on('submit', function(e){  
	e.preventDefault();
	var form_data = $(this).serialize() ;
	if($(this).valid()){
	//var chk = $('#store_stoped').is(':checked'); 
    
/*  var sList = "";
$('.allroles input[type=checkbox]').each(function () {
    var sThisVal = (this.checked ? selectedArry.push($(this).data('menu')) : "");
    sList += (sList=="" ? sThisVal : "," + sThisVal);
});
console.log (sList);
console.log (selectedArry); 
 */
	 $.ajax({
		type: 'POST',
		url:"./users-app/users-group-add",
		data:form_data,
		dataType:"json",
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			
			if(data.result){
				if(data.gid !=''){
					$('#group_id').val(data.gid);
				}
				//toastr.success(data.msg);
				//history.back();
				if(data.reload){
				window.location.href = 'users-group-list';
				}
			}else{
				toastr.error(data.msg);
			}
			$('#preloading').hide();
						
		}
	});  
	
	}
});


  
$('#group_fm').validate({
  
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
	


});

document.querySelectorAll('.tree-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        const children = this.parentNode.querySelector('.tree-children');
        if (children.style.display === 'none') {
            children.style.display = 'block';
            this.textContent = '-';
        } else {
            children.style.display = 'none';
            this.textContent = '+';
        }
    });
});
</script>