-- Run in phpMyAdmin on nufindsdb after nufindsdb.sql (and history_match_group.sql if used).
-- Pending matches are stored here; verify moves rows to history, reject keeps reports active.

CREATE TABLE IF NOT EXISTS matches (
  MatchID int(11) NOT NULL AUTO_INCREMENT,
  LostID int(11) NOT NULL,
  FoundID int(11) NOT NULL,
  Status enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  CreatedAt timestamp NOT NULL DEFAULT current_timestamp(),
  VerifiedAt timestamp NULL DEFAULT NULL,
  RejectedAt timestamp NULL DEFAULT NULL,
  VerifiedByAdminID int(11) DEFAULT NULL,
  RejectedByAdminID int(11) DEFAULT NULL,
  PRIMARY KEY (MatchID),
  UNIQUE KEY uq_lost_found (LostID, FoundID),
  KEY idx_status (Status),
  KEY idx_lost (LostID),
  KEY idx_found (FoundID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: link history rows back to the match decision
ALTER TABLE history
  ADD COLUMN MatchID int(11) DEFAULT NULL AFTER MatchGroupID;

ALTER TABLE history
  ADD KEY idx_history_match (MatchID);

-- Seed pending rows from current lost/found pairs (safe to re-run: ignores duplicates)
INSERT IGNORE INTO matches (LostID, FoundID, Status)
SELECT
    l.LostID,
    f.FoundID,
    'pending'
FROM lost l
INNER JOIN found f ON
    l.Category = f.Category
    AND l.StudentNumber <> f.StudentNumber
    AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
    AND f.Status = 'Unclaimed';
