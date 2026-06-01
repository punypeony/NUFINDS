<?php defined('NUFINDS_VIEW') || exit; ?>
<?php foreach ($categories as $cat): ?>
  <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= ($selected ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
