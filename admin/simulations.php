<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
$rows = $con->query('SELECT s.*,c.title career,q.title quiz,q.description quiz_description,(SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id=q.id) questions FROM career_simulations s JOIN careers c ON c.id=s.career_id JOIN quizzes q ON q.id=s.quiz_id ORDER BY s.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$raw = $con->query('SELECT qq.id,qq.quiz_id,qq.question_text,qq.points,qo.id option_id,qo.option_text,qo.is_correct FROM quiz_questions qq LEFT JOIN quiz_options qo ON qo.question_id=qq.id ORDER BY qq.quiz_id,qq.sort_order,qo.sort_order')->fetch_all(MYSQLI_ASSOC);
$questionsByQuiz = [];
foreach ($raw as $item) {
    $quizId = (int)$item['quiz_id'];
    $questionId = (int)$item['id'];
    if (!isset($questionsByQuiz[$quizId][$questionId])) $questionsByQuiz[$quizId][$questionId] = ['id' => $questionId, 'quiz_id' => $quizId, 'question_text' => $item['question_text'], 'points' => (int)$item['points'], 'options' => [], 'correct_option' => 0];
    if ($item['option_id']) {
        $index = count($questionsByQuiz[$quizId][$questionId]['options']);
        $questionsByQuiz[$quizId][$questionId]['options'][] = $item['option_text'];
        if ($item['is_correct']) $questionsByQuiz[$quizId][$questionId]['correct_option'] = $index;
    }
}
$quizOptions = $con->query('SELECT q.id,q.title,q.description,q.career_id,c.title career,COALESCE(s.difficulty_level,"beginner") difficulty FROM quizzes q JOIN careers c ON c.id=q.career_id LEFT JOIN career_simulations s ON s.quiz_id=q.id ORDER BY c.title,q.title')->fetch_all(MYSQLI_ASSOC);
$aiOptions = array_map(fn($q) => ['id' => $q['id'], 'label' => $q['title'], 'career_id' => $q['career_id'], 'career' => $q['career'], 'description' => $q['description'], 'difficulty' => $q['difficulty']], $quizOptions);
$pageTitle = 'Simulations';
require __DIR__ . '/includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3">Simulations</h1>
        <p class="text-muted">Select a career and quiz, then let AI create the simulation questions.</p>
    </div><button class="btn btn-primary ai-generate" data-kind="quiz_questions" data-options="<?= e(json_encode($aiOptions)) ?>" data-create-simulation="1"><i class="bi bi-stars"></i> Create simulation with AI</button>
</div>
<div class="card table-responsive">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Simulation</th>
                <th>Career</th>
                <th>Related quiz</th>
                <th>Level</th>
                <th>Questions</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody><?php foreach ($rows as $r): ?><tr>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['career']) ?></td>
                    <td><strong><?= e($r['quiz']) ?></strong>
                        <div class="small text-muted"><?= e($r['quiz_description']) ?></div>
                    </td>
                    <td><?= e(ucfirst($r['difficulty_level'])) ?></td>
                    <td><button class="btn btn-sm btn-light border" data-bs-toggle="collapse" data-bs-target="#simulationQuestions<?= $r['id'] ?>"><?= $r['questions'] ?> · View</button></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary content-editor-link" href="<?= url('admin/content_edit.php?entity=simulation&id=' . $r['id']) ?>">Edit simulation</a></td>
                </tr>
                <tr class="collapse" id="simulationQuestions<?= $r['id'] ?>">
                    <td colspan="6" class="p-0">
                        <div class="quiz-question-list"><?php foreach ($questionsByQuiz[$r['quiz_id']] ?? [] as $question): ?><div class="quiz-question-admin-row">
                                    <div><strong><?= e($question['question_text']) ?></strong><small>4 choices · <?= $question['points'] ?> point<?= $question['points'] === 1 ? '' : 's' ?></small></div><button class="btn btn-sm btn-outline-primary edit-simulation-question" data-question="<?= e(json_encode($question)) ?>" data-title="<?= e($r['quiz']) ?>"><i class="bi bi-pencil"></i> Edit</button>
                                </div><?php endforeach ?><?php if (empty($questionsByQuiz[$r['quiz_id']])): ?><div class="text-muted p-3">No questions yet. Use “Generate questions with AI” above.</div><?php endif ?></div>
                    </td>
                </tr><?php endforeach ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="simulationQuestionModal">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="simulationQuestionForm">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="simulationQuestionHeading">Add multiple-choice question</h2><small class="text-muted" id="simulationQuestionTarget"></small>
                </div><button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="quiz_id"><input type="hidden" name="question_id"><input type="hidden" name="action" value="save"><label class="form-label fw-semibold">Question</label><textarea class="form-control mb-3" name="question_text" maxlength="500" rows="3" required></textarea>
                <div class="row g-3"><?php for ($i = 0; $i < 4; $i++): ?><div class="col-md-6"><label class="form-label">Choice <?= $i + 1 ?></label>
                            <div class="input-group"><span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correct_option" value="<?= $i ?>" required></span><input class="form-control" name="options[]" required maxlength="300"></div>
                        </div><?php endfor ?><div class="col-md-4"><label class="form-label">Points</label><input type="number" class="form-control" name="points" min="1" max="100" value="1" required></div>
                </div>
                <div class="form-text mt-3">Enter four choices and select one correct answer.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-danger me-auto d-none" id="deleteSimulationQuestion">Delete</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save question</button></div>
        </form>
    </div>
</div>
<script>
    const sqModal = document.getElementById('simulationQuestionModal'),
        sqForm = document.getElementById('simulationQuestionForm'),
        sqDelete = document.getElementById('deleteSimulationQuestion');

    function openSimulationQuestion(quizId, title, question = null) {
        sqForm.reset();
        sqForm.noValidate = false;
        sqForm.elements.quiz_id.value = quizId;
        sqForm.elements.question_id.value = question?.id || '';
        sqForm.elements.action.value = 'save';
        document.getElementById('simulationQuestionTarget').textContent = title;
        document.getElementById('simulationQuestionHeading').textContent = question ? 'Edit multiple-choice question' : 'Add multiple-choice question';
        sqDelete.classList.toggle('d-none', !question);
        if (question) {
            sqForm.elements.question_text.value = question.question_text;
            sqForm.elements.points.value = question.points;
            [...sqForm.querySelectorAll('[name="options[]"]')].forEach((input, i) => input.value = question.options[i] || '');
            sqForm.querySelector(`[name=correct_option][value="${question.correct_option}"]`).checked = true
        }
        bootstrap.Modal.getOrCreateInstance(sqModal).show()
    }
    document.querySelectorAll('.edit-simulation-question').forEach(button => button.onclick = () => {
        const question = JSON.parse(button.dataset.question);
        openSimulationQuestion(question.quiz_id, button.dataset.title, question)
    });
    sqDelete.onclick = () => {
        if (confirm('Delete this question?')) {
            sqForm.noValidate = true;
            sqForm.elements.action.value = 'delete';
            sqForm.requestSubmit()
        }
    };
    sqForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        const submit = this.querySelector('[type=submit]');
        submit.disabled = true;
        try {
            const response = await fetch(APP.base + 'admin/save_quiz_question.php', {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-CSRF-Token': APP.csrf
                    }
                }),
                data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'Could not save question.');
            toast(data.message, 'success');
            bootstrap.Modal.getInstance(sqModal)?.hide();
            setTimeout(() => location.reload(), 450)
        } catch (error) {
            toast(error.message, 'danger');
            submit.disabled = false
        }
    });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>