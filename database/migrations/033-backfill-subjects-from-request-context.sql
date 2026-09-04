SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

START TRANSACTION;

UPDATE tbltriage request
LEFT JOIN tblcatalogue catalogue
    ON catalogue.id = request.catalogueid
LEFT JOIN tblservices service
    ON service.id = request.serviceid
   AND service.catalogueid = request.catalogueid
SET request.request_subject = CASE
    WHEN request.requestlang = 'fr' AND service.namefr IS NOT NULL AND TRIM(service.namefr) <> '' THEN service.namefr
    WHEN service.nameen IS NOT NULL AND TRIM(service.nameen) <> '' THEN service.nameen
    WHEN request.requestlang = 'fr' AND catalogue.namefr IS NOT NULL AND TRIM(catalogue.namefr) <> '' THEN catalogue.namefr
    WHEN catalogue.nameen IS NOT NULL AND TRIM(catalogue.nameen) <> '' THEN catalogue.nameen
    WHEN request.requestlang = 'fr' THEN 'Demande générale d’accessibilité'
    ELSE 'General accessibility request'
END
WHERE (request.request_subject IS NULL OR TRIM(request.request_subject) = '')
  AND (request.title IS NULL OR TRIM(request.title) = '');

COMMIT;