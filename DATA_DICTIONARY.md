# NUFinds Data Dictionary

## Overview
- **Database name:** `nufindsdb`
- **Primary purpose:** Store student accounts, lost/found item reports, and resolved match history.
- **Main entities:** `studentinfo`, `lost`, `found`, `matches`, `history`
- **File source of truth:** `database/nufindsdb.sql`

## Shared Business Rules
- `StudentNumber` in `lost` and `found` must exist in `studentinfo.StudentNumber`.
- `Category` values for active reports (`lost`, `found`) are restricted to:
  - `Wallet/Credit Card/Money`
  - `Identity Document`
  - `Bag`
  - `Electronics/Gadgets`
  - `Accessories`
  - `Others`
- Matching logic compares `lost` and `found` records by:
  - same `Category`
  - different reporting student (`StudentNumber` mismatch)
  - date window where `DATEDIFF(DateFound, DateLost)` is between `-3` and `30`
- Uploaded image paths are saved as relative strings like `uploads/lost/<filename>` or `uploads/found/<filename>`.

## Table: `studentinfo`
Reference table for valid student identities used during login and report submission.

| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| `StudentNumber` | `varchar(20)` | NO | PK | none | Unique student ID used as the main identity key across the app. |
| `CollegeDepartment` | `enum(...)` | NO |  | none | Student's college/department; must be one of the defined NU college enum values. |
| `StudentEmail` | `varchar(50)` | NO | UNIQUE | none | Student email used in login validation. |
| `IsActive` | `tinyint(1)` | NO |  | `1` | When `0`, the student cannot sign in (admin-deactivated account). |

### `CollegeDepartment` allowed values
- `COLLEGE OF ALLIED HEALTH`
- `COLLEGE OF ARCHITECTURE`
- `COLLEGE OF BUSINESS AND ACCOUNTANCY`
- `COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES`
- `COLLEGE OF EDUCATION ARTS AND SCIENCES`
- `COLLEGE OF ENGINEERING`
- `COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT`

## Table: `lost`
Stores active lost item reports submitted by logged-in students.

| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| `LostID` | `int(11)` | NO | PK, AI | auto increment | Internal identifier for each lost report. |
| `TicketNumber` | `varchar(10)` | NO | UNIQUE | none | User-facing tracking ID (format like `NU-1001`). |
| `StudentNumber` | `varchar(20)` | NO | FK | none | Student who reported the item as lost; references `studentinfo.StudentNumber`. |
| `Location` | `varchar(255)` | NO |  | none | Location text selected from UI location/floor controls. |
| `DateLost` | `date` | NO |  | none | Date the user says the item was lost. |
| `Category` | `enum(...)` | NO |  | none | Item category; restricted to shared category enum values. |
| `Description` | `text` | NO |  | none | Free-text description entered by user. |
| `DateReported` | `timestamp` | NO |  | `current_timestamp()` | Server timestamp when report is inserted. |
| `Image` | `varchar(255)` | YES |  | `NULL` | Optional relative path to uploaded image file. |

### Notes
- `TicketNumber` is generated in application logic from next `LostID`.
- Lost reports can be cancelled by user action, which deletes the row from `lost`.

## Table: `found`
Stores active found item reports submitted by logged-in students.

| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| `FoundID` | `int(11)` | NO | PK, AI | auto increment | Internal identifier for each found report. |
| `StudentNumber` | `varchar(20)` | NO | FK | none | Student who reported finding an item; references `studentinfo.StudentNumber`. |
| `Location` | `varchar(255)` | NO |  | none | Location text selected from UI location/floor controls. |
| `DateFound` | `date` | NO |  | none | Date the user says the item was found. |
| `Category` | `enum(...)` | NO |  | none | Item category; restricted to shared category enum values. |
| `Description` | `text` | NO |  | none | Free-text description entered by user. |
| `Status` | `varchar(20)` | YES |  | `Unclaimed` | Current state of found report (active logic expects `Unclaimed`; matched flow archives as claimed). |
| `DateReported` | `timestamp` | NO |  | `current_timestamp()` | Server timestamp when report is inserted. |
| `Image` | `varchar(255)` | YES |  | `NULL` | Optional relative path to uploaded image file. |

### Notes
- Matching and unmatched views primarily operate on rows where `Status = 'Unclaimed'`.

