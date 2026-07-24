<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
$entity = $_GET['entity'] ?? $_POST['entity'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$allowed = ['career', 'simulation', 'quiz', 'interest_question', 'badge'];
if (!in_array($entity, $allowed, true)) {
    http_response_code(404);
    exit('Unknown content type');
}
$tables = ['career' => 'careers', 'simulation' => 'career_simulations', 'quiz' => 'quizzes', 'interest_question' => 'interest_questions', 'badge' => 'badges'];
$row = [];
if ($id) {
    $stmt = $con->prepare("SELECT * FROM {$tables[$entity]} WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        flash('danger', 'Session token expired.');
    } else try {
        if ($entity === 'career') {
            $category = (int)$_POST['category_id'];
            $title = trim($_POST['title']);
            if ($title === '') throw new RuntimeException('Enter a career title.');
            $slug = unique_career_slug($con, $title, $id);
            $summary = trim($_POST['summary']);
            $description = trim($_POST['description']);
            $salary = (float)$_POST['average_salary'];
            $growth = $_POST['growth_outlook'];
            $image = (string)($row['image'] ?? '');
            $upload = $_FILES['image_file'] ?? null;
            if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($upload['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('The career image could not be uploaded. Please try again.');
                if ((int)$upload['size'] > 5 * 1024 * 1024) throw new RuntimeException('The career image must be 5 MB or smaller.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) throw new RuntimeException('Upload a JPG, PNG, or WEBP image.');
                $uploadDir = dirname(__DIR__) . '/uploads/careers';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) throw new RuntimeException('The image folder is unavailable.');
                $filename = $slug . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($upload['tmp_name'], $uploadDir . '/' . $filename)) throw new RuntimeException('The career image could not be saved.');
                $image = 'uploads/careers/' . $filename;
            }
            if ($image === '') throw new RuntimeException('Upload a career image from your computer.');
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($id) {
                $s = $con->prepare('UPDATE careers SET category_id=?,title=?,slug=?,summary=?,description=?,average_salary=?,growth_outlook=?,image=?,is_active=? WHERE id=?');
                $s->bind_param('issssdssii', $category, $title, $slug, $summary, $description, $salary, $growth, $image, $active, $id);
            } else {
                $s = $con->prepare('INSERT INTO careers(category_id,title,slug,summary,description,average_salary,growth_outlook,image,is_active) VALUES(?,?,?,?,?,?,?,?,?)');
                $s->bind_param('issssdssi', $category, $title, $slug, $summary, $description, $salary, $growth, $image, $active);
            }
        } elseif ($entity === 'simulation') {
            if (!$id) throw new RuntimeException('New simulations must be created with AI from the Simulations page.');
            $career = (int)$_POST['career_id'];
            $quiz = (int)($_POST['quiz_id'] ?? 0);
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $difficulty = $_POST['difficulty_level'];
            $duration = (int)$_POST['estimated_duration_minutes'];
            $active = isset($_POST['is_active']) ? 1 : 0;
            $check = $con->prepare('SELECT id FROM quizzes WHERE id=? AND career_id=?');
            $check->bind_param('ii', $quiz, $career);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) throw new RuntimeException('Select a quiz that belongs to the same career as the simulation.');
            $s = $con->prepare('UPDATE career_simulations SET career_id=?,quiz_id=?,title=?,description=?,difficulty_level=?,estimated_duration_minutes=?,is_active=? WHERE id=?');
            $s->bind_param('iisssiii', $career, $quiz, $title, $description, $difficulty, $duration, $active, $id);
        } elseif ($entity === 'quiz') {
            $career = (int)$_POST['career_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $pass = (int)$_POST['pass_score'];
            $time = (int)$_POST['time_limit_minutes'];
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($id) {
                $s = $con->prepare('UPDATE quizzes SET career_id=?,title=?,description=?,pass_score=?,time_limit_minutes=?,is_active=? WHERE id=?');
                $s->bind_param('issiiii', $career, $title, $description, $pass, $time, $active, $id);
            } else {
                $s = $con->prepare('INSERT INTO quizzes(career_id,title,description,pass_score,time_limit_minutes,is_active) VALUES(?,?,?,?,?,?)');
                $s->bind_param('issiii', $career, $title, $description, $pass, $time, $active);
            }
        } elseif ($entity === 'interest_question') {
            $text = trim($_POST['question_text']);
            $type = $_POST['interest_type'];
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($id) {
                $s = $con->prepare('UPDATE interest_questions SET question_text=?,interest_type=?,is_active=? WHERE id=?');
                $s->bind_param('ssii', $text, $type, $active, $id);
            } else {
                $s = $con->prepare('INSERT INTO interest_questions(question_text,interest_type,is_active) VALUES(?,?,?)');
                $s->bind_param('ssi', $text, $type, $active);
            }
        } else {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $icon = trim($_POST['icon']);
            $type = $_POST['criteria_type'];
            $value = (int)$_POST['criteria_value'];
            if ($id) {
                $s = $con->prepare('UPDATE badges SET name=?,description=?,icon=?,criteria_type=?,criteria_value=? WHERE id=?');
                $s->bind_param('ssssii', $name, $description, $icon, $type, $value, $id);
            } else {
                $s = $con->prepare('INSERT INTO badges(name,description,icon,criteria_type,criteria_value) VALUES(?,?,?,?,?)');
                $s->bind_param('ssssi', $name, $description, $icon, $type, $value);
            }
        }
        $s->execute();
        $savedId = $id ?: $con->insert_id;
        admin_log($con, $id ? 'update' : 'create', $entity, $savedId, 'Content saved by content manager');
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') json_response(['ok' => true, 'message' => 'Content saved successfully.', 'reload' => true]);
        flash('success', 'Content saved successfully.');
        $dest = ['career' => 'careers.php', 'simulation' => 'simulations.php', 'quiz' => 'quizzes.php', 'interest_question' => 'interest_questions.php', 'badge' => 'badges.php'][$entity];
        header('Location:' . url('admin/' . $dest));
        exit;
    } catch (Throwable $e) {
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        flash('danger', $e->getMessage());
    }
}
$careers = $con->query('SELECT id,title FROM careers ORDER BY title');
$categories = $con->query('SELECT id,name FROM career_categories ORDER BY name');
$quizChoices = $con->query('SELECT q.id,q.career_id,q.title,q.description,c.title career FROM quizzes q JOIN careers c ON c.id=q.career_id ORDER BY c.title,q.title')->fetch_all(MYSQLI_ASSOC);
$pageTitle = ($id ? 'Edit ' : 'Add ') . str_replace('_', ' ', $entity);
require __DIR__ . '/includes/header.php'; ?>
<div class="card p-4 mx-auto content-editor-card" style="max-width:850px">
    <h1 class="h3"><?= e(ucwords($pageTitle)) ?></h1>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/content_edit.php?entity=' . urlencode($entity) . ($id ? '&id=' . $id : '')) ?>" class="row g-3 content-editor-form"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="entity" value="<?= e($entity) ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <?php if ($entity === 'career'): ?><div class="col-md-6"><label class="form-label">Category</label><select class="form-select" name="category_id" required><?php foreach ($categories as $x): ?><option value="<?= $x['id'] ?>" <?= ($row['category_id'] ?? 0) == $x['id'] ? 'selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($row['title'] ?? '') ?>" required></div>
            <div class="col-12">
                <button type="button" class="btn btn-outline-primary btn-sm career-ai-content"><i class="bi bi-stars"></i> Generate summary and description</button>
                <span class="form-text ms-2">Uses the career title and creates an editable draft only.</span>
            </div>
            <div class="col-md-6"><label class="form-label">Average salary</label><input type="number" class="form-control" name="average_salary" value="<?= e($row['average_salary'] ?? 0) ?>"></div>
            <div class="col-12"><label class="form-label">Career image</label><input type="file" class="form-control" name="image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= empty($row['image']) ? 'required' : '' ?>>
                <div class="form-text">Upload a JPG, PNG, or WEBP image from your computer (maximum 5 MB). The slug is generated automatically from the title.</div>
                <?php if (!empty($row['image'])): $preview = preg_match('~^https?://~i', $row['image']) ? $row['image'] : url($row['image']); ?>
                    <img src="<?= e($preview) ?>" alt="Current career image" class="mt-3 rounded border" style="width:160px;height:95px;object-fit:cover">
                    <div class="form-text">Leave the file empty to keep this image.</div>
                <?php endif ?>
            </div>
            <div class="col-12"><label class="form-label">Summary</label><textarea class="form-control" name="summary" required><?= e($row['summary'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="5" name="description" required><?= e($row['description'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Growth outlook</label><select class="form-select" name="growth_outlook"><?php foreach (['declining', 'stable', 'growing', 'high_growth'] as $x): ?><option <?= $x === ($row['growth_outlook'] ?? '') ? 'selected' : '' ?>><?= $x ?></option><?php endforeach ?></select></div>
        <?php elseif (in_array($entity, ['simulation', 'quiz'])): ?><div class="col-md-6"><label class="form-label">Career</label><select class="form-select" name="career_id" id="contentCareer"><?php foreach ($careers as $x): ?><option value="<?= $x['id'] ?>" <?= ($row['career_id'] ?? 0) == $x['id'] ? 'selected' : '' ?>><?= e($x['title']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($row['title'] ?? '') ?>" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description"><?= e($row['description'] ?? '') ?></textarea></div><?php if ($entity === 'simulation'): ?><div class="col-md-6"><label class="form-label">Related quiz</label><select class="form-select" name="quiz_id" id="simulationQuiz" required><?php foreach ($quizChoices as $quiz): ?><option value="<?= $quiz['id'] ?>" data-career="<?= $quiz['career_id'] ?>" data-title="<?= e($quiz['title']) ?>" data-description="<?= e($quiz['description']) ?>" <?= ($row['quiz_id'] ?? 0) == $quiz['id'] ? 'selected' : '' ?>><?= e($quiz['career'] . ' — ' . $quiz['title']) ?></option><?php endforeach ?></select></div>
                <div class="col-md-3"><label class="form-label">Level</label><select class="form-select" name="difficulty_level"><?php foreach (['beginner', 'intermediate', 'advanced'] as $x): ?><option <?= $x === ($row['difficulty_level'] ?? '') ? 'selected' : '' ?>><?= $x ?></option><?php endforeach ?></select></div>
                <div class="col-md-3"><label class="form-label">Duration minutes</label><input type="number" class="form-control" name="estimated_duration_minutes" value="<?= e($row['estimated_duration_minutes'] ?? 30) ?>"></div>
                <div class="col-12">
                    <div class="alert alert-light border mb-0"><strong id="selectedQuizTitle"></strong>
                        <div class="small text-muted mt-1" id="selectedQuizDescription"></div>
                    </div>
                </div><?php else: ?><div class="col-md-6"><label class="form-label">Pass score %</label><input type="number" class="form-control" name="pass_score" value="<?= e($row['pass_score'] ?? 60) ?>"></div>
                <div class="col-md-6"><label class="form-label">Time limit minutes</label><input type="number" class="form-control" name="time_limit_minutes" value="<?= e($row['time_limit_minutes'] ?? 15) ?>"></div><?php endif ?>
        <?php elseif ($entity === 'interest_question'): ?><div class="col-12"><label class="form-label">Question</label><textarea class="form-control" name="question_text" required><?= e($row['question_text'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label">RIASEC type</label><select class="form-select" name="interest_type"><?php foreach (['realistic', 'investigative', 'artistic', 'social', 'enterprising', 'conventional'] as $x): ?><option <?= $x === ($row['interest_type'] ?? '') ? 'selected' : '' ?>><?= $x ?></option><?php endforeach ?></select></div>
        <?php else: ?><div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($row['name'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Icon</label><input class="form-control" name="icon" value="<?= e($row['icon'] ?? 'award') ?>"></div>
            <div class="col-12"><label class="form-label">Description</label><input class="form-control" name="description" value="<?= e($row['description'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Criteria</label><select class="form-select" name="criteria_type"><?php foreach (['simulation_complete', 'quiz_pass', 'career_count', 'streak'] as $x): ?><option <?= $x === ($row['criteria_type'] ?? '') ? 'selected' : '' ?>><?= $x ?></option><?php endforeach ?></select></div>
            <div class="col-md-6"><label class="form-label">Required value</label><input type="number" class="form-control" name="criteria_value" value="<?= e($row['criteria_value'] ?? 1) ?>"></div><?php endif ?>
        <?php if ($entity !== 'badge'): ?><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" <?= ($row['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div><?php endif ?><div class="col-12"><button class="btn btn-primary">Save</button> <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button></div>
    </form>
</div><?php require __DIR__ . '/includes/footer.php'; ?>
