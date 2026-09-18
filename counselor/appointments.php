<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');

$counselorId = (int)user()['id'];
$filter = $_GET['filter'] ?? 'upcoming';

$sql = "
  SELECT a.*, s.name AS student_name, s.email AS student_email, s.education_level, s.phone AS student_phone,
         r.topic AS request_topic
  FROM counselling_appointments a
  JOIN users s ON s.id = a.student_id
  LEFT JOIN counselling_requests r ON r.id = a.request_id
  WHERE a.counselor_id = ?
";

if ($filter === 'upcoming') {
    $sql .= " AND a.status IN ('scheduled', 'rescheduled') AND a.appointment_date >= CURDATE()";
} elseif ($filter === 'today') {
    $sql .= " AND a.appointment_date = CURDATE()";
} elseif ($filter === 'completed') {
    $sql .= " AND a.status = 'completed'";
} elseif ($filter === 'cancelled') {
    $sql .= " AND a.status = 'cancelled'";
}

$sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param('i', $counselorId);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count stats
$upcomingCount = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE counselor_id = $counselorId AND status IN ('scheduled', 'rescheduled') AND appointment_date >= CURDATE()")->fetch_assoc()['n'];
$todayCount = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE counselor_id = $counselorId AND appointment_date = CURDATE()")->fetch_assoc()['n'];
$completedCount = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE counselor_id = $counselorId AND status = 'completed'")->fetch_assoc()['n'];
$totalCount = $con->query("SELECT COUNT(*) n FROM counselling_appointments WHERE counselor_id = $counselorId")->fetch_assoc()['n'];

