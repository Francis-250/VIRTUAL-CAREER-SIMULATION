<?php
require_once dirname(__DIR__).'/includes/functions.php';require_role(['student','counselor']);$uid=(int)user()['id'];$questions=$con->query('SELECT * FROM interest_questions WHERE is_active=1 ORDER BY id');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$questionRows=$questions->fetch_all(MYSQLI_ASSOC);
 try{
  $con->begin_transaction();$ins=$con->prepare('INSERT INTO user_interest_responses(user_id,question_id,response_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE response_value=VALUES(response_value),created_at=NOW()');
  foreach($questionRows as $q){$v=(int)($_POST['response'][$q['id']]??0);if($v<1||$v>5)throw new RuntimeException('Answer every question.');$qid=(int)$q['id'];$ins->bind_param('iii',$uid,$qid,$v);$ins->execute();}
  $score=$con->prepare("SELECT q.interest_type,AVG(r.response_value)*20 score FROM user_interest_responses r JOIN interest_questions q ON q.id=r.question_id WHERE r.user_id=? AND q.is_active=1 GROUP BY q.interest_type");$score->bind_param('i',$uid);$score->execute();$scoreResult=$score->get_result();$scoreRows=$scoreResult->fetch_all(MYSQLI_ASSOC);$scoreResult->free();$score->close();
  $up=$con->prepare('INSERT INTO user_interest_scores(user_id,interest_type,score) VALUES(?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),computed_at=NOW()');
  foreach($scoreRows as $r){$interestType=$r['interest_type'];$v=(float)$r['score'];$up->bind_param('isd',$uid,$interestType,$v);$up->execute();}$up->close();$con->commit();
 }catch(Throwable $e){
  try{$con->rollback();}catch(Throwable $rollbackError){error_log('Interest assessment rollback error: '.$rollbackError->getMessage());}
  error_log('Interest assessment save error: '.$e->getMessage());json_response(['ok'=>false,'message'=>$e instanceof RuntimeException?$e->getMessage():'The assessment could not be saved. Please try again.'],422);
 }
 try{generate_recommendations($con,$uid);}catch(Throwable $e){error_log('Interest recommendation error: '.$e->getMessage());}
 json_response(['ok'=>true,'message'=>'Your interest profile is ready.','redirect'=>url('dashboard/progress.php')]);
}
$stmt=$con->prepare('SELECT interest_type,score FROM user_interest_scores WHERE user_id=?');$stmt->bind_param('i',$uid);$stmt->execute();$scores=[];foreach($stmt->get_result() as $r)$scores[$r['interest_type']]=$r['score'];
$totalQuestions=$questions->num_rows;$pageTitle='Interest assessment';$bodyClass='assessment-page';require dirname(__DIR__).'/includes/header.php';?>
<div class="assessment-shell">
 <section class="assessment-header-card">
  <h1>RIASEC interest assessment</h1>
  <p>Rate how much you would enjoy each activity: 1 means ‘not at all’ and 5 means ‘very much’.</p>
  <div class="assessment-progress"><div><span id="assessmentProgressBar"></span></div><strong id="assessmentProgressText">0 of <?=$totalQuestions?> completed</strong></div>
 </section>
 <?php if($scores):?><section class="assessment-result-card"><div><span class="assessment-result-icon"><i class="bi bi-graph-up"></i></span><div><h2>Your current interest profile</h2><p>You can retake the assessment below to refresh your recommendations.</p></div></div><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#profileChart">View chart</button><div class="collapse mt-4" id="profileChart"><div class="assessment-chart-wrap"><canvas id="riasecChart"></canvas></div></div></section><?php endif?>
 <?php if(!$totalQuestions):?><div class="alert alert-info">No active assessment questions are available yet.</div><?php else:?>
 <form method="post" data-ajax id="assessmentForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <div class="assessment-question-card">
  <?php foreach($questions as $i=>$q):?><fieldset class="assessment-question-row">
   <legend><?=$i+1?>. <?=e($q['question_text'])?></legend>
   <div class="assessment-scale"><?php for($v=1;$v<=5;$v++):?><label><input type="radio" name="response[<?=$q['id']?>]" value="<?=$v?>" required><span></span><small><?=$v?></small></label><?php endfor?></div>
  </fieldset><?php endforeach?>
  </div>
  <div class="assessment-actions"><button class="btn" type="submit"><?=empty($scores)?'Build my profile':'Rebuild my profile'?></button><p>Ensure all questions are answered for the best results.</p></div>
 </form>
 <?php endif?>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const form=document.getElementById('assessmentForm'),bar=document.getElementById('assessmentProgressBar'),text=document.getElementById('assessmentProgressText'),total=<?=$totalQuestions?>;function updateProgress(){if(!form)return;const answered=new Set([...form.querySelectorAll('input[type=radio]:checked')].map(x=>x.name)).size;bar.style.width=(total?answered/total*100:0)+'%';text.textContent=answered+' of '+total+' completed'}form?.addEventListener('change',updateProgress);updateProgress();<?php if($scores):?>new Chart(document.getElementById('riasecChart'),{type:'radar',data:{labels:<?=json_encode(array_map('ucfirst',array_keys($scores)))?>,datasets:[{label:'Interest score',data:<?=json_encode(array_values($scores),JSON_NUMERIC_CHECK)?>,backgroundColor:'rgba(13,148,136,.12)',borderColor:'#0d9488'}]},options:{maintainAspectRatio:false,scales:{r:{min:0,max:100}}}});<?php endif?>});</script>
<?php require dirname(__DIR__).'/includes/footer.php';?>
