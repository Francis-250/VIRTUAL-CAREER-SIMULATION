</main>
<footer class="public-footer text-center py-4 border-top mt-5 bg-white text-muted">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div class="fw-semibold text-dark d-flex align-items-center gap-2">
        <i class="bi bi-compass-fill text-primary"></i> Career Guidance System
      </div>
      <div class="small">Career Guidance System &middot; Empowering students through assessment, career pathways, and expert counselling.</div>
      <div class="small d-flex gap-3">
        <a href="<?=url('careers/browse.php')?>" class="text-decoration-none text-muted">Explore Careers</a>
        <a href="<?=url('auth/login.php')?>" class="text-decoration-none text-muted">Sign In</a>
      </div>
    </div>
  </div>
</footer>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastArea"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?=url('assets/js/app.js')?>"></script>
</body>
</html>
