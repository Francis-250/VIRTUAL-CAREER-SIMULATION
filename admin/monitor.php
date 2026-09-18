<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_admin();

// System Telemetry
$phpVersion = phpversion();
$mysqlVersion = $con->server_info;
$memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
$memoryLimit = ini_get('memory_limit');
$uploadLimit = ini_get('upload_max_filesize');
$dbName = 'career_sim';

// Database stats
$tableCount = $con->query("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema = '$dbName'")->fetch_assoc()['n'];
$dbSizeResult = $con->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = '$dbName'")->fetch_assoc();
$dbSizeMb = $dbSizeResult['size_mb'] ?? 0;

// User metrics
$totalUsers = $con->query("SELECT COUNT(*) n FROM users WHERE deleted_at IS NULL")->fetch_assoc()['n'];
$activeUsers = $con->query("SELECT COUNT(*) n FROM users WHERE status='active' AND deleted_at IS NULL")->fetch_assoc()['n'];
$pendingUsers = $con->query("SELECT COUNT(*) n FROM users WHERE status='pending' AND deleted_at IS NULL")->fetch_assoc()['n'];
$suspendedUsers = $con->query("SELECT COUNT(*) n FROM users WHERE status='suspended'")->fetch_assoc()['n'];

$studentsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='student' AND deleted_at IS NULL")->fetch_assoc()['n'];
$counselorsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='counselor' AND deleted_at IS NULL")->fetch_assoc()['n'];
$adminsCount = $con->query("SELECT COUNT(*) n FROM users WHERE role='admin' AND deleted_at IS NULL")->fetch_assoc()['n'];

// Guidance metrics
$assessmentsCompleted = $con->query("SELECT COUNT(DISTINCT user_id) n FROM user_interest_scores")->fetch_assoc()['n'];
$assessmentRate = $studentsCount > 0 ? round(($assessmentsCompleted / $studentsCount) * 100) : 0;
$totalRecs = $con->query("SELECT COUNT(*) n FROM career_recommendations")->fetch_assoc()['n'];
$totalPathways = $con->query("SELECT COUNT(*) n FROM counsellor_pathway_recommendations")->fetch_assoc()['n'];

// Counselling requests & appointments
$totalRequests = $con->query("SELECT COUNT(*) n FROM counselling_requests")->fetch_assoc()['n'];
$pendingRequests = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='pending'")->fetch_assoc()['n'];
$scheduledRequests = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='scheduled'")->fetch_assoc()['n'];
$completedRequests = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='completed'")->fetch_assoc()['n'];

$totalAppts = $con->query("SELECT COUNT(*) n FROM counselling_appointments")->fetch_assoc()['n'];
$upcomingAppts = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE status IN ('scheduled','rescheduled') AND appointment_date >= CURDATE()")->fetch_assoc()['n'];
$completedAppts = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE status='completed'")->fetch_assoc()['n'];

