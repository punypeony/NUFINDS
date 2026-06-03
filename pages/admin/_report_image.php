<?php
$imageUrl = nufinds_upload_url($report['Image'] ?? null);
?>
<td class="report-image-cell">
  <?php if ($imageUrl): ?>
    <a href="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
      <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Item photo" class="report-thumb">
    </a>
  <?php else: ?>
    <span class="report-no-image">&mdash;</span>
  <?php endif; ?>
</td>
