-- Inline the salutation/signature text into the global default templates instead of the
-- {{salutation}}/{{signature}} tokens, so admins can edit the greeting and sign-off directly.
-- Guarded to only touch rows that still contain the original tokens (never overwrites edits).

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{client_fname}} {{client_lname}},\n\nYour accessibility request {{requestid}} has been received.\n\nWe will review it and contact you if more information is needed.\n\nRequest title: {{requesttitle}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'client' AND `event` = 'request_created' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d''accessibilite {{requestid}} a ete recue.\n\nNous l''examinerons et nous communiquerons avec vous si des renseignements supplementaires sont necessaires.\n\nTitre de la demande : {{requesttitle}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'client' AND `event` = 'request_created' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{client_fname}} {{client_lname}},\n\nYour request {{requestid}} has been resolved.\n\nIf you believe more work is required, reply to this message and reference your request number.\n\nWe would love to hear how we did. Please fill out this short survey: {{survey_link_en}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'client' AND `event` = 'resolved' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a ete resolue.\n\nSi vous croyez que d''autres travaux sont necessaires, repondez a ce message et mentionnez votre numero de demande.\n\nNous aimerions savoir comment s''est deroulee votre experience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'client' AND `event` = 'resolved' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{teamname}},\n\nA new accessibility request {{requestid}} has been assigned to your team.\n\nRequest title: {{requesttitle}}\nCatalogue: {{catalogue_name}}\nService: {{service_name}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'request_created' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{teamname}},\n\nUne nouvelle demande d''accessibilite {{requestid}} a ete assignee a votre equipe.\n\nTitre de la demande : {{requesttitle}}\nCatalogue : {{catalogue_name}}\nService : {{service_name}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'request_created' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{teamname}},\n\nThe status of request {{requestid}} has changed to {{status_label}}.\n\nPlease review the latest details using the request link below.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'status_changed' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{teamname}},\n\nLe statut de la demande {{requestid}} a change pour {{status_label}}.\n\nVeuillez consulter les derniers details en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'status_changed' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{teamname}},\n\nAccessibility request {{requestid}} has been reassigned to {{teamname}}.\n\nReview the request context and confirm ownership with your team.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'reassigned' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{teamname}},\n\nLa demande d''accessibilite {{requestid}} a ete reattribuee a {{teamname}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre equipe.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'reassigned' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Hello {{teamname}},\n\nAccessibility request {{requestid}} has been marked as resolved.\n\nEnsure any final records or follow-up actions are complete.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'resolved' AND `language` = 'en'
  AND `body` LIKE '%{{salutation}}%';

UPDATE `tblnotificationtemplates`
SET `body` = 'Bonjour {{teamname}},\n\nLa demande d''accessibilite {{requestid}} a ete marquee comme resolue.\n\nAssurez-vous que les dossiers finaux et les actions de suivi sont complets.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0 AND `audience` = 'employee' AND `event` = 'resolved' AND `language` = 'fr'
  AND `body` LIKE '%{{salutation}}%';
