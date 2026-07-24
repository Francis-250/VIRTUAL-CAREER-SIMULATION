<?php
require_once dirname(__DIR__).'/includes/functions.php';require_content_manager();verify_csrf();
$kind=$_POST['kind']??'';$topic=trim($_POST['topic']??'');$difficulty=$_POST['difficulty']??'beginner';$targetId=(int)($_POST['target_id']??0);$interestType=$_POST['interest_type']??'';
if(!in_array($kind,['simulation_tasks','quiz_questions','interest_questions'],true)||$topic===''||mb_strlen($topic)>300)json_response(['ok'=>false,'message'=>'Choose a generator and enter a topic.'],422);
if(!in_array($difficulty,['beginner','intermediate','advanced'],true))$difficulty='beginner';
$context='';if($kind==='simulation_tasks'){$s=$con->prepare('SELECT s.title,c.title career FROM career_simulations s JOIN careers c ON c.id=s.career_id WHERE s.id=?');$s->bind_param('i',$targetId);$s->execute();$x=$s->get_result()->fetch_assoc();if(!$x)json_response(['ok'=>false,'message'=>'Simulation not found.'],404);$context="Career: {$x['career']}; simulation: {$x['title']}";}
elseif($kind==='quiz_questions'){$s=$con->prepare('SELECT q.title,c.title career FROM quizzes q JOIN careers c ON c.id=q.career_id WHERE q.id=?');$s->bind_param('i',$targetId);$s->execute();$x=$s->get_result()->fetch_assoc();if(!$x)json_response(['ok'=>false,'message'=>'Quiz not found.'],404);$context="Career: {$x['career']}; quiz: {$x['title']}";}
else{if(!in_array($interestType,['realistic','investigative','artistic','social','enterprising','conventional'],true))json_response(['ok'=>false,'message'=>'Choose a RIASEC type.'],422);$context="RIASEC type: $interestType";}
$schema=$kind==='simulation_tasks'
?'{"items":[{"title":"...","instructions":"...","task_type":"scenario_choice","max_score":10,"options":[{"text":"...","score":10,"feedback":"...","best":true}]}]}'
:'{"items":[{"question":"...","question_type":"single_choice","points":1,"options":[{"text":"...","correct":true}]}]}';
if($kind==='interest_questions')$schema='{"items":[{"question":"..."}]}';
$instructions=$kind==='quiz_questions'
?'Create exactly four single-choice multiple-choice questions that specifically assess knowledge, decisions, tools, responsibilities, or realistic situations in the named career and quiz title. Every item must use question_type "single_choice", contain exactly four plausible answer options, and have exactly one option with correct true. Avoid generic workplace trivia.'
:($kind==='simulation_tasks'?'Create realistic tasks that a person in the named career could encounter. Include useful option feedback and scores.':'Create clear interest statements that measure the named RIASEC dimension without mentioning the dimension by name.');
$reply=groq_chat([['role'=>'system','content'=>"You draft CareerSim educational content for human review. Return valid JSON only using this exact shape: $schema. $instructions Avoid personal data."],['role'=>'user','content'=>"Context: $context\nRequested topic: $topic\nDifficulty: $difficulty"]],['json'=>true,'max_tokens'=>1500,'timeout'=>25]);
if($reply===null)json_response(['ok'=>false,'message'=>'AI is temporarily unavailable. Please try again later.'],503);
$data=json_decode($reply,true);if(!is_array($data)||!isset($data['items'])){error_log('[CareerSim Groq] Invalid authoring JSON');json_response(['ok'=>false,'message'=>'AI is temporarily unavailable. Please try again later.'],503);}
json_response(['ok'=>true,'draft'=>$data,'draft_json'=>json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)]);
