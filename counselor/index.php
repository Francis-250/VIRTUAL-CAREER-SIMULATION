<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');

$q = trim($_GET['q'] ?? '');
$eduFilter = $_GET['education'] ?? '';
$like = '%' . $q . '%';

$counselorId = (int)user()['id'];

// KPI counts
$totalStudents = $con->query("SELECT COUNT(*) n FROM users WHERE role='student' AND deleted_at IS NULL")->fetch_assoc()['n'];
$pendingRequests = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='pending'")->fetch_assoc()['n'];
$upcomingAppts = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE counselor_id=$counselorId AND status IN ('scheduled','rescheduled') AND appointment_date >= CURDATE()")->fetch_assoc()['n'];
$totalPathways = $con->query("SELECT COUNT(*) n FROM counsellor_pathway_recommendations WHERE counselor_id=$counselorId")->fetch_assoc()['n'];

// Fetch students with stats
$sql = "
  SELECT u.id, u.name, u.email, u.phone, u.education_level, u.created_at, u.resume_path,
         COUNT(DISTINCT CASE WHEN p.status='completed' THEN p.id END) AS simulations,
         COUNT(DISTINCT CASE WHEN a.passed=1 THEN a.id END) AS quizzes,
         (SELECT COUNT(*) FROM user_interest_scores WHERE user_id=u.id) AS riasec_count,
         (SELECT COUNT(*) FROM counselling_requests WHERE student_id=u.id AND status='pending') AS pending_reqs,
         (SELECT COUNT(*) FROM counselling_appointments WHERE student_id=u.id AND status='scheduled' AND appointment_date >= CURDATE()) AS upcoming_appts
  FROM users u
  LEFT JOIN user_simulation_progress p ON p.user_id = u.id
  LEFT JOIN quiz_attempts a ON a.user_id = u.id
  WHERE u.role = 'student' AND u.deleted_at IS NULL
";

$params = [];
$types = '';

