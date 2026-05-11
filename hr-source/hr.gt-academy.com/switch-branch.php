<?php
//($_SERVER['REQUEST_METHOD'] == 'GET') ? "":die();

if($extraBranchies && !empty($_GET['id']) )
{ 

 //$_SESSION['branch'] = $_GET['id'];
 //$branch = $_SESSION['branch'];
 
 //$_SESSION['branch_name'] = $_SESSION['user']['extraBranchies'][$branch];


function getBranch($connect ,$id){
    $query = "SELECT branch_id,branch_ref, branch_name,isdefault,branch_style, isstopped FROM branches where branch_id =:id limit 1";
    $stm   = $connect->prepare($query);
    $stm->execute(['id'=>$id]);
    if($stm->rowCount() > 0){
        return $stm->fetch();
    }
    return null;
}

$agree = '';
$go_to = '';

$new_branch = getBranch($connect_pdo ,(int) $_GET['id']);
if(!empty($new_branch)){
    if($new_branch['branch_id'] != $User->branch){
        
        if($User->is_owner || array_key_exists($new_branch['branch_id'],$User->allBranches())){
            
            if(empty($new_branch['isstopped'])){
                    $msg = 'هل ترغب بالفعل بمغادرة <b>"'.$_SESSION['user']['extraBranchies'][$branch].'" </b><br>والانتقال إلى <b> " '.$new_branch['branch_name'].' "</b>';
                    $go_to = $new_branch['branch_id'];
                    $branch_style = $new_branch['branch_style'];
            }else{
                $error = 'عذراً .. <b>" '.$new_branch['branch_name'].' "</b> موقف حالياً ..!';
            }
            
        }else{
            $error = 'لايوجد لديك صلاحية للدخول إلى <b>" '.$new_branch['branch_name'].' "</b> ..!';
        }
        
    }else{
        $error = 'أنت بالفعل داخل <b>" '.$new_branch['branch_name'].' "</b> ..!';
    }

}else{
    $error = 'فرع غير موجود ..!';
}



/* echo '<pre>';
print_r($_SESSION['user']['extraBranchies']);
echo '</pre>';
echo '<pre>';
print_r($User->branches);
echo '</pre>'; */



?>
<style>
.modal-body  button{
	float: left;
	margin: 0px 5px;
}

</style>
<div  id="modal_container" style="display:none_">
<?php
	if(empty($error)){
        
        $agree='<button class="btn btn-success" type="submit" >نعم</button>';
         echo '<p class="">'.$msg.'</p>';
         echo '<form class="form-horizontal" role="form" action="#" method="post" id="modal_fm" style="display:none_">
	
	<input type="hidden" name="new_branch" class="form-control" value="'.$go_to.'" >
	<input type="hidden" name="branch_style" class="form-control" value="'.$branch_style.'" >
	<hr>
</form>
    <button class="btn btn-success" type="button" id="submit_modal" >نعم</button>
    <button class="btn btn-default" type="button"  id="cancel_modal">لا</button>
	
';

    }else{
        echo '<p class="text-danger">'.$error.'</p><hr>';
        echo '<button class="btn btn-default" type="button"  id="cancel_modal">اغلاق</button>';
        
     }
    
?>



<div id="modify_confirme"></div>

<?php
	if($User->userIsAdmin()){
        
   
    }else{
       
     }
    
?>

</div>


<script>

$(document).ready(function(){

//$('#preloading').hide();
    $('#cancel_modal').on('click', function(e){
        $('#modal_default').modal('toggle');
    });

    $('#submit_modal').on('click', function(e){
        $('#modal_fm').trigger('submit');
    });
    
    
    
$("#modal_fm").on('submit', function(e){
	e.preventDefault();
	
	
	
		$.ajax({
			url:'./branches-app/switch-branch',
			type: 'POST',
			data:$(this).serialize(),
			dataType:"json",
			beforeSend:function(){
				$('#preloading').show();
			}, 
			success:function(data){
				if(data.result){
					toastr.success(data.msg);
				}else{
					toastr.error(data.msg);
				}
				if(data.reload){
					//window.location.href = ''+backPath+'';
                    location.reload();
				}
				
				$('#preloading').hide();
				$('#modal_default').modal('toggle');
			}
	});
	
})
    
    

});
</script>

<?php
}
?>