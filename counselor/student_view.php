<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role('counselor');

$id = (int)($_GET['id'] ?? 0);

// Student info
$stmt = $con->prepare("
  SELECT id, name, email, phone, education_level, date_of_birth, resume_path, resume_updated_at, created_at 
  FROM users 
  WHERE id = ? AND role = 'student' AND deleted_at IS NULL
");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    http_response_code(404);
    exit('Student not found');
}

// Student Education History
$stmt = $con->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY is_current DESC, end_year DESC, start_year DESC");
$stmt->bind_param('i', $id);
$stmt->execute();
$education = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// RIASEC Assessment Scores
$stmt = $con->prepare("SELECT interest_type, score FROM user_interest_scores WHERE user_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$riasec = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Career Recommendations
$stmt = $con->prepare("
  SELECT r.*, c.title, c.summary 
  FROM career_recommendations r 
  JOIN careers c ON c.id = r.career_id 
  WHERE r.user_id = ? 
  ORDER BY r.match_score DESC 
  LIMIT 6
");
$stmt->bind_param('i', $id);
$stmt->execute();
$recs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Counsellor Pathway Recommendations for this student
$stmt = $con->prepare("
  SELECT p.*, c.title AS career_title, u.name AS counselor_name 
  FROM counsellor_pathway_recommendations p 
  LEFT JOIN careers c ON c.id = p.career_id 
  JOIN users u ON u.id = p.counselor_id 
  WHERE p.student_id = ? 
  ORDER BY p.created_at DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$pathways = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Counselling Requests from this student
$stmt = $con->prepare("
  SELECT r.*, resp.name AS responder_name 
  FROM counselling_requests r 
  LEFT JOIN users resp ON resp.id = r.responded_by 
  WHERE r.student_id = ? 
  ORDER BY r.created_at DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Appointments for this student
$stmt = $con->prepare("
  SELECT a.*, c.name AS counselor_name 
  FROM counselling_appointments a 
  JOIN users c ON c.id = a.counselor_id 
  WHERE a.student_id = ? 
  ORDER BY a.appointment_date DESC, a.start_time DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Simulations Progress
$stmt = $con->prepare("
  SELECT s.title, p.status, p.total_score, p.max_possible_score, p.completed_at 
  FROM user_simulation_progress p 
  JOIN career_simulations s ON s.id = p.simulation_id 
  WHERE p.user_id = ? 
  ORDER BY p.updated_at DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Quiz Attempts
$stmt = $con->prepare("
  SELECT c.title AS career_title, q.title AS quiz, a.score, a.max_score, a.passed, a.completed_at 
  FROM quiz_attempts a 
  JOIN quizzes q ON q.id = a.quiz_id 
  JOIN careers c ON c.id = q.career_id 
  WHERE a.user_id = ? 
  ORDER BY a.completed_at DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Written Task Attempts
$stmt = $con->prepare("
  SELECT a.id attempt_id, t.title, t.max_score, a.response_text, a.ai_feedback, a.counselor_score, a.counselor_feedback, a.attempted_at 
  FROM user_task_attempts a 
  JOIN simulation_tasks t ON t.id = a.task_id 
  WHERE a.user_id = ? AND a.response_text IS NOT NULL AND a.response_text <> '' 
  ORDER BY a.attempted_at DESC
");
$stmt->bind_param('i', $id);
$stmt->execute();
$responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Active careers for pathway modal
$careers = $con->query("SELECT id, title FROM careers WHERE is_active=1 ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = e($student['name']) . ' · Student Profile';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1140px;">
  <!-- Breadcrumb & Actions -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?=url('counselor/index.php')?>" class="text-decoration-none text-muted">
      <i class="bi bi-arrow-left me-1"></i> Back to Student Directory
    </a>
    <div class="d-flex gap-2">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pathwayModal">
        <i class="bi bi-signpost-split me-1"></i> Recommend Career Pathway
      </button>
      <a href="<?=url('counselor/appointments.php')?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-calendar-plus me-1"></i> Schedule Appointment
      </a>
    </div>
  </div>

  <!-- Student Hero Card -->
  <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width:64px;height:64px;">
          <?=strtoupper(substr($student['name'], 0, 1))?>
        </div>
        <div>
          <h1 class="h3 fw-bold mb-1"><?=e($student['name'])?></h1>
          <div class="text-muted small d-flex flex-wrap gap-3">
            <span><i class="bi bi-envelope me-1"></i><?=e($student['email'])?></span>
            <?php if ($student['phone']): ?>
              <span><i class="bi bi-telephone me-1"></i><?=e($student['phone'])?></span>
            <?php endif; ?>
            <span><i class="bi bi-mortarboard me-1"></i><?=e(ucfirst($student['education_level'] ?: 'Student'))?></span>
            <span><i class="bi bi-calendar3 me-1"></i>Joined <?=date('M Y', strtotime($student['created_at']))?></span>
          </div>
        </div>
      </div>
      <div>
        <?php if ($student['resume_path']): ?>
          <a class="btn btn-outline-primary" href="<?=url($student['resume_path'])?>" target="_blank">
            <i class="bi bi-file-earmark-person me-1"></i> View Submitted Resume
          </a>
        <?php else: ?>
          <span class="badge bg-light text-muted border p-2">No Resume Uploaded</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Assessment Results & Recommendations -->
    <div class="col-lg-7">
      <!-- RIASEC Assessment Card -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0">
            <i class="bi bi-clipboard2-pulse text-primary me-2"></i>RIASEC Career Assessment Results
          </h2>
          <?php if ($riasec): ?>
            <span class="badge bg-success-subtle text-success border px-2 py-1">Assessment Completed</span>
          <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Not Completed Yet</span>
          <?php endif; ?>
        </div>

        <?php if ($riasec): ?>
          <div class="row align-items-center mb-3">
            <div class="col-md-7">
              <div style="height: 240px;">
                <canvas id="studentRiasecChart"></canvas>
              </div>
            </div>
            <div class="col-md-5">
              <div class="d-flex flex-column gap-2 small">
                <?php foreach ($riasec as $trait): ?>
                  <div>
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-semibold text-dark"><?=ucfirst($trait['interest_type'])?></span>
                      <span class="text-primary fw-bold"><?=round($trait['score'])?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-primary" style="width: <?=$trait['score']?>%"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="p-4 bg-light rounded-3 text-center text-muted">
            <i class="bi bi-lightbulb fs-2 d-block mb-1"></i>
            This student has not yet submitted their RIASEC career assessment.
          </div>
        <?php endif; ?>
      </div>

      <!-- System Career Recommendations -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <h2 class="h5 fw-bold mb-3">
          <i class="bi bi-stars text-warning me-2"></i>System Recommended Careers
        </h2>
        <?php if ($recs): ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($recs as $r): ?>
              <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="text-dark"><?=e($r['title'])?></strong>
                  <span class="badge bg-primary-subtle text-primary border rounded-pill">
                    <?=round($r['match_score'])?>% Match
                  </span>
                </div>
                <p class="small text-muted mb-2"><?=e($r['explanation'] ?: $r['summary'])?></p>
                <div class="d-flex justify-content-between align-items-center small">
                  <span class="text-secondary">Based on: <?=e($r['based_on'])?></span>
                  <a href="<?=url('careers/view.php?id='.$r['career_id'])?>" target="_blank" class="text-primary text-decoration-none fw-medium">
                    Career Details <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="small text-muted mb-0">No system recommendations generated yet.</p>
        <?php endif; ?>
      </div>

      <!-- Counsellor Pathway Recommendations -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0">
            <i class="bi bi-signpost-split text-primary me-2"></i>Recommended Career Pathways
          </h2>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#pathwayModal">
            <i class="bi bi-plus-circle me-1"></i>Add Pathway
          </button>
        </div>

        <?php if (empty($pathways)): ?>
          <div class="p-3 bg-light rounded-3 text-center text-muted small">
            No custom career pathway recommended for this student yet. Click "Add Pathway" to formulate advice.
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($pathways as $pw): ?>
              <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <strong class="text-dark fs-6"><?=e($pw['pathway_title'])?></strong>
                  <small class="text-muted"><i class="bi bi-person-badge text-primary me-1"></i><?=e($pw['counselor_name'])?></small>
                </div>
                <?php if ($pw['career_title']): ?>
                  <div class="small text-primary fw-medium mb-2">Target Career: <?=e($pw['career_title'])?></div>
                <?php endif; ?>
                <p class="small text-secondary mb-2"><?=nl2br(e($pw['guidance_notes']))?></p>
                <?php if ($pw['recommended_steps']): ?>
                  <div class="p-2 bg-white border rounded small">
                    <strong class="text-dark">Action Steps:</strong>
                    <div class="text-muted"><?=nl2br(e($pw['recommended_steps']))?></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Written Task Responses & Grading -->
      <div class="card p-4 shadow-sm border-0 rounded-4 bg-white">
        <h2 class="h5 fw-bold mb-3"><i class="bi bi-journal-text text-primary me-2"></i>Simulation Tasks & Grading</h2>
        <?php if (empty($responses)): ?>
          <p class="text-muted small mb-0">No written tasks submitted by this student.</p>
        <?php endif; ?>
        <?php foreach ($responses as $r): ?>
          <div class="border-bottom py-3">
            <strong class="text-dark"><?=e($r['title'])?></strong>
            <p class="small text-secondary mb-2 mt-1"><?=nl2br(e($r['response_text']))?></p>
            <?php if ($r['ai_feedback']): ?>
              <div class="alert alert-info py-2 small mb-2"><strong>AI Feedback:</strong> <?=e($r['ai_feedback'])?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-muted">
                Submitted <?=date('M j, Y', strtotime($r['attempted_at']))?>
                <?php if ($r['counselor_score'] !== null): ?>
                  &middot; <strong>Graded: <?=$r['counselor_score']?> / <?=$r['max_score']?></strong>
                <?php endif; ?>
              </small>
              <button class="btn btn-sm btn-outline-primary grade-attempt" data-row='<?=e(json_encode($r))?>'>
                <?=$r['counselor_score'] !== null ? 'Update Review' : 'Grade Response'?>
              </button>
            </div>
            <?php if ($r['counselor_feedback']): ?>
              <div class="alert alert-success py-2 small mt-2 mb-0">
                <strong>Counselor:</strong> <?=e($r['counselor_feedback'])?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Column: Education, Requests & Appointments -->
    <div class="col-lg-5">
      <!-- Education Background -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <h2 class="h5 fw-bold mb-3"><i class="bi bi-mortarboard text-primary me-2"></i>Education Background</h2>
        <?php if (empty($education)): ?>
          <p class="small text-muted mb-0">No education items recorded.</p>
        <?php endif; ?>
        <?php foreach ($education as $item): ?>
          <div class="border-bottom pb-2 mb-2">
            <strong class="text-dark small d-block"><?=e($item['qualification'])?></strong>
            <div class="text-primary small"><?=e($item['institution'])?></div>
            <small class="text-muted">
              <?=e($item['field_of_study'])?> &middot; <?=$item['start_year']?>–<?=$item['is_current'] ? 'Present' : $item['end_year']?>
            </small>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Student Counselling Requests -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <h2 class="h5 fw-bold mb-3"><i class="bi bi-inbox text-primary me-2"></i>Counselling Requests</h2>
        <?php if (empty($requests)): ?>
          <p class="small text-muted mb-0">No counselling requests submitted by this student.</p>
        <?php endif; ?>
        <?php foreach ($requests as $req): ?>
          <div class="border rounded-3 p-3 mb-2 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong class="small text-dark"><?=e($req['topic'])?></strong>
              <span class="badge bg-<?=match($req['status']){'pending'=>'warning text-dark','scheduled'=>'success text-white','completed'=>'secondary text-white',default=>'info text-dark'}?> rounded-pill">
                <?=ucfirst($req['status'])?>
              </span>
            </div>
            <p class="small text-muted mb-2"><?=e($req['message'])?></p>
            <?php if ($req['counselor_response']): ?>
              <div class="small p-2 bg-white rounded border mb-2 text-dark">
                <strong>Your Reply:</strong> <?=e($req['counselor_response'])?>
              </div>
            <?php endif; ?>
            <a href="<?=url('counselor/requests.php?q='.urlencode($student['name']))?>" class="btn btn-sm btn-outline-primary w-100">
              Manage Request in Portal
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Scheduled Appointments -->
      <div class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0"><i class="bi bi-calendar-event text-primary me-2"></i>Appointments</h2>
          <a href="<?=url('counselor/appointments.php')?>" class="btn btn-sm btn-outline-primary">View Calendar</a>
        </div>
        <?php if (empty($appointments)): ?>
          <p class="small text-muted mb-0">No guidance appointments scheduled with this student.</p>
        <?php endif; ?>
        <?php foreach ($appointments as $appt): ?>
          <div class="border rounded-3 p-3 mb-2 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong class="small text-dark"><?=e($appt['title'])?></strong>
              <span class="badge bg-<?=match($appt['status']){'completed'=>'success','cancelled'=>'danger',default=>'primary'}?> rounded-pill">
                <?=ucfirst($appt['status'])?>
              </span>
            </div>
            <div class="small text-muted mb-2">
              <i class="bi bi-calendar3 me-1"></i><?=date('M j, Y', strtotime($appt['appointment_date']))?> at <?=date('H:i', strtotime($appt['start_time']))?>
              &middot; <?=ucwords(str_replace('_',' ',$appt['meeting_type']))?>
            </div>
            <?php if ($appt['action_plan']): ?>
              <div class="p-2 bg-success-subtle rounded small text-dark">
                <strong>Action Plan:</strong> <?=e($appt['action_plan'])?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Quiz Pass History -->
      <div class="card p-4 shadow-sm border-0 rounded-4 bg-white">
        <h2 class="h5 fw-bold mb-3"><i class="bi bi-patch-question text-primary me-2"></i>Career Knowledge Quizzes</h2>
        <?php if (empty($quizzes)): ?>
          <p class="small text-muted mb-0">No career quizzes attempted.</p>
        <?php endif; ?>
        <?php foreach ($quizzes as $q): ?>
          <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong class="small text-dark d-block"><?=e($q['quiz'])?></strong>
              <small class="text-muted"><?=e($q['career_title'])?></small>
            </div>
            <div class="text-end">
              <span class="badge bg-<?=$q['passed']?'success':'danger'?> rounded-pill">
                <?=$q['score']?> / <?=$q['max_score']?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recommend Pathway Modal -->
<div class="modal fade" id="pathwayModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?=url('counselor/recommend_pathway.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5 fw-bold">Recommend Career Pathway for <?=e($student['name'])?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="student_id" value="<?=$student['id']?>">

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
            <input class="form-control" name="pathway_title" required placeholder="e.g. Software Engineering & Cloud Roadmap">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Guidance & Rationale (Why this fits <?=e($student['name'])?>) <span class="text-danger">*</span></label>
            <textarea class="form-control" name="guidance_notes" rows="3" required placeholder="Detail the student's aptitudes and why this pathway is recommended..."></textarea>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Recommended Action Steps & Milestones</label>
            <textarea class="form-control" name="recommended_steps" rows="4" placeholder="Step 1: Focus on prerequisite subjects&#10;Step 2: Complete targeted certification&#10;Step 3: Schedule follow-up appointment..."></textarea>
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

<!-- Grade Response Modal -->
<div class="modal fade" id="gradeModal">
  <div class="modal-dialog">
    <form class="modal-content" action="<?=url('counselor/grade_attempt.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5">Grade Written Response</h2>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="attempt_id">
        <label class="form-label">Score <span id="gradeMaximum"></span></label>
        <input type="number" class="form-control mb-3" name="score" min="0" required>
        <label class="form-label">Counselor Feedback</label>
        <textarea class="form-control" name="feedback" rows="5" maxlength="2000" required></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Save Review</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if ($riasec): ?>
    new Chart(document.getElementById('studentRiasecChart'), {
      type: 'radar',
      data: {
        labels: <?=json_encode(array_map(fn($x)=>ucfirst($x['interest_type']), $riasec))?>,
        datasets: [{
          label: 'RIASEC (%)',
          data: <?=json_encode(array_column($riasec, 'score'), JSON_NUMERIC_CHECK)?>,
          borderColor: '#0d9488',
          backgroundColor: 'rgba(13,148,136,.15)',
          pointBackgroundColor: '#0d9488'
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          r: { min: 0, max: 100, ticks: { stepSize: 25 } }
        }
      }
    });
  <?php endif; ?>

  document.querySelectorAll('.grade-attempt').forEach(button => {
    button.addEventListener('click', () => {
      const row = JSON.parse(button.dataset.row);
      const form = document.querySelector('#gradeModal form');
      form.elements.attempt_id.value = row.attempt_id;
      form.elements.score.max = row.max_score;
      form.elements.score.value = row.counselor_score ?? 0;
      form.elements.feedback.value = row.counselor_feedback ?? '';
      document.getElementById('gradeMaximum').textContent = '(maximum ' + row.max_score + ')';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('gradeModal')).show();
    });
  });
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
