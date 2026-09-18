<?php 
require_once __DIR__.'/includes/functions.php';
$pageTitle = 'Discover Your Future';
require __DIR__.'/includes/header.php';
?>
<section class="hero py-5 bg-light border-bottom">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge text-bg-primary-subtle text-primary border px-3 py-2 rounded-pill mb-3">
          <i class="bi bi-compass-fill me-1"></i> Career Guidance System
        </span>
        <h1 class="display-4 fw-bold text-dark">Navigate your career path with clarity and expert guidance.</h1>
        <p class="lead text-muted">
          Discover careers tailored to your personality through RIASEC assessments, review evidence-based recommendations, and connect directly with certified career counsellors for 1-on-1 guidance appointments.
        </p>
        <div class="d-flex gap-3 flex-wrap mt-4">
          <?php if (user()): ?>
            <?php if (user()['role'] === 'student'): ?>
              <a class="btn btn-primary btn-lg shadow-sm" href="<?=url('assessment/interest.php')?>">
                <i class="bi bi-clipboard2-check me-2"></i>Take Career Assessment
              </a>
              <a class="btn btn-outline-primary btn-lg" href="<?=url('dashboard/counselling.php')?>">
                <i class="bi bi-chat-heart me-2"></i>Request Counselling
              </a>
            <?php elseif (user()['role'] === 'counselor'): ?>
              <a class="btn btn-primary btn-lg shadow-sm" href="<?=url('counselor/index.php')?>">
                <i class="bi bi-people me-2"></i>Student Profiles
              </a>
              <a class="btn btn-outline-primary btn-lg" href="<?=url('counselor/requests.php')?>">
                <i class="bi bi-inbox me-2"></i>Counselling Requests
              </a>
            <?php elseif (user()['role'] === 'admin'): ?>
              <a class="btn btn-primary btn-lg shadow-sm" href="<?=url('admin/index.php')?>">
                <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
              </a>
              <a class="btn btn-outline-primary btn-lg" href="<?=url('admin/monitor.php')?>">
                <i class="bi bi-activity me-2"></i>System Monitor
              </a>
            <?php endif; ?>
          <?php else: ?>
            <a class="btn btn-primary btn-lg shadow-sm" href="<?=url('auth/register.php')?>">
              <i class="bi bi-person-plus me-2"></i>Create Student Account
            </a>
            <a class="btn btn-outline-primary btn-lg" href="<?=url('careers/browse.php')?>">
              <i class="bi bi-briefcase me-2"></i>Explore Careers
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
          <h2 class="h5 fw-bold mb-3"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Guidance Ecosystem</h2>
          <div class="row g-3">
            <div class="col-6">
              <div class="p-3 bg-light rounded-3 h-100 border">
                <i class="bi bi-clipboard2-pulse fs-3 text-primary"></i>
                <div class="fw-semibold mt-2">Career Assessment</div>
                <small class="text-muted">RIASEC dimensions</small>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 bg-light rounded-3 h-100 border">
                <i class="bi bi-stars fs-3 text-success"></i>
                <div class="fw-semibold mt-2">Recommended Careers</div>
                <small class="text-muted">Evidence & pathways</small>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 bg-light rounded-3 h-100 border">
                <i class="bi bi-chat-dots fs-3 text-info"></i>
                <div class="fw-semibold mt-2">Career Counselling</div>
                <small class="text-muted">1-on-1 appointments</small>
              </div>
            </div>
            <div class="col-6">
              <div class="p-3 bg-light rounded-3 h-100 border">
                <i class="bi bi-shield-check fs-3 text-warning"></i>
                <div class="fw-semibold mt-2">Admin Control</div>
                <small class="text-muted">System monitoring & access</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container py-5">
  <div class="text-center mb-5">
    <h2 class="fw-bold">How Career Guidance Works</h2>
    <p class="text-muted">A structured three-way ecosystem connecting Students, Career Counsellors, and Administrators.</p>
  </div>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card p-4 h-100 border shadow-sm rounded-3">
        <div class="badge bg-primary fs-6 align-self-start mb-3 rounded-pill px-3 py-2">Student</div>
        <h3 class="h5 fw-bold">Assess & Discover</h3>
        <p class="text-muted mb-3">Complete RIASEC assessments to uncover your personality strengths, review recommended careers, and request tailored career guidance sessions.</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-1"><i class="bi bi-check2-circle text-primary me-2"></i>RIASEC interest assessment</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-primary me-2"></i>Review matched careers</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-primary me-2"></i>Request career counselling sessions</li>
        </ul>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4 h-100 border shadow-sm rounded-3">
        <div class="badge bg-info text-dark fs-6 align-self-start mb-3 rounded-pill px-3 py-2">Career Counsellor</div>
        <h3 class="h5 fw-bold">Guide & Advise</h3>
        <p class="text-muted mb-3">Examine student profiles and assessment scores, respond to guidance inquiries, recommend tailored career pathways, and manage counselling appointments.</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-1"><i class="bi bi-check2-circle text-info me-2"></i>Review student assessment profiles</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-info me-2"></i>Recommend career pathways & steps</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-info me-2"></i>Manage appointments & session notes</li>
        </ul>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4 h-100 border shadow-sm rounded-3">
        <div class="badge bg-dark fs-6 align-self-start mb-3 rounded-pill px-3 py-2">Administrator</div>
        <h3 class="h5 fw-bold">Manage & Monitor</h3>
        <p class="text-muted mb-3">Maintain user accounts and role permissions, manage platform career resources, and monitor real-time system performance and counseling engagement.</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-1"><i class="bi bi-check2-circle text-dark me-2"></i>Manage user accounts & access</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-dark me-2"></i>Monitor system metrics & audit logs</li>
          <li class="mb-1"><i class="bi bi-check2-circle text-dark me-2"></i>Curate career catalog</li>
        </ul>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
