<?php
require_once dirname(__DIR__).'/includes/functions.php';$uid=(int)($_SESSION['verify_user_id']??$_GET['user_id']??0);if(!$uid){header('Location:'.url('auth/register.php'));exit;}$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals(csrf_token(),$_POST['csrf']??''))$error='Invalid session token.';
 elseif(isset($_POST['resend'])){
  $stmt=$con->prepare("SELECT created_at FROM otp_codes WHERE user_id=? AND purpose='registration' ORDER BY id DESC LIMIT 1");$stmt->bind_param('i',$uid);$stmt->execute();$last=$stmt->get_result()->fetch_assoc();
  if($last&&time()-strtotime($last['created_at'])<60)$error='Please wait 60 seconds before requesting another code.';
  else{$code=otp_code();$stmt=$con->prepare("INSERT INTO otp_codes(user_id,code,purpose,expires_at) VALUES(?,?,'registration',DATE_ADD(NOW(),INTERVAL 10 MINUTE))");$stmt->bind_param('is',$uid,$code);$stmt->execute();$u=$con->query("SELECT name,email FROM users WHERE id=$uid")->fetch_assoc();try{require_once dirname(__DIR__).'/config/mailer.php';$mail=configured_mailer();$mail->addAddress($u['email'],$u['name']);$mail->Subject='Your new Career Guidance System code';$mail->Body='<p>Your new code is <strong>'.e($code).'</strong>.</p>';$mail->send();flash('success','A new code was sent.');}catch(Throwable $e){$error='The code was created but email delivery failed. You can retry resend in 60 seconds.';}}
 }else{
  $code=trim($_POST['code']??'');$stmt=$con->prepare("SELECT * FROM otp_codes WHERE user_id=? AND purpose='registration' AND used_at IS NULL ORDER BY id DESC LIMIT 1 FOR UPDATE");$stmt->bind_param('i',$uid);$stmt->execute();$otp=$stmt->get_result()->fetch_assoc();
  if(!$otp||strtotime($otp['expires_at'])<time())$error='This code has expired. Request a new one.';
  elseif((int)$otp['attempts']>=5)$error='Too many attempts. Request a new code.';
  elseif(!hash_equals($otp['code'],$code)){$con->query('UPDATE otp_codes SET attempts=attempts+1 WHERE id='.(int)$otp['id']);$error='Incorrect code.';}
  else{$con->begin_transaction();try{$con->query('UPDATE otp_codes SET used_at=NOW() WHERE id='.(int)$otp['id']);$con->query("UPDATE users SET status='active',email_verified_at=NOW() WHERE id=$uid");$con->commit();unset($_SESSION['verify_user_id']);flash('success','Email verified. You can now sign in.');header('Location:'.url('auth/login.php'));exit;}catch(Throwable $e){$con->rollback();$error='Verification failed.';}}
 }
}
$pageTitle='Verify email';require dirname(__DIR__).'/includes/header.php';?>
<div class="container py-5"><div class="card p-4 mx-auto" style="max-width:480px"><h1 class="h3">Verify your email</h1><p class="text-muted">Enter the six-digit code sent to your inbox.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input class="form-control form-control-lg text-center mb-3" name="code" inputmode="numeric" maxlength="6" pattern="\d{6}" required><button class="btn btn-primary w-100">Verify</button></form><form method="post" class="mt-2"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><button class="btn btn-link w-100" name="resend" value="1">Resend OTP</button></form></div></div><?php require dirname(__DIR__).'/includes/footer.php';?>
