-- Import in phpMyAdmin (database: nufindsdb) after nufindsdb.sql, matches.sql, and history_match_group.sql
-- Uses stored procedures for admin reports, verify, and history.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_admin_get_lost_reports$$
CREATE PROCEDURE sp_admin_get_lost_reports()
BEGIN
    SELECT l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
           l.Category, l.Description, l.Image, l.DateReported,
           s.CollegeDepartment, s.StudentEmail
    FROM lost l
    INNER JOIN studentinfo s ON l.StudentNumber = s.StudentNumber
    ORDER BY s.CollegeDepartment ASC, l.DateLost DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_get_found_reports$$
CREATE PROCEDURE sp_admin_get_found_reports()
BEGIN
    SELECT f.FoundID, f.StudentNumber, f.Location, f.DateFound,
           f.Category, f.Description, f.Image, f.Status, f.DateReported,
           s.CollegeDepartment, s.StudentEmail
    FROM found f
    INNER JOIN studentinfo s ON f.StudentNumber = s.StudentNumber
    ORDER BY s.CollegeDepartment ASC, f.DateFound DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_get_history$$
CREATE PROCEDURE sp_admin_get_history()
BEGIN
    SELECT * FROM history ORDER BY DateCompleted DESC, HistoryID DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_update_lost$$
CREATE PROCEDURE sp_admin_update_lost(
    IN p_lost_id INT,
    IN p_ticket VARCHAR(10),
    IN p_student VARCHAR(20),
    IN p_location VARCHAR(255),
    IN p_date_lost DATE,
    IN p_category VARCHAR(100),
    IN p_description TEXT
)
BEGIN
    UPDATE lost
    SET TicketNumber = p_ticket,
        StudentNumber = p_student,
        Location = p_location,
        DateLost = p_date_lost,
        Category = p_category,
        Description = p_description
    WHERE LostID = p_lost_id;
END$$

DROP PROCEDURE IF EXISTS sp_admin_delete_lost$$
CREATE PROCEDURE sp_admin_delete_lost(IN p_lost_id INT)
BEGIN
    DELETE FROM lost WHERE LostID = p_lost_id;
END$$

DROP PROCEDURE IF EXISTS sp_admin_update_found$$
CREATE PROCEDURE sp_admin_update_found(
    IN p_found_id INT,
    IN p_student VARCHAR(20),
    IN p_location VARCHAR(255),
    IN p_date_found DATE,
    IN p_category VARCHAR(100),
    IN p_description TEXT,
    IN p_status VARCHAR(20)
)
BEGIN
    UPDATE found
    SET StudentNumber = p_student,
        Location = p_location,
        DateFound = p_date_found,
        Category = p_category,
        Description = p_description,
        Status = p_status
    WHERE FoundID = p_found_id;
END$$

DROP PROCEDURE IF EXISTS sp_admin_delete_found$$
CREATE PROCEDURE sp_admin_delete_found(IN p_found_id INT)
BEGIN
    DELETE FROM found WHERE FoundID = p_found_id;
END$$

DROP PROCEDURE IF EXISTS sp_admin_update_history$$
CREATE PROCEDURE sp_admin_update_history(
    IN p_history_id INT,
    IN p_ticket VARCHAR(10),
    IN p_student VARCHAR(20),
    IN p_location VARCHAR(255),
    IN p_report_date DATE,
    IN p_category VARCHAR(100),
    IN p_description TEXT,
    IN p_final_status VARCHAR(50)
)
BEGIN
    UPDATE history
    SET TicketNumber = p_ticket,
        StudentNumber = p_student,
        Location = p_location,
        ReportDate = p_report_date,
        Category = p_category,
        Description = p_description,
        FinalStatus = p_final_status
    WHERE HistoryID = p_history_id;
END$$

DROP PROCEDURE IF EXISTS sp_admin_delete_history$$
CREATE PROCEDURE sp_admin_delete_history(IN p_history_id INT)
BEGIN
    DELETE FROM history WHERE HistoryID = p_history_id;
END$$

