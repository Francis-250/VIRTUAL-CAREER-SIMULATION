<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_admin();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=career_guidance_users.csv');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['ID', 'Name', 'Email', 'Role', 'Status', 'Education', 'Created', 'Last Login']);
    foreach ($con->query("SELECT id, name, email, role, status, education_level, created_at, last_login_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC") as $r) {
        fputcsv($o, $r);
    }
    exit;
}

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$like = '%' . $q . '%';

$sql = "SELECT id, name, email, phone, role, status, education_level, last_login_at, created_at FROM users WHERE deleted_at IS NULL";
$params = [];
$types = '';

if (!empty($q)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if (!empty($roleFilter) && in_array($roleFilter, ['student', 'counselor', 'admin'], true)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

if (!empty($statusFilter) && in_array($statusFilter, ['active', 'pending', 'suspended'], true)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = $con->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Counts
$totalUsers = $con->query("SELECT COUNT(*) n FROM users WHERE deleted_at IS NULL")->fetch_assoc()['n'];
$studentsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='student' AND deleted_at IS NULL")->fetch_assoc()['n'];
$counselorsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='counselor' AND deleted_at IS NULL")->fetch_assoc()['n'];
$adminsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='admin' AND deleted_at IS NULL")->fetch_assoc()['n'];

$pageTitle = 'Manage User Accounts & Access · Career Guidance System';
require __DIR__.'/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">
      <i class="bi bi-shield-lock-fill me-1"></i> Access Control
    </span>
    <h1 class="h3 fw-bold mb-1">Manage User Accounts & Access</h1>
    <p class="text-muted mb-0">Create, configure, and oversee accounts for Students, Career Counsellors, and Administrators.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
      <i class="bi bi-person-plus-fill me-1"></i> Add New User
    </button>
    <a class="btn btn-outline-secondary" href="?export=csv">
      <i class="bi bi-file-earmark-arrow-down me-1"></i> Export CSV
    </a>
  </div>
</div>

<!-- Role Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card p-3 border shadow-sm rounded-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-dark text-white p-3 rounded-circle"><i class="bi bi-people fs-4"></i></div>
        <div>
          <div class="h3 mb-0 fw-bold"><?=$totalUsers?></div>
          <div class="text-muted small">Total Accounts</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card p-3 border shadow-sm rounded-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-mortarboard fs-4"></i></div>
        <div>
          <div class="h3 mb-0 fw-bold"><?=$studentsCount?></div>
          <div class="text-muted small">Students</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card p-3 border shadow-sm rounded-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-person-badge fs-4"></i></div>
        <div>
          <div class="h3 mb-0 fw-bold"><?=$counselorsCount?></div>
          <div class="text-muted small">Career Counsellors</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card p-3 border shadow-sm rounded-3 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-warning-subtle text-warning p-3 rounded-circle"><i class="bi bi-shield-check fs-4"></i></div>
        <div>
          <div class="h3 mb-0 fw-bold"><?=$adminsCount?></div>
          <div class="text-muted small">Administrators</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter and Search -->
<form class="card p-3 mb-4 border-0 shadow-sm rounded-3 bg-white">
  <div class="row g-2 align-items-center">
    <div class="col-md-5">
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input class="form-control" name="q" value="<?=e($q)?>" placeholder="Search by name or email address...">
      </div>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="role">
        <option value="">All Roles</option>
        <option value="student" <?=$roleFilter==='student'?'selected':''?>>Student</option>
        <option value="counselor" <?=$roleFilter==='counselor'?'selected':''?>>Career Counsellor</option>
        <option value="admin" <?=$roleFilter==='admin'?'selected':''?>>Administrator</option>
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" name="status">
        <option value="">All Statuses</option>
        <option value="active" <?=$statusFilter==='active'?'selected':''?>>Active</option>
        <option value="pending" <?=$statusFilter==='pending'?'selected':''?>>Pending</option>
        <option value="suspended" <?=$statusFilter==='suspended'?'selected':''?>>Suspended</option>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-primary w-100">Filter</button>
      <?php if ($q || $roleFilter || $statusFilter): ?>
        <a href="<?=url('admin/users.php')?>" class="btn btn-outline-secondary">Reset</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- Users Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>User Account</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Last Login</th>
          <th class="text-end">Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
              No user accounts found matching this criteria.
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $roleBadge = match($r['role']) {
              'student' => 'bg-primary-subtle text-primary border',
              'counselor' => 'bg-info-subtle text-info-emphasis border',
              'admin' => 'bg-dark text-white',
              default => 'bg-secondary text-white'
            };
            $statusBadge = match($r['status']) {
              'active' => 'bg-success-subtle text-success border',
              'pending' => 'bg-warning-subtle text-warning-emphasis border',
              'suspended' => 'bg-danger-subtle text-danger border',
              default => 'bg-light text-dark'
            };
            $roleLabel = match($r['role']) {
              'student' => 'Student',
              'counselor' => 'Career Counsellor',
              'admin' => 'Administrator',
              default => ucfirst($r['role'])
            };
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-light text-primary border d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">
                  <?=strtoupper(substr($r['name'],0,1))?>
                </div>
                <div>
                  <strong class="text-dark"><?=e($r['name'])?></strong>
                  <div class="small text-muted"><?=e($r['email'])?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?=$roleBadge?> px-2 py-1 rounded-pill">
                <?=$roleLabel?>
              </span>
            </td>
            <td>
              <span class="badge <?=$statusBadge?> px-2 py-1 rounded-pill">
                <?=ucfirst($r['status'])?>
              </span>
            </td>
            <td class="small text-muted">
              <?=date('M j, Y', strtotime($r['created_at']))?>
            </td>
            <td class="small text-muted">
              <?=$r['last_login_at'] ? date('M j, Y H:i', strtotime($r['last_login_at'])) : '<span class="text-secondary">Never</span>'?>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary edit-user-btn" data-row='<?=e(json_encode($r))?>'>
                <i class="bi bi-gear-fill me-1"></i>Manage Access
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" action="<?=url('admin/save_user.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Provision New User Account</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="create">

        <div class="mb-3">
          <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
          <input class="form-control" name="name" required placeholder="e.g. Jane Doe">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" name="email" required placeholder="jane@example.com">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
            <select class="form-select" name="role" required>
              <option value="student">Student</option>
              <option value="counselor">Career Counsellor</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
            <select class="form-select" name="status" required>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Temporary Password (min 8 chars) <span class="text-danger">*</span></label>
          <input type="password" class="form-control" name="password" minlength="8" required placeholder="Set initial password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" action="<?=url('admin/save_user.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i>Manage User Account & Access</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editUserId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Full Name</label>
          <input class="form-control" name="name" id="editUserName" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Email Address</label>
          <input type="email" class="form-control" name="email" id="editUserEmail" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Role</label>
            <select class="form-select" name="role" id="editUserRole" required>
              <option value="student">Student</option>
              <option value="counselor">Career Counsellor</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status" id="editUserStatus" required>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div class="p-3 bg-light rounded-3 border mb-3">
          <label class="form-label fw-semibold small text-dark mb-1">
            <i class="bi bi-key me-1"></i>Reset Password (Optional)
          </label>
          <input type="password" class="form-control" name="new_password" minlength="8" placeholder="Leave blank to keep existing password">
          <div class="form-text">Enter 8+ characters to override user's current password.</div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="deactivateUserBtn">
          <i class="bi bi-person-x me-1"></i>Deactivate
        </button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.edit-user-btn').forEach(btn => {
  btn.onclick = () => {
    const row = JSON.parse(btn.dataset.row);
    document.getElementById('editUserId').value = row.id;
    document.getElementById('editUserName').value = row.name;
    document.getElementById('editUserEmail').value = row.email;
    document.getElementById('editUserRole').value = row.role;
    document.getElementById('editUserStatus').value = row.status;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
  };
});

document.getElementById('deactivateUserBtn').onclick = async () => {
  const userId = document.getElementById('editUserId').value;
  if (!confirm('Are you sure you want to deactivate and suspend this account?')) return;
  const form = new FormData();
  form.append('csrf', window.APP.csrf);
  form.append('action', 'delete');
  form.append('id', userId);
  const res = await fetch('<?=url('admin/save_user.php')?>', {
    method: 'POST',
    body: form
  });
  const json = await res.json();
  if (json.ok) {
    location.reload();
  } else {
    alert(json.message || 'Could not deactivate user.');
  }
};
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
