<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor']);
verify_csrf();

$studentId = (int)user()['id'];
$requestId = (int)($_POST['id'] ?? 0);

$stmt = $con->prepare("SELECT id FROM counselling_requests WHERE id=? AND student_id=? AND status='pending'");
$stmt->bind_param('ii', $requestId, $studentId);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    json_response(['ok' => false, 'message' => 'Pending counselling request not found.'], 404);
}

$stmt = $con->prepare("UPDATE counselling_requests SET status='cancelled', updated_at=NOW() WHERE id=?");
$stmt->bind_param('i', $requestId);
$stmt->execute();

json_response(['ok' => true, 'message' => 'Counselling request cancelled.', 'reload' => true]);