DROP PROCEDURE IF EXISTS sp_sync_pending_matches$$
CREATE PROCEDURE sp_sync_pending_matches()
BEGIN
    DELETE m FROM matches m
    LEFT JOIN lost l ON m.LostID = l.LostID
    LEFT JOIN found f ON m.FoundID = f.FoundID
    WHERE m.Status = 'pending'
      AND (
        l.LostID IS NULL
        OR f.FoundID IS NULL
        OR f.Status <> 'Unclaimed'
        OR l.Category <> f.Category
        OR l.StudentNumber = f.StudentNumber
        OR DATEDIFF(f.DateFound, l.DateLost) NOT BETWEEN -3 AND 30
      );

    INSERT IGNORE INTO matches (LostID, FoundID, Status)
    SELECT l.LostID, f.FoundID, 'pending'
    FROM lost l
    INNER JOIN found f ON
        l.Category = f.Category
        AND l.StudentNumber <> f.StudentNumber
        AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
        AND f.Status = 'Unclaimed';
END$$

DROP PROCEDURE IF EXISTS sp_get_pending_matches$$
CREATE PROCEDURE sp_get_pending_matches()
BEGIN
    CALL sp_sync_pending_matches();

    SELECT
        m.MatchID,
        l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
        l.Category, l.Description,
        f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation,
        f.DateFound, f.Status
    FROM matches m
    INNER JOIN lost l ON m.LostID = l.LostID
    INNER JOIN found f ON m.FoundID = f.FoundID
    WHERE m.Status = 'pending'
    ORDER BY m.CreatedAt DESC, l.DateLost DESC;
END$$

DROP PROCEDURE IF EXISTS sp_reject_match$$
CREATE PROCEDURE sp_reject_match(IN p_match_id INT, IN p_admin_id INT)
BEGIN
    UPDATE matches
    SET Status = 'rejected',
        RejectedAt = CURRENT_TIMESTAMP,
        RejectedByAdminID = NULLIF(p_admin_id, 0)
    WHERE MatchID = p_match_id
      AND Status = 'pending';

    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Match not found or already handled.';
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_verify_match$$
CREATE PROCEDURE sp_verify_match(IN p_match_id INT, IN p_lost_id INT, IN p_found_id INT)
BEGIN
    DECLARE v_resolved_match INT DEFAULT 0;
    DECLARE v_lost_id INT DEFAULT 0;
    DECLARE v_found_id INT DEFAULT 0;
    DECLARE v_ticket VARCHAR(10);
    DECLARE v_lost_student VARCHAR(20);
    DECLARE v_location VARCHAR(255);
    DECLARE v_date_lost DATE;
    DECLARE v_category VARCHAR(100);
    DECLARE v_lost_desc TEXT;
    DECLARE v_found_student VARCHAR(20);
    DECLARE v_found_location VARCHAR(255);
    DECLARE v_date_found DATE;
    DECLARE v_found_desc TEXT;
    DECLARE v_match_group VARCHAR(36);
    DECLARE v_has_group TINYINT DEFAULT 0;
    DECLARE v_has_match_id TINYINT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_match_id > 0 THEN
        SELECT MatchID, LostID, FoundID
        INTO v_resolved_match, v_lost_id, v_found_id
        FROM matches
        WHERE MatchID = p_match_id AND Status = 'pending'
        LIMIT 1;
    ELSEIF p_lost_id > 0 AND p_found_id > 0 THEN
        SELECT MatchID, LostID, FoundID
        INTO v_resolved_match, v_lost_id, v_found_id
        FROM matches
        WHERE LostID = p_lost_id AND FoundID = p_found_id AND Status = 'pending'
        LIMIT 1;
    END IF;

    IF v_resolved_match = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Match not found or already handled.';
    END IF;

    SELECT COUNT(*) INTO @lost_ok FROM lost WHERE LostID = v_lost_id;
    SELECT COUNT(*) INTO @found_ok FROM found WHERE FoundID = v_found_id;

    IF @lost_ok = 0 OR @found_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lost or Found record not found.';
    END IF;

    SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description
    INTO v_ticket, v_lost_student, v_location, v_date_lost, v_category, v_lost_desc
    FROM lost WHERE LostID = v_lost_id;

    SELECT StudentNumber, Location, DateFound, Description
    INTO v_found_student, v_found_location, v_date_found, v_found_desc
    FROM found WHERE FoundID = v_found_id;

    SET v_match_group = LOWER(UUID());

    SELECT COUNT(*) INTO v_has_group
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'history'
      AND COLUMN_NAME = 'MatchGroupID';

    SELECT COUNT(*) INTO v_has_match_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'history'
      AND COLUMN_NAME = 'MatchID';

    START TRANSACTION;

    IF v_has_group > 0 AND v_has_match_id > 0 THEN
        INSERT INTO history (MatchGroupID, MatchID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, v_resolved_match, v_lost_id, v_found_id, 'Lost', v_lost_id,
            v_ticket, v_lost_student, v_location, v_date_lost, v_category, v_lost_desc, 'Retrieved');

        INSERT INTO history (MatchGroupID, MatchID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, v_resolved_match, v_lost_id, v_found_id, 'Found', v_found_id,
            NULL, v_found_student, v_found_location, v_date_found, v_category, v_found_desc, 'Claimed');
    ELSEIF v_has_group > 0 THEN
        INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, v_lost_id, v_found_id, 'Lost', v_lost_id,
            v_ticket, v_lost_student, v_location, v_date_lost, v_category, v_lost_desc, 'Retrieved');

        INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, v_lost_id, v_found_id, 'Found', v_found_id,
            NULL, v_found_student, v_found_location, v_date_found, v_category, v_found_desc, 'Claimed');
    ELSE
        INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location,
            ReportDate, Category, Description, FinalStatus)
        VALUES ('Lost', v_lost_id, v_ticket, v_lost_student, v_location,
            v_date_lost, v_category, v_lost_desc, 'Retrieved');

        INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location,
            ReportDate, Category, Description, FinalStatus)
        VALUES ('Found', v_found_id, NULL, v_found_student, v_found_location,
            v_date_found, v_category, v_found_desc, 'Claimed');
    END IF;

    UPDATE matches
    SET Status = 'verified',
        VerifiedAt = CURRENT_TIMESTAMP
    WHERE MatchID = v_resolved_match;

    DELETE FROM lost WHERE LostID = v_lost_id;
    DELETE FROM found WHERE FoundID = v_found_id;

    COMMIT;
