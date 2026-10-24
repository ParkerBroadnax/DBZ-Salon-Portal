<option value="">Select Service</option>
<?php foreach ($services as $service): ?>
<option value="<?= e($service['id']) ?>"<?= (string) ($old['service_id'] ?? '') === (string) $service['id'] ? ' selected' : '' ?>><?= e($service['name']) ?></option>
<?php endforeach; ?>
