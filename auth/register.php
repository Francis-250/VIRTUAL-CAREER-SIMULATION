<?php
require_once dirname(__DIR__).'/includes/functions.php';
if (user()) {
    header('Location: ' . url());
    exit;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid session token.';
    }
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $education = $_POST['education_level'] ?? null;
    $dob = $_POST['date_of_birth'] ?: null;
    $password = $_POST['password'] ?? '';

    if (strlen($name) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("INSERT INTO users(name,email,password,phone,education_level,date_of_birth,role,status) VALUES(?,?,?,?,?,?,'student','pending')");
            $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $education, $dob);
            $stmt->execute();
            $uid = $con->insert_id;
            $code = otp_code();
            $stmt = $con->prepare("INSERT INTO otp_codes(user_id,code,purpose,expires_at) VALUES(?,?,'registration',DATE_ADD(NOW(),INTERVAL 10 MINUTE))");
            $stmt->bind_param('is', $uid, $code);
            $stmt->execute();
            $_SESSION['verify_user_id'] = $uid;

            try {
                require_once dirname(__DIR__).'/config/mailer.php';
                $mail = configured_mailer();
                $mail->addAddress($email, $name);
                $mail->Subject = 'Your Career Guidance System verification code';
                $mail->Body = '<h2>Welcome to Career Guidance System</h2><p>Your verification code is <strong>' . e($code) . '</strong>. It expires in 10 minutes.</p>';
                $mail->send();
                flash('success', 'Account created! Check your email for the verification code.');
            } catch (Throwable $e) {
                flash('warning', 'Account created, but email delivery was not available in this environment. You can enter code: ' . $code);
            }
            header('Location: ' . url('auth/verify_otp.php'));
            exit;
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getCode() === 1062 ? 'That email address is already registered.' : 'Registration could not be completed.';
        }
    }
}
$pageTitle = 'Register an Account';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5">
  <div class="card p-4 p-md-5 mx-auto shadow-sm border-0 rounded-4" style="max-width: 680px;">
    <div class="text-center mb-4">
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-2 mb-2">
        <i class="bi bi-person-plus-fill me-1"></i> Student Registration
      </span>
      <h1 class="h3 fw-bold">Create your Student Account</h1>
      <p class="text-muted">Register to complete RIASEC career assessments, receive tailored recommendations, and request one-on-one career counselling.</p>
    </div>

    <?php foreach($errors as $x): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div><?=e($x)?></div>
      </div>
    <?php endforeach; ?>

    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Full name</label>
        <input class="form-control" name="name" required value="<?=e($_POST['name'] ?? '')?>" placeholder="e.g. Alex Johnson">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Email address</label>
        <input type="email" class="form-control" name="email" required value="<?=e($_POST['email'] ?? '')?>" placeholder="student@example.com">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Phone number</label>
        <input class="form-control" name="phone" value="<?=e($_POST['phone'] ?? '')?>" placeholder="+1 555-0199">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Date of birth</label>
        <input type="date" class="form-control" name="date_of_birth">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Current education level</label>
        <select class="form-select" name="education_level" required>
          <option value="">Select your level</option>
          <?php foreach(['secondary' => 'Secondary / High School', 'undergraduate' => 'Undergraduate / College', 'graduate' => 'Graduate / Master / PhD', 'other' => 'Other Vocational / Self-Taught'] as $v => $l): ?>
            <option value="<?=$v?>"><?=$l?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Password (min 8 chars)</label>
        <input type="password" class="form-control" name="password" minlength="8" required placeholder="Choose a secure password">
      </div>
      <div class="col-12 mt-4">
        <button class="btn btn-primary w-100 py-2 fw-semibold">
          <i class="bi bi-check-circle me-1"></i> Register Student Account
        </button>
      </div>
    </form>

    <div class="text-center mt-4 pt-3 border-top">
      <p class="text-muted mb-0">Already registered? <a href="<?=url('auth/login.php')?>" class="fw-semibold">Sign in here</a></p>
    </div>
  </div>
</div>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