// Students list for scheduling
$students = $con->query("SELECT id, name, email, education_level FROM users WHERE role='student' AND deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Counselling Appointments · Career Guidance System';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1140px;">
  <!-- Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">
        <i class="bi bi-calendar2-check-fill me-1"></i> Session Management
      </span>
      <h1 class="h2 fw-bold mb-1">Counselling Appointments</h1>
      <p class="text-muted mb-0">Schedule guidance sessions, conduct meetings, and formulate student action plans.</p>
    </div>
    <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#newApptModal">
      <i class="bi bi-calendar-plus me-1"></i> Schedule New Appointment
    </button>
  </div>

  <!-- KPI Row -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-calendar-event fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$upcomingCount?></div>
            <div class="text-muted small">Upcoming Sessions</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-warning-subtle text-warning p-3 rounded-circle"><i class="bi bi-clock-history fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$todayCount?></div>
            <div class="text-muted small">Today's Sessions</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-check-circle fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$completedCount?></div>
            <div class="text-muted small">Completed Sessions</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-secondary-subtle text-secondary p-3 rounded-circle"><i class="bi bi-collection fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=$totalCount?></div>
            <div class="text-muted small">All-Time Bookings</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Buttons -->
  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="?filter=upcoming" class="btn btn-sm <?=$filter==='upcoming'?'btn-primary':'btn-outline-secondary'?> rounded-pill px-3">
      Upcoming (<?=$upcomingCount?>)
    </a>
    <a href="?filter=today" class="btn btn-sm <?=$filter==='today'?'btn-warning text-dark':'btn-outline-secondary'?> rounded-pill px-3">
      Today (<?=$todayCount?>)
    </a>
    <a href="?filter=completed" class="btn btn-sm <?=$filter==='completed'?'btn-success':'btn-outline-secondary'?> rounded-pill px-3">
      Completed (<?=$completedCount?>)
    </a>
    <a href="?filter=all" class="btn btn-sm <?=$filter==='all'?'btn-dark':'btn-outline-secondary'?> rounded-pill px-3">
      All Appointments (<?=$totalCount?>)
    </a>
  </div>

  <!-- Appointments List -->
  <div class="row g-3">
    <?php if (empty($appointments)): ?>
      <div class="col-12">
        <div class="card p-5 text-center border-0 shadow-sm rounded-4 text-muted bg-white">
          <i class="bi bi-calendar-check fs-1 d-block mb-2 text-secondary"></i>
          <h3 class="h5">No appointments found</h3>
          <p class="small text-muted mb-3">There are no appointments matching this view.</p>
          <div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newApptModal">
              <i class="bi bi-plus-circle me-1"></i>Schedule an Appointment
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($appointments as $appt): ?>
      <?php
        $isToday = $appt['appointment_date'] === date('Y-m-d');
        $badgeClass = match($appt['status']) {
          'completed' => 'bg-success text-white',
          'cancelled' => 'bg-danger text-white',
          'rescheduled' => 'bg-warning text-dark',
          default => $isToday ? 'bg-primary text-white' : 'bg-info-subtle text-info-emphasis border'
        };
      ?>
      <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white d-flex flex-column <?=$isToday && $appt['status']==='scheduled' ? 'border-start border-4 border-primary' : ''?>">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge <?=$badgeClass?> rounded-pill px-3 py-1">
              <?=$isToday && $appt['status']==='scheduled' ? 'Today' : ucfirst($appt['status'])?>
            </span>
            <div class="dropdown">
              <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><button class="dropdown-item edit-appt-btn" data-row='<?=e(json_encode($appt))?>'><i class="bi bi-pencil me-2"></i>Edit / Reschedule</button></li>
                <?php if ($appt['status'] !== 'completed'): ?>
                  <li><button class="dropdown-item text-success complete-appt-btn" data-row='<?=e(json_encode($appt))?>'><i class="bi bi-check2-circle me-2"></i>Mark Completed & Add Plan</button></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><button class="dropdown-item text-danger cancel-appt-btn" data-id="<?=$appt['id']?>"><i class="bi bi-x-circle me-2"></i>Cancel Session</button></li>
                <?php endif; ?>
              </ul>
            </div>
          </div>

          <h3 class="h6 fw-bold text-dark mb-1"><?=e($appt['title'])?></h3>

          <div class="small mb-3">
            <a href="<?=url('counselor/student_view.php?id='.$appt['student_id'])?>" class="text-decoration-none fw-semibold">
              <i class="bi bi-person me-1"></i><?=e($appt['student_name'])?>
            </a>
            <span class="text-muted"> &middot; <?=e($appt['student_email'])?></span>
          </div>

          <div class="small text-muted mb-3 d-flex flex-column gap-1 bg-light p-3 rounded-3">
            <div>
              <i class="bi bi-calendar3 me-1 text-primary"></i><strong><?=date('l, F j, Y', strtotime($appt['appointment_date']))?></strong>
            </div>
            <div>
              <i class="bi bi-clock me-1 text-primary"></i><?=date('H:i', strtotime($appt['start_time']))?> &ndash; <?=date('H:i', strtotime($appt['end_time']))?>
            </div>
            <div>
              <i class="bi bi-camera-video me-1 text-primary"></i>Mode: <strong><?=ucwords(str_replace('_',' ',$appt['meeting_type']))?></strong>
              <?php if ($appt['location_details']): ?>
                (<?=e($appt['location_details'])?>)
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($appt['meeting_link']) && $appt['status'] !== 'completed' && $appt['status'] !== 'cancelled'): ?>
            <div class="mb-3">
              <a href="<?=e($appt['meeting_link'])?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-box-arrow-up-right me-1"></i> Open Meeting Link
              </a>
            </div>
          <?php endif; ?>

          <?php if (!empty($appt['action_plan'])): ?>
            <div class="p-3 bg-success-subtle rounded-3 mt-auto small">
              <strong class="text-success-emphasis d-block mb-1"><i class="bi bi-journal-check me-1"></i>Action Plan Provided:</strong>
              <div class="text-dark"><?=nl2br(e($appt['action_plan']))?></div>
            </div>
          <?php elseif (!empty($appt['counselor_notes'])): ?>
            <div class="p-2 bg-light rounded-3 mt-auto small text-muted">
              <strong>Counselor Notes:</strong> <?=e($appt['counselor_notes'])?>
            </div>
          <?php endif; ?>

          <?php if ($appt['status'] === 'scheduled'): ?>
            <div class="mt-auto pt-3 d-flex gap-2">
              <button class="btn btn-sm btn-success flex-grow-1 complete-appt-btn" data-row='<?=e(json_encode($appt))?>'>
                <i class="bi bi-check2-circle me-1"></i>Mark Completed
              </button>
              <button class="btn btn-sm btn-outline-secondary edit-appt-btn" data-row='<?=e(json_encode($appt))?>'>
                <i class="bi bi-pencil"></i>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- New Appointment Modal -->