// Recent audit logs
$auditLogs = $con->query("
  SELECT l.*, u.name AS admin_name, u.email AS admin_email 
  FROM admin_logs l 
  LEFT JOIN users u ON u.id = l.admin_id 
  ORDER BY l.created_at DESC 
  LIMIT 15
")->fetch_all(MYSQLI_ASSOC);

// Daily registrations for chart
$dailyRegs = $con->query("
  SELECT DATE(created_at) d, COUNT(*) n 
  FROM users 
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
  GROUP BY DATE(created_at)
")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'System Monitor · Career Guidance System';
require __DIR__.'/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 mb-2">
      <i class="bi bi-activity me-1"></i> Real-Time Health & Telemetry
    </span>
    <h1 class="h3 fw-bold mb-1">System Monitor</h1>
    <p class="text-muted mb-0">Overview of server health, guidance activity, database status, and audit logs.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-primary" onclick="location.reload()">
      <i class="bi bi-arrow-clockwise me-1"></i> Refresh Metrics
    </button>
    <a href="<?=url('admin/users.php')?>" class="btn btn-primary">
      <i class="bi bi-people me-1"></i> User Accounts
    </a>
  </div>
</div>

<!-- Server Health Telemetry Bar -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 fw-bold mb-0"><i class="bi bi-hdd-network text-primary me-2"></i>Server & Platform Status</h2>
    <span class="badge bg-success text-white px-3 py-1 rounded-pill">
      <i class="bi bi-check-circle-fill me-1"></i> Operational
    </span>
  </div>
  <div class="row g-3">
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-light rounded-3 border">
        <small class="text-muted d-block">PHP Runtime</small>
        <strong class="fs-6 text-dark"><?=e($phpVersion)?></strong>
        <div class="small text-secondary mt-1">Limit: <?=e($memoryLimit)?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-light rounded-3 border">
        <small class="text-muted d-block">Database Engine</small>
        <strong class="fs-6 text-dark">MySQL <?=e(explode('-', $mysqlVersion)[0])?></strong>
        <div class="small text-secondary mt-1"><?=e($dbName)?> (<?=$tableCount?> tables &middot; <?=$dbSizeMb?> MB)</div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-light rounded-3 border">
        <small class="text-muted d-block">Memory Footprint</small>
        <strong class="fs-6 text-dark"><?=$memoryUsage?> MB</strong>
        <div class="small text-secondary mt-1">Upload Max: <?=e($uploadLimit)?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-light rounded-3 border">
        <small class="text-muted d-block">Server Time</small>
        <strong class="fs-6 text-dark"><?=date('H:i:s')?></strong>
        <div class="small text-secondary mt-1"><?=date('Y-m-d')?> &middot; <?=date_default_timezone_get()?></div>
      </div>
    </div>
  </div>
</div>

<!-- Monitoring Metrics Grid -->
<div class="row g-4 mb-4">
  <!-- Guidance & Counselling Activity -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-compass text-primary me-2"></i>Career Guidance Operations</h2>
      <div class="row g-3 mb-3">
        <div class="col-6">
          <div class="border rounded-3 p-3 bg-light">
            <div class="text-muted small">RIASEC Assessments</div>
            <div class="h3 fw-bold text-dark mb-0"><?=$assessmentsCompleted?></div>
            <div class="small text-success fw-medium mt-1"><?=$assessmentRate?>% student completion</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded-3 p-3 bg-light">
            <div class="text-muted small">Counsellor Pathways</div>
            <div class="h3 fw-bold text-dark mb-0"><?=$totalPathways?></div>
            <div class="small text-muted mt-1">Custom pathways given</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded-3 p-3 bg-light">
            <div class="text-muted small">Counselling Inquiries</div>
            <div class="h3 fw-bold text-dark mb-0"><?=$totalRequests?></div>
            <div class="small text-warning fw-semibold mt-1"><?=$pendingRequests?> pending review</div>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded-3 p-3 bg-light">
            <div class="text-muted small">Appointments Held</div>
            <div class="h3 fw-bold text-dark mb-0"><?=$totalAppts?></div>
            <div class="small text-primary fw-medium mt-1"><?=$upcomingAppts?> upcoming sessions</div>
          </div>
        </div>
      </div>

      <div class="mt-auto pt-2">
        <div class="d-flex justify-content-between small text-muted mb-1">
          <span>Student Assessment Coverage</span>
          <span><strong><?=$assessmentRate?>%</strong></span>
        </div>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-primary" style="width: <?=$assessmentRate?>%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- User Account Health -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0"><i class="bi bi-people text-primary me-2"></i>User Access Breakdown</h2>
        <a href="<?=url('admin/users.php')?>" class="small text-primary text-decoration-none">Manage Accounts</a>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-4">
          <div class="border rounded-3 p-3 text-center bg-light">
            <div class="text-muted small">Students</div>
            <div class="h3 fw-bold text-primary mb-0"><?=$studentsCount?></div>
          </div>
        </div>
        <div class="col-4">
          <div class="border rounded-3 p-3 text-center bg-light">
            <div class="text-muted small">Counsellors</div>
            <div class="h3 fw-bold text-info mb-0"><?=$counselorsCount?></div>
          </div>
        </div>
        <div class="col-4">
          <div class="border rounded-3 p-3 text-center bg-light">
            <div class="text-muted small">Admins</div>
            <div class="h3 fw-bold text-dark mb-0"><?=$adminsCount?></div>
          </div>
        </div>
      </div>

      <!-- Account Status Distribution -->
      <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
        <span class="small fw-semibold text-dark"><i class="bi bi-check-circle-fill text-success me-2"></i>Active Accounts</span>
        <span class="badge bg-success rounded-pill px-3 py-1"><?=$activeUsers?></span>
      </div>
      <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
        <span class="small fw-semibold text-dark"><i class="bi bi-hourglass-split text-warning me-2"></i>Pending Verification</span>
        <span class="badge bg-warning text-dark rounded-pill px-3 py-1"><?=$pendingUsers?></span>
      </div>
      <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
        <span class="small fw-semibold text-dark"><i class="bi bi-slash-circle-fill text-danger me-2"></i>Suspended / Inactive</span>
        <span class="badge bg-danger rounded-pill px-3 py-1"><?=$suspendedUsers?></span>
      </div>
    </div>
  </div>
</div>

<!-- Live System Audit Trail -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i>System Activity & Audit Trail</h2>
      <p class="small text-muted mb-0">Recent events, administrative actions, and guidance updates.</p>
    </div>
    <a href="<?=url('admin/logs.php')?>" class="btn btn-sm btn-outline-secondary">View Full Logs</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>Timestamp</th>
          <th>Actor</th>
          <th>Action</th>
          <th>Target Type</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($auditLogs)): ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($auditLogs as $log): ?>
          <tr>
            <td class="text-muted" style="white-space: nowrap;">
              <?=date('M j, Y H:i:s', strtotime($log['created_at']))?>
            </td>
            <td>
              <span class="fw-semibold text-dark"><?=e($log['admin_name'] ?: 'System')?></span>
            </td>
            <td>
              <span class="badge bg-light text-dark border">
                <?=e(ucwords(str_replace('_',' ',$log['action'])))?>
              </span>
            </td>
            <td>
              <code><?=e($log['target_type'])?> #<?=$log['target_id']?></code>
            </td>
            <td class="text-muted">
              <?=e($log['details'])?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
