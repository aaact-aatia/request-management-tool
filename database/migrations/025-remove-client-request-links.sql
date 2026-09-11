-- Remove request-view links from app-wide client notification defaults.
-- Guarded by the current default subjects so custom edits are preserved.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{client_fname}} {{client_lname}},\n\nYour accessibility request {{requestid}} has been received.\n\nWe will review it and contact you if more information is needed.\n\nRequest title: {{requesttitle}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'request_created' AND `language` = 'en'
  AND `subject` = 'Your accessibility request {{requestid}} has been received';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d''accessibilité {{requestid}} a été reçue.\n\nNous l''examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.\n\nTitre de la demande : {{requesttitle}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'request_created' AND `language` = 'fr'
  AND `subject` = 'Votre demande d''accessibilité {{requestid}} a été reçue';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{client_fname}} {{client_lname}},\n\nYour request {{requestid}} has been resolved.\n\nIf you believe more work is required, reply to this message and reference your request number.\n\nWe would love to hear how we did. Please fill out this short survey: {{survey_link_en}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'resolved' AND `language` = 'en'
  AND `subject` = 'Your accessibility request {{requestid}} has been resolved';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a été résolue.\n\nSi vous croyez que d''autres travaux sont nécessaires, répondez à ce message et mentionnez votre numéro de demande.\n\nNous aimerions savoir comment s''est déroulée votre expérience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'resolved' AND `language` = 'fr'
  AND `subject` = 'Votre demande d''accessibilité {{requestid}} a été résolue';
