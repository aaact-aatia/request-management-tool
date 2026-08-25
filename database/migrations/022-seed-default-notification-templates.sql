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
 'Hello {{client_fname}} {{client_lname}},\n\nYour accessibility request {{requestid}} has been received.\n\nWe will review it and contact you if more information is needed.\n\nRequest title: {{requesttitle}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'client', 'request_created', 'fr',
 'Votre demande d''accessibilite {{requestid}} a ete recue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d''accessibilite {{requestid}} a ete recue.\n\nNous l''examinerons et nous communiquerons avec vous si des renseignements supplementaires sont necessaires.\n\nTitre de la demande : {{requesttitle}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1),

(0, 0, 0, 'client', 'resolved', 'en',
 'Your accessibility request {{requestid}} has been resolved',
 'Hello {{client_fname}} {{client_lname}},\n\nYour request {{requestid}} has been resolved.\n\nIf you believe more work is required, reply to this message and reference your request number.\n\nWe would love to hear how we did. Please fill out this short survey: {{survey_link_en}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'client', 'resolved', 'fr',
 'Votre demande d''accessibilite {{requestid}} a ete resolue',
 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a ete resolue.\n\nSi vous croyez que d''autres travaux sont necessaires, repondez a ce message et mentionnez votre numero de demande.\n\nNous aimerions savoir comment s''est deroulee votre experience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'request_created', 'en',
 'New accessibility request {{requestid}} assigned to your team',
 'Hello {{teamname}},\n\nA new accessibility request {{requestid}} has been assigned to your team.\n\nRequest title: {{requesttitle}}\nCatalogue: {{catalogue_name}}\nService: {{service_name}}\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'request_created', 'fr',
 'Nouvelle demande d''accessibilite {{requestid}} assignee a votre equipe',
 'Bonjour {{teamname}},\n\nUne nouvelle demande d''accessibilite {{requestid}} a ete assignee a votre equipe.\n\nTitre de la demande : {{requesttitle}}\nCatalogue : {{catalogue_name}}\nService : {{service_name}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'status_changed', 'en',
 'Status update for accessibility request {{requestid}}',
 'Hello {{teamname}},\n\nThe status of request {{requestid}} has changed to {{status_label}}.\n\nPlease review the latest details using the request link below.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'status_changed', 'fr',
 'Mise a jour du statut de la demande {{requestid}}',
 'Bonjour {{teamname}},\n\nLe statut de la demande {{requestid}} a change pour {{status_label}}.\n\nVeuillez consulter les derniers details en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'reassigned', 'en',
 'Accessibility request {{requestid}} reassigned to {{teamname}}',
 'Hello {{teamname}},\n\nAccessibility request {{requestid}} has been reassigned to {{teamname}}.\n\nReview the request context and confirm ownership with your team.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'reassigned', 'fr',
 'Demande d''accessibilite {{requestid}} reattribuee a {{teamname}}',
 'Bonjour {{teamname}},\n\nLa demande d''accessibilite {{requestid}} a ete reattribuee a {{teamname}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre equipe.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'resolved', 'en',
 'Accessibility request {{requestid}} marked as resolved',
 'Hello {{teamname}},\n\nAccessibility request {{requestid}} has been marked as resolved.\n\nEnsure any final records or follow-up actions are complete.\n\nView request: {{url}}\n\nThank you very much,\n{{teamname}}',
 1),

(0, 0, 0, 'employee', 'resolved', 'fr',
 'Demande d''accessibilite {{requestid}} marquee comme resolue',
 'Bonjour {{teamname}},\n\nLa demande d''accessibilite {{requestid}} a ete marquee comme resolue.\n\nAssurez-vous que les dossiers finaux et les actions de suivi sont complets.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}',
 1);

