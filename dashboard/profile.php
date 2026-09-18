<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor']);
$uid = (int)user()['id'];

$stmt = $con->prepare('SELECT name, email, phone, education_level, date_of_birth, resume_path, resume_updated_at, created_at FROM users WHERE id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$stmt = $con->prepare('SELECT * FROM user_education WHERE user_id=? ORDER BY is_current DESC, end_year DESC, start_year DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$education = $stmt->get_result();

// Get RIASEC completion
$stmt = $con->prepare('SELECT COUNT(*) n FROM user_interest_scores WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$hasAssessment = (int)$stmt->get_result()->fetch_assoc()['n'] > 0;

$pageTitle = 'View and Update Profile · Career Guidance System';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="container py-5" style="max-width: 1080px;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">Student Profile</span>
      <h1 class="h2 fw-bold mb-1"><?=e($profile['name'])?></h1>
      <p class="text-muted mb-0"><i class="bi bi-envelope me-1"></i><?=e($profile['email'])?> &middot; Member since <?=date('M Y', strtotime($profile['created_at']))?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?=url('assessment/interest.php')?>" class="btn btn-outline-primary">
        <i class="bi bi-clipboard2-check me-1"></i><?=$hasAssessment ? 'Review Assessment' : 'Take Assessment'?>
      </a>
      <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-primary">
        <i class="bi bi-chat-heart me-1"></i>Request Counselling
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Personal Information & Resume -->
    <div class="col-lg-5">
      <!-- Personal Details Card -->
      <div class="card p-4 shadow-sm border-0 rounded-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Details</h2>
          <span class="badge bg-light text-muted border">Editable</span>
        </div>
        <form action="<?=url('ajax/profile_update.php')?>" method="post" data-ajax>
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Full Name</label>
            <input class="form-control" name="name" value="<?=e($profile['name'])?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Email (Read-only)</label>
            <input class="form-control bg-light" value="<?=e($profile['email'])?>" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Phone Number</label>
            <input class="form-control" name="phone" value="<?=e($profile['phone'] ?? '')?>" placeholder="+1 555-0199">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Date of Birth</label>
            <input type="date" class="form-control" name="date_of_birth" value="<?=e($profile['date_of_birth'] ?? '')?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Education Level</label>
            <select class="form-select" name="education_level" required>
              <?php foreach(['secondary' => 'Secondary / High School', 'undergraduate' => 'Undergraduate / College', 'graduate' => 'Graduate / Master / PhD', 'other' => 'Other / Vocational'] as $v => $lbl): ?>
                <option value="<?=$v?>" <?=$profile['education_level'] === $v ? 'selected' : ''?>><?=$lbl?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Update Personal Details</button>
        </form>
      </div>

      <!-- Resume Card -->
      <div class="card p-4 shadow-sm border-0 rounded-3">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="profile-document-icon bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-file-earmark-person fs-3"></i></span>
          <div>
            <h2 class="h5 fw-bold mb-0">Career Resume</h2>
            <small class="text-muted"><?=$profile['resume_updated_at'] ? 'Updated ' . date('M j, Y', strtotime($profile['resume_updated_at'])) : 'No resume uploaded yet'?></small>
          </div>
        </div>
        <?php if ($profile['resume_path']): ?>
          <a class="btn btn-outline-primary mb-3 w-100" target="_blank" href="<?=url($profile['resume_path'])?>">
            <i class="bi bi-eye me-1"></i> View Current Resume
          </a>
        <?php endif; ?>
        <form action="<?=url('ajax/resume_upload.php')?>" method="post" enctype="multipart/form-data" data-ajax>
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <label class="form-label small fw-semibold text-muted"><?=$profile['resume_path'] ? 'Upload New Version' : 'Upload Resume'?></label>
          <input class="form-control mb-2" type="file" name="resume" accept=".pdf,.doc,.docx" required>
          <div class="form-text mb-3">PDF, DOC, or DOCX &middot; max 5 MB</div>
          <button class="btn btn-outline-dark w-100"><i class="bi bi-cloud-arrow-up me-1"></i> <?=$profile['resume_path'] ? 'Replace Resume' : 'Save Resume'?></button>
        </form>
      </div>
    </div>

    <!-- Right Column: Education Background -->
    <div class="col-lg-7">
      <div class="card p-4 shadow-sm border-0 rounded-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Education Background</h2>
            <p class="small text-muted mb-0">List your qualifications, academic programs, and ongoing study.</p>
          </div>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#educationModal">
            <i class="bi bi-plus-circle me-1"></i> Add Education
          </button>
        </div>

        <?php if (!$education->num_rows): ?>
          <div class="p-4 bg-light rounded text-center text-muted">
            <i class="bi bi-book fs-2 d-block mb-2"></i>
            No education records added yet. Click "Add Education" to list your study background.
          </div>
        <?php endif; ?>

        <?php foreach ($education as $item): ?>
          <div class="border rounded-3 p-3 mb-3 d-flex justify-content-between align-items-start bg-white">
            <div>
              <strong class="text-dark fs-6"><?=e($item['qualification'])?></strong>
              <div class="text-primary fw-medium"><?=e($item['institution'])?></div>
              <small class="text-muted d-block mt-1">
                <i class="bi bi-mortarboard me-1"></i><?=e($item['field_of_study'])?> &middot; 
                <?=$item['start_year']?> &ndash; <?=$item['is_current'] ? '<span class="badge bg-success-subtle text-success border">Present</span>' : $item['end_year']?>
              </small>
            </div>
            <button class="btn btn-sm btn-outline-secondary edit-education" data-row='<?=e(json_encode($item))?>'>
              <i class="bi bi-pencil"></i> Edit
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Quick Guidance Status Card -->
      <div class="card p-4 shadow-sm border-0 rounded-3 mt-4 bg-light border">
        <h3 class="h6 fw-bold mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Guidance Integration</h3>
        <p class="small text-muted mb-3">Your profile information is shared directly with your assigned career counsellor when you request guidance or appointments.</p>
        <div class="d-flex gap-2">
          <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-calendar2-check me-1"></i>View Counselling Appointments
          </a>
          <a href="<?=url('dashboard/progress.php')?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-stars me-1"></i>View Career Recommendations
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Education Modal -->
<div class="modal fade" id="educationModal">
  <div class="modal-dialog">
    <form class="modal-content" action="<?=url('ajax/education_save.php')?>" method="post" data-ajax>
      <div class="modal-header">
        <h2 class="modal-title fs-5">Education Background</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="id">
        <div class="mb-3">
          <label class="form-label fw-semibold">Institution</label>
          <input class="form-control" name="institution" required placeholder="e.g. University of Cape Town">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Qualification</label>
          <input class="form-control" name="qualification" required placeholder="e.g. Bachelor of Science">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Field of Study</label>
          <input class="form-control" name="field_of_study" placeholder="e.g. Computer Science & Statistics">
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Start Year</label>
            <input type="number" class="form-control" name="start_year" min="1960" max="<?=date('Y')+1?>" required>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">End Year</label>
            <input type="number" class="form-control" name="end_year" min="1960" max="<?=date('Y')+10?>">
          </div>
        </div>
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" name="is_current" id="isCurrentEdu">
          <label class="form-check-label" for="isCurrentEdu">I am currently studying here</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger d-none" id="deleteEducation">Delete</button>
        <button type="submit" class="btn btn-primary">Save Education</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.edit-education').forEach(button => {
  button.onclick = () => {
    const row = JSON.parse(button.dataset.row);
    const form = document.querySelector('#educationModal form');
    Object.keys(row).forEach(key => {
      if (form.elements[key]) {
        if (form.elements[key].type === 'checkbox') {
          form.elements[key].checked = +row[key] === 1;
        } else {
          form.elements[key].value = row[key] ?? '';
        }
      }
    });
    document.getElementById('deleteEducation').classList.remove('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('educationModal')).show();
  };
});

document.getElementById('educationModal').addEventListener('hidden.bs.modal', () => {
  const form = document.querySelector('#educationModal form');
  form.reset();
  form.elements.id.value = '';
  document.getElementById('deleteEducation').classList.add('d-none');
});

document.getElementById('deleteEducation').onclick = () => {
  const form = document.querySelector('#educationModal form');
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'delete';
  input.value = '1';
  form.append(input);
  form.requestSubmit();
};
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
