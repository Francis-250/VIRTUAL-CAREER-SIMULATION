<?php
require_once dirname(__DIR__).'/includes/functions.php';
require_admin();

$stats = [
    'students' => $con->query("SELECT COUNT(*) n FROM users WHERE role='student' AND deleted_at IS NULL")->fetch_assoc()['n'],
    'counselors' => $con->query("SELECT COUNT(*) n FROM users WHERE role='counselor' AND deleted_at IS NULL")->fetch_assoc()['n'],
    'counselling_requests' => $con->query("SELECT COUNT(*) n FROM counselling_requests")->fetch_assoc()['n'],
    'appointments' => $con->query("SELECT COUNT(*) n FROM counselling_appointments")->fetch_assoc()['n'],
    'assessments' => $con->query("SELECT COUNT(DISTINCT user_id) n FROM user_interest_scores")->fetch_assoc()['n'],
    'careers' => $con->query("SELECT COUNT(*) n FROM careers WHERE is_active=1")->fetch_assoc()['n']
];

$days = $con->query("SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(created_at)")->fetch_all(MYSQLI_ASSOC);
$riasec = $con->query("SELECT interest_type, AVG(score) n FROM user_interest_scores GROUP BY interest_type")->fetch_all(MYSQLI_ASSOC);
$explored = $con->query("SELECT c.title, COUNT(r.id) n FROM careers c LEFT JOIN career_recommendations r ON r.career_id = c.id GROUP BY c.id ORDER BY n DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin Reports Dashboard · Career Guidance System';
require __DIR__.'/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2">Central Management</span>
    <h1 class="h3 fw-bold mb-1">Career Guidance System &middot; Admin Portal</h1>
    <p class="text-muted mb-0">System performance, guidance engagement, and user access oversight.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?=url('admin/monitor.php')?>" class="btn btn-outline-success shadow-sm">
      <i class="bi bi-activity me-1"></i> System Monitor
    </a>
    <a href="<?=url('admin/users.php')?>" class="btn btn-primary shadow-sm">
      <i class="bi bi-people-fill me-1"></i> User Accounts & Access
    </a>
  </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-people fs-4 text-primary mb-1"></i>
      <div class="text-muted small">Total Students</div>
      <div class="h3 fw-bold mb-0"><?=$stats['students']?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-person-badge fs-4 text-info mb-1"></i>
      <div class="text-muted small">Counsellors</div>
      <div class="h3 fw-bold mb-0"><?=$stats['counselors']?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-clipboard2-check fs-4 text-success mb-1"></i>
      <div class="text-muted small">Assessments</div>
      <div class="h3 fw-bold mb-0"><?=$stats['assessments']?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-inbox fs-4 text-warning mb-1"></i>
      <div class="text-muted small">Guidance Requests</div>
      <div class="h3 fw-bold mb-0"><?=$stats['counselling_requests']?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-calendar-check fs-4 text-danger mb-1"></i>
      <div class="text-muted small">Appointments</div>
      <div class="h3 fw-bold mb-0"><?=$stats['appointments']?></div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-2">
    <div class="card metric p-3 border-0 shadow-sm rounded-3 bg-white h-100">
      <i class="bi bi-briefcase fs-4 text-secondary mb-1"></i>
      <div class="text-muted small">Active Careers</div>
      <div class="h3 fw-bold mb-0"><?=$stats['careers']?></div>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row g-4">
  <div class="col-xl-8">
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">Student Registrations &middot; 30 Days</h2>
        <a href="<?=url('admin/monitor.php')?>" class="small text-primary text-decoration-none">Detailed Telemetry &rarr;</a>
      </div>
      <div class="chart-box" style="height: 280px;">
        <canvas id="trend"></canvas>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white h-100">
      <h2 class="h5 fw-bold mb-3">RIASEC Aggregate Fit</h2>
      <div class="chart-box" style="height: 280px;">
        <canvas id="riaChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">Most Recommended Careers</h2>
        <a href="<?=url('admin/careers.php')?>" class="small text-primary text-decoration-none">View Career Catalog &rarr;</a>
      </div>
      <div class="chart-box" style="height: 220px;">
        <canvas id="careersChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
const days = [...Array(30)].map((_, i) => {
  let d = new Date();
  d.setDate(d.getDate() - 29 + i);
  return d.toISOString().slice(0, 10);
});
const map = (rows) => Object.fromEntries(rows.map(x => [x.d, +x.n]));

document.addEventListener('DOMContentLoaded', () => {
  new Chart(trend, {
    type: 'line',
    data: {
      labels: days,
      datasets: [{
        label: 'New Registrations',
        data: days.map(d => map(<?=json_encode($days)?>)[d] || 0),
        borderColor: '#0f8b8d',
        backgroundColor: 'rgba(15,139,141,0.08)',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  new Chart(riaChart, {
    type: 'radar',
    data: {
      labels: <?=json_encode(array_map('ucfirst', array_column($riasec, 'interest_type')))?>,
      datasets: [{
        label: 'Average RIASEC Score',
        data: <?=json_encode(array_column($riasec, 'n'), JSON_NUMERIC_CHECK)?>,
        borderColor: '#0f8b8d',
        backgroundColor: 'rgba(15,139,141,0.15)',
        pointBackgroundColor: '#0f8b8d'
      }]
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        r: { min: 0, max: 100, ticks: { stepSize: 25 } }
      }
    }
  });

  new Chart(careersChart, {
    type: 'bar',
    data: {
      labels: <?=json_encode(array_column($explored, 'title'))?>,
      datasets: [{
        label: 'Career Recommendations Generated',
        data: <?=json_encode(array_column($explored, 'n'), JSON_NUMERIC_CHECK)?>,
        backgroundColor: '#173b67',
        borderRadius: 4
      }]
    },
    options: {
      maintainAspectRatio: false,
      indexAxis: 'y',
      scales: {
        x: { beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });
});
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
