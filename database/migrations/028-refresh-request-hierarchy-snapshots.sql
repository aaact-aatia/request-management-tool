START TRANSACTION;

UPDATE tbltriage t
LEFT JOIN tblcatalogue c
    ON c.id = t.catalogueid
LEFT JOIN tblservices s
    ON s.id = t.serviceid
   AND s.catalogueid = t.catalogueid
LEFT JOIN tblsubservices ss
    ON ss.id = t.subserviceid
   AND ss.serviceid = t.serviceid
SET
    t.cataloguenameen = c.nameen,
    t.cataloguenamefr = c.namefr,
    t.servicenameen = s.nameen,
    t.servicenamefr = s.namefr,
    t.subservicenameen = ss.nameen,
    t.subservicenamefr = ss.namefr
WHERE t.catalogueid IS NOT NULL
   OR t.serviceid IS NOT NULL
   OR t.subserviceid IS NOT NULL;

COMMIT;