END$$

-- ---------------------------------------------------------------------------
-- Student registration: auto ID YYYY-0001 for current year
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_generate_student_number$$
CREATE PROCEDURE sp_generate_student_number()
BEGIN
    DECLARE v_year INT DEFAULT YEAR(CURDATE());
    DECLARE v_seq INT DEFAULT 1;

    SELECT IFNULL(MAX(CAST(SUBSTRING(StudentNumber, 6) AS UNSIGNED)), 0) + 1
    INTO v_seq
    FROM studentinfo
    WHERE StudentNumber REGEXP CONCAT('^', v_year, '-[0-9]{4}$');

    SELECT CONCAT(v_year, '-', LPAD(v_seq, 4, '0')) AS StudentNumber;
END$$

-- ---------------------------------------------------------------------------
-- Admin search (ticket, student ID, email, location, category, description)
-- ---------------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_admin_search_lost_reports$$
CREATE PROCEDURE sp_admin_search_lost_reports(IN p_query VARCHAR(255))
BEGIN
    SET p_query = TRIM(IFNULL(p_query, ''));
    SELECT l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
           l.Category, l.Description, l.Image, l.DateReported,
           s.CollegeDepartment, s.StudentEmail
    FROM lost l
    INNER JOIN studentinfo s ON l.StudentNumber = s.StudentNumber
    WHERE p_query = ''
       OR l.TicketNumber LIKE CONCAT('%', p_query, '%')
       OR l.StudentNumber LIKE CONCAT('%', p_query, '%')
       OR s.StudentEmail LIKE CONCAT('%', p_query, '%')
       OR l.Location LIKE CONCAT('%', p_query, '%')
       OR l.Category LIKE CONCAT('%', p_query, '%')
       OR l.Description LIKE CONCAT('%', p_query, '%')
    ORDER BY s.CollegeDepartment ASC, l.DateLost DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_search_found_reports$$
