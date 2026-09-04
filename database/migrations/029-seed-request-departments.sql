SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO tblcommlog (triageid, dateadded, notes, creatorid, status)
SELECT
    request.id,
    COALESCE(request.datereceived, CURRENT_DATE),
    CASE
        WHEN request.requestlang = 'fr' THEN CONCAT('Ministère/organisme: ', department.namefr)
        ELSE CONCAT('Department/agency: ', department.nameen)
    END,
    COALESCE(request.creatorid, 0),
    1
FROM tbltriage request
LEFT JOIN (
    SELECT DISTINCT triageid
    FROM tblcommlog
    WHERE status = 1
      AND (notes LIKE 'Department/agency:%' OR notes LIKE 'Ministère/organisme:%')
) existing_department ON existing_department.triageid = request.id
INNER JOIN tblorganizations department
    ON department.status = 1
   AND department.id = 1 + MOD(CRC32(CONCAT('rmt-demo-department-', request.id)), 8)
WHERE existing_department.triageid IS NULL;