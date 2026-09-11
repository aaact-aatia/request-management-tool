-- Seed starting content for the global default notification templates (team_id=0, service_id=0,
-- subservice_id=0), so the admin UI shows editable defaults instead of blank/built-in text.
-- Uses INSERT IGNORE so it never overwrites rows an admin has already customized.
-- Salutation/signature text is written out directly (not the {{salutation}}/{{signature}} tokens)
-- so admins can edit the greeting and sign-off wording per template.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT IGNORE INTO `tblnotificationtemplates`
  (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`, `subject`, `body`, `status`)
VALUES
(0, 0, 0, 'client', 'request_created', 'en',
 'Your accessibility request {{requestid}} has been received',
 'Hello {{client_fname}} {{client_lname}},\n\nYour accessibility request {{requestid}} has been received.\n\nWe will review it and contact you if more information is needed.\n\nRequest title: {{requesttitle}}\n\nThank you very much,\n{{teamname}}\n{{teamemail}}\nAccessibility, Accommodation and Adaptive Computer Technology (AAACT)\nDigital Transformation Canada',
 1),

(0, 0, 0, 'client', 'request_created', 'fr',
 'Votre demande d''accessibilité {{requestid}} a été reçue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d''accessibilité {{requestid}} a été reçue.\n\nNous l''examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.\n\nTitre de la demande : {{requesttitle}}\n\nMerci beaucoup,\n{{teamname}}\n{{teamemail}}\nAccessibilité, adaptation et technologie informatique adaptée (AATIA)\nTransformation numérique Canada',
 1),

(0, 0, 0, 'client', 'resolved', 'en',
 'Your accessibility request {{requestid}} has been resolved',
 'Hello {{client_fname}} {{client_lname}},\n\nYour request {{requestid}} has been resolved.\n\nIf you believe more work is required, reply to this message and reference your request number.\n\nWe would love to hear how we did. Please fill out this short survey: {{survey_link_en}}\n\nThank you very much,\n{{teamname}}\n{{teamemail}}\nAccessibility, Accommodation and Adaptive Computer Technology (AAACT)\nDigital Transformation Canada',
 1),

(0, 0, 0, 'client', 'resolved', 'fr',
 'Votre demande d''accessibilité {{requestid}} a été résolue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a été résolue.\n\nSi vous croyez que d''autres travaux sont nécessaires, répondez à ce message et mentionnez votre numéro de demande.\n\nNous aimerions savoir comment s''est déroulée votre expérience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nMerci beaucoup,\n{{teamname}}\n{{teamemail}}\nAccessibilité, adaptation et technologie informatique adaptée (AATIA)\nTransformation numérique Canada',
 1),

(0, 0, 0, 'employee', 'request_created', 'en',
 'New accessibility request {{requestid}} assigned to your team',
 'A new accessibility request {{requestid}} has been assigned to your team.\n\nRequest title: {{requesttitle}}\nCatalogue: {{catalogue_name}}\nService: {{service_name}}\n\nView request: {{url}}\n\nRequest Management Tool\nIT Accessibility Office',
 1),

(0, 0, 0, 'employee', 'request_created', 'fr',
 'Nouvelle demande d''accessibilité {{requestid}} assignée à votre équipe',
 'Une nouvelle demande d''accessibilité {{requestid}} a été assignée à votre équipe.\n\nTitre de la demande : {{requesttitle}}\nCatalogue : {{catalogue_name}}\nService : {{service_name}}\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes\nBureau de l''accessibilité de la TI',
 1),

(0, 0, 0, 'employee', 'status_changed', 'en',
 'Status update for accessibility request {{requestid}}',
 'The status of request {{requestid}} has changed to {{status_label}}.\n\nPlease review the latest details using the request link below.\n\nView request: {{url}}\n\nRequest Management Tool\nIT Accessibility Office',
 1),

(0, 0, 0, 'employee', 'status_changed', 'fr',
 'Mise à jour du statut de la demande {{requestid}}',
 'Le statut de la demande {{requestid}} a changé pour {{status_label}}.\n\nVeuillez consulter les derniers détails en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes\nBureau de l''accessibilité de la TI',
 1),

(0, 0, 0, 'employee', 'reassigned', 'en',
 'Accessibility request {{requestid}} assigned to {{teamname}}',
 'Accessibility request {{requestid}} has been assigned to {{teamname}}.\n\nReview the request context and confirm ownership with your team.\n\nView request: {{url}}\n\nRequest Management Tool\nIT Accessibility Office',
 1),

(0, 0, 0, 'employee', 'reassigned', 'fr',
 'Demande d''accessibilité {{requestid}} assignée à {{teamname}}',
 'La demande d''accessibilité {{requestid}} a été assignée à {{teamname}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre équipe.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes\nBureau de l''accessibilité de la TI',
 1),

(0, 0, 0, 'employee', 'resolved', 'en',
 'Accessibility request {{requestid}} marked as resolved',
 'Accessibility request {{requestid}} has been marked as resolved.\n\nEnsure any final records or follow-up actions are complete.\n\nView request: {{url}}\n\nRequest Management Tool\nIT Accessibility Office',
 1),

(0, 0, 0, 'employee', 'resolved', 'fr',
 'Demande d''accessibilité {{requestid}} marquée comme résolue',
 'La demande d''accessibilité {{requestid}} a été marquée comme résolue.\n\nAssurez-vous que les dossiers finaux et les actions de suivi sont complets.\n\nVoir la demande : {{url}}\n\nOutil de gestion des demandes\nBureau de l''accessibilité de la TI',
 1);

