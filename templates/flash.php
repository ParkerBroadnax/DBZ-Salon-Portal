<?php if (!empty($flash)): ?>
<div class="alert alert-<?= e($flash['type']) ?> mb-4" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
	<?= e($flash['text']) ?>
</div>
<?php endif; ?>
