<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/groq.php';

const BASE_URL = '/Virtual%20career%20simulation%20system';
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function slugify(string $value): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = $ascii !== false ? $ascii : $value;
    $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
    return $slug !== '' ? $slug : 'career';
}
function unique_career_slug(mysqli $con, string $title, int $excludeId = 0): string {
    $base = slugify($title);
    $slug = $base;
    $suffix = 2;
    $stmt = $con->prepare('SELECT id FROM careers WHERE slug=? AND id<>? LIMIT 1');
    do {
        $stmt->bind_param('si', $slug, $excludeId);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_assoc();
        if ($exists) $slug = $base . '-' . $suffix++;
    } while ($exists);
    return $slug;
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function flash(string $type, string $message): void { $_SESSION['flash'][] = compact('type', 'message'); }
function require_login(): void {
    if (!user()) { flash('warning', 'Please sign in to continue.'); header('Location: ' . url('auth/login.php')); exit; }
}
function role_home(string $role): string {
    return match($role) {'admin'=>'admin/index.php','content_manager'=>'admin/careers.php','counselor'=>'counselor/index.php',default=>'dashboard/progress.php'};
}
function profile_complete(mysqli $con): bool {
    if (!user()) return false;
    if (array_key_exists('profile_complete', $_SESSION['user'])) return (bool)$_SESSION['user']['profile_complete'];
    $id=(int)user()['id'];$stmt=$con->prepare('SELECT profile_completed_at FROM users WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();
    return $_SESSION['user']['profile_complete']=(bool)$stmt->get_result()->fetch_assoc()['profile_completed_at'];
}
function require_complete_profile(): void {
    global $con;
    if (!profile_complete($con)) { flash('info','Complete your profile before continuing.'); header('Location: '.url('profile.php')); exit; }
}
function require_role(string|array $roles): void {
    require_login();require_complete_profile();$roles = (array)$roles;
    if (!in_array(user()['role'], $roles, true)) {
        flash('danger', 'You are not authorized to access that area.');
        header('Location: ' . url(role_home(user()['role']))); exit;
    }
}
function require_admin(): void { require_role('admin'); }
function require_content_manager(): void { require_role('content_manager'); }
function json_response(array $data, int $status = 200): never {
    http_response_code($status); header('Content-Type: application/json'); echo json_encode($data); exit;
}
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(24)); }
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_response(['ok'=>false,'message'=>'Your session token expired. Refresh and try again.'], 419);
}
function otp_code(): string { return (string)random_int(100000, 999999); }
function notify_user(mysqli $con, int $userId, string $type, string $message): void {
    $stmt=$con->prepare('INSERT INTO notifications(user_id,type,data) VALUES(?,?,?)'); $stmt->bind_param('iss',$userId,$type,$message); $stmt->execute();
}
function admin_log(mysqli $con, string $action, string $targetType, int $targetId, string $details=''): void {
    $id=(int)user()['id']; $stmt=$con->prepare('INSERT INTO admin_logs(admin_id,action,target_type,target_id,details) VALUES(?,?,?,?,?)');
    $stmt->bind_param('issis',$id,$action,$targetType,$targetId,$details); $stmt->execute();
}
function generate_recommendations(mysqli $con, int $userId): int {
    $types=['realistic','investigative','artistic','social','enterprising','conventional'];
    $scores=array_fill_keys($types,0.0); $stmt=$con->prepare('SELECT interest_type,score FROM user_interest_scores WHERE user_id=?'); $stmt->bind_param('i',$userId); $stmt->execute();
    foreach($stmt->get_result() as $r) $scores[$r['interest_type']] = (float)$r['score'];
    $hasInterests=max($scores)>0; $performance=[];
    $stmt=$con->prepare("SELECT c.id, AVG(CASE WHEN p.max_possible_score>0 THEN p.total_score/p.max_possible_score*100 ELSE 0 END) perf FROM careers c JOIN career_simulations s ON s.career_id=c.id JOIN user_simulation_progress p ON p.simulation_id=s.id AND p.user_id=? AND p.status='completed' GROUP BY c.id");
    $stmt->bind_param('i',$userId); $stmt->execute(); foreach($stmt->get_result() as $r)$performance[(int)$r['id']]=(float)$r['perf'];
    $stmt=$con->prepare("SELECT q.career_id,AVG(a.score/NULLIF(a.max_score,0)*100) perf FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.user_id=? AND a.completed_at IS NOT NULL GROUP BY q.career_id");
    $stmt->bind_param('i',$userId); $stmt->execute(); foreach($stmt->get_result() as $r){$id=(int)$r['career_id'];$performance[$id]=isset($performance[$id])?($performance[$id]+(float)$r['perf'])/2:(float)$r['perf'];}
    $maps=[];$careerInfo=[];foreach($con->query("SELECT c.id,c.title,c.summary,m.interest_type,m.weight FROM careers c LEFT JOIN career_interest_mapping m ON m.career_id=c.id WHERE c.is_active=1") as $r){$cid=(int)$r['id'];$careerInfo[$cid]=['title'=>$r['title'],'summary'=>$r['summary']];if($r['interest_type'])$maps[$cid][$r['interest_type']] = (float)$r['weight'];}
    $con->begin_transaction();
    try {
        $del=$con->prepare('DELETE FROM career_recommendations WHERE user_id=?');$del->bind_param('i',$userId);$del->execute();
        $ins=$con->prepare('INSERT INTO career_recommendations(user_id,career_id,match_score,based_on,explanation) VALUES(?,?,?,?,?)');$count=0;arsort($scores);$topTraits=array_slice(array_keys($scores),0,3);$traitSummary=implode(', ',array_map(fn($t)=>ucfirst($t).' '.round($scores[$t]).'%', $topTraits));
        foreach($maps as $careerId=>$weights){
            $dot=$a=$b=0.0; foreach($types as $t){$x=$scores[$t];$y=$weights[$t]??0;$dot+=$x*$y;$a+=$x*$x;$b+=$y*$y;}
            $interest=($a>0&&$b>0)?$dot/(sqrt($a)*sqrt($b))*100:0; $hasPerf=array_key_exists($careerId,$performance);
            if(!$hasInterests&&!$hasPerf)continue;
            $match=$hasPerf&&$hasInterests?($interest*.65+$performance[$careerId]*.35):($hasPerf?$performance[$careerId]:$interest);
            $based=$hasPerf&&$hasInterests?'combined':($hasPerf?'performance':'interest');$match=max(0,min(100,$match));$perfText=$hasPerf?round($performance[$careerId]).'% activity performance':'No career-specific performance yet';
            $explanation=groq_chat([['role'=>'system','content'=>'Write a concise, encouraging career-fit explanation using only the supplied non-identifying evidence. Use 2-3 sentences, avoid certainty, and do not mention AI.'],['role'=>'user','content'=>"Top RIASEC traits: $traitSummary\nPerformance: $perfText\nCareer: {$careerInfo[$careerId]['title']}\nSummary: {$careerInfo[$careerId]['summary']}\nMatch score: ".round($match).'%']],['max_tokens'=>180,'timeout'=>12]);
            $explanation??='This match reflects the interests and learning performance currently available in your profile. Explore the career activities to gather more evidence about how well it suits you.';
            $ins->bind_param('iidss',$userId,$careerId,$match,$based,$explanation);$ins->execute();$count++;
        }
        $con->commit(); if($count)notify_user($con,$userId,'recommendation','Your career recommendations were refreshed.'); return $count;
    } catch(Throwable $e){$con->rollback();throw $e;}
}
function award_badges(mysqli $con,int $userId): array {
    $stats=['simulation_complete'=>0,'quiz_pass'=>0,'career_count'=>0,'streak'=>0];
    $s=$con->prepare("SELECT COUNT(*) n,COUNT(DISTINCT s.career_id) careers FROM user_simulation_progress p JOIN career_simulations s ON s.id=p.simulation_id WHERE p.user_id=? AND p.status='completed'");$s->bind_param('i',$userId);$s->execute();$r=$s->get_result()->fetch_assoc();$stats['simulation_complete']=(int)$r['n'];$stats['career_count']=(int)$r['careers'];
    $s=$con->prepare("SELECT COUNT(*) n FROM quiz_attempts WHERE user_id=? AND passed=1");$s->bind_param('i',$userId);$s->execute();$stats['quiz_pass']=(int)$s->get_result()->fetch_assoc()['n'];
    $earned=[];foreach($con->query('SELECT * FROM badges') as $b)if(($stats[$b['criteria_type']]??0)>=(int)$b['criteria_value']){
        $s=$con->prepare('INSERT IGNORE INTO user_badges(user_id,badge_id) VALUES(?,?)');$s->bind_param('ii',$userId,$b['id']);$s->execute();
        if($s->affected_rows){$earned[]=$b['name'];notify_user($con,$userId,'badge','Badge earned: '.$b['name']);}
    } return $earned;
}
