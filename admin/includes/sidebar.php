<?php
$active = basename($_SERVER['PHP_SELF']);
$links = user()['role'] === 'admin'
    ? [
        'index.php' => ['speedometer2', 'Reports Dashboard'],
        'monitor.php' => ['activity', 'System Monitor'],
        'users.php' => ['people-fill', 'User Accounts & Access'],
        'recommendations.php' => ['compass', 'Recommendations Report'],
        'careers.php' => ['briefcase', 'Career Catalog'],
        'feedback.php' => ['chat-left-text', 'Feedback Report'],
        'logs.php' => ['journal-text', 'Audit Logs']
      ]
    : [
        'careers.php' => ['briefcase', 'Careers'],
        'quizzes.php' => ['question-circle', 'Quizzes'],
        'simulations.php' => ['controller', 'Simulations'],
        'categories.php' => ['tags', 'Categories'],
        'skills.php' => ['tools', 'Skills'],
        'interest_questions.php' => ['clipboard-heart', 'Interest Questions'],
        'badges.php' => ['award', 'Badges'],
        'jobs.php' => ['building', 'Job Opportunities'],
        'applications.php' => ['file-earmark-person', 'Applications']
      ];
?>
<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar">
    <div class="offcanvas-header d-lg-none">
        <span class="text-white fw-bold">Career Guidance System</span>
        <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar"></button>
    </div>
    <div class="admin-brand">
        <span class="admin-brand-mark"><i class="bi bi-compass-fill text-primary"></i></span>
        <span class="admin-brand-copy">
            <strong>Career Guidance</strong>
            <small><?= user()['role'] === 'admin' ? 'Admin Portal' : 'Manager Portal' ?></small>
        </span>
    </div>
    <nav class="admin-nav">
        <?php foreach ($links as $file => $x): ?>
            <a class="<?= $active === $file ? 'active' : '' ?>" href="<?= url('admin/' . $file) ?>" title="<?= $x[1] ?>">
                <i class="bi bi-<?= $x[0] ?>"></i>
                <span><?= $x[1] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-footer">
        <a href="<?= url() ?>" title="View website"><i class="bi bi-house"></i><span>View website</span></a>
        <a href="<?= url('auth/logout.php') ?>" title="Sign out"><i class="bi bi-box-arrow-left"></i><span>Sign out</span></a>
    </div>
</aside>
