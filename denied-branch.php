<?php
 //include_once('inc/header.php');
?>

    <!-- Content Header (Page header) -->
    
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
		<div class="row">
		<div class="col-md-6 mx-auto text-center invoice p-3 text-danger">
        <div class=" h1"><i class="fa fa-ban"></i></div>
        <div class="py-3">
        <?php
        if(!empty($msg)){
            echo '<h6>'.$msg.'</h6>';
        }else{
            echo'<h6>عذراً.. ! هذا الإجراء متاح فقط للمركز الرئيسي</h6>';
        }
        ?>
		</div>
        <button type="button" class="btn btn-default btn-xs" onclick="history.back()" id="cancel-bt"><i class="fa fa-times"></i> رجوع</button>
        <a href="dashboard" type="button" class="btn btn-primary btn-xs" ><i class="fa fa-home"></i> البداية</a>
            
		</div>
		</div>
      </div><!-- /.container-fluid -->
    </section>
<?php
include_once('inc/footer.php');
die();
?>