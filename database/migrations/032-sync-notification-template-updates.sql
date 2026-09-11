-- Auto-generated app-wide default notification templates
-- Generated from docs/notification-templates.md on 2026-09-11 19:33:15

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblnotificationtemplates`
  (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`, `subject`, `body`, `status`)
VALUES
(0, 0, 0, 'client', 'request_created', 'en',
 'Your accessibility request {{requestid}} has been received',
 'Hello {{client_fname}} {{client_lname}},\n\nYour accessibility request {{requestid}} has been received.\n\nWe will review it and contact you if more information is needed.\n\nRequest title: {{requesttitle}}\n\nThank you very much,\n{{teamname}}\n{{teamemail}}\nAccessibility, Accommodation and Adaptive Computer Technology (AAACT)\nDigital Transformation Canada',
 1),

(0, 0, 0, 'client', 'request_created', 'fr',
 'Votre demande d\'accessibilité {{requestid}} a été reçue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d\'accessibilité {{requestid}} a été reçue.\n\nNous l\'examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.\n\nTitre de la demande : {{requesttitle}}\n\nMerci beaucoup,\n{{teamname}}\n{{teamemail}}\nAccessibilité, adaptation et technologie informatique adaptée (AATIA)\nTransformation numérique Canada',
 1),

(0, 0, 0, 'client', 'resolved', 'en',
 'Your accessibility request {{requestid}} has been resolved',
 'Hello {{client_fname}} {{client_lname}},\n\nYour request {{requestid}} has been resolved.\n\nIf you believe more work is required, reply to this message.\n\nWe would love to hear how we did. Please fill out this short survey: {{survey_link_en}}\n\nThank you very much,\n{{teamname}}\n{{teamemail}}\nAccessibility, Accommodation and Adaptive Computer Technology (AAACT)\nDigital Transformation Canada',
 1),

(0, 0, 0, 'client', 'resolved', 'fr',
 'Votre demande d\'accessibilité {{requestid}} a été résolue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a été résolue.\n\nSi vous croyez que d\'autres travaux sont nécessaires, répondez à ce message.\n\nNous aimerions savoir comment s\'est déroulée votre expérience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nMerci beaucoup,\n{{teamname}}\n{{teamemail}}\nAccessibilité, adaptation et technologie informatique adaptée (AATIA)\nTransformation numérique Canada',
 1),

(0, 0, 0, 'employee', 'request_created', 'en',
 'New accessibility request {{requestid}} assigned to your team',
 'A new accessibility request {{requestid}} has been assigned to your team.\n\nRequest title: {{requesttitle}}\nCatalogue: {{catalogue_name}}\nService: {{service_name}}\n\nView request: {{url}}\n\nRequest Management Tool (RMT)',
 1),

(0, 0, 0, 'employee', 'request_created', 'fr',
 'Nouvelle demande d\'accessibilité {{requestid}} assignée à votre équipe',
 'Une nouvelle demande d\'accessibilité {{requestid}} a été assignée à votre équipe.\n\nTitre de la demande : {{requesttitle}}\nCatalogue : {{catalogue_name}}\nService : {{service_name}}\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)',
 1),

(0, 0, 0, 'employee', 'reassigned', 'en',
 'Accessibility request {{requestid}} assigned to you',
 'Accessibility request {{requestid}} has been assigned to you by {{assigned_by}}.\n\nReview the request context and confirm ownership with your team.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)',
 1),

(0, 0, 0, 'employee', 'reassigned', 'fr',
 'Demande d\'accessibilité {{requestid}} vous a été attribuée',
 'La demande d\'accessibilité {{requestid}} vous a été attribuée par {{assigned_by}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre équipe.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)',
 1),

(0, 0, 0, 'employee', 'resolved', 'en',
 'Accessibility request {{requestid}} marked as resolved',
 'Accessibility request {{requestid}} has been marked as resolved.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)',
 1),

(0, 0, 0, 'employee', 'resolved', 'fr',
 'Demande d\'accessibilité {{requestid}} marquée comme résolue',
 'La demande d\'accessibilité {{requestid}} a été marquée comme résolue.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)',
 1),

(0, 0, 0, 'employee', 'status_changed', 'en',
 'Status update for accessibility request {{requestid}}',
 'The status of request {{requestid}} has changed to {{status_label}}.\n\nPlease review the latest details using the request link below.\n\nView request: {{url}}\n\nRequest Management Tool (RMT)',
 1),

(0, 0, 0, 'employee', 'status_changed', 'fr',
 'Mise à jour du statut de la demande {{requestid}}',
 'Le statut de la demande {{requestid}} a changé pour {{status_label}}.\n\nVeuillez consulter les derniers détails en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes (OGD)',
 1)
ON DUPLICATE KEY UPDATE
  `subject` = VALUES(`subject`),
  `body` = VALUES(`body`),
  `status` = 1;
