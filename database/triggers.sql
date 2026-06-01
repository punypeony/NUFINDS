-- NUFinds MySQL triggers
-- Import in phpMyAdmin after nufindsdb.sql, history_match_group.sql, and stored_procedures.sql
-- Database: nufindsdb

DELIMITER $$

-- ---------------------------------------------------------------------------
-- LOST: auto ticket number, validate dates, require valid student
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trg_lost_before_insert$$
CREATE TRIGGER trg_lost_before_insert
BEFORE INSERT ON lost
FOR EACH ROW
BEGIN
    DECLARE v_student_count INT DEFAULT 0;
    DECLARE v_ticket_count INT DEFAULT 0;
    DECLARE v_next_num INT DEFAULT 0;

    IF NEW.DateLost > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Date lost cannot be in the future.';
    END IF;

    SELECT COUNT(*) INTO v_student_count
    FROM studentinfo
    WHERE StudentNumber = NEW.StudentNumber;

    IF v_student_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student number does not exist in student records.';
    END IF;

    IF NEW.TicketNumber IS NULL OR TRIM(NEW.TicketNumber) = '' THEN
        SELECT IFNULL(MAX(LostID), 0) + 1 INTO v_next_num FROM lost;
        SET NEW.TicketNumber = CONCAT('NU-', LPAD(1000 + v_next_num, 4, '0'));
    END IF;

    SELECT COUNT(*) INTO v_ticket_count
    FROM lost
    WHERE TicketNumber = NEW.TicketNumber;

    IF v_ticket_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ticket number already exists.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_lost_before_update$$
CREATE TRIGGER trg_lost_before_update
BEFORE UPDATE ON lost
FOR EACH ROW
BEGIN
    DECLARE v_ticket_count INT DEFAULT 0;
    DECLARE v_student_count INT DEFAULT 0;

    IF NEW.DateLost > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Date lost cannot be in the future.';
    END IF;

    SELECT COUNT(*) INTO v_student_count
    FROM studentinfo
    WHERE StudentNumber = NEW.StudentNumber;

    IF v_student_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student number does not exist in student records.';
    END IF;

    IF NEW.TicketNumber <> OLD.TicketNumber THEN
        SELECT COUNT(*) INTO v_ticket_count
        FROM lost
        WHERE TicketNumber = NEW.TicketNumber
          AND LostID <> OLD.LostID;

        IF v_ticket_count > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Ticket number already exists.';
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------------------
-- FOUND: default status, validate dates, require valid student
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trg_found_before_insert$$
CREATE TRIGGER trg_found_before_insert
BEFORE INSERT ON found
FOR EACH ROW
BEGIN
    DECLARE v_student_count INT DEFAULT 0;

    IF NEW.DateFound > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Date found cannot be in the future.';
    END IF;

    IF NEW.Status IS NULL OR TRIM(NEW.Status) = '' THEN
        SET NEW.Status = 'Unclaimed';
    END IF;

    SELECT COUNT(*) INTO v_student_count
    FROM studentinfo
    WHERE StudentNumber = NEW.StudentNumber;

    IF v_student_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student number does not exist in student records.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_found_before_update$$
CREATE TRIGGER trg_found_before_update
BEFORE UPDATE ON found
FOR EACH ROW
BEGIN
    DECLARE v_student_count INT DEFAULT 0;

    IF NEW.DateFound > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Date found cannot be in the future.';
    END IF;

    IF NEW.Status IS NULL OR TRIM(NEW.Status) = '' THEN
        SET NEW.Status = 'Unclaimed';
    END IF;

    SELECT COUNT(*) INTO v_student_count
    FROM studentinfo
    WHERE StudentNumber = NEW.StudentNumber;

    IF v_student_count = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student number does not exist in student records.';
    END IF;
END$$

-- ---------------------------------------------------------------------------
-- STUDENT: block delete when reports still exist
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trg_studentinfo_before_delete$$
CREATE TRIGGER trg_studentinfo_before_delete
BEFORE DELETE ON studentinfo
FOR EACH ROW
BEGIN
    DECLARE v_lost_count INT DEFAULT 0;
    DECLARE v_found_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_lost_count
    FROM lost
    WHERE StudentNumber = OLD.StudentNumber;

    SELECT COUNT(*) INTO v_found_count
    FROM found
    WHERE StudentNumber = OLD.StudentNumber;

    IF v_lost_count > 0 OR v_found_count > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete student with active lost or found reports.';
    END IF;
END$$

-- ---------------------------------------------------------------------------
-- HISTORY: stamp completion time on insert; keep archive immutable fields tidy
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trg_history_before_insert$$
CREATE TRIGGER trg_history_before_insert
BEFORE INSERT ON history
FOR EACH ROW
BEGIN
    IF NEW.DateCompleted IS NULL THEN
        SET NEW.DateCompleted = CURRENT_TIMESTAMP;
    END IF;

    IF NEW.FinalStatus IS NULL OR TRIM(NEW.FinalStatus) = '' THEN
        SET NEW.FinalStatus = 'Archived';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_history_before_update$$
CREATE TRIGGER trg_history_before_update
BEFORE UPDATE ON history
FOR EACH ROW
BEGIN
    IF NEW.ReportDate > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Report date cannot be in the future.';
    END IF;
END$$

-- ---------------------------------------------------------------------------
-- ADMIN: prevent deactivating the last active admin account
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trg_admin_before_update$$
CREATE TRIGGER trg_admin_before_update
BEFORE UPDATE ON adminaccounts
FOR EACH ROW
BEGIN
    DECLARE v_active_count INT DEFAULT 0;

    IF OLD.IsActive = 1 AND NEW.IsActive = 0 THEN
        SELECT COUNT(*) INTO v_active_count
        FROM adminaccounts
        WHERE IsActive = 1
          AND AdminID <> OLD.AdminID;

        IF v_active_count = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cannot deactivate the only active admin account.';
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_admin_before_delete$$
CREATE TRIGGER trg_admin_before_delete
BEFORE DELETE ON adminaccounts
FOR EACH ROW
BEGIN
    DECLARE v_active_count INT DEFAULT 0;

    IF OLD.IsActive = 1 THEN
        SELECT COUNT(*) INTO v_active_count
        FROM adminaccounts
        WHERE IsActive = 1
          AND AdminID <> OLD.AdminID;

        IF v_active_count = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cannot delete the only active admin account.';
        END IF;
    END IF;
END$$

DELIMITER ;
