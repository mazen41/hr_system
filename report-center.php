<?php
$screen = 'التقارير';
$page_title = 'مركز التقارير';
//setcookie('lasturl', 'reports-account-statement', time() + (86400 * 30), "/"); // 86400 = 1 day
 include_once('inc/header.php');


function allReports($connect, $branch){
    $data = [];
    $query = "SELECT  r.id,r.app,r.name,r.parent,r.icon,r.url 
    FROM reports r
    INNER JOIN tblbranchesapps ba ON ba.AppID = r.app AND ba.BranchID = '".$branch."'
    where r.stopped is null order by r.sort   ";
    $stm = $connect->prepare($query);
    $stm->execute();
        if($stm->rowCount() > 0){
            $rows = $stm->fetchAll();
            foreach($rows as $row)
			{
				$sub_array = array();
				$sub_array ['id']       = $row['id'];
                $sub_array ['app']      = $row['app'];
				$sub_array ['name']     = $row['name'];
				$sub_array ['parent']   = $row['parent'];
				$sub_array ['icon']     = $row['icon'];
				$sub_array ['url']      = $row['url'];
				$data[] = $sub_array;
			}
        }
    return $data;
}

$reports = allReports($connect_pdo,$branch);
 
?>
<style>
.btn-app {

    height: 75px;
    min-width: 120px;
    padding: 15px;
}
.btn-app .fa{

   font-size: 25px;
}

#elements, #curl{
list-style: none;
list-style: none;
    margin: 0px;
    padding: 0px;
}

#elements li{
background: white;
    border-bottom: 1px solid #dfe7df;
    line-height: 20px;
    padding: 10px;
    cursor: pointer;
}
#elements li:hover{
background: #f8f9fa;

}

#curl li>.fa{
margin-left: 8px;
}


</style>
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title">مركز التقارير</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
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
    </div>
      
			
    <?php 
    if(!empty($reports)){?>
    <div class="container-fluid" >
		<div class="row ">
			<div class="col-md-10 mx-auto text-center mb-4"  id="cats">
          
            <?php
            foreach($reports as $report){
                if($report['parent'] == 0){
                echo '<a class="btn btn-app bold " id="'.$report['id'].'">
                  <span class="badge bg-purple"></span>
                  <i class="fa fa-'.$report['icon'].'"></i>'.$report['name'].'
                </a>';
              //  echo '<li id="'.$report['id'].'"><a href="'.$report['url'].'">'.$report['name'].'</a></li>';
                   // unset($reports[$report['id']]);
                }
            }
            
           /*  echo '<pre>';
            print_r($reports);
            echo '</pre>'; */
            
			?>	
            
            </div>
            <!--<div class="col-md-12 mb-4"  >
            <ul id="curl">
            </ul>
            </div>-->
            
            <div class="col-md-10 mx-auto mb-4 details" style="display:none">
            <div class="card card-outline card-primary"  >
                <div class="card-header bg-gry">
					<h3 class="card-title">
                        <ul id="curl">
                        </ul>
                    </h3>
				</div>
                <div class="card-body card-body pt-0 pb-0" id="elements">
                </div>
            </div>
            </div>
		</div>
        
        
       
    </div>
    <?php
    }?>
			
</section>





<?php
 include_once('inc/footer.php');
?>

<script src="plugins/select2_n/dist/js/select2.full.js"></script>


<script>

const reports = <?php echo json_encode($reports); ?>;
//console.log(reports);

$(document).ready(function(){
    
    
function newReport(report){
     // $('#elements').append('<li id="'+report['id']+'">'+report['name']+'</li>');
     // return'<li id="'+report['id']+'"><a href="'+report['url']+'">'+report['name']+'</a></li>';
      return'<li id="'+report['id']+'">'+report['name']+'</li>';
}

function newCurl(report){
    if(report['parent']>0){
     $('#curl').append('<li id="'+report['id']+'"><i class="fa fa-'+report['icon']+'"></i> '+report['name']+'</li>');
    }else{
      $('#curl').html('<li  id="'+report['id']+'"><i class="fa fa-'+report['icon']+'"></i><a>'+report['name']+'</a></li>');  
    }
}    
    
 for(var i = 0; i < reports.length; i++){
    //newReport(reports[i]);
    console.log(reports[i]);
} 

$(document).on('click', '#cats a', function(){
    var id = $(this).attr('id');
    var html ='';
	//alert(id);
    var report_info = reports.find(obj => obj.id == id);
    id = report_info['id'];
    newCurl(report_info);
    for(var i=0; i < reports.length; i++){
        
		if(reports[i].parent == id){
			html += newReport(reports[i]);
		}
	}
   $('.details').show();
    $('#elements').html(html);
});

$(document).on('click', '#elements li', function(){
    var id = $(this).attr('id');
    var html ='';
    var report_info = reports.find(obj => obj.id == id);
    var id = report_info['id'];
    var url = report_info['url'];
    
    for(var i=0; i < reports.length; i++){
		if(reports[i].parent == id){
			html += newReport(reports[i]);
		}
	}
    if( html !=''){
        newCurl(report_info);
        $('#elements').html(html);
    }else{
       // alert(url);
       if(url =="#"){
           //alert('غير متاح في النسخة التجريبية');
       }else{
        $('#preloading').show();
         window.location.href = url;
       }
    }

});




  
});
 

</script>