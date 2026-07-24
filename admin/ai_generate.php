<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
verify_csrf();
$kind = $_POST['kind'] ?? '';
$topic = trim($_POST['topic'] ?? '');
$difficulty = $_POST['difficulty'] ?? 'beginner';
$targetId = (int)($_POST['target_id'] ?? 0);
$interestType = $_POST['interest_type'] ?? '';
$createSimulation = isset($_POST['create_simulation']);
if (!in_array($kind, ['simulation_tasks', 'quiz_questions', 'interest_questions'], true) || ($kind === 'interest_questions' && $topic === '') || mb_strlen($topic) > 300) json_response(['ok' => false, 'message' => 'Choose a generator and enter a topic.'], 422);
if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'beginner';
$context = '';
if ($kind === 'simulation_tasks') {
    $s = $con->prepare('SELECT s.title,s.description simulation_description,c.title career,q.title quiz,q.description quiz_description FROM career_simulations s JOIN careers c ON c.id=s.career_id JOIN quizzes q ON q.id=s.quiz_id WHERE s.id=?');
    $s->bind_param('i', $targetId);
    $s->execute();
    $x = $s->get_result()->fetch_assoc();
    if (!$x) json_response(['ok' => false, 'message' => 'Simulation or its related quiz was not found.'], 404);
    if ($topic === '') $topic = $x['quiz_description'] ?: $x['quiz'];
    $context = "Career: {$x['career']}; simulation: {$x['title']}; simulation description: {$x['simulation_description']}; related quiz: {$x['quiz']}; quiz description: {$x['quiz_description']}";
} elseif ($kind === 'quiz_questions') {
    $s = $con->prepare('SELECT q.title,q.description,c.title career FROM quizzes q JOIN careers c ON c.id=q.career_id WHERE q.id=?');
    $s->bind_param('i', $targetId);
    $s->execute();
    $x = $s->get_result()->fetch_assoc();
    if (!$x) json_response(['ok' => false, 'message' => 'Quiz not found.'], 404);
    if ($createSimulation || $topic === '') $topic = $x['description'] ?: $x['title'];
    $context = "Career: {$x['career']}; quiz: {$x['title']}; quiz description: {$x['description']}";
} else {
    if (!in_array($interestType, ['realistic', 'investigative', 'artistic', 'social', 'enterprising', 'conventional'], true)) json_response(['ok' => false, 'message' => 'Choose a RIASEC type.'], 422);
    $context = "RIASEC type: $interestType";
}
$schema = $kind === 'simulation_tasks'
    ? '{"items":[{"title":"...","instructions":"...","task_type":"scenario_choice|case_study|practical_exercise|reflection","max_score":10,"options":[{"text":"...","score":10,"feedback":"...","best":true}]}]}'
    : '{"items":[{"question":"...","question_type":"single_choice","points":1,"options":[{"text":"...","correct":true}]}]}';
if ($kind === 'interest_questions') $schema = '{"items":[{"question":"..."}]}';
$instructions = $kind === 'quiz_questions'
    ? 'Create exactly ten unique single-choice multiple-choice questions that specifically assess knowledge, decisions, tools, responsibilities, or realistic situations in the named career and quiz title. Every item must use question_type "single_choice", contain exactly four plausible answer options, and have exactly one option with correct true. Do not reuse generic questions across careers.'
    : ($kind === 'simulation_tasks' ? 'Create realistic tasks that a person in the named career could encounter. Use scenario_choice or case_study when answer options are appropriate, with exactly four options, useful feedback, scores, and exactly one best option. Use practical_exercise or reflection for free-text tasks and return an empty options array for those tasks.' : 'Create clear interest statements that measure the named RIASEC dimension without mentioning the dimension by name.');
$reply = groq_chat([['role' => 'system', 'content' => "You draft CareerSim educational content for human review. Return valid JSON only using this exact shape: $schema. $instructions Avoid personal data."], ['role' => 'user', 'content' => "Context: $context\nRequested topic: $topic\nDifficulty: $difficulty"]], ['json' => true, 'max_tokens' => $kind === 'quiz_questions' ? 3500 : 1800, 'timeout' => 30]);
if ($reply === null) json_response(['ok' => false, 'message' => 'AI is temporarily unavailable. Please try again later.'], 503);
$data = json_decode($reply, true);
if (!is_array($data) || !isset($data['items'])) {
    error_log('[CareerSim Groq] Invalid authoring JSON');
    json_response(['ok' => false, 'message' => 'AI is temporarily unavailable. Please try again later.'], 503);
}
json_response(['ok' => true, 'draft' => $data, 'draft_json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);
