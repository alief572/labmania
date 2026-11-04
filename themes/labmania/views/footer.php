  </main>
</div>
</div> <!-- .app -->

<div id="menu-root"></div>
<div id="modal-root"></div>
<div class="toasts" id="toasts" aria-live="polite"></div>

<!-- Labmania Core Script -->
<script src="<?= base_url('assets/labmania/js/labmania.js'); ?>"></script>

<?php if (isset($template['extra_js'])) echo $template['extra_js']; ?>
</body>
</html>
