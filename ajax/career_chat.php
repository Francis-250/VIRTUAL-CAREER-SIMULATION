<?php
require_once dirname(__DIR__).'/includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'message'=>'Method not allowed.'],405);
verify_csrf();$careerId=(int)($_POST['career_id']??0);$message=trim($_POST['message']??'');
if($message===''||mb_strlen($message)>800)json_response(['ok'=>false,'message'=>'Enter a question of up to 800 characters.'],422);
$stmt=$con->prepare("SELECT c.title,c.summary,c.description,GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') skills FROM careers c LEFT JOIN career_skills cs ON cs.career_id=c.id LEFT JOIN skills s ON s.id=cs.skill_id WHERE c.id=? AND c.is_active=1 GROUP BY c.id");
$stmt->bind_param('i',$careerId);$stmt->execute();$career=$stmt->get_result()->fetch_assoc();if(!$career)json_response(['ok'=>false,'message'=>'Career not found.'],404);
$key='career_chat_'.$careerId;$history=$_SESSION[$key]??[];$messages=[['role'=>'system','content'=>"You are CareerSim's career guidance assistant. Answer only career and education questions grounded in this career context. If asked about unrelated topics, politely redirect. Do not ask for or infer personal identifying information. Be concise and practical.\nCareer: {$career['title']}\nSummary: {$career['summary']}\nDescription: {$career['description']}\nSkills: {$career['skills']}"]];
foreach(array_slice($history,-8) as $item)$messages[]=$item;$messages[]=['role'=>'user','content'=>$message];
$reply=groq_chat($messages,['max_tokens'=>420,'timeout'=>18]);if($reply===null)json_response(['ok'=>false,'message'=>'AI is temporarily unavailable. Please try again later.'],503);
$history[]=['role'=>'user','content'=>$message];$history[]=['role'=>'assistant','content'=>$reply];$_SESSION[$key]=array_slice($history,-10);
json_response(['ok'=>true,'reply'=>$reply]);
