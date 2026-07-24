<?php
require_once dirname(__DIR__).'/includes/functions.php';if(user()){header('Location:'.url());exit;}$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=strtolower(trim($_POST['email']??''));$stmt=$con->prepare('SELECT * FROM users WHERE email=? AND deleted_at IS NULL LIMIT 1');$stmt->bind_param('s',$email);$stmt->execute();$u=$stmt->get_result()->fetch_assoc();
 if(!$u||!password_verify($_POST['password']??'',$u['password']))$error='Invalid email or password.';
 elseif($u['status']==='pending'){$error='Please verify your email before signing in.';$_SESSION['verify_user_id']=$u['id'];}
 elseif($u['status']==='suspended')$error='This account is suspended. Contact an administrator.';
 else{session_regenerate_id(true);$_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'profile_complete'=>(bool)$u['profile_completed_at']];$stmt=$con->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?');$stmt->bind_param('i',$u['id']);$stmt->execute();$target=$u['profile_completed_at']?role_home($u['role']):'profile.php';header('Location:'.url($target));exit;}
}
$pageTitle='Sign in';require dirname(__DIR__).'/includes/header.php';?>
<div class="container py-5"><div class="card p-4 mx-auto" style="max-width:480px"><h1 class="h3">Welcome back</h1><p class="text-muted">Sign in to continue your career journey.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?><?php if(isset($_SESSION['verify_user_id'])):?><a class="alert-link" href="<?=url('auth/verify_otp.php')?>"> Verify now</a><?php endif?></div><?php endif?><form method="post"><label class="form-label">Email</label><input type="email" class="form-control mb-3" name="email" required><label class="form-label">Password</label><input type="password" class="form-control mb-3" name="password" required><button class="btn btn-primary w-100">Sign in</button></form><div class="d-flex justify-content-between mt-3"><a href="<?=url('auth/register.php')?>">Create account</a><a href="<?=url('auth/forgot_password.php')?>">Forgot password?</a></div></div></div><?php require dirname(__DIR__).'/includes/footer.php';?>
