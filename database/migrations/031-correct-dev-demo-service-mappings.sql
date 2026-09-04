SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

START TRANSACTION;

UPDATE tbltriage request
INNER JOIN tblcatalogue catalogue
    ON catalogue.nameen = 'Advisory services'
INNER JOIN tblservices service
    ON service.catalogueid = catalogue.id
   AND service.nameen = 'Web accessibility'
SET
    request.catalogueid = catalogue.id,
    request.serviceid = service.id,
    request.subserviceid = 0,
    request.cataloguenameen = catalogue.nameen,
    request.cataloguenamefr = catalogue.namefr,
    request.servicenameen = service.nameen,
    request.servicenamefr = service.namefr,
    request.subservicenameen = NULL,
    request.subservicenamefr = NULL
WHERE request.requestid IN ('REQ-DEV-2026-001', 'REQ-DEV-2026-002');

UPDATE tbltriage request
INNER JOIN tblcatalogue catalogue
    ON catalogue.nameen = 'Advisory services'
INNER JOIN tblservices service
    ON service.catalogueid = catalogue.id
   AND service.nameen = 'Document accessibility'
SET
    request.catalogueid = catalogue.id,
    request.serviceid = service.id,
    request.subserviceid = 0,
    request.cataloguenameen = catalogue.nameen,
    request.cataloguenamefr = catalogue.namefr,
    request.servicenameen = service.nameen,
    request.servicenamefr = service.namefr,
    request.subservicenameen = NULL,
    request.subservicenamefr = NULL
WHERE request.requestid = 'REQ-DEV-2026-007';

COMMIT;