## Table: `matches`
Working queue of suggested lost/found pairs before admin decision.

| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| `MatchID` | `int(11)` | NO | PK, AI | auto increment | Internal identifier for each suggested pair. |
| `LostID` | `int(11)` | NO | UNIQUE (with FoundID) | none | Lost report in the pair. |
| `FoundID` | `int(11)` | NO | UNIQUE (with LostID) | none | Found report in the pair. |
| `Status` | `enum('pending','verified','rejected')` | NO |  | `pending` | `pending` = awaiting admin; `verified` = archived to history; `rejected` = dismissed, reports stay active. |
| `CreatedAt` | `timestamp` | NO |  | `current_timestamp()` | When the suggestion was created. |
| `VerifiedAt` | `timestamp` | YES |  | `NULL` | When admin verified the pair. |
| `RejectedAt` | `timestamp` | YES |  | `NULL` | When admin dismissed the pair. |
| `VerifiedByAdminID` | `int(11)` | YES |  | `NULL` | Admin who verified (optional). |
| `RejectedByAdminID` | `int(11)` | YES |  | `NULL` | Admin who rejected (optional). |

### Notes
- Rows are created by sync logic when `lost` and `found` meet category/date/student rules.
- Verify moves reports to `history`, marks the match `verified`, and deletes active `lost`/`found` rows.
- Reject marks the match `rejected` so the same pair is not suggested again.

## Table: `history`
Archive table for completed/verified matches. Rows are inserted when a lost-found pair is verified, then original rows are removed from active tables.

| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| `HistoryID` | `int(11)` | NO | PK, AI | auto increment | Internal identifier for archived history record. |
| `ReportType` | `enum('Lost','Found')` | NO |  | none | Indicates whether archived row came from `lost` or `found`. |
| `OriginalReportID` | `int(11)` | NO |  | none | Original `LostID` or `FoundID` from source table. |
| `TicketNumber` | `varchar(10)` | YES |  | `NULL` | Lost ticket number; typically null for found records. |
| `StudentNumber` | `varchar(20)` | NO |  | none | Student tied to the archived source report. |
| `Location` | `varchar(255)` | NO |  | none | Location copied at archive time. |
| `ReportDate` | `date` | NO |  | none | Original `DateLost` or `DateFound`. |
| `Category` | `varchar(100)` | NO |  | none | Category copied from source report. |
| `Description` | `text` | NO |  | none | Description copied from source report. |
| `FinalStatus` | `varchar(50)` | NO |  | none | Final outcome value (`Retrieved` for lost, `Claimed` for found in current flow). |
| `DateCompleted` | `timestamp` | NO |  | `current_timestamp()` | Timestamp when match verification completed. |

## Input-to-Database Field Mapping
How front-end form inputs map to stored database columns.

### Login form (`pages/login.html` -> `database/php/Auth.php`)
- `StudentNumber` -> validates against `studentinfo.StudentNumber`
- `CollegeDepartment` -> validates against `studentinfo.CollegeDepartment`
- `StudentEmail` -> validates against `studentinfo.StudentEmail`

### Report Lost (`database/php/ReportLost.php` -> `database/php/ReportSubmission.php`)
- `report_type = "lost"` -> route to `lost` table
- `Location` -> `lost.Location`
- `DateLost` -> normalized as generic `Date` in server code -> `lost.DateLost`
- `Category` -> `lost.Category`
- `Description` -> `lost.Description`
- `ItemImage` (file) -> upload path -> `lost.Image`
- logged-in session `StudentNumber` -> `lost.StudentNumber`

### Report Found (`database/php/ReportFound.php` -> `database/php/ReportSubmission.php`)
- `report_type = "found"` -> route to `found` table
- `Location` -> `found.Location`
- `DateFound` -> normalized as generic `Date` in server code -> `found.DateFound`
- `Category` -> `found.Category`
- `Description` -> `found.Description`
- `ItemImage` (file) -> upload path -> `found.Image`
- logged-in session `StudentNumber` -> `found.StudentNumber`

## Sensitive Data Notes
- **Personally identifiable data stored:** `StudentNumber`, `StudentEmail`, `CollegeDepartment`.
- **Potentially sensitive free text:** `Description` (users may still enter personal details despite UI warning).
- **Image uploads:** May contain identifying information; stored as file paths in DB and binary files on disk under `uploads/`.
