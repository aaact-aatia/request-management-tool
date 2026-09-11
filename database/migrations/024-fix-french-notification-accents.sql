-- Restore French accents in untouched app-wide default notification templates.
-- Each update is guarded by the original unaccented subject/body so custom edits are preserved.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `subject` = 'Votre demande d''accessibilité {{requestid}} a été reçue',
    `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande d''accessibilité {{requestid}} a été reçue.\n\nNous l''examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.\n\nTitre de la demande : {{requesttitle}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'request_created' AND `language` = 'fr'
  AND `subject` = 'Votre demande d''accessibilite {{requestid}} a ete recue';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Votre demande d''accessibilité {{requestid}} a été résolue',
    `body` = 'Bonjour {{client_fname}} {{client_lname}},\n\nVotre demande {{requestid}} a été résolue.\n\nSi vous croyez que d''autres travaux sont nécessaires, répondez à ce message et mentionnez votre numéro de demande.\n\nNous aimerions savoir comment s''est déroulée votre expérience. Veuillez remplir ce court sondage : {{survey_link_fr}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'client' AND `event` = 'resolved' AND `language` = 'fr'
  AND `subject` = 'Votre demande d''accessibilite {{requestid}} a ete resolue';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Nouvelle demande d''accessibilité {{requestid}} assignée à votre équipe',
    `body` = 'Bonjour {{teamname}},\n\nUne nouvelle demande d''accessibilité {{requestid}} a été assignée à votre équipe.\n\nTitre de la demande : {{requesttitle}}\nCatalogue : {{catalogue_name}}\nService : {{service_name}}\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'request_created' AND `language` = 'fr'
  AND `subject` = 'Nouvelle demande d''accessibilite {{requestid}} assignee a votre equipe';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Mise à jour du statut de la demande {{requestid}}',
    `body` = 'Bonjour {{teamname}},\n\nLe statut de la demande {{requestid}} a changé pour {{status_label}}.\n\nVeuillez consulter les derniers détails en utilisant le lien ci-dessous.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'status_changed' AND `language` = 'fr'
  AND `subject` = 'Mise a jour du statut de la demande {{requestid}}';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Demande d''accessibilité {{requestid}} réattribuée à {{teamname}}',
    `body` = 'Bonjour {{teamname}},\n\nLa demande d''accessibilité {{requestid}} a été réattribuée à {{teamname}}.\n\nExaminez le contexte de la demande et confirmez la prise en charge avec votre équipe.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'reassigned' AND `language` = 'fr'
  AND `subject` = 'Demande d''accessibilite {{requestid}} reattribuee a {{teamname}}';

UPDATE `tblnotificationtemplates`
SET `subject` = 'Demande d''accessibilité {{requestid}} marquée comme résolue',
    `body` = 'Bonjour {{teamname}},\n\nLa demande d''accessibilité {{requestid}} a été marquée comme résolue.\n\nAssurez-vous que les dossiers finaux et les actions de suivi sont complets.\n\nVoir la demande : {{url}}\n\nMerci beaucoup,\n{{teamname}}'
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `audience` = 'employee' AND `event` = 'resolved' AND `language` = 'fr'
  AND `subject` = 'Demande d''accessibilite {{requestid}} marquee comme resolue';
