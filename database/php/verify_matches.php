<?php
require_once 'MatchVerifier.php';

$verifier      = new MatchVerifier();
$matches       = $verifier->getPendingMatches();
$unmatchedFound = $verifier->getUnmatchedFoundItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NU Finds - Verify Matches</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
    body { background: #f5f5f5; overflow-x: hidden; }
    .topbar { width: 100%; height: 50px; background: #25358c; border-bottom: 4px solid #f2c100; display: flex; align-items: center; padding: 0 25px; position: sticky; top: 0; z-index: 1000; }
    .topbar .logo-header { height: 60px; width: auto; margin-right: 20px; }
    .container { width: 100%; min-height: 100vh; padding: 40px 80px; max-width: 1400px; margin: 0 auto; }
    .page-header { background: #25358c; color: white; padding: 30px; border-radius: 14px; margin-bottom: 40px; text-align: center; }
    .page-header h1 { font-size: 42px; color: #f2c100; margin-bottom: 10px; }
    .page-header p { color: white; font-size: 16px; }
    .section-title { font-size: 24px; font-weight: bold; color: #25358c; margin: 40px 0 20px; padding-bottom: 10px; border-bottom: 3px solid #f2c100; }
    .match-card { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-top: 4px solid #25358c; transition: opacity 0.4s ease; }
    .match-card.no-match { border-top: 4px solid #ccc; background: #fafafa; }
    .match-card.has-match { border-top: 4px solid #f2c100; background: #fffbf0; }
    .match-card.found-only { border-top: 4px solid #4caf50; background: #f0fff4; }
    .match-card.removing { opacity: 0; }
    .match-card h2 { color: #25358c; margin-bottom: 20px; font-size: 24px; }
    .match-card.found-only h2 { color: #2e7d32; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .label { font-weight: bold; color: #25358c; min-width: 150px; }
    .value { color: #555; text-align: right; flex: 1; }
    .match-icon { color: #f2c100; font-size: 1.3rem; font-weight: bold; margin-bottom: 15px; }
    .no-match-text { color: #999; font-style: italic; padding: 15px; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .verify-btn { padding: 10px 25px; background: #25358c; color: #f2c100; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; transition: all 0.3s ease; }
    .verify-btn:hover { background: #1a2563; transform: scale(1.05); }
    .actions { margin-top: 20px; display: flex; gap: 10px; }
    hr { border: none; border-top: 1px solid #e0e0e0; margin: 1.5rem 0; }
    .no-matches-message { background: white; padding: 40px; border-radius: 12px; text-align: center; color: #666; font-size: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; margin-bottom: 15px; }
    .badge-green { background: #e8f5e9; color: #2e7d32; }
    .badge-yellow { background: #fff8e1; color: #f57f17; }
    .popup { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); z-index: 9999; }
    .popup.hidden { display: none; }
    .popup-content { background: white; padding: 2rem; border-radius: 14px; width: min(95%, 420px); text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); border-top: 5px solid transparent; }
    .popup-content.success { border-top-color: #f2c100; }
    .popup-content.error { border-top-color: #b00020; }
    .popup-content h2 { margin-bottom: 1rem; color: #25358c; font-size: 24px; }
    .popup-content.error h2 { color: #b00020; }
    .popup-content p { margin-bottom: 1.5rem; color: #555; font-size: 16px; }
    .popup-content button { background: #25358c; color: #f2c100; border: none; border-radius: 8px; padding: 10px 25px; cursor: pointer; font-weight: bold; font-size: 16px; }
    .popup-content button:hover { background: #1a2563; }
</style>
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/nufindslogo white.png" alt="NU Finds White Logo" class="logo-header">
</div>

<div class="container">
    <div class="page-header">
        <h1>Verify Found & Lost Matches</h1>
        <p>Review and confirm potential matches between lost and found items</p>
    </div>

    <!-- SECTION 1: LOST REPORTS WITH POTENTIAL MATCHES -->
    <div class="section-title">Lost Reports</div>
    <div id="cards-container">
    <?php if (count($matches) === 0): ?>
        <div class="no-matches-message">
            <p>No pending lost reports at this time.</p>
        </div>
    <?php else: ?>
        <?php foreach ($matches as $match): ?>
            <div class="match-card <?= $match['FoundID'] ? 'has-match' : 'no-match' ?>"
                 data-lost-id="<?= $match['LostID'] ?>">
                <h2>Lost Report: <?= htmlspecialchars($match['TicketNumber'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="lost-info">
                    <div class="info-row"><span class="label">Student:</span>    <span class="value"><?= htmlspecialchars($match['StudentNumber'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="info-row"><span class="label">Category:</span>   <span class="value"><?= htmlspecialchars($match['Category'],      ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="info-row"><span class="label">Location:</span>   <span class="value"><?= htmlspecialchars($match['Location'],      ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="info-row"><span class="label">Date Lost:</span>  <span class="value"><?= date('F j, Y', strtotime($match['DateLost'])) ?></span></div>
                    <div class="info-row"><span class="label">Description:</span><span class="value"><?= htmlspecialchars(substr($match['Description'], 0, 100), ENT_QUOTES, 'UTF-8') ?>...</span></div>
                </div>
                <hr>
                <?php if ($match['FoundID']): ?>
                    <div class="match-icon">✓ Potential Match Found</div>
                    <div class="found-info">
                        <div class="info-row"><span class="label">Found By:</span>      <span class="value"><?= htmlspecialchars($match['FoundBy'],       ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="info-row"><span class="label">Location Found:</span><span class="value"><?= htmlspecialchars($match['FoundLocation'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="info-row"><span class="label">Date Found:</span>    <span class="value"><?= date('F j, Y', strtotime($match['DateFound'])) ?></span></div>
                    </div>
                    <div class="actions">
                        <form class="verify-form" action="verify_match.php" method="post" style="display:inline;">
                            <input type="hidden" name="lost_id"  value="<?= $match['LostID']  ?>">
                            <input type="hidden" name="found_id" value="<?= $match['FoundID'] ?>">
                            <button type="submit" class="verify-btn">Verify Match</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="no-match-text">No matching found items at this time.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <!-- SECTION 2: UNMATCHED FOUND ITEMS -->
    <div class="section-title">Unmatched Found Items</div>
    <div id="found-container">
    <?php if (count($unmatchedFound) === 0): ?>
        <div class="no-matches-message">
            <p>No unmatched found items at this time.</p>
        </div>
    <?php else: ?>
        <?php foreach ($unmatchedFound as $item): ?>
            <div class="match-card found-only" data-found-id="<?= $item['FoundID'] ?>">
                <span class="badge badge-green">Found Item — No Match Yet</span>
                <h2>Found Item #<?= $item['FoundID'] ?></h2>
                <div class="info-row"><span class="label">Found By:</span>   <span class="value"><?= htmlspecialchars($item['StudentNumber'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="info-row"><span class="label">Category:</span>   <span class="value"><?= htmlspecialchars($item['Category'],      ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="info-row"><span class="label">Location:</span>   <span class="value"><?= htmlspecialchars($item['Location'],      ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="info-row"><span class="label">Date Found:</span> <span class="value"><?= date('F j, Y', strtotime($item['DateFound'])) ?></span></div>
                <div class="info-row"><span class="label">Description:</span><span class="value"><?= htmlspecialchars(substr($item['Description'], 0, 100), ENT_QUOTES, 'UTF-8') ?>...</span></div>
                <div class="info-row"><span class="label">Status:</span>     <span class="value"><?= htmlspecialchars($item['Status'], ENT_QUOTES, 'UTF-8') ?></span></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <div id="message-popup" class="popup hidden">
        <div class="popup-content" id="message-content">
            <h2 id="popup-title">Message</h2>
            <p id="popup-message"></p>
            <button id="popup-ok">OK</button>
        </div>
    </div>
</div>

<script>
    let verifiedCard = null;

    function showPopup(type, message) {
        if (!message) return;
        const popup   = document.getElementById('message-popup');
        const content = document.getElementById('message-content');
        const title   = document.getElementById('popup-title');
        const msg     = document.getElementById('popup-message');
        content.classList.remove('success', 'error');
        content.classList.add(type === 'success' ? 'success' : 'error');
        title.textContent = type === 'success' ? 'Match Verified' : 'Error';
        msg.textContent   = message;
        popup.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const error  = params.get('error');
        if (error) showPopup('error', decodeURIComponent(error));

        document.getElementById('popup-ok').addEventListener('click', function () {
            document.getElementById('message-popup').classList.add('hidden');
            history.replaceState(null, '', window.location.pathname);

            if (verifiedCard) {
                verifiedCard.classList.add('removing');
                setTimeout(() => {
                    verifiedCard.remove();
                    verifiedCard = null;
                    const remaining = document.querySelectorAll('#cards-container .match-card');
                    if (remaining.length === 0) {
                        window.location.reload();
                    }
                }, 400);
            }
        });

        document.querySelectorAll('.verify-form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (result.status === 'success') {
                        verifiedCard = form.closest('.match-card');
                        showPopup('success', result.message || 'Match has been verified successfully.');
                    } else {
                        showPopup('error', result.message || 'Unable to verify the match.');
                    }
                } catch (err) {
                    showPopup('error', 'Unable to verify the match. Please try again.');
                }
            });
        });
    });
</script>
</body>
</html>