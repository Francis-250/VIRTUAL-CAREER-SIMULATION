<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor']);
$studentId = (int)user()['id'];

// Get student's requests
$stmt = $con->prepare("
  SELECT r.*, c.name AS counselor_name, resp.name AS responder_name 
  FROM counselling_requests r 
  LEFT JOIN users c ON c.id = r.counselor_id 
  LEFT JOIN users resp ON resp.id = r.responded_by 
  WHERE r.student_id = ? 
  ORDER BY r.created_at DESC
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get student's appointments
$stmt = $con->prepare("
  SELECT a.*, c.name AS counselor_name, c.email AS counselor_email 
  FROM counselling_appointments a 
  JOIN users c ON c.id = a.counselor_id 
  WHERE a.student_id = ? 
  ORDER BY a.appointment_date ASC, a.start_time ASC
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available counselors for dropdown
$counselors = $con->query("SELECT id, name, email FROM users WHERE role='counselor' AND status='active' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$upcomingAppts = array_filter($appointments, fn($a) => in_array($a['status'], ['scheduled', 'rescheduled']) && strtotime($a['appointment_date']) >= strtotime(date('Y-m-d')));
$pastAppts = array_filter($appointments, fn($a) => $a['status'] === 'completed' || strtotime($a['appointment_date']) < strtotime(date('Y-m-d')));

$pageTitle = 'Career Counselling';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1100px;">
  <!-- Header with Actions -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">
        <i class="bi bi-chat-heart me-1"></i> 1-on-1 Guidance Support
      </span>
      <h1 class="h2 fw-bold mb-1">Career Counselling</h1>
      <p class="text-muted mb-0">Connect with certified career counsellors, request guidance sessions, and track scheduled appointments.</p>
    </div>
    <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
      <i class="bi bi-calendar-plus me-1"></i> Request Counselling
    </button>
  </div>

  <!-- KPI Row -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-chat-left-dots fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=count($requests)?></div>
            <div class="text-muted small">Counselling Requests</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-calendar-check fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=count($upcomingAppts)?></div>
            <div class="text-muted small">Upcoming Appointments</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card p-3 border shadow-sm rounded-3 bg-white">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-check2-circle fs-4"></i></div>
          <div>
            <div class="h3 mb-0 fw-bold"><?=count(array_filter($appointments, fn($a)=>$a['status']==='completed'))?></div>
            <div class="text-muted small">Sessions Completed</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Appointments Section -->
  <div class="card p-4 shadow-sm border-0 rounded-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="h5 fw-bold mb-1"><i class="bi bi-calendar2-event text-primary me-2"></i>Scheduled Counselling Appointments</h2>
        <p class="small text-muted mb-0">Upcoming and completed guidance sessions with meeting links and action plans.</p>
      </div>
    </div>

    <?php if (empty($appointments)): ?>
      <div class="p-4 text-center bg-light rounded-3 text-muted">
        <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
        <div class="fw-semibold">No counselling appointments scheduled yet</div>
        <p class="small mb-3">Submit a request below to get scheduled with a career counsellor.</p>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
          <i class="bi bi-plus-circle me-1"></i>Request Counselling
        </button>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($appointments as $appt): ?>
          <?php 
            $isUpcoming = in_array($appt['status'], ['scheduled', 'rescheduled']) && strtotime($appt['appointment_date']) >= strtotime(date('Y-m-d'));
            $isToday = $appt['appointment_date'] === date('Y-m-d');
            $badgeClass = match($appt['status']) {
              'completed' => 'bg-success text-white',
              'cancelled' => 'bg-danger text-white',
              'rescheduled' => 'bg-warning text-dark',
              default => $isToday ? 'bg-primary text-white' : 'bg-info-subtle text-info-emphasis border'
            };
          ?>
          <div class="col-md-6">
            <div class="card h-100 border p-3 rounded-3 <?=$isToday ? 'border-primary shadow-sm' : ''?>">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge <?=$badgeClass?> rounded-pill px-3 py-1">
                  <?=$isToday && $appt['status'] === 'scheduled' ? 'Today' : ucfirst($appt['status'])?>
                </span>
                <small class="text-muted">
                  <i class="bi bi-person-badge text-primary me-1"></i><?=e($appt['counselor_name'])?>
                </small>
              </div>

              <h3 class="h6 fw-bold mb-2"><?=e($appt['title'])?></h3>

              <div class="small text-muted mb-3 d-flex flex-column gap-1">
                <div>
                  <i class="bi bi-calendar3 me-1 text-primary"></i><strong><?=date('l, F j, Y', strtotime($appt['appointment_date']))?></strong>
                </div>
                <div>
                  <i class="bi bi-clock me-1 text-primary"></i><?=date('H:i', strtotime($appt['start_time']))?> &ndash; <?=date('H:i', strtotime($appt['end_time']))?>
                </div>
                <div>
                  <i class="bi bi-geo-alt me-1 text-primary"></i>Mode: 
                  <strong class="text-dark"><?=ucwords(str_replace('_', ' ', $appt['meeting_type']))?></strong>
                  <?php if (!empty($appt['location_details'])): ?>
                    (<?=e($appt['location_details'])?>)
                  <?php endif; ?>
                </div>
              </div>

              <?php if (!empty($appt['meeting_link']) && $appt['status'] === 'scheduled'): ?>
                <div class="mb-3">
                  <a href="<?=e($appt['meeting_link'])?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-camera-video me-1"></i> Open Meeting Link
                  </a>
                </div>
              <?php endif; ?>

              <?php if (!empty($appt['action_plan'])): ?>
                <div class="p-3 bg-success-subtle rounded-3 mt-auto">
                  <div class="fw-bold small text-success-emphasis mb-1">
                    <i class="bi bi-journal-check me-1"></i>Counsellor Action Plan:
                  </div>
                  <p class="small mb-0 text-dark"><?=nl2br(e($appt['action_plan']))?></p>
                </div>
              <?php elseif (!empty($appt['counselor_notes'])): ?>
                <div class="p-2 bg-light rounded small text-muted mt-auto">
                  <strong>Notes:</strong> <?=e($appt['counselor_notes'])?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Requests Section -->
  <div class="card p-4 shadow-sm border-0 rounded-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="h5 fw-bold mb-1"><i class="bi bi-inbox-fill text-primary me-2"></i>My Counselling Requests</h2>
        <p class="small text-muted mb-0">Inquiries and requests submitted to career counsellors.</p>
      </div>
      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
        <i class="bi bi-plus-circle me-1"></i>New Request
      </button>
    </div>

    <?php if (empty($requests)): ?>
      <div class="p-4 text-center bg-light rounded-3 text-muted">
        <i class="bi bi-chat-square-dots fs-2 d-block mb-2 text-secondary"></i>
        You have not submitted any career counselling requests yet.
      </div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($requests as $req): ?>
          <?php 
            $statusBadge = match($req['status']) {
              'pending' => 'bg-warning text-dark',
              'responded' => 'bg-info text-dark',
              'scheduled' => 'bg-success text-white',
              'completed' => 'bg-secondary text-white',
              'cancelled' => 'bg-light text-muted border',
              default => 'bg-light text-dark'
            };
          ?>
          <div class="border rounded-3 p-3 bg-white shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-2">
              <div>
                <span class="badge <?=$statusBadge?> rounded-pill px-3 py-1 me-2">
                  <?=ucfirst($req['status'])?>
                </span>
                <strong class="fs-6 text-dark"><?=e($req['topic'])?></strong>
              </div>
              <small class="text-muted">Requested on <?=date('M j, Y H:i', strtotime($req['created_at']))?></small>
            </div>

            <p class="mb-2 text-secondary small bg-light p-2 rounded">
              <?=nl2br(e($req['message']))?>
            </p>

            <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
              <?php if (!empty($req['preferred_date'])): ?>
                <div><i class="bi bi-calendar-event me-1"></i>Preferred Date: <strong><?=e($req['preferred_date'])?></strong></div>
              <?php endif; ?>
              <?php if (!empty($req['preferred_time_slot'])): ?>
                <div><i class="bi bi-clock me-1"></i>Preferred Slot: <strong><?=e($req['preferred_time_slot'])?></strong></div>
              <?php endif; ?>
              <?php if (!empty($req['counselor_name'])): ?>
                <div><i class="bi bi-person me-1"></i>Assigned: <strong><?=e($req['counselor_name'])?></strong></div>
              <?php endif; ?>
            </div>

            <?php if (!empty($req['counselor_response'])): ?>
              <div class="p-3 bg-info-subtle border border-info-subtle rounded-3 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="text-info-emphasis small">
                    <i class="bi bi-chat-quote-fill me-1"></i>Counsellor Response (<?=e($req['responder_name'] ?: 'Counsellor')?>):
                  </strong>
                  <small class="text-muted"><?=date('M j, Y H:i', strtotime($req['responded_at']))?></small>
                </div>
                <div class="small text-dark"><?=nl2br(e($req['counselor_response']))?></div>
              </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'pending'): ?>
              <div class="mt-2 text-end">
                <button class="btn btn-sm btn-outline-danger cancel-req-btn" data-id="<?=$req['id']?>">
                  <i class="bi bi-x-circle me-1"></i>Cancel Request
                </button>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Request Counselling Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('ajax/counselling_request_submit.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold">
          <i class="bi bi-chat-heart text-primary me-2"></i>Request Career Counselling
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">

        <div class="alert alert-info py-2 small d-flex align-items-center gap-2">
          <i class="bi bi-info-circle-fill fs-5"></i>
          <div>Your counsellor will review your RIASEC assessment results, quiz records, and profile before responding or scheduling your appointment.</div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Guidance Topic <span class="text-danger">*</span></label>
            <select class="form-select" name="topic" required>
              <option value="">Select a topic</option>
              <option value="Career Choice & Direction">Career Choice & Direction</option>
              <option value="RIASEC Assessment Review">RIASEC Assessment Review & Next Steps</option>
              <option value="Higher Education & Degree Pathways">Higher Education & Degree Pathways</option>
              <option value="Subject / Major Selection">Subject / Major Selection</option>
              <option value="Resume, CV & Portfolio Review">Resume, CV & Portfolio Review</option>
              <option value="Internship & Workplace Transition">Internship & Workplace Transition</option>
              <option value="Other Career Guidance Question">Other Career Guidance Question</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Preferred Counsellor</label>
            <select class="form-select" name="counselor_id">
              <option value="">Any available Career Counsellor</option>
              <?php foreach ($counselors as $c): ?>
                <option value="<?=$c['id']?>"><?=e($c['name'])?> (<?=e($c['email'])?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Preferred Date</label>
            <input type="date" class="form-control" name="preferred_date" min="<?=date('Y-m-d')?>">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Preferred Time Window</label>
            <select class="form-select" name="preferred_time_slot">
              <option value="Morning (09:00 - 12:00)">Morning (09:00 - 12:00)</option>
              <option value="Afternoon (13:00 - 16:00)" selected>Afternoon (13:00 - 16:00)</option>
              <option value="Late Afternoon (16:00 - 18:00)">Late Afternoon (16:00 - 18:00)</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Your Situation or Questions for the Counsellor <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" rows="4" required placeholder="Describe what you would like advice on (e.g. your interests, concerns, choices you are weighing, questions about specific careers or educational paths)..." minlength="10" maxlength="2000"></textarea>
            <div class="form-text">Share enough details so your counsellor can prepare relevant recommendations.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-1"></i> Submit Guidance Request
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.cancel-req-btn').forEach(btn => {
  btn.onclick = async () => {
    if (!confirm('Are you sure you want to cancel this counselling request?')) return;
    const form = new FormData();
    form.append('csrf', window.APP.csrf);
    form.append('id', btn.dataset.id);
    const res = await fetch('<?=url('ajax/counselling_request_cancel.php')?>', {
      method: 'POST',
      body: form
    });
    const json = await res.json();
    if (json.ok) {
      location.reload();
    } else {
      alert(json.message || 'Could not cancel request.');
    }
  };
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
