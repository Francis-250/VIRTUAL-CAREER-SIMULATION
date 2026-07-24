<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
$rows = $con->query('SELECT q.*,c.title career,(SELECT COUNT(*) FROM quiz_questions x WHERE x.quiz_id=q.id) questions FROM quizzes q JOIN careers c ON c.id=q.career_id ORDER BY q.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Quizzes';
require __DIR__ . '/includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3">Quizzes</h1>
        <p class="text-muted">Create quiz details here. Questions are added from the linked simulation.</p>
    </div><a class="btn btn-primary create-action content-editor-link" href="<?= url('admin/content_edit.php?entity=quiz') ?>"><i class="bi bi-plus-circle"></i><span>Add quiz</span></a>
</div>
<div class="card table-responsive">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Quiz</th>
                <th>Career</th>
                <th>Pass score</th>
                <th>Time limit</th>
                <th>Questions</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody><?php foreach ($rows as $r): ?><tr>
                    <td><strong><?= e($r['title']) ?></strong>
                        <div class="small text-muted"><?= e($r['description']) ?></div>
                    </td>
                    <td><?= e($r['career']) ?></td>
                    <td><?= $r['pass_score'] ?>%</td>
                    <td><?= $r['time_limit_minutes'] ?> minutes</td>
                    <td><?= $r['questions'] ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary content-editor-link" href="<?= url('admin/content_edit.php?entity=quiz&id=' . $r['id']) ?>">Edit quiz details</a></td>
                </tr><?php endforeach ?></tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>