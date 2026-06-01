-- Run in phpMyAdmin on existing nufindsdb (skip columns that already exist)

ALTER TABLE history
  ADD COLUMN MatchGroupID VARCHAR(36) DEFAULT NULL AFTER HistoryID,
  ADD COLUMN LostID INT DEFAULT NULL AFTER MatchGroupID,
  ADD COLUMN FoundID INT DEFAULT NULL AFTER LostID;

ALTER TABLE history
  ADD KEY idx_match_group (MatchGroupID);
