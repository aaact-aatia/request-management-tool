-- Remove the literal backslash from the French accessibility catalogue label.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblcatalogue`
SET `namefr` = REPLACE(`namefr`, '\\''', '''')
WHERE `namefr` LIKE '%\\\\''%';