CREATE PROCEDURE sp_admin_search_found_reports(IN p_query VARCHAR(255))
BEGIN
    SET p_query = TRIM(IFNULL(p_query, ''));
    SELECT f.FoundID, f.StudentNumber, f.Location, f.DateFound,
           f.Category, f.Description, f.Image, f.Status, f.DateReported,
           s.CollegeDepartment, s.StudentEmail
    FROM found f
    INNER JOIN studentinfo s ON f.StudentNumber = s.StudentNumber
    WHERE p_query = ''
       OR CAST(f.FoundID AS CHAR) LIKE CONCAT('%', p_query, '%')
       OR f.StudentNumber LIKE CONCAT('%', p_query, '%')
       OR s.StudentEmail LIKE CONCAT('%', p_query, '%')
       OR f.Location LIKE CONCAT('%', p_query, '%')
       OR f.Category LIKE CONCAT('%', p_query, '%')
       OR f.Description LIKE CONCAT('%', p_query, '%')
       OR f.Status LIKE CONCAT('%', p_query, '%')
    ORDER BY s.CollegeDepartment ASC, f.DateFound DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_search_history$$
CREATE PROCEDURE sp_admin_search_history(IN p_query VARCHAR(255))
BEGIN
    SET p_query = TRIM(IFNULL(p_query, ''));
    SELECT h.*
    FROM history h
    LEFT JOIN studentinfo s ON h.StudentNumber = s.StudentNumber
    WHERE p_query = ''
       OR IFNULL(h.TicketNumber, '') LIKE CONCAT('%', p_query, '%')
       OR h.StudentNumber LIKE CONCAT('%', p_query, '%')
       OR IFNULL(s.StudentEmail, '') LIKE CONCAT('%', p_query, '%')
       OR h.Location LIKE CONCAT('%', p_query, '%')
       OR h.Category LIKE CONCAT('%', p_query, '%')
       OR h.Description LIKE CONCAT('%', p_query, '%')
       OR h.FinalStatus LIKE CONCAT('%', p_query, '%')
       OR IFNULL(h.MatchGroupID, '') LIKE CONCAT('%', p_query, '%')
       OR CAST(h.HistoryID AS CHAR) LIKE CONCAT('%', p_query, '%')
    ORDER BY h.DateCompleted DESC, h.HistoryID DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_search_pending_matches$$
CREATE PROCEDURE sp_admin_search_pending_matches(IN p_query VARCHAR(255))
BEGIN
    SET p_query = TRIM(IFNULL(p_query, ''));

    CALL sp_sync_pending_matches();

    SELECT
        m.MatchID,
        l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
        l.Category, l.Description,
        f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation,
        f.DateFound, f.Status
    FROM matches m
    INNER JOIN lost l ON m.LostID = l.LostID
    INNER JOIN found f ON m.FoundID = f.FoundID
    LEFT JOIN studentinfo s_lost ON l.StudentNumber = s_lost.StudentNumber
    LEFT JOIN studentinfo s_found ON f.StudentNumber = s_found.StudentNumber
    WHERE m.Status = 'pending'
      AND (
        p_query = ''
        OR l.TicketNumber LIKE CONCAT('%', p_query, '%')
        OR l.StudentNumber LIKE CONCAT('%', p_query, '%')
        OR IFNULL(s_lost.StudentEmail, '') LIKE CONCAT('%', p_query, '%')
        OR l.Location LIKE CONCAT('%', p_query, '%')
        OR l.Category LIKE CONCAT('%', p_query, '%')
        OR l.Description LIKE CONCAT('%', p_query, '%')
        OR f.StudentNumber LIKE CONCAT('%', p_query, '%')
        OR IFNULL(s_found.StudentEmail, '') LIKE CONCAT('%', p_query, '%')
        OR f.Location LIKE CONCAT('%', p_query, '%')
        OR CAST(m.MatchID AS CHAR) LIKE CONCAT('%', p_query, '%')
      )
    ORDER BY m.CreatedAt DESC, l.DateLost DESC;
END$$

DELIMITER ;
