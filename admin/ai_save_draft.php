<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
verify_csrf();
$kind = $_POST['kind'] ?? '';
$targetId = (int)($_POST['target_id'] ?? 0);
$interestType = $_POST['interest_type'] ?? '';
$difficulty = $_POST['difficulty'] ?? 'beginner';
$createSimulation = isset($_POST['create_simulation']);
$data = json_decode($_POST['draft_json'] ?? '', true);
if (!in_array($kind, ['simulation_tasks', 'quiz_questions', 'interest_questions'], true) || !is_array($data['items'] ?? null)) json_response(['ok' => false, 'message' => 'The reviewed draft is not valid JSON.'], 422);
if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'beginner';
if ($kind === 'simulation_tasks') {
    $check = $con->prepare('SELECT s.id FROM career_simulations s JOIN quizzes q ON q.id=s.quiz_id AND q.career_id=s.career_id WHERE s.id=?');
    $check->bind_param('i', $targetId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) json_response(['ok' => false, 'message' => 'Link this simulation to a quiz from the same career before saving AI tasks.'], 422);
}
$con->begin_transaction();
try {
    $count = 0;
    if ($kind === 'simulation_tasks') {
        $order = (int)$con->query("SELECT COALESCE(MAX(sort_order),0) n FROM simulation_tasks WHERE simulation_id=$targetId")->fetch_assoc()['n'];
        $task = $con->prepare('INSERT INTO simulation_tasks(simulation_id,title,instructions,task_type,sort_order,max_score) VALUES(?,?,?,?,?,?)');
        $opt = $con->prepare('INSERT INTO task_options(task_id,option_text,score_value,feedback_text,is_best_practice,sort_order) VALUES(?,?,?,?,?,?)');
        foreach ($data['items'] as $item) {
            $title = trim($item['title'] ?? '');
            $instructions = trim($item['instructions'] ?? '');
            $type = $item['task_type'] ?? 'scenario_choice';
            $choices = array_values(array_filter($item['options'] ?? [], fn($o) => trim($o['text'] ?? '') !== ''));
            $isChoice = in_array($type, ['scenario_choice', 'case_study'], true);
            $bestCount = count(array_filter($choices, fn($o) => !empty($o['best'])));
            if (!$title || !$instructions || !in_array($type, ['scenario_choice', 'case_study', 'practical_exercise', 'reflection'], true) || ($isChoice && (count($choices) !== 4 || $bestCount !== 1))) continue;
            $max = max(0, min(1000, (int)($item['max_score'] ?? 10)));
            $order++;
            $task->bind_param('isssii', $targetId, $title, $instructions, $type, $order, $max);
            $task->execute();
            $taskId = $con->insert_id;
            $i = 0;
            if ($isChoice) foreach ($choices as $o) {
                $text = trim($o['text']);
                $score = (int)($o['score'] ?? 0);
                $feedback = trim($o['feedback'] ?? '');
                $best = !empty($o['best']) ? 1 : 0;
                $i++;
                $opt->bind_param('isisii', $taskId, $text, $score, $feedback, $best, $i);
                $opt->execute();
            }
            $count++;
        }
    } elseif ($kind === 'quiz_questions') {
        if ($createSimulation) {
            $quizStmt = $con->prepare('SELECT id,career_id,title,description FROM quizzes WHERE id=?');
            $quizStmt->bind_param('i', $targetId);
            $quizStmt->execute();
            $quiz = $quizStmt->get_result()->fetch_assoc();
            if (!$quiz) throw new RuntimeException('Select a valid quiz.');
            $existing = $con->prepare('SELECT id FROM career_simulations WHERE quiz_id=? LIMIT 1');
            $existing->bind_param('i', $targetId);
            $existing->execute();
            $simulation = $existing->get_result()->fetch_assoc();
            if ($simulation) {
                $update = $con->prepare('UPDATE career_simulations SET difficulty_level=? WHERE id=?');
                $update->bind_param('si', $difficulty, $simulation['id']);
                $update->execute();
            } else {
                $simulationTitle = $quiz['title'] . ' Simulation';
                $simulationDescription = $quiz['description'] ?: 'AI-generated simulation for ' . $quiz['title'];
                $duration = 30;
                $active = 1;
                $insertSimulation = $con->prepare('INSERT INTO career_simulations(career_id,quiz_id,title,description,difficulty_level,estimated_duration_minutes,is_active) VALUES(?,?,?,?,?,?,?)');
                $insertSimulation->bind_param('iisssii', $quiz['career_id'], $targetId, $simulationTitle, $simulationDescription, $difficulty, $duration, $active);
                $insertSimulation->execute();
            }
        }
        $order = (int)$con->query("SELECT COALESCE(MAX(sort_order),0) n FROM quiz_questions WHERE quiz_id=$targetId")->fetch_assoc()['n'];
        $question = $con->prepare('INSERT INTO quiz_questions(quiz_id,question_text,question_type,sort_order,points) VALUES(?,?,?,?,?)');
        $opt = $con->prepare('INSERT INTO quiz_options(question_id,option_text,is_correct,sort_order) VALUES(?,?,?,?)');
        foreach ($data['items'] as $item) {
            $text = trim($item['question'] ?? '');
            $choices = array_values(array_filter($item['options'] ?? [], fn($o) => trim($o['text'] ?? '') !== ''));
            $correctCount = count(array_filter($choices, fn($o) => !empty($o['correct'])));
            $choiceTexts = array_map(fn($o) => mb_strtolower(trim($o['text'] ?? '')), $choices);
            if (!$text || count($choices) !== 4 || count(array_unique($choiceTexts)) !== 4 || $correctCount !== 1) continue;
            $type = 'single_choice';
            $points = max(1, min(100, (int)($item['points'] ?? 1)));
            $order++;
            $question->bind_param('issii', $targetId, $text, $type, $order, $points);
            $question->execute();
            $qid = $con->insert_id;
            $i = 0;
            foreach ($choices as $o) {
                $ot = trim($o['text']);
                $correct = !empty($o['correct']) ? 1 : 0;
                $i++;
                $opt->bind_param('isii', $qid, $ot, $correct, $i);
                $opt->execute();
            }
            $count++;
        }
    } else {
        if (!in_array($interestType, ['realistic', 'investigative', 'artistic', 'social', 'enterprising', 'conventional'])) throw new RuntimeException('Invalid RIASEC type.');
        $s = $con->prepare('INSERT INTO interest_questions(question_text,interest_type,is_active) VALUES(?,?,1)');
        foreach ($data['items'] as $item) {
            $text = trim($item['question'] ?? '');
            if (!$text) continue;
            $s->bind_param('ss', $text, $interestType);
            $s->execute();
            $count++;
        }
    }
    if (!$count) throw new RuntimeException('No valid items were found in the reviewed draft.');
    $con->commit();
    admin_log($con, 'create', 'ai_' . $kind, $targetId, "Approved and saved $count AI-drafted items");
    json_response(['ok' => true, 'message' => "$count reviewed items saved.", 'reload' => true]);
} catch (Throwable $e) {
    $con->rollback();
    json_response(['ok' => false, 'message' => $e->getMessage()], 422);
}
