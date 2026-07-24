<?php
require_once dirname(__DIR__).'/includes/functions.php';require_content_manager();
$rows=$con->query('SELECT c.*,cc.name category FROM careers c JOIN career_categories cc ON cc.id=c.category_id ORDER BY c.title');
$stats=$con->query("SELECT COUNT(*) total,SUM(growth_outlook='high_growth') high_growth,SUM(is_active=0) inactive,(SELECT COUNT(*) FROM career_skills) links FROM careers")->fetch_assoc();
$pageTitle='Careers';require __DIR__.'/includes/header.php';?>
<section class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
 <div><div class="admin-page-kicker">Platform Management</div><h1 class="h3">Careers</h1><p class="text-muted mb-0">Manage the career catalog.</p></div>
 <a class="btn btn-primary create-action content-editor-link" href="<?=url('admin/content_edit.php?entity=career')?>"><i class="bi bi-plus-circle me-2"></i>Add career</a>
</section>
<section class="row g-4 mb-4">
 <?php foreach([
  ['Total Careers',$stats['total'],'briefcase','All catalog entries','#0d9488'],
  ['High Growth',$stats['high_growth'],'rocket-takeoff','Fast-growing pathways','#3b82f6'],
  ['Skills Linked',$stats['links'],'link-45deg','Across all careers','#0f172a'],
  ['Inactive Data',$stats['inactive'],'exclamation-triangle','Hidden from students','#0d9488']
 ] as $s):?>
 <div class="col-sm-6 col-xl-3"><div class="card admin-stat-card h-100" style="--stat-color:<?=$s[4]?>"><div class="admin-stat-label"><?=$s[0]?></div><div class="admin-stat-value"><?=number_format((int)$s[1])?></div><div class="admin-stat-note"><i class="bi bi-<?=$s[2]?> me-1"></i><?=$s[3]?></div></div></div>
 <?php endforeach?>
</section>
<div class="card overflow-hidden">
 <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3 border-bottom bg-white">
  <div class="d-flex gap-4"><strong class="border-bottom border-2 pb-1" style="border-color:#0d9488!important">All Careers</strong><span class="text-muted">High Growth</span><span class="text-muted">Inactive</span></div>
  <div class="d-flex gap-2"><button class="btn btn-sm btn-light border" title="Filter"><i class="bi bi-funnel"></i></button><button class="btn btn-sm btn-light border" title="Export"><i class="bi bi-download"></i></button></div>
 </div>
 <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Title</th><th>Category</th><th>Growth</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
 <?php foreach($rows as $r):?><tr><td><div class="d-flex align-items-center gap-3"><span class="d-inline-grid place-items-center rounded p-2" style="background:#dce9ff;color:#0f172a"><i class="bi bi-briefcase"></i></span><strong class="text-dark"><?=e($r['title'])?></strong></div></td><td><?=e($r['category'])?></td><td><span class="status-pill growth-<?=e($r['growth_outlook']?:'stable')?>"><?=e(str_replace('_',' ',$r['growth_outlook']?:'stable'))?></span></td><td><span class="status-pill <?=$r['is_active']?'status-active':'status-inactive'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td><td class="text-end"><a class="text-decoration-none fw-semibold content-editor-link" style="color:#0d9488" href="<?=url('admin/content_edit.php?entity=career&id='.$r['id'])?>">Edit</a></td></tr><?php endforeach?>
 </tbody></table></div>
 <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light border-top"><small class="text-muted">Showing <?=$rows->num_rows?> careers</small><div><button class="btn btn-sm btn-light border" disabled>Previous</button> <button class="btn btn-sm btn-light border" disabled>Next</button></div></div>
</div>
<?php require __DIR__.'/includes/footer.php';?>
