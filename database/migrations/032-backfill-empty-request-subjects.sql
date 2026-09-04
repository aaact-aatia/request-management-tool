SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

START TRANSACTION;

UPDATE tbltriage
SET request_subject = TRIM(title)
WHERE (request_subject IS NULL OR TRIM(request_subject) = '')
  AND title IS NOT NULL
  AND TRIM(title) <> '';

COMMIT;