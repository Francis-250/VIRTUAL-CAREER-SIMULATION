<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');
verify_csrf();

$counselorId = (int)user()['id'];
$action = $_POST['action'] ?? 'create';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'create') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['appointment_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $type = $_POST['meeting_type'] ?? 'online_video';
    $link = trim($_POST['meeting_link'] ?? '');
    $location = trim($_POST['location_details'] ?? '');
    $notes = trim($_POST['counselor_notes'] ?? '');

    if (!$studentId || empty($title) || empty($date) || empty($startTime) || empty($endTime)) {
        json_response(['ok' => false, 'message' => 'Please fill in all required appointment fields.'], 422);
    }

    $stmt = $con->prepare("
        INSERT INTO counselling_appointments 
        (student_id, counselor_id, title, appointment_date, start_time, end_time, meeting_type, meeting_link, location_details, counselor_notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmt->bind_param('iissssssss', $studentId, $counselorId, $title, $date, $startTime, $endTime, $type, $link, $location, $notes);
    $stmt->execute();
    $apptId = $con->insert_id;

    notify_user($con, $studentId, 'appointment', 'New career counselling appointment scheduled: ' . $title . ' on ' . date('M j, Y', strtotime($date)) . ' at ' . date('H:i', strtotime($startTime)));
    admin_log($con, 'schedule_appointment', 'counselling_appointments', $apptId, 'Appointment scheduled with student #' . $studentId);

    json_response(['ok' => true, 'message' => 'Appointment scheduled successfully!', 'reload' => true]);

} elseif ($action === 'complete') {
    $actionPlan = trim($_POST['action_plan'] ?? '');
    $notes = trim($_POST['counselor_notes'] ?? '');

    $stmt = $con->prepare("SELECT * FROM counselling_appointments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();

    if (!$appt) {
        json_response(['ok' => false, 'message' => 'Appointment not found.'], 404);
    }

    $stmt = $con->prepare("
        UPDATE counselling_appointments 
        SET status='completed', action_plan=?, counselor_notes=?, updated_at=NOW() 
        WHERE id=?
    ");
    $stmt->bind_param('ssi', $actionPlan, $notes, $id);
    $stmt->execute();

    if (!empty($appt['request_id'])) {
        $con->query("UPDATE counselling_requests SET status='completed' WHERE id=" . (int)$appt['request_id']);
    }

    notify_user($con, (int)$appt['student_id'], 'appointment_completed', 'Your counsellor completed session "' . $appt['title'] . '" and added an Action Plan.');
    admin_log($con, 'complete_appointment', 'counselling_appointments', $id, 'Completed session #' . $id);

    json_response(['ok' => true, 'message' => 'Session marked as completed with Action Plan.', 'reload' => true]);

} elseif ($action === 'update' || $action === 'reschedule') {
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['appointment_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $type = $_POST['meeting_type'] ?? 'online_video';
    $link = trim($_POST['meeting_link'] ?? '');
    $location = trim($_POST['location_details'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    $notes = trim($_POST['counselor_notes'] ?? '');

    $stmt = $con->prepare("SELECT * FROM counselling_appointments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();

    if (!$appt) {
        json_response(['ok' => false, 'message' => 'Appointment not found.'], 404);
    }

    $stmt = $con->prepare("
        UPDATE counselling_appointments 
        SET title=?, appointment_date=?, start_time=?, end_time=?, meeting_type=?, meeting_link=?, location_details=?, counselor_notes=?, status=?, updated_at=NOW() 
        WHERE id=?
    ");
    $stmt->bind_param('sssssssssi', $title, $date, $startTime, $endTime, $type, $link, $location, $notes, $status, $id);
    $stmt->execute();

    notify_user($con, (int)$appt['student_id'], 'appointment_update', 'Appointment updated: ' . $title . ' on ' . date('M j, Y', strtotime($date)) . ' at ' . date('H:i', strtotime($startTime)));

    json_response(['ok' => true, 'message' => 'Appointment updated successfully.', 'reload' => true]);

} elseif ($action === 'cancel') {
    $stmt = $con->prepare("SELECT * FROM counselling_appointments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $appt = $stmt->get_result()->fetch_assoc();

    if (!$appt) {
        json_response(['ok' => false, 'message' => 'Appointment not found.'], 404);
    }

    $stmt = $con->prepare("UPDATE counselling_appointments SET status='cancelled', updated_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    notify_user($con, (int)$appt['student_id'], 'appointment_cancelled', 'Your appointment "' . $appt['title'] . '" was cancelled by the counsellor.');

    json_response(['ok' => true, 'message' => 'Appointment cancelled.', 'reload' => true]);
}

json_response(['ok' => false, 'message' => 'Invalid action.'], 400);
