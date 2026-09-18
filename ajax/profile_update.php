<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor','admin']);
verify_csrf();

$uid = (int)user()['id'];
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$education = $_POST['education_level'] ?? null;
$dob = $_POST['date_of_birth'] ?: null;

if (mb_strlen($name) < 2) {
    json_response(['ok' => false, 'message' => 'Please enter your full name.'], 422);
}
if ($education && !in_array($education, ['secondary','undergraduate','graduate','other'], true)) {
    json_response(['ok' => false, 'message' => 'Invalid education level.'], 422);
}

$stmt = $con->prepare('UPDATE users SET name=?, phone=?, education_level=?, date_of_birth=?, updated_at=NOW() WHERE id=?');
$stmt->bind_param('ssssi', $name, $phone, $education, $dob, $uid);
$stmt->execute();

$_SESSION['user']['name'] = $name;

json_response(['ok' => true, 'message' => 'Profile updated successfully!', 'reload' => true]);
