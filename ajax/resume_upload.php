<?php
require_once dirname(__DIR__).'/includes/functions.php';require_role(['student','counselor']);verify_csrf();$uid=(int)user()['id'];
if(!isset($_FILES['resume'])||$_FILES['resume']['error']!==UPLOAD_ERR_OK)json_response(['ok'=>false,'message'=>'Choose a resume file to upload.'],422);
$file=$_FILES['resume'];if($file['size']>5*1024*1024)json_response(['ok'=>false,'message'=>'Resume files must be 5 MB or smaller.'],422);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=['application/pdf'=>'pdf','application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/zip'=>'docx'];
if(!isset($allowed[$mime]))json_response(['ok'=>false,'message'=>'Upload a PDF, DOC, or DOCX resume.'],422);
$name='resume_'.$uid.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];$relative='uploads/resumes/'.$name;$target=dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
if(!move_uploaded_file($file['tmp_name'],$target))json_response(['ok'=>false,'message'=>'The resume could not be stored.'],500);
$stmt=$con->prepare('UPDATE users SET resume_path=?,resume_updated_at=NOW() WHERE id=?');$stmt->bind_param('si',$relative,$uid);$stmt->execute();json_response(['ok'=>true,'message'=>'Resume uploaded successfully.','reload'=>true]);
