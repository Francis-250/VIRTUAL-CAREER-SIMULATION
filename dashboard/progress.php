<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_role(['student','counselor']);
$uid = (int)user()['id'];

// Simulation stats
$stmt = $con->prepare("SELECT COUNT(*) completed, (SELECT COUNT(*) FROM career_simulations WHERE is_active=1) available, COALESCE(AVG(CASE WHEN max_possible_score>0 THEN total_score/max_possible_score*100 END),0) avg_score FROM user_simulation_progress WHERE user_id=? AND status='completed'");
$stmt->bind_param('i', $uid);
$stmt->execute();
$sim = $stmt->get_result()->fetch_assoc();

// Quiz scores
$stmt = $con->prepare("SELECT c.title, AVG(a.score/NULLIF(a.max_score,0)*100) score FROM quiz_attempts a JOIN quizzes q ON q.id=a.quiz_id JOIN careers c ON c.id=q.career_id WHERE a.user_id=? AND a.completed_at IS NOT NULL GROUP BY c.id ORDER BY c.title");
$stmt->bind_param('i', $uid);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// RIASEC scores
$stmt = $con->prepare('SELECT interest_type, score FROM user_interest_scores WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$interests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recommendations
$stmt = $con->prepare('SELECT r.*, c.title, c.summary FROM career_recommendations r JOIN careers c ON c.id=r.career_id WHERE r.user_id=? ORDER BY r.match_score DESC LIMIT 6');
$stmt->bind_param('i', $uid);
$stmt->execute();
$recs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Counsellor Pathway Recommendations
$stmt = $con->prepare("
  SELECT p.*, c.title AS career_title, c.slug AS career_slug, u.name AS counselor_name 
  FROM counsellor_pathway_recommendations p 
  LEFT JOIN careers c ON c.id = p.career_id 
  JOIN users u ON u.id = p.counselor_id 
  WHERE p.student_id = ? 
  ORDER BY p.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$pathways = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Next upcoming appointment
$stmt = $con->prepare("
  SELECT a.*, u.name AS counselor_name 
  FROM counselling_appointments a 
  JOIN users u ON u.id = a.counselor_id 
  WHERE a.student_id = ? AND a.status = 'scheduled' AND a.appointment_date >= CURDATE() 
  ORDER BY a.appointment_date ASC, a.start_time ASC 
  LIMIT 1
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$nextAppt = $stmt->get_result()->fetch_assoc();

// Badges
$stmt = $con->prepare('SELECT b.*, ub.earned_at FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$badges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$completionPercent = $sim['available'] ? min(100, round($sim['completed'] / $sim['available'] * 100)) : 0;
$pageTitle = 'My Guidance & Progress · Career Guidance System';
$bodyClass = 'progress-page';
require dirname(__DIR__).'/includes/header.php';
?>
<div class="progress-shell">
  <!-- Next Appointment Alert -->
  <?php if ($nextAppt): ?>
    <div class="alert alert-primary shadow-sm border-0 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 p-3 rounded-4 mb-4">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-white text-primary p-2 rounded-circle fs-4 shadow-sm"><i class="bi bi-calendar2-check"></i></div>
        <div>
          <strong>Upcoming Career Counselling Session:</strong> <?=e($nextAppt['title'])?> with <?=e($nextAppt['counselor_name'])?>
          <div class="small text-dark mt-1">
            <i class="bi bi-clock me-1"></i><?=date('l, F j, Y', strtotime($nextAppt['appointment_date']))?> at <?=date('H:i', strtotime($nextAppt['start_time']))?> &middot; Mode: <?=ucwords(str_replace('_',' ',$nextAppt['meeting_type']))?>
          </div>
        </div>
      </div>
      <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-sm btn-primary">
        <i class="bi bi-arrow-right-circle me-1"></i>View Counselling Portal
      </a>
    </div>
  <?php endif; ?>

  <header class="progress-page-header">
    <div>
      <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">Student Guidance Portal</span>
      <h1 class="h2 fw-bold">My Guidance & Progress</h1>
      <p class="text-muted">Review your RIASEC assessment profile, recommended careers, and counsellor pathways.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?=url('assessment/interest.php')?>" class="btn btn-outline-primary">
        <i class="bi bi-clipboard2-check me-1"></i>Career Assessment
      </a>
      <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-primary">
        <i class="bi bi-chat-heart me-1"></i>Request Counselling
      </a>
      <form method="post" action="<?=url('ajax/generate_recommendations.php')?>" data-ajax class="d-inline">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <button class="btn btn-light border" title="Refresh recommendations based on latest inputs">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
      </form>
    </div>
  </header>

  <!-- KPI Grid -->
  <section class="progress-kpi-grid">
    <article class="progress-kpi">
      <h2>RIASEC Assessment</h2>
      <div><strong><?=$interests ? 'Completed' : 'Pending'?></strong></div>
      <p><?=$interests ? 'Personality profile unlocked' : '<a href="'.url('assessment/interest.php').'">Take assessment now</a>'?></p>
    </article>
    <article class="progress-kpi">
      <h2>Simulations Completed</h2>
      <div><strong><?=$sim['completed']?></strong><span>/ <?=$sim['available']?></span></div>
      <div class="progress-kpi-bar"><span style="width:<?=$completionPercent?>%"></span></div>
    </article>
    <article class="progress-kpi">
      <h2>Average Learning Score</h2>
      <div><strong><?=round($sim['avg_score'])?>%</strong></div>
      <p><?=$sim['completed'] ? 'Across completed simulations' : 'Hands-on practice score'?></p>
    </article>
    <article class="progress-kpi">
      <h2>Badges Earned</h2>
      <div><strong><?=count($badges)?></strong></div>
      <div class="progress-mini-badges">
        <?php if ($badges): foreach (array_slice($badges,0,4) as $b): ?>
          <span title="<?=e($b['name'])?>"><i class="bi bi-<?=e($b['icon']?:'award')?>"></i></span>
        <?php endforeach; else: ?>
          <span><i class="bi bi-lock"></i></span>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <!-- Counsellor Pathways Section (if any) -->
  <?php if (!empty($pathways)): ?>
    <section class="card p-4 shadow-sm border-0 rounded-4 mb-4 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 mb-1">
            <i class="bi bi-person-check-fill me-1"></i>Personalized Guidance
          </span>
          <h2 class="h5 fw-bold mb-0">Counsellor Pathway Recommendations</h2>
          <small class="text-muted">Direct career pathway guidance created specifically for you by certified career counsellors.</small>
        </div>
        <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-chat-dots me-1"></i>Discuss with Counsellor
        </a>
      </div>

      <div class="row g-3">
        <?php foreach ($pathways as $pw): ?>
          <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-primary rounded-pill px-3 py-1">Recommended Pathway</span>
                <small class="text-muted"><i class="bi bi-person-badge text-primary me-1"></i><?=e($pw['counselor_name'])?></small>
              </div>
              <h3 class="h6 fw-bold text-dark mb-1"><?=e($pw['pathway_title'])?></h3>
              <?php if (!empty($pw['career_title'])): ?>
                <div class="small mb-2">
                  Target Career: <a href="<?=url('careers/view.php?slug='.$pw['career_slug'])?>" class="fw-semibold text-primary"><?=e($pw['career_title'])?></a>
                </div>
              <?php endif; ?>

              <p class="small text-secondary mb-3"><?=nl2br(e($pw['guidance_notes']))?></p>

              <?php if (!empty($pw['recommended_steps'])): ?>
                <div class="p-3 bg-white border rounded-3 mt-auto small">
                  <div class="fw-bold text-dark mb-1"><i class="bi bi-signpost-split text-primary me-1"></i>Action Steps:</div>
                  <div class="text-muted"><?=nl2br(e($pw['recommended_steps']))?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Bento Grid: Interest, Quiz, Recommendations & Badges -->
  <div class="progress-bento">
    <!-- RIASEC Interest Radar -->
    <section class="progress-panel progress-interest">
      <?php if ($interests): ?>
        <div class="progress-panel-heading">
          <h2>RIASEC Interest Profile</h2>
          <a href="<?=url('assessment/interest.php')?>" class="small text-decoration-none">Retake</a>
        </div>
        <div class="progress-chart"><canvas id="interestChart"></canvas></div>
      <?php else: ?>
        <div class="progress-empty-icon"><i class="bi bi-lightbulb"></i></div>
        <h2>Interest Profile</h2>
        <p>Complete your interest assessment to unlock a personalized chart showing your career alignment across six RIASEC dimensions.</p>
        <a class="btn btn-primary btn-sm" href="<?=url('assessment/interest.php')?>">Take Assessment</a>
      <?php endif; ?>
    </section>

    <!-- Quiz Performance -->
    <section class="progress-panel progress-quiz">
      <div class="progress-panel-heading">
        <h2>Career Knowledge Scores</h2>
        <i class="bi bi-patch-question text-muted"></i>
      </div>
      <?php if ($quiz): ?>
        <div class="progress-chart"><canvas id="quizChart"></canvas></div>
      <?php else: ?>
        <div class="progress-dashed-empty">Knowledge quiz results will appear here as you explore careers and complete quizzes.</div>
      <?php endif; ?>
    </section>

    <!-- Recommended Careers -->
    <section class="progress-panel progress-recommendations" id="recommendations">
      <div class="progress-panel-heading">
        <h2>Recommended Careers</h2>
        <a href="<?=url('careers/browse.php')?>">View catalog <i class="bi bi-arrow-right"></i></a>
      </div>
      <?php if ($recs): ?>
        <div class="recommendation-grid">
          <?php foreach ($recs as $r): ?>
            <div class="recommendation-tile position-relative">
              <span><i class="bi bi-briefcase"></i></span>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <a href="<?=url('careers/view.php?id='.$r['career_id'])?>" class="text-decoration-none text-dark">
                    <strong><?=e($r['title'])?></strong>
                  </a>
                  <span class="badge bg-primary-subtle text-primary border rounded-pill">
                    <?=round($r['match_score'])?>% match
                  </span>
                </div>
                <small class="text-muted d-block mb-1">Based on: <?=e($r['based_on'])?></small>
                <p class="small text-muted mb-2"><?=e($r['explanation'] ?: $r['summary'])?></p>
                <div class="d-flex gap-2">
                  <a href="<?=url('careers/view.php?id='.$r['career_id'])?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;">
                    View Career
                  </a>
                  <a href="<?=url('dashboard/counselling.php')?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;">
                    Discuss with Counsellor
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="progress-dashed-empty">
          <p class="mb-2">Complete the RIASEC career assessment to generate recommendations tailored to your interests.</p>
          <a href="<?=url('assessment/interest.php')?>" class="btn btn-sm btn-primary">Take Career Assessment</a>
        </div>
      <?php endif; ?>
    </section>

    <!-- Badges -->
    <section class="progress-panel progress-badges">
      <div class="progress-panel-heading">
        <h2>Achievement Badges</h2>
        <i class="bi bi-award text-muted"></i>
      </div>
      <div class="badge-gallery">
        <?php if ($badges): foreach ($badges as $b): ?>
          <div title="<?=e($b['description'])?>">
            <span><i class="bi bi-<?=e($b['icon'] ?: 'award')?>"></i></span>
            <small><?=e($b['name'])?></small>
          </div>
        <?php endforeach; else: for ($i=0; $i<6; $i++): ?>
          <div class="locked">
            <span><i class="bi bi-<?=$i<3 ? ['award','stars','trophy'][$i] : 'lock'?>"></i></span>
          </div>
        <?php endfor; endif; ?>
      </div>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if ($interests): ?>
    new Chart(document.getElementById('interestChart'), {
      type: 'radar',
      data: {
        labels: <?=json_encode(array_map(fn($x)=>ucfirst($x['interest_type']), $interests))?>,
        datasets: [{
          label: 'RIASEC Score (%)',
          data: <?=json_encode(array_column($interests, 'score'), JSON_NUMERIC_CHECK)?>,
          borderColor: '#0d9488',
          backgroundColor: 'rgba(13,148,136,.15)',
          pointBackgroundColor: '#0d9488',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: '#0d9488'
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          r: {
            min: 0,
            max: 100,
            ticks: { stepSize: 20 }
          }
        }
      }
    });
  <?php endif; ?>

  <?php if ($quiz): ?>
    new Chart(document.getElementById('quizChart'), {
      type: 'bar',
      data: {
        labels: <?=json_encode(array_column($quiz, 'title'))?>,
        datasets: [{
          label: 'Average Pass Score (%)',
          data: <?=json_encode(array_column($quiz, 'score'), JSON_NUMERIC_CHECK)?>,
          backgroundColor: '#131b2e',
          borderRadius: 4
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, max: 100 }
        }
      }
    });
  <?php endif; ?>
});
</script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
