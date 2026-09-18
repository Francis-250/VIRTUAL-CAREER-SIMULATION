<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_admin();
verify_csrf();

$action = $_POST['action'] ?? 'update';
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? 'student';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        if (mb_strlen($name) < 2) throw new RuntimeException('Please enter a valid full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please enter a valid email address.');
        if (!in_array($role, ['student', 'counselor', 'admin'], true)) throw new RuntimeException('Invalid role specified.');
        if (!in_array($status, ['active', 'pending', 'suspended'], true)) throw new RuntimeException('Invalid status specified.');
        if (strlen($password) < 8) throw new RuntimeException('Password must be at least 8 characters long.');

        // Check if email exists
        $stmt = $con->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException('An account with that email address already exists.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $verifiedAt = $status === 'active' ? date('Y-m-d H:i:s') : null;
        $profileCompletedAt = date('Y-m-d H:i:s');

        $stmt = $con->prepare("
            INSERT INTO users (name, email, password, role, status, email_verified_at, profile_completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssssss', $name, $email, $hash, $role, $status, $verifiedAt, $profileCompletedAt);
        $stmt->execute();
        $newId = $con->insert_id;

        admin_log($con, 'create_user', 'users', $newId, "Admin created user account for $name ($email) as $role");

        json_response(['ok' => true, 'message' => "User account for $name ($role) created successfully.", 'reload' => true]);

    } elseif ($action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';
        $resetPassword = trim($_POST['new_password'] ?? '');

        if (!$id) throw new RuntimeException('User ID is required.');
        if (mb_strlen($name) < 2) throw new RuntimeException('Please enter a valid full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please enter a valid email address.');
        if (!in_array($role, ['student', 'counselor', 'admin'], true)) throw new RuntimeException('Invalid role specified.');
        if (!in_array($status, ['active', 'pending', 'suspended'], true)) throw new RuntimeException('Invalid status specified.');

        // Self-protection
        if ($id === (int)user()['id']) {
            if ($role !== 'admin' || $status !== 'active') {
                throw new RuntimeException('You cannot demote or suspend your own administrator account.');
            }
        }

        // Email uniqueness
        $stmt = $con->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException('That email address is already in use by another user.');
        }

        if (!empty($resetPassword)) {
            if (strlen($resetPassword) < 8) throw new RuntimeException('New password must be at least 8 characters long.');
            $hash = password_hash($resetPassword, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE users SET name=?, email=?, role=?, status=?, password=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sssssi', $name, $email, $role, $status, $hash, $id);
        } else {
            $stmt = $con->prepare("UPDATE users SET name=?, email=?, role=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ssssi', $name, $email, $role, $status, $id);
        }
        $stmt->execute();

        admin_log($con, 'update_user', 'users', $id, "Admin updated user #$id: role=$role, status=$status" . (!empty($resetPassword) ? " (password reset)" : ""));

        json_response(['ok' => true, 'message' => "User account updated successfully.", 'reload' => true]);

    } elseif ($action === 'delete') {
        if (!$id) throw new RuntimeException('User ID is required.');
        if ($id === (int)user()['id']) {
            throw new RuntimeException('You cannot delete your own administrator account.');
        }

        $stmt = $con->prepare("UPDATE users SET deleted_at=NOW(), status='suspended' WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        admin_log($con, 'delete_user', 'users', $id, "Admin deactivated user #$id");

        json_response(['ok' => true, 'message' => "User account deactivated successfully.", 'reload' => true]);
    }

    throw new RuntimeException('Unsupported user action.');
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => $e->getMessage()], 422);
}
