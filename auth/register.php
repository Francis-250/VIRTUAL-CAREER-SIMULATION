<?php
require_once dirname(__DIR__).'/includes/functions.php'; if(user()){header('Location:'.url());exit;}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals(csrf_token(),$_POST['csrf']??''))$errors[]='Invalid session token.';
    $name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$phone=trim($_POST['phone']??'');$education=$_POST['education_level']??null;$dob=$_POST['date_of_birth']?:null;$password=$_POST['password']??'';
    if(strlen($name)<2)$errors[]='Enter your full name.';if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email.';if(strlen($password)<8)$errors[]='Password must contain at least 8 characters.';
    if(!$errors)try{
        $hash=password_hash($password,PASSWORD_DEFAULT);$stmt=$con->prepare("INSERT INTO users(name,email,password,phone,education_level,date_of_birth,role,status) VALUES(?,?,?,?,?,?,'student','pending')");
        $stmt->bind_param('ssssss',$name,$email,$hash,$phone,$education,$dob);$stmt->execute();$uid=$con->insert_id;$code=otp_code();
        $stmt=$con->prepare("INSERT INTO otp_codes(user_id,code,purpose,expires_at) VALUES(?,?,'registration',DATE_ADD(NOW(),INTERVAL 10 MINUTE))");$stmt->bind_param('is',$uid,$code);$stmt->execute();
        $_SESSION['verify_user_id']=$uid;
        try{require_once dirname(__DIR__).'/config/mailer.php';$mail=configured_mailer();$mail->addAddress($email,$name);$mail->Subject='Your CareerSim verification code';$mail->Body='<h2>Welcome to CareerSim</h2><p>Your verification code is <strong>'.e($code).'</strong>. It expires in 10 minutes.</p>';$mail->send();flash('success','Account created. Check your email for the verification code.');}
        catch(Throwable $e){flash('warning','Account created, but email delivery failed. Use Resend OTP to try again.');}
        header('Location:'.url('auth/verify_otp.php'));exit;
    }catch(mysqli_sql_exception $e){$errors[]=$e->getCode()===1062?'That email is already registered.':'Registration could not be completed.';}
}
$pageTitle='Create account';require dirname(__DIR__).'/includes/header.php';?>
<div class="container py-5"><div class="card p-4 p-md-5 mx-auto" style="max-width:720px"><h1 class="h3">Create your student account</h1><p class="text-muted">Start exploring careers with hands-on simulations.</p><?php foreach($errors as $x):?><div class="alert alert-danger"><?=e($x)?></div><?php endforeach?>
<form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="name" required value="<?=e($_POST['name']??'')?>"></div><div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required value="<?=e($_POST['email']??'')?>"></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?=e($_POST['phone']??'')?>"></div><div class="col-md-6"><label class="form-label">Date of birth</label><input type="date" class="form-control" name="date_of_birth"></div><div class="col-md-6"><label class="form-label">Education level</label><select class="form-select" name="education_level"><option value="">Select level</option><?php foreach(['secondary','undergraduate','graduate','other'] as $v):?><option value="<?=$v?>"><?=ucfirst($v)?></option><?php endforeach?></select></div><div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="password" minlength="8" required></div><div class="col-12"><button class="btn btn-primary w-100">Create account</button></div></form><p class="text-center mt-3 mb-0">Already registered? <a href="<?=url('auth/login.php')?>">Sign in</a></p></div></div>
<?php require dirname(__DIR__).'/includes/footer.php';?>
