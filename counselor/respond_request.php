<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');
verify_csrf();

$counselorId = (int)user()['id'];
$requestId = (int)($_POST['request_id'] ?? 0);
$response = trim($_POST['counselor_response'] ?? '');
$status = trim($_POST['status'] ?? 'responded');
$scheduleAppt = !empty($_POST['schedule_appointment']);

// Fetch request
$stmt = $con->prepare("SELECT * FROM counselling_requests WHERE id=?");
$stmt->bind_param('i', $requestId);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    json_response(['ok' => false, 'message' => 'Counselling request not found.'], 404);
}
if (empty($response) && !$scheduleAppt) {
    json_response(['ok' => false, 'message' => 'Please provide a response or schedule an appointment.'], 422);
}

// Allowed statuses
$validStatuses = ['pending', 'responded', 'scheduled', 'completed', 'cancelled'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'responded';
}

$apptId = null;
if ($scheduleAppt) {
    $apptDate = $_POST['appointment_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $meetingType = $_POST['meeting_type'] ?? 'online_video';
    $meetingLink = trim($_POST['meeting_link'] ?? '');
    $location = trim($_POST['location_details'] ?? '');
    $title = trim($_POST['title'] ?? 'Career Guidance Session: ' . $req['topic']);

    if (empty($apptDate) || empty($startTime) || empty($endTime)) {
        json_response(['ok' => false, 'message' => 'Please provide an appointment date, start time, and end time.'], 422);
    }

    $stmtAppt = $con->prepare("
        INSERT INTO counselling_appointments 
        (request_id, student_id, counselor_id, title, appointment_date, start_time, end_time, meeting_type, meeting_link, location_details, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmtAppt->bind_param('iiisssssss', $requestId, $req['student_id'], $counselorId, $title, $apptDate, $startTime, $endTime, $meetingType, $meetingLink, $location);
    $stmtAppt->execute();
    $apptId = $con->insert_id;
    $status = 'scheduled';

    notify_user($con, (int)$req['student_id'], 'appointment', 'Your counsellor scheduled a guidance appointment: ' . $title . ' on ' . date('M j, Y', strtotime($apptDate)) . ' at ' . date('H:i', strtotime($startTime)));
}

// Update request
$stmt = $con->prepare("
    UPDATE counselling_requests 
    SET counselor_response = ?, responded_by = ?, responded_at = NOW(), status = ?, updated_at = NOW() 
    WHERE id = ?
");
$stmt->bind_param('sisi', $response, $counselorId, $status, $requestId);
$stmt->execute();

// Notify student
notify_user($con, (int)$req['student_id'], 'counselling_response', 'Your counsellor replied to your request: ' . $req['topic']);

admin_log($con, 'respond_counselling_request', 'counselling_requests', $requestId, 'Counselor responded to request #' . $requestId);

json_response([
    'ok' => true,
    'message' => $scheduleAppt ? 'Response sent and appointment scheduled successfully.' : 'Response sent to student successfully.',
    'reload' => true
]);
