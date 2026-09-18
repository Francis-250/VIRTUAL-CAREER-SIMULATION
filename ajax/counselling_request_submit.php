<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor']);
verify_csrf();

$studentId = (int)user()['id'];
$topic = trim($_POST['topic'] ?? '');
$message = trim($_POST['message'] ?? '');
$preferredDate = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
$preferredTime = trim($_POST['preferred_time_slot'] ?? '');
$counselorId = !empty($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : null;

if (empty($topic)) {
    json_response(['ok' => false, 'message' => 'Please select or enter a topic for counselling.'], 422);
}
if (mb_strlen($message) < 10) {
    json_response(['ok' => false, 'message' => 'Please describe your counselling request in at least 10 characters.'], 422);
}
if ($preferredDate && strtotime($preferredDate) < strtotime(date('Y-m-d'))) {
    json_response(['ok' => false, 'message' => 'Preferred date must be today or in the future.'], 422);
}

// Verify counselor exists if selected
if ($counselorId) {
    $cCheck = $con->prepare("SELECT id FROM users WHERE id=? AND role='counselor' AND status='active'");
    $cCheck->bind_param('i', $counselorId);
    $cCheck->execute();
    if (!$cCheck->get_result()->fetch_assoc()) {
        $counselorId = null;
    }
}

$stmt = $con->prepare("INSERT INTO counselling_requests (student_id, counselor_id, topic, message, preferred_date, preferred_time_slot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param('iissss', $studentId, $counselorId, $topic, $message, $preferredDate, $preferredTime);
$stmt->execute();
$requestId = $con->insert_id;

// Notify counselor
if ($counselorId) {
    notify_user($con, $counselorId, 'counselling_request', 'New counselling request from ' . user()['name'] . ': ' . $topic);
} else {
    // Notify all active counselors
    $counselors = $con->query("SELECT id FROM users WHERE role='counselor' AND status='active'");
    while ($c = $counselors->fetch_assoc()) {
        notify_user($con, (int)$c['id'], 'counselling_request', 'New counselling request from ' . user()['name'] . ': ' . $topic);
    }
}

admin_log($con, 'request_counselling', 'counselling_requests', $requestId, 'Student ' . user()['name'] . ' requested counselling on ' . $topic);

json_response([
    'ok' => true,
    'message' => 'Your career counselling request has been sent! A counsellor will review your profile and respond soon.',
    'reload' => true
]);
