<?php require_once dirname(__DIR__,2).'/includes/functions.php';require_role(['admin','content_manager']);$pageTitle=$pageTitle??'Career Guidance Admin';?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=e($pageTitle)?> · Career Guidance System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="<?=url('assets/css/custom.css')?>" rel="stylesheet">
  <script>window.APP={base:'<?=url()?>',csrf:'<?=csrf_token()?>'};if(localStorage.getItem('careerSimSidebar')==='collapsed')document.documentElement.classList.add('sidebar-is-collapsed');</script>
</head>
<body class="management-body">
<div class="admin-shell">
  <?php require __DIR__.'/sidebar.php';?>
  <div class="admin-main">
    <header class="admin-top d-flex align-items-center justify-content-between gap-3 px-3 px-lg-4">
      <button class="btn admin-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
        <i class="bi bi-list fs-4"></i>
      </button>
      <button class="admin-collapse-button d-none d-lg-grid" id="sidebarCollapse" type="button" aria-label="Collapse sidebar" title="Collapse sidebar">
        <i class="bi bi-layout-sidebar-inset"></i>
      </button>
      <div class="admin-search d-none d-md-block">
        <i class="bi bi-search"></i>
        <input class="form-control" id="adminGlobalSearch" placeholder="Search guidance records, careers, users..." aria-label="Search">
      </div>
      <div class="d-flex align-items-center gap-3 ms-auto">
        <a href="<?=url('admin/monitor.php')?>" class="btn btn-sm btn-outline-success d-none d-md-flex align-items-center gap-1" title="Real-time System Monitor">
          <i class="bi bi-activity"></i> Monitor
        </a>
        <div class="admin-divider d-none d-md-block"></div>
        <div class="text-end d-none d-sm-block">
          <div class="admin-profile-name"><?=e(user()['name'])?></div>
          <div class="admin-profile-role"><?=user()['role']==='admin'?'System Administrator':'Content Manager'?></div>
        </div>
        <div class="admin-avatar"><?=e(strtoupper(substr(user()['name'],0,1)))?></div>
        <a class="btn admin-signout" href="<?=url('auth/logout.php')?>">Sign out</a>
      </div>
    </header>
    <main class="admin-canvas">
