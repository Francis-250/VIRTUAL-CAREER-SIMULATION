<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
verify_csrf();
$quizId = (int)($_POST['quiz_id'] ?? 0);
$questionId = (int)($_POST['question_id'] ?? 0);
$questionText = trim($_POST['question_text'] ?? '');
$points = max(1, min(100, (int)($_POST['points'] ?? 1)));
$options = array_map('trim', (array)($_POST['options'] ?? []));
$correct = (int)($_POST['correct_option'] ?? -1);
$action = $_POST['action'] ?? 'save';
if ($action === 'delete') {
    if ($questionId < 1) json_response(['ok' => false, 'message' => 'Question not found.'], 404);
    $stmt = $con->prepare('DELETE FROM quiz_questions WHERE id=?');
    $stmt->bind_param('i', $questionId);
    $stmt->execute();
    if (!$stmt->affected_rows) json_response(['ok' => false, 'message' => 'Question not found.'], 404);
    admin_log($con, 'delete', 'quiz_question', $questionId, 'Deleted multiple-choice question');
    json_response(['ok' => true, 'message' => 'Question deleted.', 'reload' => true]);
}
if ($quizId < 1 || $questionText === '' || mb_strlen($questionText) > 500) json_response(['ok' => false, 'message' => 'Enter a valid question.'], 422);
if (!$questionId) json_response(['ok' => false, 'message' => 'New simulation questions must be generated with AI. You can edit generated questions here.'], 422);
if (count($options) !== 4 || in_array('', $options, true) || count(array_unique(array_map('mb_strtolower', $options))) !== 4 || $correct < 0 || $correct > 3) json_response(['ok' => false, 'message' => 'Enter four different answer choices and select exactly one correct answer.'], 422);
$stmt = $con->prepare('SELECT id FROM quizzes WHERE id=?');
$stmt->bind_param('i', $quizId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) json_response(['ok' => false, 'message' => 'Quiz not found.'], 404);
$con->begin_transaction();
try {
    $type = 'single_choice';
    if ($questionId) {
        $stmt = $con->prepare('UPDATE quiz_questions SET quiz_id=?,question_text=?,question_type=?,points=? WHERE id=?');
        $stmt->bind_param('issii', $quizId, $questionText, $type, $points, $questionId);
        $stmt->execute();
        $delete = $con->prepare('DELETE FROM quiz_options WHERE question_id=?');
        $delete->bind_param('i', $questionId);
        $delete->execute();
        $logAction = 'update';
        $message = 'Multiple-choice question updated.';
    } else {
        $order = (int)$con->query("SELECT COALESCE(MAX(sort_order),0)+1 n FROM quiz_questions WHERE quiz_id=$quizId")->fetch_assoc()['n'];
        $stmt = $con->prepare('INSERT INTO quiz_questions(quiz_id,question_text,question_type,sort_order,points) VALUES(?,?,?,?,?)');
        $stmt->bind_param('issii', $quizId, $questionText, $type, $order, $points);
        $stmt->execute();
        $questionId = $con->insert_id;
        $logAction = 'create';
        $message = 'Multiple-choice question added.';
    }
    $insert = $con->prepare('INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order) VALUES(?,?,?,?)');
    foreach ($options as $i => $text) {
        if ($text === '') continue;
        $isCorrect = $i === $correct ? 1 : 0;
        $sort = $i + 1;
        $insert->bind_param('isii', $questionId, $text, $isCorrect, $sort);
        $insert->execute();
    }
    $con->commit();
    admin_log($con, $logAction, 'quiz_question', $questionId, 'Saved multiple-choice question');
    json_response(['ok' => true, 'message' => $message, 'reload' => true]);
} catch (Throwable $e) {
    $con->rollback();
    error_log('Quiz question save error: ' . $e->getMessage());
    json_response(['ok' => false, 'message' => 'The question could not be saved.'], 500);
}
