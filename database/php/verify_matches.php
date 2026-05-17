<?php
require_once 'db_connect.php';

$sql = "
    SELECT 
        l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost, l.Category, l.Description,
        f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation, f.DateFound, f.Status
    FROM lost l
    LEFT JOIN found f ON 
        l.Category = f.Category 
        AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -1 AND 7
        AND f.Status = 'Unclaimed'
    WHERE l.LostID NOT IN (SELECT OriginalReportID FROM history WHERE ReportType = 'Lost')
    ORDER BY l.DateLost DESC
";

$result = $conn->query($sql);
$matches = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NU Finds - Verify Matches</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 2rem; }
    .container { max-width: 1200px; margin: 0 auto; }
    h1 { margin-bottom: 2rem; color: #073b4c; }
    .match-card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .match-card.no-match { background: #fff8f8; border-left: 4px solid #ccc; }
    .match-card.has-match { border-left: 4px solid #4caf50; }
    .lost-info, .found-info { margin-bottom: 1rem; }
    .info-row { display: flex; justify-content: space-between; margin: 0.5rem 0; }
    .label { font-weight: bold; color: #073b4c; min-width: 120px; }
    .value { color: #333; }
    .match-icon { color: #4caf50; font-size: 1.5rem; }
    .no-match-text { color: #999; }
    .verify-btn, .claim-btn { padding: 0.75rem 1.5rem; background: #1f7a8c; color: white; border: none; border-radius: 8px; cursor: pointer; margin-right: 0.5rem; }
    .verify-btn:hover { background: #155a6d; }
    .claim-btn { background: #4caf50; }
    .claim-btn:hover { background: #45a049; }
    .actions { margin-top: 1rem; }
    hr { border: none; border-top: 1px solid #eee; margin: 1rem 0; }
    .popup { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.45); z-index: 9999; }
    .popup.hidden { display: none; }
    .popup-content { background: white; padding: 1.5rem; border-radius: 14px; width: min(95%,420px); text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
    .popup-content h2 { margin-bottom: 1rem; color: #b00020; }
    .popup-content p { margin-bottom: 1.25rem; color: #333; }
    .popup-content button { background: #1f7a8c; color: white; border: none; border-radius: 10px; padding: 0.75rem 1.25rem; cursor: pointer; }
</style>
</head>
<body>

<div class="container">
    <h1>Verify Found & Lost Matches</h1>

    <?php if (count($matches) === 0): ?>
        <p>No pending matches to verify.</p>
    <?php else: ?>
        <?php foreach ($matches as $match): ?>
            <div class="match-card <?= $match['FoundID'] ? 'has-match' : 'no-match' ?>">
                
                <h2>Lost Report: <?= htmlspecialchars($match['TicketNumber'], ENT_QUOTES, 'UTF-8') ?></h2>
                
                <div class="lost-info">
                    <div class="info-row">
                        <span class="label">Student:</span>
                        <span class="value"><?= htmlspecialchars($match['StudentNumber'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Category:</span>
                        <span class="value"><?= htmlspecialchars($match['Category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Location:</span>
                        <span class="value"><?= htmlspecialchars($match['Location'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Date Lost:</span>
                        <span class="value"><?= date('F j, Y', strtotime($match['DateLost'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Description:</span>
                        <span class="value"><?= htmlspecialchars(substr($match['Description'], 0, 100), ENT_QUOTES, 'UTF-8') ?>...</span>
                    </div>
                </div>

                <hr>

                <?php if ($match['FoundID']): ?>
                    <div class="match-icon">✓ Potential Match Found</div>
                    <div class="found-info">
                        <div class="info-row">
                            <span class="label">Found By:</span>
                            <span class="value"><?= htmlspecialchars($match['FoundBy'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Location Found:</span>
                            <span class="value"><?= htmlspecialchars($match['FoundLocation'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Date Found:</span>
                            <span class="value"><?= date('F j, Y', strtotime($match['DateFound'])) ?></span>
                        </div>
                    </div>

                    <div class="actions">
                        <form action="verify_match.php" method="post" style="display: inline;">
                            <input type="hidden" name="lost_id" value="<?= $match['LostID'] ?>">
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

    <div id="error-popup" class="popup hidden">
        <div class="popup-content">
            <h2>Error</h2>
            <p id="popup-message"></p>
            <button id="popup-ok">OK</button>
        </div>
    </div>
</div>

<script>
    function showErrorPopup(message) {
        if (!message) return;
        document.getElementById('popup-message').textContent = message;
        document.getElementById('error-popup').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const error = params.get('error');
        if (error) {
            showErrorPopup(decodeURIComponent(error));
        }
        document.getElementById('popup-ok').addEventListener('click', function () {
            document.getElementById('error-popup').classList.add('hidden');
            history.replaceState(null, '', window.location.pathname);
        });
    });
</script>

</body>
</html>
