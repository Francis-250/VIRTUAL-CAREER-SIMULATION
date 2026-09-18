<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');
verify_csrf();

$counselorId = (int)user()['id'];
$studentId = (int)($_POST['student_id'] ?? 0);
$careerId = !empty($_POST['career_id']) ? (int)$_POST['career_id'] : null;
$pathwayTitle = trim($_POST['pathway_title'] ?? '');
$guidanceNotes = trim($_POST['guidance_notes'] ?? '');
$recommendedSteps = trim($_POST['recommended_steps'] ?? '');

// Verify student exists
$stmt = $con->prepare("SELECT id, name FROM users WHERE id=? AND role='student' AND deleted_at IS NULL");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    json_response(['ok' => false, 'message' => 'Student record not found.'], 404);
}
if (empty($pathwayTitle)) {
    json_response(['ok' => false, 'message' => 'Please provide a pathway title.'], 422);
}
if (empty($guidanceNotes)) {
    json_response(['ok' => false, 'message' => 'Please provide guidance notes explaining this pathway.'], 422);
}

$stmt = $con->prepare("
  INSERT INTO counsellor_pathway_recommendations (student_id, counselor_id, career_id, pathway_title, guidance_notes, recommended_steps)
  VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('iiisss', $studentId, $counselorId, $careerId, $pathwayTitle, $guidanceNotes, $recommendedSteps);
$stmt->execute();
$recId = $con->insert_id;

// Notify the student
notify_user($con, $studentId, 'pathway_recommendation', 'Your Career Counsellor recommended a career pathway: ' . $pathwayTitle);

admin_log($con, 'recommend_pathway', 'counsellor_pathway_recommendations', $recId, 'Counselor recommended pathway "' . $pathwayTitle . '" to student #' . $studentId);

json_response([
    'ok' => true,
    'message' => 'Career pathway recommendation saved and student notified.',
    'reload' => true
]);
