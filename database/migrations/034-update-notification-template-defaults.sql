-- Follow-up notification template defaults after migration 033.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblnotificationtemplates`
  (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`, `subject`, `body`, `status`)
VALUES
(0, 0, 0, 'employee', 'details_updated', 'en',
 'Accessibility request {{requestid}} assigned to one of your staff',
 '{{requesttitle}} has been assigned to {{assignee}} by {{assigned_by}}.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)', 1),
(0, 0, 0, 'employee', 'status_changed', 'en',
 'Status update for {{requesttitle}}',
 'The status of {{requesttitle}} has changed from {{status_from}} to {{status_to}} by {{changed_by}}.\n\nPlease review the latest details using the request link below.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)', 1),
(0, 0, 0, 'employee', 'status_changed', 'fr',
 'Mise à jour du statut de la demande {{requesttitle}}',
 'Le statut de {{requesttitle}} est passé de {{status_from}} à {{status_to}}, par {{changed_by}}.\n\nVeuillez consulter les derniers détails en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)', 1),
(0, 0, 0, 'employee', 'ownership_changed', 'en',
 'Responsible team updated for accessibility request {{requestid}}',
 'The responsible team for accessibility request {{requestid}} has been updated.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)', 1),
(0, 0, 0, 'employee', 'ownership_changed', 'fr',
 'Équipe responsable mise à jour pour la demande d\'accessibilité {{requestid}}',
 'L\'équipe responsable de la demande d\'accessibilité {{requestid}} a été mise à jour.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)', 1)
ON DUPLICATE KEY UPDATE
  `subject` = VALUES(`subject`),
  `body` = VALUES(`body`),
  `status` = 1;