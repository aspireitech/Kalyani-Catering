<?php $flash = flash_get('success'); $flashError = flash_get('error'); ?>
  </main>
</div>
<script>
  window.KC = {
    baseUrl: <?= json_encode(rtrim(SITE_URL, '/') . '/') ?>,
    csrfToken: <?= json_encode(csrf_token()) ?>,
    flashSuccess: <?= json_encode($flash) ?>,
    flashError: <?= json_encode($flashError) ?>
  };
</script>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
</body>
</html>
