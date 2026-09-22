-- App-wide default templates for the client survey completed notification.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblnotificationtemplates`
  (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`, `subject`, `body`, `status`)
VALUES
(0, 0, 0, 'employee', 'survey_completed', 'en',
 'Survey response received for {{requesttitle}}',
 'A client has completed the satisfaction survey for accessibility request {{requestid}}.\n\nOverall satisfaction: {{survey_overall}} out of 10\nResponse time: {{survey_response}} out of 10\n\nView request: {{url}}\n\nRequest Management Tool (RMT)', 1),
(0, 0, 0, 'employee', 'survey_completed', 'fr',
 'Réponse au sondage reçue pour {{requesttitle}}',
 'Un client a rempli le sondage de satisfaction pour la demande d\'accessibilité {{requestid}}.\n\nSatisfaction globale : {{survey_overall}} sur 10\nDélai de réponse : {{survey_response}} sur 10\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)', 1)
ON DUPLICATE KEY UPDATE
  `subject` = VALUES(`subject`),
  `body` = VALUES(`body`),
  `status` = 1;
