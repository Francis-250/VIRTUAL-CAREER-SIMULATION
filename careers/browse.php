<?php
require_once dirname(__DIR__).'/includes/functions.php';$q=trim($_GET['q']??'');$category=(int)($_GET['category']??0);$where=['c.is_active=1'];$params=[];$types='';
if($q!==''){$where[]='(MATCH(c.title,c.summary,c.description) AGAINST(? IN NATURAL LANGUAGE MODE) OR c.title LIKE ?)';$params[]=$q;$params[]='%'.$q.'%';$types.='ss';}
if($category){$where[]='c.category_id=?';$params[]=$category;$types.='i';}
$sql="SELECT c.*,cc.name category,GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') skills FROM careers c JOIN career_categories cc ON cc.id=c.category_id LEFT JOIN career_skills cs ON cs.career_id=c.id LEFT JOIN skills s ON s.id=cs.skill_id WHERE ".implode(' AND ',$where).' GROUP BY c.id ORDER BY c.title';
$stmt=$con->prepare($sql);if($params)$stmt->bind_param($types,...$params);$stmt->execute();$careers=$stmt->get_result();$categories=$con->query('SELECT * FROM career_categories ORDER BY name');
$careerImages=[
 'civil-engineer'=>'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=900&q=80',
 'data-analyst'=>'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80',
 'marketing-specialist'=>'https://images.unsplash.com/photo-1533750349088-cd871a92f312?auto=format&fit=crop&w=900&q=80',
 'registered-nurse'=>'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=900&q=80',
 'software-developer'=>'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=900&q=80',
 'teacher'=>'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=900&q=80'
];$pageTitle='Explore careers';$bodyClass='student-catalog';require dirname(__DIR__).'/includes/header.php';?>
<div class="career-catalog-shell">
 <header class="career-catalog-header"><h1>Explore Careers</h1><p>Find your path, then experience the work.</p></header>
 <form class="career-filter-card">
  <div class="career-search-field"><i class="bi bi-search"></i><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Search careers"></div>
  <select class="form-select" name="category"><option value="0">All categories</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$category===$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach?></select>
  <button class="btn">Filter</button>
 </form>
 <?php if(!$careers->num_rows):?><div class="alert alert-info">No careers match your filters.</div><?php endif?>
 <div class="career-bento-grid">
 <?php foreach($careers as $c):$growth=$c['growth_outlook']?:'stable';?>
  <article class="catalog-career-card">
   <div class="catalog-career-media">
    <img src="<?=e($c['image']?url($c['image']):($careerImages[$c['slug']]??'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=900&q=80'))?>" alt="<?=e($c['title'])?> workspace">
    <div><h2><?=e($c['title'])?></h2></div>
   </div>
   <div class="catalog-career-body">
    <span class="catalog-category"><?=e($c['category'])?></span>
    <p class="catalog-summary"><?=e($c['summary'])?></p>
    <div class="catalog-meta mt-auto">
     <div><span>Market status</span><strong class="catalog-growth growth-<?=e($growth)?>"><?=e(ucwords(str_replace('_',' ',$growth)))?></strong></div>
     <div><span>Salary range</span><strong><?=$c['average_salary']?'R'.number_format($c['average_salary']):'Not listed'?></strong></div>
    </div>
    <div class="catalog-action"><a href="<?=url('careers/view.php?id='.$c['id'])?>">View career</a></div>
   </div>
  </article>
 <?php endforeach?>
 </div>
</div>
<?php require dirname(__DIR__).'/includes/footer.php';?>
