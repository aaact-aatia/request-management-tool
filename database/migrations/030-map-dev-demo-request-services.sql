SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

START TRANSACTION;

UPDATE tbltriage
SET
    catalogueid = CASE requestid
        WHEN 'REQ-DEV-2026-001' THEN 2
        WHEN 'REQ-DEV-2026-002' THEN 2
        WHEN 'REQ-DEV-2026-003' THEN 2
        WHEN 'REQ-DEV-2026-004' THEN 1
        WHEN 'REQ-DEV-2026-005' THEN 1
        WHEN 'REQ-DEV-2026-006' THEN 1
        WHEN 'REQ-DEV-2026-007' THEN 2
        WHEN 'REQ-DEV-2026-008' THEN 1
        WHEN 'REQ-DEV-2026-009' THEN 1
        WHEN 'REQ-DEV-2026-010' THEN 1
        WHEN 'REQ-DEV-2026-012' THEN 1
    END,
    serviceid = CASE requestid
        WHEN 'REQ-DEV-2026-001' THEN 8
        WHEN 'REQ-DEV-2026-002' THEN 8
        WHEN 'REQ-DEV-2026-003' THEN 12
        WHEN 'REQ-DEV-2026-004' THEN 5
        WHEN 'REQ-DEV-2026-005' THEN 3
        WHEN 'REQ-DEV-2026-006' THEN 5
        WHEN 'REQ-DEV-2026-007' THEN 8
        WHEN 'REQ-DEV-2026-008' THEN 1
        WHEN 'REQ-DEV-2026-009' THEN 1
        WHEN 'REQ-DEV-2026-010' THEN 1
        WHEN 'REQ-DEV-2026-012' THEN 1
    END,
    subserviceid = 0
WHERE requestid IN (
    'REQ-DEV-2026-001', 'REQ-DEV-2026-002', 'REQ-DEV-2026-003',
    'REQ-DEV-2026-004', 'REQ-DEV-2026-005', 'REQ-DEV-2026-006',
    'REQ-DEV-2026-007', 'REQ-DEV-2026-008', 'REQ-DEV-2026-009',
    'REQ-DEV-2026-010', 'REQ-DEV-2026-012'
);

UPDATE tbltriage t
INNER JOIN tblcatalogue c ON c.id = t.catalogueid
INNER JOIN tblservices s
    ON s.id = t.serviceid
   AND s.catalogueid = t.catalogueid
SET
    t.cataloguenameen = c.nameen,
    t.cataloguenamefr = c.namefr,
    t.servicenameen = s.nameen,
    t.servicenamefr = s.namefr,
    t.subservicenameen = NULL,
    t.subservicenamefr = NULL
WHERE t.requestid IN (
    'REQ-DEV-2026-001', 'REQ-DEV-2026-002', 'REQ-DEV-2026-003',
    'REQ-DEV-2026-004', 'REQ-DEV-2026-005', 'REQ-DEV-2026-006',
    'REQ-DEV-2026-007', 'REQ-DEV-2026-008', 'REQ-DEV-2026-009',
    'REQ-DEV-2026-010', 'REQ-DEV-2026-012'
);

COMMIT;