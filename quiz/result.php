<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
$result = $_SESSION['quiz_result'] ?? null;
if (!$result) {
    header('Location:' . url('careers/browse.php'));
    exit;
}
$pageTitle = 'Quiz result';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="container py-5">
    <div class="card p-5 text-center mx-auto" style="max-width:650px">
        <i class="bi bi-<?= $result['passed'] ? 'check-circle-fill text-success' : 'book text-primary' ?> display-3"></i>
        <h1 class="mt-3"><?= e($result['title']) ?></h1>
        <div class="display-4 fw-bold"><?= e($result['percent']) ?>%</div>
        <p class="lead"><?= (int)$result['score'] ?> of <?= (int)$result['max'] ?> points · <?= $result['passed'] ? 'Passed' : 'Not passed yet' ?></p>
        <p class="text-muted">This result is attached to the quiz on its career profile.</p>
        <div>
            <?php if (!empty($result['career_id'])): ?>
                <a class="btn btn-primary" href="<?= url('careers/view.php?id=' . (int)$result['career_id']) ?>">Back to career</a>
            <?php endif ?>
            <a class="btn btn-outline-primary" href="<?= url('dashboard/progress.php') ?>">View progress</a>
        </div>
    </div>
</div>
<?php
unset($_SESSION['quiz_result']);
require dirname(__DIR__) . '/includes/footer.php';
