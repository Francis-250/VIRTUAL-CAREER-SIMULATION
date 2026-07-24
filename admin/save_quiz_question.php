<?php
require_once dirname(__DIR__).'/includes/functions.php';require_content_manager();verify_csrf();
$quizId=(int)($_POST['quiz_id']??0);$questionText=trim($_POST['question_text']??'');$points=max(1,min(100,(int)($_POST['points']??1)));$options=array_map('trim',(array)($_POST['options']??[]));$correct=(int)($_POST['correct_option']??-1);
if($quizId<1||$questionText===''||mb_strlen($questionText)>500)json_response(['ok'=>false,'message'=>'Enter a valid question.'],422);
if(count($options)<2||in_array('',array_slice($options,0,4),true)||$correct<0||$correct>=count($options))json_response(['ok'=>false,'message'=>'Enter all answer choices and select the correct answer.'],422);
$stmt=$con->prepare('SELECT id FROM quizzes WHERE id=?');$stmt->bind_param('i',$quizId);$stmt->execute();if(!$stmt->get_result()->fetch_assoc())json_response(['ok'=>false,'message'=>'Quiz not found.'],404);
$order=(int)$con->query("SELECT COALESCE(MAX(sort_order),0)+1 n FROM quiz_questions WHERE quiz_id=$quizId")->fetch_assoc()['n'];
$con->begin_transaction();try{$type='single_choice';$stmt=$con->prepare('INSERT INTO quiz_questions(quiz_id,question_text,question_type,sort_order,points) VALUES(?,?,?,?,?)');$stmt->bind_param('issii',$quizId,$questionText,$type,$order,$points);$stmt->execute();$questionId=$con->insert_id;
$insert=$con->prepare('INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order) VALUES(?,?,?,?)');foreach($options as $i=>$text){if($text==='')continue;$isCorrect=$i===$correct?1:0;$sort=$i+1;$insert->bind_param('isii',$questionId,$text,$isCorrect,$sort);$insert->execute();}
$con->commit();admin_log($con,'create','quiz_question',$questionId,'Added multiple-choice question');json_response(['ok'=>true,'message'=>'Multiple-choice question added.','reload'=>true]);}catch(Throwable $e){$con->rollback();json_response(['ok'=>false,'message'=>'The question could not be saved.'],500);}
