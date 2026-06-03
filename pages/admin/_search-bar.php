<?php
$searchQuery    = trim($searchQuery ?? '');
$searchAction   = $searchAction ?? '';
$searchClearUrl = $searchClearUrl ?? $searchAction;
?>
<form class="admin-search-bar" method="get" action="<?= htmlspecialchars($searchAction, ENT_QUOTES, 'UTF-8') ?>" role="search">
  <label class="admin-search-label" for="admin-search-q">Search</label>
  <input
    id="admin-search-q"
    type="search"
    name="q"
    value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>"
    placeholder="Ticket, student ID, email, location, category…"
    autocomplete="off"
  >
  <button type="submit" class="admin-search-submit">Search</button>
  <?php if ($searchQuery !== ''): ?>
    <a class="admin-search-clear" href="<?= htmlspecialchars($searchClearUrl, ENT_QUOTES, 'UTF-8') ?>">Clear</a>
  <?php endif; ?>
</form>
