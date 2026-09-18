<?php require_once __DIR__.'/functions.php'; $pageTitle = $pageTitle ?? 'Career Guidance System'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=e($pageTitle)?> · Career Guidance System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?=url('assets/css/custom.css')?>" rel="stylesheet">
  <script>window.APP={base:'<?=url()?>',csrf:'<?=csrf_token()?>'};</script>
</head>
<body class="<?=e($bodyClass??'')?>">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top public-navbar shadow-sm">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-primary" href="<?=url()?>">
      <i class="bi bi-compass-fill fs-4"></i>
      <span class="text-dark">Career Guidance System</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php $currentPath = $_SERVER['PHP_SELF'] ?? ''; ?>
        <?php if (user()): ?>
          <?php if (user()['role'] === 'student'): ?>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/dashboard/progress') ? 'active fw-semibold' : ''?>" href="<?=url('dashboard/progress.php')?>"><i class="bi bi-bar-chart me-1"></i>My Guidance</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/assessment/') ? 'active fw-semibold' : ''?>" href="<?=url('assessment/interest.php')?>"><i class="bi bi-clipboard2-check me-1"></i>Assessment</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/dashboard/counselling') ? 'active fw-semibold' : ''?>" href="<?=url('dashboard/counselling.php')?>"><i class="bi bi-chat-heart me-1"></i>Career Counselling</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/careers/') ? 'active fw-semibold' : ''?>" href="<?=url('careers/browse.php')?>"><i class="bi bi-briefcase me-1"></i>Explore Careers</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/dashboard/profile') ? 'active fw-semibold' : ''?>" href="<?=url('dashboard/profile.php')?>"><i class="bi bi-person me-1"></i>Profile</a></li>
          <?php elseif (user()['role'] === 'counselor'): ?>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/counselor/index') ? 'active fw-semibold' : ''?>" href="<?=url('counselor/index.php')?>"><i class="bi bi-people me-1"></i>Students</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/counselor/requests') ? 'active fw-semibold' : ''?>" href="<?=url('counselor/requests.php')?>"><i class="bi bi-inbox me-1"></i>Counselling Requests</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/counselor/appointments') ? 'active fw-semibold' : ''?>" href="<?=url('counselor/appointments.php')?>"><i class="bi bi-calendar-event me-1"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/counselor/applications') ? 'active fw-semibold' : ''?>" href="<?=url('counselor/applications.php')?>"><i class="bi bi-file-earmark-person me-1"></i>Applications</a></li>
          <?php elseif (user()['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/admin/index') ? 'active fw-semibold' : ''?>" href="<?=url('admin/index.php')?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/admin/users') ? 'active fw-semibold' : ''?>" href="<?=url('admin/users.php')?>"><i class="bi bi-people-fill me-1"></i>User Accounts</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/admin/monitor') ? 'active fw-semibold' : ''?>" href="<?=url('admin/monitor.php')?>"><i class="bi bi-activity me-1"></i>System Monitor</a></li>
            <li class="nav-item"><a class="nav-link <?=$currentPath && str_contains($currentPath,'/admin/careers') ? 'active fw-semibold' : ''?>" href="<?=url('admin/careers.php')?>"><i class="bi bi-briefcase me-1"></i>Career Catalog</a></li>
          <?php elseif (user()['role'] === 'content_manager'): ?>
            <li class="nav-item"><a class="nav-link" href="<?=url('admin/careers.php')?>"><i class="bi bi-folder me-1"></i>Content Manager</a></li>
          <?php endif; ?>

          <li class="nav-item dropdown ms-lg-2">
            <button class="btn btn-light position-relative rounded-pill px-3" data-bs-toggle="dropdown" aria-label="Notifications">
              <i class="bi bi-bell"></i>
              <span id="unreadCount" class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"></span>
            </button>
            <div id="notificationMenu" class="dropdown-menu dropdown-menu-end notification-menu p-2 shadow"></div>
          </li>

          <li class="nav-item ms-lg-2 d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-2 py-1">
              <i class="bi bi-person-badge text-primary me-1"></i><?=e(ucwords(str_replace('_',' ',user()['role'])))?>
            </span>
            <a class="btn btn-sm btn-outline-danger" href="<?=url('auth/logout.php')?>"><i class="bi bi-box-arrow-right me-1"></i>Sign out</a>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?=url('careers/browse.php')?>">Explore Careers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=url('auth/login.php')?>">Sign in</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-primary" href="<?=url('auth/register.php')?>">Get started</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main>
  <?php foreach($_SESSION['flash'] ?? [] as $f): ?>
    <div class="container mt-3">
      <div class="alert alert-<?=e($f['type'])?> alert-dismissible fade show shadow-sm" role="alert">
        <?=e($f['message'])?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  <?php endforeach; unset($_SESSION['flash']); ?>
