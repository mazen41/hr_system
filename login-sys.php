<?php
 function siteInfo($connect){
    //$query = 'SELECT * FROM tblsite WHERE SiteUrl = :site limit 1 ';
     $query = 'SELECT * FROM tblsite order by AccountID desc limit 1 ';
    //$values = array(':site' => $subdomain);
		try
		{
			$res = $connect->prepare($query);
			$res->execute();
		}
		catch (PDOException $e)
		{
		   //throw new Exception('!');
           return false;
		}
        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (is_array($row))
		{
            return $row;
 
        }
        return false;
}

$instance = siteInfo($connect_pdo);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//if(isset($_POST['user_inpt'])){
	

$login = FALSE;

	try
	{
		$login = $User->login($_POST['user_inpt'], $_POST['user_password']);
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
		die();
	}

	if ($login && empty($_SESSION['fatel_error']))
	{ 
           /*  $_SESSION['account']['title']       = $instance['SiteTitle'];
            $_SESSION['account']['logo']        = $instance['SiteTitle'];
            
            $_SESSION['country']                = $instance['SiteCountryID'];
            $_SESSION['timezone']               = $instance['SiteTimeZone'];
            $_SESSION['dateformat']             = $instance['SiteDateFormat']; // للتعديل من جدول الجهة
            
            $_SESSION['currency'] = [];
            $_SESSION['currency']['id']= $instance['SiteCurrencyID']; // need
            $_SESSION['currency']['name']= 'ريال سعودي';
            $_SESSION['currency']['shortname']= $instance['SiteCurrencyID']; */
 
 
		/* echo 'Authentication successful';
		echo 'Account ID: ' . $User->getId() . '<br>';
		echo 'Account name: ' . $User->getName() . '<br>';
		echo 'time now: ' . $now_date . '<br>'; */

       
       $home_page = !empty($_SESSION['user']['home_page'])  ? $_SESSION['user']['home_page'] : 'dashboard' ; 
		if($page =='login-sys'){
			//header("Location: home");
                echo'<script> location.replace("'.$home_page.'"); </script>';
                
               // echo'<script> location.replace("dashboard"); </script>';
               
		}else{
			//header("Location: $page");
           // echo'<script> location.replace(""'.$page.'""); </script>';
                echo'<script> location.replace(""'.$home_page.'""); </script>';
            // echo'<script> location.replace("dashboard"); </script>';
            die();
		};
		//header("Location: home");
		echo'<script> location.replace("login-sys"); </script>';
		die();
	}
	else
	{
		$login_error = 'معلومات الدخول خاطئة';
        if(!empty($_SESSION['fatel_error'])){
            
            $login_error = $_SESSION['fatel_error'];
           session_destroy();
        }
		//die();
	}
	
}




?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تسجيل الدخول</title>
<meta name="theme-color" content="#42c1e8">
   <link rel="manifest" href="manifest.json">
   <link rel="icon" type="image/x-icon"  href="icon.png">
   <link rel="apple-touch-icon" href="icon-192.png">
  
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
	<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.css">
 <script>
      if ('serviceWorker' in navigator) {
   // window.addEventListener('load', function() {
        navigator.serviceWorker.register('sw.js').then(function(registration) {
        console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, function(err) {
        console.log('ServiceWorker registration failed: ', err);
        });
   // });
    }  
   </script>
</head>
<body class="hold-transition login-page">
<?php
if(!empty($instance['SiteLogo']) && file_exists('uploads/basics/'.$instance['SiteLogo'])){
    echo'<div class="mb-2"><img src="uploads/basics/'.$instance['SiteLogo'].'" style="max-width: 150px;"></div>';
}

?>
<?= !empty($instance['SiteTitle'])? '<h6>'.$instance['SiteTitle'].'</h6>' : '' ?>

<div class="login-box">
  <!-- /.login-logo -->
	<?php if(!empty($login_error)):?>
		<div class="alert alert-danger alert-dismissible" id="result-alert">
            <?=$login_error?>
        </div>
	<?php endif;?>
    
  <div class="card card-outline card-success text-center">
  <?php
  if(!empty($_SESSION['msg'])){
      echo $_SESSION['msg'];
      if(!empty($_SESSION['ended'])){
          echo '<h5> عذراً ..! إنتهت الفترة التجريبية</h5> لمتابعة العمل يمكنك التواصل معنا<br> لتفعيل نسختك الخاصة من النظام';
      }
      unset($_SESSION['msg']);
      unset($_SESSION['ended']);
      die();
  }
  $_SESSION['end_demo'] = !empty($instance['SiteEndDate']) ? $instance['SiteEndDate'] : null;
    if(!empty($instance)){?>
    <?php if(empty($instance['SiteEndDate']) || (!empty($instance['SiteEndDate']) && $instance['SiteEndDate'] >= $today_date)){?>
    <div class="card-header text-center">
      <h5 style="margin: 3px;">تسجيل الدخول</h5>
    </div>
    <div class="card-body">
	
      <form action="" role="form" method="post" >
        <div class="input-group mb-3" >
          <input type="email" class="form-control" name="user_inpt" id="user_inpt" placeholder="البريد الإلكتروني" autocomplete="off" style="text-align: left;">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" name="user_password" id="user_password" placeholder="كلمة المرور" autocomplete="off"style="text-align: left;">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          
          <!-- /.col -->
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block" name="user_login">دخول</button>
          </div>
          <!-- /.col -->
        </div>
      </form>
        <div class="small text-center mt-3 bod" style="font-weight: 500;">نسيت كلمة المرور ؟ <a href="forget-paswd">إستعادة</a></div>
    <?php }else{?>
        <div class="card-body text-center mt-5 mb-5 mx-4">
        <h4>عذراً..!</h4>
        <h5 class="text-danger">إنتهت الفترة التجريبية</h5>
        <a href="https://visionsys.net" type="button" class="btn btn-success btn-block mt-3" >ترقية النظام</a>
        </div>
    <?php    
    }}?>

      
      
    </div>
    <!-- /.card-body -->
	<div class="overlay" style="display:none" id="add_holdon"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
  </div>
  <!-- /.card -->
</div>
<p class="mt-1">مدعوم بواسطة <a href="https://visionsys.net" target="blank">شركة التطور الرقمي</a></p>
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>


</body>
</html>
<script>
$(document).ready(function(){
document.addEventListener("keydown", function(event) {
    if (event.altKey && event.code === "KeyX")
    {
		window.open('test/test_user_session.php', '', 'window settings');
		return false;
       /*  alert('Alt + X pressed!');
        event.preventDefault(); */
    }
});	
});
</script>