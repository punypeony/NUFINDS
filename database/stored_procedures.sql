-- Import in phpMyAdmin (database: nufindsdb) after nufindsdb.sql and history_match_group.sql
-- Uses stored procedures for admin reports, verify, and history.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_admin_get_lost_reports$$
CREATE PROCEDURE sp_admin_get_lost_reports()
BEGIN
    SELECT l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
           l.Category, l.Description, l.DateReported,
           s.CollegeDepartment, s.StudentEmail
    FROM lost l
    INNER JOIN studentinfo s ON l.StudentNumber = s.StudentNumber
    ORDER BY s.CollegeDepartment ASC, l.DateLost DESC;
END$$

DROP PROCEDURE IF EXISTS sp_admin_get_found_reports$$
CREATE PROCEDURE sp_admin_get_found_reports()
BEGIN
    SELECT f.FoundID, f.StudentNumber, f.Location, f.DateFound,
           f.Category, f.Description, f.Status, f.DateReported,
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

DROP PROCEDURE IF EXISTS sp_get_pending_matches$$
CREATE PROCEDURE sp_get_pending_matches()
BEGIN
    SELECT
        l.LostID, l.TicketNumber, l.StudentNumber, l.Location, l.DateLost,
        l.Category, l.Description,
        f.FoundID, f.StudentNumber AS FoundBy, f.Location AS FoundLocation,
        f.DateFound, f.Status
    FROM lost l
    INNER JOIN found f ON
        l.Category = f.Category
        AND l.StudentNumber <> f.StudentNumber
        AND DATEDIFF(f.DateFound, l.DateLost) BETWEEN -3 AND 30
        AND f.Status = 'Unclaimed'
    WHERE l.LostID NOT IN (
        SELECT OriginalReportID FROM history WHERE ReportType = 'Lost'
    )
    ORDER BY l.DateLost DESC;
END$$

DROP PROCEDURE IF EXISTS sp_verify_match$$
CREATE PROCEDURE sp_verify_match(IN p_lost_id INT, IN p_found_id INT)
BEGIN
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

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT COUNT(*) INTO @lost_ok FROM lost WHERE LostID = p_lost_id;
    SELECT COUNT(*) INTO @found_ok FROM found WHERE FoundID = p_found_id;

    IF @lost_ok = 0 OR @found_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lost or Found record not found.';
    END IF;

    SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description
    INTO v_ticket, v_lost_student, v_location, v_date_lost, v_category, v_lost_desc
    FROM lost WHERE LostID = p_lost_id;

    SELECT StudentNumber, Location, DateFound, Description
    INTO v_found_student, v_found_location, v_date_found, v_found_desc
    FROM found WHERE FoundID = p_found_id;

    SET v_match_group = LOWER(UUID());

    SELECT COUNT(*) INTO v_has_group
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'history'
      AND COLUMN_NAME = 'MatchGroupID';

    START TRANSACTION;

    IF v_has_group > 0 THEN
        INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, p_lost_id, p_found_id, 'Lost', p_lost_id,
            v_ticket, v_lost_student, v_location, v_date_lost, v_category, v_lost_desc, 'Retrieved');

        INSERT INTO history (MatchGroupID, LostID, FoundID, ReportType, OriginalReportID,
            TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus)
        VALUES (v_match_group, p_lost_id, p_found_id, 'Found', p_found_id,
            NULL, v_found_student, v_found_location, v_date_found, v_category, v_found_desc, 'Claimed');
    ELSE
        INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location,
            ReportDate, Category, Description, FinalStatus)
        VALUES ('Lost', p_lost_id, v_ticket, v_lost_student, v_location,
            v_date_lost, v_category, v_lost_desc, 'Retrieved');

        INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location,
            ReportDate, Category, Description, FinalStatus)
        VALUES ('Found', p_found_id, NULL, v_found_student, v_found_location,
            v_date_found, v_category, v_found_desc, 'Claimed');
    END IF;

    DELETE FROM lost WHERE LostID = p_lost_id;
    DELETE FROM found WHERE FoundID = p_found_id;

    COMMIT;
END$$

DELIMITER ;
