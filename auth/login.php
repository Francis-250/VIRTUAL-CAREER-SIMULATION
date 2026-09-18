<?php
require_once dirname(__DIR__).'/includes/functions.php';
if (user()) {
    header('Location: ' . url(role_home(user()['role'])));
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $con->prepare('SELECT * FROM users WHERE email=? AND deleted_at IS NULL LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if (!$u || !password_verify($password, $u['password'])) {
        $error = 'Invalid email or password.';
    } elseif ($u['status'] === 'pending') {
        $error = 'Please verify your email before signing in.';
        $_SESSION['verify_user_id'] = $u['id'];
    } elseif ($u['status'] === 'suspended') {
        $error = 'This account is suspended. Please contact an administrator.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'role' => $u['role'],
            'profile_complete' => (bool)$u['profile_completed_at']
        ];
        $stmt = $con->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?');
        $stmt->bind_param('i', $u['id']);
        $stmt->execute();

        // Admin log
        admin_log($con, 'login', 'user', $u['id'], 'User logged in: ' . $u['role']);

        $target = $u['profile_completed_at'] ? role_home($u['role']) : 'profile.php';
        header('Location: ' . url($target));
        exit;
    }
}
$pageTitle = 'Sign in';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5">
  <div class="card p-4 p-md-5 mx-auto shadow-sm border-0 rounded-4" style="max-width: 500px;">
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
        <i class="bi bi-compass-fill fs-3"></i>
      </div>
      <h1 class="h3 fw-bold">Career Guidance System</h1>
      <p class="text-muted">Sign in to access your guidance portal</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
          <?=e($error)?>
          <?php if (isset($_SESSION['verify_user_id'])): ?>
            <a class="alert-link ms-1" href="<?=url('auth/verify_otp.php')?>">Verify now</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" id="loginForm">
      <div class="mb-3">
        <label class="form-label fw-semibold">Email address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" name="email" id="loginEmail" required autofocus>
        </div>
      </div>

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label fw-semibold mb-0">Password</label>
          <a href="<?=url('auth/forgot_password.php')?>" class="small text-decoration-none">Forgot?</a>
        </div>
        <div class="input-group mt-1">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" name="password" id="loginPassword" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
      </button>
    </form>

    <div class="mt-4 pt-3 border-top text-center">
      <p class="small text-muted mb-2">New student? <a href="<?=url('auth/register.php')?>" class="fw-semibold">Register an account</a></p>
    </div>

    <!-- Quick Role Switcher Demo Helpers -->
    <!-- <div class="card mt-3 bg-light border p-3 rounded-3">
      <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Quick Demo Accounts</div>
      <div class="d-flex flex-column gap-1 small">
        <button type="button" class="btn btn-sm btn-outline-primary text-start fill-creds" data-email="student@careersim.test" data-pass="Student123!">
          <i class="bi bi-person me-1"></i><strong>Student:</strong> student@careersim.test
        </button>
        <button type="button" class="btn btn-sm btn-outline-info text-dark text-start fill-creds" data-email="counselor@careersim.test" data-pass="Counselor123!">
          <i class="bi bi-person-badge me-1"></i><strong>Career Counsellor:</strong> counselor@careersim.test
        </button>
        <button type="button" class="btn btn-sm btn-outline-dark text-start fill-creds" data-email="admin@careersim.test" data-pass="Admin123!">
          <i class="bi bi-shield-lock me-1"></i><strong>Admin:</strong> admin@careersim.test
        </button>
      </div>
    </div> -->
  </div>
</div>

<script>
document.querySelectorAll('.fill-creds').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('loginEmail').value = btn.dataset.email;
    document.getElementById('loginPassword').value = btn.dataset.pass;
  });
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