if (!empty($q)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if (!empty($eduFilter) && in_array($eduFilter, ['secondary', 'undergraduate', 'graduate', 'other'], true)) {
    $sql .= " AND u.education_level = ?";
    $params[] = $eduFilter;
    $types .= 's';
}

$sql .= " GROUP BY u.id ORDER BY pending_reqs DESC, u.name ASC";

$stmt = $con->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$careers = $con->query("SELECT id, title FROM careers WHERE is_active=1 ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Career Counsellor Dashboard · Career Guidance System';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1140px;">
  <!-- Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
      <span class="badge bg-info-subtle text-info-emphasis border rounded-pill px-3 py-1 mb-2">
        <i class="bi bi-person-badge-fill me-1"></i> Career Counsellor Portal
      </span>
      <h1 class="h2 fw-bold mb-1">Student Guidance & Profiles</h1>
      <p class="text-muted mb-0">Review student assessment results, recommend customized career pathways, and manage guidance requests.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?=url('counselor/requests.php')?>" class="btn btn-warning position-relative shadow-sm">
        <i class="bi bi-inbox-fill me-1"></i>Guidance Requests
        <?php if ($pendingRequests > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?=$pendingRequests?>
          </span>
        <?php endif; ?>
      </a>
      <a href="<?=url('counselor/appointments.php')?>" class="btn btn-primary shadow-sm">
        <i class="bi bi-calendar-event me-1"></i>Appointments (<?=$upcomingAppts?>)
      </a>
    </div>
  </div>

  <!-- KPI Row -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-people fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$totalStudents?></div>
            <div class="text-muted small">Registered Students</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-warning-subtle text-warning p-3 rounded-circle"><i class="bi bi-inbox fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$pendingRequests?></div>
            <div class="text-muted small">Pending Requests</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-calendar-check fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$upcomingAppts?></div>
            <div class="text-muted small">Upcoming Sessions</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-signpost-split fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$totalPathways?></div>
            <div class="text-muted small">Pathways Recommended</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search and Filter Form -->
  <form class="card p-3 mb-4 border-0 shadow-sm rounded-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-7">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input class="form-control" name="q" value="<?=e($q)?>" placeholder="Search student by name or email...">
        </div>
      </div>
      <div class="col-md-3">
        <select class="form-select" name="education">
          <option value="">All Education Levels</option>
          <option value="secondary" <?=$eduFilter==='secondary'?'selected':''?>>Secondary / High School</option>
          <option value="undergraduate" <?=$eduFilter==='undergraduate'?'selected':''?>>Undergraduate / College</option>
          <option value="graduate" <?=$eduFilter==='graduate'?'selected':''?>>Graduate / Postgrad</option>
          <option value="other" <?=$eduFilter==='other'?'selected':''?>>Other</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100">Filter</button>
        <?php if ($q || $eduFilter): ?>
          <a href="<?=url('counselor/index.php')?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- Students Table -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Student Profile</th>
            <th>Education Level</th>
            <th>RIASEC Assessment</th>
            <th>Learning Activity</th>
            <th>Guidance Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                No students found matching your search.
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($students as $s): ?>
            <tr>
              <td>
                <div class="fw-bold text-dark fs-6"><?=e($s['name'])?></div>
                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?=e($s['email'])?></div>
                <?php if ($s['phone']): ?>
                  <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?=e($s['phone'])?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <?=e(ucfirst($s['education_level'] ?: 'Not Specified'))?>
                </span>
                <?php if ($s['resume_path']): ?>
                  <a href="<?=url($s['resume_path'])?>" target="_blank" class="d-block small text-primary text-decoration-none mt-1">
                    <i class="bi bi-file-earmark-person"></i> Resume attached
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($s['riasec_count'] > 0): ?>
                  <span class="badge bg-success-subtle text-success border px-2 py-1">
                    <i class="bi bi-check2-circle me-1"></i>Completed
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                    Pending
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <div class="small">
                  <strong><?=$s['simulations']?></strong> simulations &middot; 
                  <strong><?=$s['quizzes']?></strong> quiz passes
                </div>
              </td>
              <td>
                <?php if ($s['pending_reqs'] > 0): ?>
                  <a href="<?=url('counselor/requests.php?q='.urlencode($s['name']))?>" class="badge bg-warning text-dark text-decoration-none">
                    <i class="bi bi-bell-fill me-1"></i><?=$s['pending_reqs']?> Pending Request
                  </a>
                <?php endif; ?>
                <?php if ($s['upcoming_appts'] > 0): ?>
                  <span class="badge bg-primary text-white d-block mt-1" style="width: fit-content;">
                    <i class="bi bi-calendar2-check me-1"></i><?=$s['upcoming_appts']?> Upcoming Appt
                  </span>
                <?php endif; ?>
                <?php if ($s['pending_reqs'] == 0 && $s['upcoming_appts'] == 0): ?>
                  <span class="small text-muted">No active requests</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary" href="<?=url('counselor/student_view.php?id='.$s['id'])?>" title="View Profile & Review Assessment">
                    <i class="bi bi-eye me-1"></i>View Profile
                  </a>
                  <button class="btn btn-primary recommend-pathway-btn" data-id="<?=$s['id']?>" data-name="<?=e($s['name'])?>" title="Recommend Career Pathway">
                    <i class="bi bi-signpost-split"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Recommend Pathway Modal -->
<div class="modal fade" id="pathwayModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/recommend_pathway.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <div>
          <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-signpost-split text-primary me-2"></i>Recommend Career Pathway</h2>
          <div class="small text-muted" id="pathwayModalStudentName"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="student_id" id="pathwayStudentId">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Target Career (from Catalog)</label>
            <select class="form-select" name="career_id">
              <option value="">Select a career (Optional)</option>
              <?php foreach ($careers as $c): ?>
                <option value="<?=$c['id']?>"><?=e($c['title'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Pathway Title <span class="text-danger">*</span></label>
            <input class="form-control" name="pathway_title" required placeholder="e.g. Data Analytics to BI Consultant Roadmap">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Guidance & Rationale (Why this fits the student) <span class="text-danger">*</span></label>
            <textarea class="form-control" name="guidance_notes" rows="3" required placeholder="Explain how the student's RIASEC scores, performance, and background align with this pathway..."></textarea>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Recommended Action Steps & Milestones</label>
            <textarea class="form-control" name="recommended_steps" rows="4" placeholder="Step 1: Complete Python and SQL basics&#10;Step 2: Earn Power BI certification&#10;Step 3: Build 2 portfolio projects on GitHub&#10;Step 4: Apply for Junior Data Analyst roles..."></textarea>
            <div class="form-text">These steps will appear prominently in the student's guidance dashboard.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish Pathway Recommendation</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.recommend-pathway-btn').forEach(btn => {
  btn.onclick = () => {
    document.getElementById('pathwayStudentId').value = btn.dataset.id;
    document.getElementById('pathwayModalStudentName').textContent = 'For student: ' + btn.dataset.name;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('pathwayModal')).show();
  };
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
