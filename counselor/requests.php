<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');

$counselorId = (int)user()['id'];
$statusFilter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$sql = "
  SELECT r.*, s.name AS student_name, s.email AS student_email, s.education_level, 
         s.phone, c.name AS counselor_name, resp.name AS responder_name,
         (SELECT COUNT(*) FROM user_interest_scores WHERE user_id = s.id) AS riasec_completed
  FROM counselling_requests r
  JOIN users s ON s.id = r.student_id
  LEFT JOIN users c ON c.id = r.counselor_id
  LEFT JOIN users resp ON resp.id = r.responded_by
  WHERE s.deleted_at IS NULL
";

$params = [];
$types = '';

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'responded', 'scheduled', 'completed', 'cancelled'], true)) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($q)) {
    $sql .= " AND (s.name LIKE ? OR s.email LIKE ? OR r.topic LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql .= " ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC";

$stmt = $con->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Counts
$pendingCount = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='pending'")->fetch_assoc()['n'];
$scheduledCount = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='scheduled'")->fetch_assoc()['n'];
$completedCount = $con->query("SELECT COUNT(*) n FROM counselling_requests WHERE status='completed'")->fetch_assoc()['n'];

$pageTitle = 'Counselling Requests · Career Guidance System';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1140px;">
  <!-- Page Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">
        <i class="bi bi-inbox-fill me-1"></i> Guidance Requests
      </span>
      <h1 class="h2 fw-bold mb-1">Counselling Requests</h1>
      <p class="text-muted mb-0">Review student inquiries, give career guidance advice, and schedule 1-on-1 sessions.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?=url('counselor/appointments.php')?>" class="btn btn-outline-primary">
        <i class="bi bi-calendar-event me-1"></i>Manage Appointments
      </a>
      <a href="<?=url('counselor/index.php')?>" class="btn btn-outline-secondary">
        <i class="bi bi-people me-1"></i>Students Directory
      </a>
    </div>
  </div>

  <!-- Status Tabs -->
  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="?status=" class="btn btn-sm <?=empty($statusFilter)?'btn-dark':'btn-outline-secondary'?> rounded-pill px-3">
      All Requests (<?=count($requests)?>)
    </a>
    <a href="?status=pending" class="btn btn-sm <?=$statusFilter==='pending'?'btn-warning text-dark':'btn-outline-warning text-dark'?> rounded-pill px-3">
      Pending Review (<?=$pendingCount?>)
    </a>
    <a href="?status=scheduled" class="btn btn-sm <?=$statusFilter==='scheduled'?'btn-success text-white':'btn-outline-success'?> rounded-pill px-3">
      Scheduled (<?=$scheduledCount?>)
    </a>
    <a href="?status=completed" class="btn btn-sm <?=$statusFilter==='completed'?'btn-info text-dark':'btn-outline-info text-dark'?> rounded-pill px-3">
      Completed (<?=$completedCount?>)
    </a>
  </div>

  <!-- Search Card -->
  <form class="card p-3 mb-4 border-0 shadow-sm rounded-3">
    <div class="input-group">
      <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
      <input type="hidden" name="status" value="<?=e($statusFilter)?>">
      <input class="form-control" name="q" value="<?=e($q)?>" placeholder="Search student name, email, or guidance topic...">
      <button class="btn btn-primary px-4">Search</button>
      <?php if ($q || $statusFilter): ?>
        <a href="<?=url('counselor/requests.php')?>" class="btn btn-outline-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Requests Table / List -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Student</th>
            <th>Topic & Inquiry</th>
            <th>Preferred Schedule</th>
            <th>Status</th>
            <th>Date</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($requests)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                No counselling requests found matching the filter.
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($requests as $r): ?>
            <?php
              $badgeClass = match($r['status']) {
                'pending' => 'bg-warning text-dark',
                'responded' => 'bg-info text-dark',
                'scheduled' => 'bg-success text-white',
                'completed' => 'bg-secondary text-white',
                'cancelled' => 'bg-light text-muted border',
                default => 'bg-light text-dark'
              };
            ?>
            <tr>
              <td>
                <div class="fw-bold text-dark"><?=e($r['student_name'])?></div>
                <div class="small text-muted"><?=e($r['student_email'])?></div>
                <div class="small text-primary">
                  <?=e(ucfirst($r['education_level'] ?: 'Student'))?> &middot; 
                  <?=$r['riasec_completed'] ? '<span class="text-success"><i class="bi bi-check-circle"></i> RIASEC Done</span>' : '<span class="text-secondary">No RIASEC</span>'?>
                </div>
              </td>
              <td style="max-width: 320px;">
                <div class="fw-semibold text-dark"><?=e($r['topic'])?></div>
                <div class="small text-muted text-truncate" title="<?=e($r['message'])?>">
                  <?=e($r['message'])?>
                </div>
                <?php if (!empty($r['counselor_response'])): ?>
                  <div class="small text-info-emphasis mt-1 bg-light p-1 rounded">
                    <strong>Reply:</strong> <?=e(mb_strimwidth($r['counselor_response'], 0, 80, '...'))?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r['preferred_date']): ?>
                  <div class="small fw-medium"><i class="bi bi-calendar3 me-1 text-primary"></i><?=date('M j, Y', strtotime($r['preferred_date']))?></div>
                <?php else: ?>
                  <span class="small text-muted">Flexible Date</span>
                <?php endif; ?>
                <?php if ($r['preferred_time_slot']): ?>
                  <div class="small text-muted"><i class="bi bi-clock me-1"></i><?=e($r['preferred_time_slot'])?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?=$badgeClass?> rounded-pill px-3 py-1">
                  <?=ucfirst($r['status'])?>
                </span>
              </td>
              <td class="small text-muted">
                <?=date('M j, Y', strtotime($r['created_at']))?><br>
                <?=date('H:i', strtotime($r['created_at']))?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-primary respond-btn" data-row='<?=e(json_encode($r))?>'>
                    <i class="bi bi-reply me-1"></i>Respond
                  </button>
                  <a href="<?=url('counselor/student_view.php?id='.$r['student_id'])?>" class="btn btn-outline-secondary" title="View Student Profile & Assessment">
                    <i class="bi bi-person-vcard"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Response & Appointment Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/respond_request.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <div>
          <h2 class="modal-title fs-5 fw-bold">Respond to Guidance Request</h2>
          <div class="small text-muted" id="modalStudentInfo"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="request_id" id="modalRequestId">

        <!-- Student Inquiry Callout -->
        <div class="p-3 bg-light rounded-3 mb-3 border">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong class="text-dark" id="modalTopic"></strong>
            <span class="badge bg-secondary" id="modalSchedulePreference"></span>
          </div>
          <p class="small text-secondary mb-0" id="modalMessage"></p>
        </div>

        <!-- Response Advice -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Guidance Advice / Response to Student <span class="text-danger">*</span></label>
          <textarea class="form-control" name="counselor_response" id="modalResponseText" rows="4" required placeholder="Write constructive career guidance, answer the student's questions, or provide instructions for an upcoming session..." maxlength="3000"></textarea>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Update Request Status</label>
            <select class="form-select" name="status" id="modalStatusSelect">
              <option value="responded">Responded (Advice Provided)</option>
              <option value="scheduled">Scheduled (Appointment Booked)</option>
              <option value="completed">Completed (Resolved)</option>
              <option value="pending">Keep Pending</option>
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check p-2 bg-light rounded border w-100">
              <input class="form-check-input ms-1 me-2" type="checkbox" name="schedule_appointment" value="1" id="scheduleApptCheck">
              <label class="form-check-label fw-semibold" for="scheduleApptCheck">
                <i class="bi bi-calendar-plus text-primary me-1"></i>Book Counselling Appointment Now
              </label>
            </div>
          </div>
        </div>

        <!-- Appointment Fields (Toggled) -->
        <div id="apptFields" class="border rounded-3 p-3 bg-light d-none">
          <h3 class="h6 fw-bold mb-3 text-primary"><i class="bi bi-calendar-check me-1"></i>Appointment Details</h3>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold">Session Title</label>
              <input class="form-control" name="title" id="modalApptTitle" placeholder="e.g. 1-on-1 Guidance on Career Choice">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Appointment Date</label>
              <input type="date" class="form-control" name="appointment_date" id="modalApptDate" min="<?=date('Y-m-d')?>">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Start Time</label>
              <input type="time" class="form-control" name="start_time" id="modalStartTime" value="10:00">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">End Time</label>
              <input type="time" class="form-control" name="end_time" id="modalEndTime" value="10:45">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Meeting Mode</label>
              <select class="form-select" name="meeting_type">
                <option value="online_video">Online Video (Google Meet / Zoom)</option>
                <option value="in_person">In-Person (Guidance Office)</option>
                <option value="phone_call">Phone Call</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Meeting Link / Room Location</label>
              <input class="form-control" name="meeting_link" placeholder="https://meet.google.com/xyz or Room 204">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-1"></i> Save Response & Notify Student
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.respond-btn').forEach(btn => {
  btn.onclick = () => {
    const row = JSON.parse(btn.dataset.row);
    document.getElementById('modalRequestId').value = row.id;
    document.getElementById('modalStudentInfo').textContent = row.student_name + ' (' + row.student_email + ') · ' + (row.education_level || 'Student');
    document.getElementById('modalTopic').textContent = row.topic;
    document.getElementById('modalMessage').textContent = row.message;
    document.getElementById('modalSchedulePreference').textContent = 'Preferred: ' + (row.preferred_date || 'Flexible') + ' · ' + (row.preferred_time_slot || 'Any slot');
    document.getElementById('modalResponseText').value = row.counselor_response || '';
    document.getElementById('modalStatusSelect').value = row.status === 'pending' ? 'responded' : row.status;
    document.getElementById('modalApptTitle').value = 'Career Guidance: ' + row.topic;
    if (row.preferred_date) {
      document.getElementById('modalApptDate').value = row.preferred_date;
    }
    document.getElementById('scheduleApptCheck').checked = false;
    document.getElementById('apptFields').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('responseModal')).show();
  };
});

document.getElementById('scheduleApptCheck').onchange = function() {
  const fields = document.getElementById('apptFields');
  if (this.checked) {
    fields.classList.remove('d-none');
    document.getElementById('modalStatusSelect').value = 'scheduled';
  } else {
    fields.classList.add('d-none');
  }
};
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