<div class="modal fade" id="newApptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/save_appointment.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-calendar-plus text-primary me-2"></i>Schedule Counselling Appointment</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="create">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
            <select class="form-select" name="student_id" required>
              <option value="">Choose a student</option>
              <?php foreach ($students as $s): ?>
                <option value="<?=$s['id']?>"><?=e($s['name'])?> (<?=e($s['email'])?>) &middot; <?=e($s['education_level'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Session Title <span class="text-danger">*</span></label>
            <input class="form-control" name="title" required placeholder="e.g. RIASEC Career Roadmap & Review">
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="appointment_date" required min="<?=date('Y-m-d')?>" value="<?=date('Y-m-d')?>">
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
            <input type="time" class="form-control" name="start_time" required value="10:00">
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
            <input type="time" class="form-control" name="end_time" required value="10:45">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Meeting Mode</label>
            <select class="form-select" name="meeting_type">
              <option value="online_video">Online Video (Google Meet / Zoom)</option>
              <option value="in_person">In-Person (Guidance Office)</option>
              <option value="phone_call">Phone Call</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Meeting Link / Room Location</label>
            <input class="form-control" name="meeting_link" placeholder="https://meet.google.com/xyz or Room 204">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Preparation Notes (Internal or for student)</label>
            <textarea class="form-control" name="counselor_notes" rows="3" placeholder="Notes on what the student should bring or prepare..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Confirm & Schedule</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit / Reschedule Modal -->
<div class="modal fade" id="editApptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/save_appointment.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold">Update Appointment</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editApptId">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Title</label>
            <input class="form-control" name="title" id="editApptTitle" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status" id="editApptStatus">
              <option value="scheduled">Scheduled</option>
              <option value="rescheduled">Rescheduled</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" class="form-control" name="appointment_date" id="editApptDate" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Start Time</label>
            <input type="time" class="form-control" name="start_time" id="editApptStartTime" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">End Time</label>
            <input type="time" class="form-control" name="end_time" id="editApptEndTime" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Mode</label>
            <select class="form-select" name="meeting_type" id="editApptType">
              <option value="online_video">Online Video</option>
              <option value="in_person">In-Person</option>
              <option value="phone_call">Phone Call</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Link / Location</label>
            <input class="form-control" name="meeting_link" id="editApptLink">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Notes</label>
            <textarea class="form-control" name="counselor_notes" id="editApptNotes" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Complete Session Modal -->
<div class="modal fade" id="completeApptModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/save_appointment.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-check2-circle text-success me-2"></i>Complete Session & Formulate Action Plan</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="complete">
        <input type="hidden" name="id" id="completeApptId">

        <div class="alert alert-success py-2 small mb-3">
          Completing this session will notify the student and publish their tailored <strong>Action Plan</strong> in their student guidance dashboard.
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Student Action Plan (Visible to Student) <span class="text-danger">*</span></label>
          <textarea class="form-control" name="action_plan" id="completeApptActionPlan" rows="5" required placeholder="Step 1: Explore recommended careers. Step 2: Enroll in subject prerequisites. Step 3: Complete target certification..."></textarea>
          <div class="form-text">List clear, actionable milestones agreed during the counselling session.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Counselor Session Notes (Summary & Reflections)</label>
          <textarea class="form-control" name="counselor_notes" id="completeApptNotes" rows="3" placeholder="Key observations on student strengths, identified barriers, and agreed timeline..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Mark Session Complete & Notify Student</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.edit-appt-btn').forEach(btn => {
  btn.onclick = () => {
    const row = JSON.parse(btn.dataset.row);
    document.getElementById('editApptId').value = row.id;
    document.getElementById('editApptTitle').value = row.title;
    document.getElementById('editApptDate').value = row.appointment_date;
    document.getElementById('editApptStartTime').value = row.start_time;
    document.getElementById('editApptEndTime').value = row.end_time;
    document.getElementById('editApptType').value = row.meeting_type;
    document.getElementById('editApptLink').value = row.meeting_link || '';
    document.getElementById('editApptNotes').value = row.counselor_notes || '';
    document.getElementById('editApptStatus').value = row.status;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editApptModal')).show();
  };
});

document.querySelectorAll('.complete-appt-btn').forEach(btn => {
  btn.onclick = () => {
    const row = JSON.parse(btn.dataset.row);
    document.getElementById('completeApptId').value = row.id;
    document.getElementById('completeApptActionPlan').value = row.action_plan || '';
    document.getElementById('completeApptNotes').value = row.counselor_notes || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('completeApptModal')).show();
  };
});

document.querySelectorAll('.cancel-appt-btn').forEach(btn => {
  btn.onclick = async () => {
    if (!confirm('Are you sure you want to cancel this appointment? The student will be notified.')) return;
    const form = new FormData();
    form.append('csrf', window.APP.csrf);
    form.append('action', 'cancel');
    form.append('id', btn.dataset.id);
    const res = await fetch('<?=url('counselor/save_appointment.php')?>', {
      method: 'POST',
      body: form
    });
    const json = await res.json();
    if (json.ok) {
      location.reload();
    } else {
      alert(json.message || 'Could not cancel appointment.');
    }
  };
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
