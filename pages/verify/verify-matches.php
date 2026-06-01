<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NU Finds - Verify Matches</title>
  <link rel="stylesheet" href="<?= nufinds_asset('css/home.css') ?>">
  <link rel="stylesheet" href="<?= nufinds_asset('css/admin-reports.css') ?>">
  <link rel="stylesheet" href="<?= nufinds_asset('css/verify-matches.css') ?>">
</head>
<body class="admin-reports-page verify-page">

<div class="container">
  <?php require dirname(__DIR__) . '/admin/_topbar.php'; ?>

  <a href="<?= nufinds_admin_page('home.html') ?>" class="back-icon">
    <img src="<?= nufinds_asset('assets/images/back.png') ?>" alt="Back">
  </a>

  <div class="admin-reports-container">
    <div class="admin-reports-header">
      <h1>Verify Matches</h1>
      <p>Review paired lost and found reports. Confirming moves them to <a class="header-inline-link" href="<?= nufinds_admin_page('history.html') ?>">History</a>.</p>
    </div>

    <div id="cards-container" class="verify-grid">
      <?php if (count($matches) === 0): ?>
        <div class="empty-state">No pending matches to verify right now.</div>
      <?php else: ?>
        <?php foreach ($matches as $match): ?>
          <article class="verify-card" data-lost-id="<?= (int)$match['LostID'] ?>">
            <header class="verify-card-head">
              <span class="verify-ticket"><?= htmlspecialchars($match['TicketNumber'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="verify-badge">Potential match</span>
            </header>

            <div class="verify-split">
              <section class="verify-pane">
                <h3>Lost item</h3>
                <ul class="verify-facts">
                  <li><span>Student</span><strong><?= htmlspecialchars($match['StudentNumber'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                  <li><span>Category</span><strong><?= htmlspecialchars($match['Category'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                  <li><span>Location</span><strong><?= htmlspecialchars($match['Location'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                  <li><span>Date lost</span><strong><?= date('M j, Y', strtotime($match['DateLost'])) ?></strong></li>
                </ul>
                <p class="verify-desc"><?= htmlspecialchars($match['Description'], ENT_QUOTES, 'UTF-8') ?></p>
              </section>

              <section class="verify-pane verify-pane--found">
                <h3>Found item</h3>
                <ul class="verify-facts">
                  <li><span>Found by</span><strong><?= htmlspecialchars($match['FoundBy'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                  <li><span>Location</span><strong><?= htmlspecialchars($match['FoundLocation'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                  <li><span>Date found</span><strong><?= date('M j, Y', strtotime($match['DateFound'])) ?></strong></li>
                </ul>
              </section>
            </div>

            <form class="verify-form" action="<?= nufinds_php_url('verify/verify_match.php') ?>" method="post">
              <input type="hidden" name="lost_id" value="<?= (int)$match['LostID'] ?>">
              <input type="hidden" name="found_id" value="<?= (int)$match['FoundID'] ?>">
              <button type="submit" class="verify-btn">Verify match</button>
            </form>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="message-popup" class="popup hidden">
  <div class="popup-content" id="message-content">
    <h2 id="popup-title">Message</h2>
    <p id="popup-message"></p>
    <button type="button" id="popup-ok">OK</button>
  </div>
</div>

<script src="<?= nufinds_asset('js/home.js') ?>"></script>
<script src="<?= nufinds_asset('js/verify-matches.js') ?>"></script>
</body>
</html>
