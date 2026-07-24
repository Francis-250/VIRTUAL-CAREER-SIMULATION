<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_role(['student', 'counselor']);
$uid = (int)user()['id'];
$sid = (int)($_GET['id'] ?? 0);
$stmt = $con->prepare('SELECT s.*,c.title career,q.title quiz_title FROM career_simulations s JOIN careers c ON c.id=s.career_id JOIN quizzes q ON q.id=s.quiz_id WHERE s.id=? AND s.is_active=1');
$stmt->bind_param('i', $sid);
$stmt->execute();
$sim = $stmt->get_result()->fetch_assoc();
if (!$sim) {
    http_response_code(404);
    exit('Simulation not found');
}
$stmt = $con->prepare('SELECT * FROM simulation_tasks WHERE simulation_id=? ORDER BY sort_order,id');
$stmt->bind_param('i', $sid);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt = $con->prepare('SELECT task_id FROM user_task_attempts WHERE user_id=? AND task_id IN (SELECT id FROM simulation_tasks WHERE simulation_id=?) GROUP BY task_id');
$stmt->bind_param('ii', $uid, $sid);
$stmt->execute();
$done = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'task_id');
$current = null;
foreach ($tasks as $t) if (!in_array($t['id'], $done)) {
    $current = $t;
    break;
}
$completed = count($tasks) > 0 && count($done) >= count($tasks);
if ($current) {
    $stmt = $con->prepare('SELECT * FROM task_options WHERE task_id=? ORDER BY sort_order,id');
    $stmt->bind_param('i', $current['id']);
    $stmt->execute();
    $options = $stmt->get_result();
}
$stmt = $con->prepare("INSERT INTO user_simulation_progress(user_id,simulation_id,status,started_at) VALUES(?,?,'in_progress',NOW()) ON DUPLICATE KEY UPDATE status=IF(status='completed',status,'in_progress'),started_at=COALESCE(started_at,NOW())");
$stmt->bind_param('ii', $uid, $sid);
$stmt->execute();
$stmt = $con->prepare('SELECT * FROM user_simulation_progress WHERE user_id=? AND simulation_id=?');
$stmt->bind_param('ii', $uid, $sid);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc();
$pageTitle = $sim['title'];
require dirname(__DIR__) . '/includes/header.php'; ?>
<div class="container py-5"><?php if (!empty($_SESSION['latest_ai_feedback'])): ?><div class="alert alert-info"><strong><i class="bi bi-stars"></i> AI coach feedback</strong>
            <p class="mb-0 mt-1"><?= e($_SESSION['latest_ai_feedback']) ?></p>
        </div><?php unset($_SESSION['latest_ai_feedback']);
                            endif ?><div class="mb-4">
        <div class="text-muted"><?= e($sim['career']) ?></div>
        <h1><?= e($sim['title']) ?></h1>
        <div class="progress" role="progressbar">
            <div class="progress-bar" style="width:<?= count($tasks) ? round(count($done) / count($tasks) * 100) : 0 ?>%"></div>
        </div><small class="text-muted"><?= count($done) ?> of <?= count($tasks) ?> tasks completed</small>
    </div>
    <?php if (!$tasks): ?><div class="card p-5 text-center">
            <h2 class="h4">Career quizzes</h2>
            <p class="text-muted">Choose a knowledge check for <?= e($sim['career']) ?>.</p><a class="btn btn-primary align-self-center" href="<?= url('quiz/choose.php?career_id=' . $sim['career_id']) ?>">Choose a quiz</a>
        </div><?php elseif ($completed): ?><div class="card p-5 text-center"><i class="bi bi-trophy-fill text-warning display-3"></i>
            <h2 class="mt-3">Simulation complete</h2>
            <p class="lead">You scored <?= $progress['total_score'] ?> of <?= $progress['max_possible_score'] ?>.</p>
            <p class="text-muted">Choose a knowledge check for <?= e($sim['career']) ?>.</p>
            <div class="d-flex justify-content-center gap-2"><a class="btn btn-primary" href="<?= url('quiz/choose.php?career_id=' . $sim['career_id']) ?>">Choose a quiz</a><a class="btn btn-outline-primary" href="<?= url('dashboard/progress.php') ?>">View progress</a><button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">Share feedback</button></div>
        </div>
        <div class="modal fade" id="feedbackModal">
            <div class="modal-dialog">
                <form class="modal-content" action="<?= url('ajax/feedback_submit.php') ?>" method="post" data-ajax>
                    <div class="modal-header">
                        <h2 class="modal-title fs-5">How was this simulation?</h2><button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="simulation_id" value="<?= $sid ?>"><label class="form-label">Rating</label><select name="rating" class="form-select mb-3" required>
                            <option value="">Choose 1–5</option><?php for ($i = 5; $i >= 1; $i--): ?><option><?= $i ?></option><?php endfor ?>
                        </select><label class="form-label">Comment</label><textarea class="form-control" name="comment" maxlength="500"></textarea></div>
                    <div class="modal-footer"><button class="btn btn-primary">Submit feedback</button></div>
                </form>
            </div>
        </div>
    <?php else: ?><div class="card p-4">
            <div class="d-flex justify-content-between"><span class="badge bg-primary-subtle text-primary-emphasis"><?= e(ucwords(str_replace('_', ' ', $current['task_type']))) ?></span><span class="text-muted"><?= $current['max_score'] ?> points</span></div>
            <h2 class="h4 mt-3"><?= e($current['title']) ?></h2>
            <p><?= nl2br(e($current['instructions'])) ?></p>
            <form action="<?= url('simulation/submit_task.php') ?>" method="post" data-ajax><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="task_id" value="<?= $current['id'] ?>"><input type="hidden" name="time_spent_seconds" id="elapsed" value="0">
                <?php if (in_array($current['task_type'], ['scenario_choice', 'case_study'])): foreach ($options as $o): ?><label class="form-check border rounded p-3 mb-2"><input class="form-check-input ms-0 me-2" type="radio" name="option_id" value="<?= $o['id'] ?>" required><span><?= e($o['option_text']) ?></span></label><?php endforeach;
                                                                                                                                                                                                                                                                                                                    else: ?><label class="form-label">Your response</label><textarea class="form-control" name="response_text" rows="7" required maxlength="5000"></textarea><?php endif ?><button class="btn btn-primary mt-3">Submit task</button></form>
        </div>
        <script>
            let taskSeconds = 0;
            setInterval(() => {
                taskSeconds++;
                document.getElementById('elapsed').value = taskSeconds
            }, 1000)
        </script><?php endif ?>
</div><?php require dirname(__DIR__) . '/includes/footer.php'; ?>
