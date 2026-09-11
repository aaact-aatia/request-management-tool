-- Change visible reassignment notification wording to "assigned".
-- Guarded by the previous default subjects so custom templates are preserved.
-- The internal event key remains `reassigned` for routing and notification settings.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `subject` = 'Accessibility request {{requestid}} assigned to {{teamname}}',
    `body` = 'Hello {{teamname}},\n\nAccessibility request {{requestid}} has been assigned to {{teamname}}.\n\nReview the request context and confirm ownership with your team.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}\n{{teamemail}}\nAccessibility, Accommodation and Adaptive Computer Technology (AAACT)\nDigital Transformation Canada'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'reassigned' AND `language` = 'en'
  AND `subject` = 'Accessibility request {{requestid}} reassigned to {{teamname}}';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Demande d''accessibilité {{requestid}} assignée à {{teamname}}',
    `body` = 'Bonjour {{teamname}},\n\nLa demande d''accessibilité {{requestid}} a été assignée à {{teamname}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre équipe.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}\n{{teamemail}}\nAccessibilité, adaptation et technologie informatique adaptée (AATIA)\nTransformation numérique Canada'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'reassigned' AND `language` = 'fr'
  AND `subject` = 'Demande d''accessibilité {{requestid}} réattribuée à {{teamname}}';
