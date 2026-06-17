-- RMT Reference Data
-- Production-safe lookup/configuration data required by the app

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Account types
INSERT INTO `tblaccounttype` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'Super Admin', 'Super administrateur', 1),
(2, 'Admin', 'Administrateur', 1),
(3, 'Manager', 'Gestionnaire', 1),
(4, 'Team Lead', 'Chef d''équipe', 1),
(5, 'Employee', 'Employé', 1),
(6, 'External', 'Externe', 1);

-- Catalogue (matching openrequest.php options)
INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'Accessibility Compliance Project (ACP)', 'Projet de conformité d''accessibilité (PCA)', 0),
(2, 'Accessibility coaching / session', 'Coaching / session en accessibilité', 1),
(3, 'Advice and recommendations', 'Conseils et recommandations', 1),
(4, 'Adaptive technology support', 'Soutien en technologie adaptée', 1),
(5, 'Client''s need assessment', 'Évaluation des besoins du client', 1),
(6, 'Document accessibility audits', 'Audits d''accessibilité des documents', 1),
(7, 'Enterprise Project Management Office (EPMO)', 'Bureau de gestion de projet d''entreprise (BGPE)', 1),
(8, 'Accessibility audit (assessments)', 'Audit d''accessibilité (évaluations)', 1),
(9, 'Loan bank', 'Banque de prêts', 1),
(10, 'Procurement', 'Approvisionnement', 1),
(11, 'Accessibility testing tools', 'Outils de test d''accessibilité', 1);

-- Services for Catalogue #2 (Accessibility coaching)
-- Services for Catalogue #2 (Accessibility coaching) - legacy entry, superseded by ID 33
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(1, 2, 'Accessibility learning curriculum', 'Programme d''apprentissage en accessibilité', 5, 0),
(2, 2, 'ICT developer coaching', 'Coaching pour développeurs TIC', 5, 1),
(3, 2, 'Microsoft document and email coaching', 'Coaching pour documents Microsoft et courriels', 5, 1),
(4, 2, 'PDF documents coaching', 'Coaching pour documents PDF', 5, 1);

-- Services for Catalogue #3 (Advice and recommendations) - legacy entries, superseded by IDs 34, 45, 46
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(5, 3, 'Adaptive technologies', 'Technologies adaptatives', 3, 0),
(6, 3, 'ICT design and development (including documents)', 'Conception et développement TIC (y compris documents)', 3, 0),
(7, 3, 'Planning inclusive events', 'Planification d''événements inclusifs', 3, 0);

-- Services for Catalogue #4 (Adaptive technology) - legacy entries; all superseded by IDs 15, 55-60, 111-118
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(8, 4, 'JAWS', 'JAWS', 5, 0),
(9, 4, 'ZoomText for Windows', 'ZoomText pour Windows', 5, 0),
(10, 4, 'Dragon NaturallySpeaking (Professional Edition)', 'Dragon NaturallySpeaking (Édition professionnelle)', 5, 0);

-- Services for Catalogue #8 (Accessibility audit) - legacy entries superseded by IDs 27, 28, 66
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(11, 8, 'Software applications', 'Applications logicielles', 10, 0),
(12, 8, 'Websites / web applications', 'Sites Web / applications Web', 10, 0),
(13, 8, 'Audit report question(s)', 'Question(s) sur le rapport d''audit', 10, 0);

-- Services for Catalogue #11 (Testing tools) - legacy entry, superseded by ID 53
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(14, 11, 'Colour Contrast Analyzer', 'Analyseur de contraste des couleurs', 2, 0);

-- Active services for Catalogue #3 (Advice and recommendations)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(34, 3, 'ICT design and development (including documents)', 'Conception et développement TIC (y compris documents)', 3, 1);

-- Active services for Catalogue #4 (Adaptive technology) - mapped by openrequest2.php softwareMap
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(15, 4, 'Dragon Medical Practice', 'Dragon Medical Practice', 5, 1),
(55, 4, 'Dragon NaturallySpeaking (Professional Edition)', 'Dragon NaturallySpeaking (Édition professionnelle)', 5, 1),
(56, 4, 'J-Say', 'J-Say', 5, 1),
(57, 4, 'JAWS', 'JAWS', 5, 1),
(58, 4, 'Kurzweil 3000', 'Kurzweil 3000', 5, 1),
(59, 4, 'TextAloud', 'TextAloud', 5, 1),
(60, 4, 'wordQ & speakQ', 'wordQ & speakQ', 5, 1),
(111, 4, 'OpenBook', 'OpenBook', 5, 1),
(112, 4, 'ZoomText for Windows', 'ZoomText pour Windows', 5, 1),
(113, 4, 'Interact AS', 'Interact AS', 5, 1),
(114, 4, 'Interact streamer', 'Interact streamer', 5, 1),
(115, 4, 'NVDA', 'NVDA', 5, 1),
(116, 4, 'SuperNova', 'SuperNova', 5, 1),
(117, 4, 'Tint & Track', 'Tint & Track', 5, 1),
(118, 4, 'Pixie', 'Pixie', 5, 1);

-- Services for Catalogue #5 (Needs assessment)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(16, 5, 'Blindness / Low vision', 'Cécité / Basse vision', 10, 1),
(17, 5, 'Cognitive disability', 'Déficience cognitive', 10, 1),
(18, 5, 'Deafness / Hard of hearing', 'Surdité / Malentendant', 10, 1),
(19, 5, 'Mobility', 'Mobilité', 10, 1),
(50, 5, 'Multiple needs', 'Besoins multiples', 10, 1);

-- Services for Catalogue #6 (Document accessibility audits)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(25, 6, 'Microsoft Word documents', 'Documents Microsoft Word', 5, 1),
(61, 6, 'Microsoft Excel documents', 'Documents Microsoft Excel', 5, 1),
(62, 6, 'Microsoft PowerPoint presentations', 'Présentations Microsoft PowerPoint', 5, 1),
(63, 6, 'Emails', 'Courriels', 5, 1),
(64, 6, 'PDF documents', 'Documents PDF', 5, 1),
(65, 6, 'Other document type', 'Autre type de document', 5, 1);

-- Services for Catalogue #7 (EPMO)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(26, 7, 'Project consultation', 'Consultation de projet', 10, 1);

-- Additional services for Catalogue #8 (Accessibility audit)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(27, 8, 'Software applications', 'Applications logicielles', 10, 1),
(28, 8, 'Websites / web applications', 'Sites Web / applications Web', 10, 1),
(51, 8, 'SAMS / OCMC request', 'Demande SAMS / OCMC', 10, 0),
(54, 8, 'SAMS / OCMC request', 'Demande SAMS / OCMC', 10, 1),
(66, 8, 'Audit report question(s)', 'Question(s) sur le rapport d''audit', 10, 1);

-- Services for Catalogue #9 (Loan bank)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(29, 9, 'Adaptive hardware loan', 'Prêt de matériel adaptatif', 3, 1);

-- Services for Catalogue #10 (Procurement)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(30, 10, 'Procurement guidelines or consultation', 'Lignes directrices ou consultation en matière d''approvisionnement', 10, 1),
(31, 10, 'Vendor / Request for proposals (RFP) evaluation', 'Évaluation de fournisseur / Demande de propositions (DP)', 10, 1);

-- Additional services for Catalogue #11 (Testing tools)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(53, 11, 'Colour Contrast Analyzer', 'Analyseur de contraste des couleurs', 2, 1);

-- Services for Catalogue #1 (ACP)
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(32, 1, 'Accessibility Compliance Project (ACP)', 'Projet de conformité d''accessibilité (PCA)', 10, 1);

-- Active services for Catalogue #2 (Accessibility coaching) - mapped by openrequest2.php coachingMap
INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(33, 2, 'ICT developer coaching', 'Coaching pour développeur TIC', 5, 1),
(45, 2, 'Accessibility learning curriculum', 'Programme de formation sur l''accessibilité', 5, 1),
(46, 2, 'ACP development coaching', 'PCA - Coaching en développement d''applications', 5, 0),
(47, 2, 'PDF documents coaching', 'Coaching pour document PDF', 5, 1),
(48, 2, 'Microsoft document and email coaching', 'Coaching pour document Microsoft et courriel', 5, 1);

-- Subservices for ICT design (service #6)
INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(1, 6, 'Courses', 'Cours', 1),
(2, 6, 'Documents', 'Documents', 1),
(3, 6, 'Emails', 'Courriels', 1),
(4, 6, 'Forms', 'Formulaires', 1),
(5, 6, 'Services', 'Services', 1),
(6, 6, 'Testing', 'Tests', 1),
(7, 6, 'Web content', 'Contenu Web', 1);

-- Subservices for Software audit (service #11)
INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(8, 11, 'Audit', 'Audit', 1),
(9, 11, 'Re-audit', 'Ré-audit', 1);

-- Subservices for Website audit (service #12)
INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(10, 12, 'Sprint spot-check', 'Vérification de sprint', 1),
(11, 12, 'Audit of representative sample', 'Audit d''échantillon représentatif', 1),
(12, 12, 'Audit', 'Audit', 1),
(13, 12, 'Re-audit', 'Ré-audit', 1);

-- Subservices for website accessibility audit active path (service #28 - Websites / web applications)
INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(95, 28, 'Sprint spot-check', 'Vérification ponctuelle du sprint', 1),
(96, 28, 'Audit of representative sample', 'Audit d''un échantillon représentatif', 1);

-- Subservices for ICT design advice (service #34 - ICT design and development)
INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(104, 34, 'Forms', 'Formulaires', 1),
(105, 34, 'Courses', 'Cours', 1),
(106, 34, 'Documents', 'Documents', 1),
(107, 34, 'Web content', 'Contenu Web', 1),
(108, 34, 'Services', 'Services', 1),
(109, 34, 'Testing', 'Tests', 1),
(110, 34, 'Emails', 'Courriels', 1);

-- Team assignments for services (contactid: 1=AAACT, 2=Dev Team)
UPDATE `tblservices` SET `contactid` = 2 WHERE `id` IN (27, 28, 54); -- Dev Team: software apps, websites, SAMS
UPDATE `tblservices` SET `contactid` = 1 WHERE `id` IN (13, 66);     -- AAACT: audit report questions

-- Request-first routing: keep only current catalogue/services visible
UPDATE `tblcatalogue`
SET `status` = CASE WHEN `id` IN (3, 6, 8) THEN 1 ELSE 0 END;

UPDATE `tblservices`
SET `status` = CASE
	WHEN `id` IN (34, 25, 61, 62, 63, 64, 65, 27, 28, 66) THEN 1
	ELSE 0
END;

-- Sources (for adaptive tech coaching subservices)
INSERT INTO `tblsources` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'Coaching', 'Coaching', 1),
(2, 'Installation / Removal', 'Installation / Désinstallation', 1),
(3, 'Troubleshooting / Configuration', 'Dépannage / Configuration', 1);

-- Products (Adaptive Technology)
INSERT INTO `tblproducts` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'JAWS (Job Access With Speech)', 'JAWS (Job Access With Speech)', 1),
(2, 'ZoomText', 'ZoomText', 1),
(3, 'Dragon NaturallySpeaking', 'Dragon NaturallySpeaking', 1),
(4, 'Read&Write', 'Read&Write', 1),
(5, 'Kurzweil 3000', 'Kurzweil 3000', 1),
(6, 'ClaroRead', 'ClaroRead', 1),
(7, 'Pixie', 'Pixie', 1),
(8, 'Tint & Track', 'Tint & Track', 1);

-- Status values
INSERT INTO `tblstatus` (`id`, `nameen`, `namefr`, `is_resolved`, `status`) VALUES
(1, 'New', 'Nouveau', 0, 1),
(2, 'In Progress', 'En cours', 0, 1),
(3, 'Pending', 'En attente', 0, 1),
(4, 'Resolved', 'Résolu', 1, 1),
(5, 'Closed', 'Fermé', 0, 1),
(6, 'Cancelled', 'Annulé', 0, 1);

-- Teams
INSERT INTO `tblteams` (`nameen`, `namefr`, `email`, `contactname`, `contactemail`, `escalationcontactname`, `escalationcontactemail`, `status`) VALUES
('IT Accessibility Office', 'Bureau de l''accessibilité des TI', 'accessibility@example.com', 'John Doe', 'john.doe@example.com', 'Jane Manager', 'jane.manager@example.com', 1),
('Development Team', 'Équipe de développement', 'dev.team@example.com', 'Alice Developer', 'alice.dev@example.com', 'Bob Tech Lead', 'bob.techlead@example.com', 1);

-- Canadian Federal Holidays (2019-2030)
INSERT INTO `tblholidays` (`holiday_date`, `name_en`, `name_fr`, `recurring`, `status`) VALUES
('2019-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2019-04-19', 'Good Friday', 'Vendredi saint', 0, 1),
('2019-04-23', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2019-05-20', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2019-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2019-08-05', 'Civic Holiday', 'Congé civique', 0, 1),
('2019-09-02', 'Labour Day', 'Fête du travail', 0, 1),
('2019-10-14', 'Thanksgiving', 'Action de grâces', 0, 1),
('2019-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2019-12-25', 'Christmas Day', 'Noël', 1, 1),
('2019-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2020-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2020-04-10', 'Good Friday', 'Vendredi saint', 0, 1),
('2020-04-14', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2020-05-18', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2020-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2020-08-03', 'Civic Holiday', 'Congé civique', 0, 1),
('2020-09-07', 'Labour Day', 'Fête du travail', 0, 1),
('2020-10-12', 'Thanksgiving', 'Action de grâces', 0, 1),
('2020-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2020-12-25', 'Christmas Day', 'Noël', 1, 1),
('2020-12-30', 'Boxing Day (Observed)', 'Lendemain de Noël (Observé)', 0, 1),
('2021-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2021-04-02', 'Good Friday', 'Vendredi saint', 0, 1),
('2021-04-06', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2021-05-24', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2021-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2021-08-02', 'Civic Holiday', 'Congé civique', 0, 1),
('2021-09-06', 'Labour Day', 'Fête du travail', 0, 1),
('2021-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2021-10-11', 'Thanksgiving', 'Action de grâces', 0, 1),
('2021-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2021-12-28', 'Christmas Day (Observed)', 'Noël (Observé)', 0, 1),
('2021-12-29', 'Boxing Day (Observed)', 'Lendemain de Noël (Observé)', 0, 1),
('2022-01-05', 'New Year''s Day (Observed)', 'Jour de l''An (Observé)', 0, 1),
('2022-04-15', 'Good Friday', 'Vendredi saint', 0, 1),
('2022-04-19', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2022-05-23', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2022-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2022-08-01', 'Civic Holiday', 'Congé civique', 0, 1),
('2022-09-05', 'Labour Day', 'Fête du travail', 0, 1),
('2022-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2022-10-10', 'Thanksgiving', 'Action de grâces', 0, 1),
('2022-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2022-12-26', 'Christmas Day (Observed)', 'Noël (Observé)', 0, 1),
('2022-12-27', 'Boxing Day (Observed)', 'Lendemain de Noël (Observé)', 0, 1),
('2023-01-03', 'New Year''s Day (Observed)', 'Jour de l''An (Observé)', 0, 1),
('2023-04-07', 'Good Friday', 'Vendredi saint', 0, 1),
('2023-04-11', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2023-05-22', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2023-07-05', 'Canada Day (Observed)', 'Fête du Canada (Observé)', 0, 1),
('2023-08-07', 'Civic Holiday', 'Congé civique', 0, 1),
('2023-09-04', 'Labour Day', 'Fête du travail', 0, 1),
('2023-10-04', 'National Day for Truth and Reconciliation (Observed)', 'Journée nationale de la vérité et de la réconciliation (Observé)', 0, 1),
('2023-10-09', 'Thanksgiving', 'Action de grâces', 0, 1),
('2023-11-15', 'Remembrance Day (Observed)', 'Jour du Souvenir (Observé)', 0, 1),
('2023-12-25', 'Christmas Day', 'Noël', 1, 1),
('2023-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2024-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2024-03-29', 'Good Friday', 'Vendredi saint', 0, 1),
('2024-04-02', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2024-05-20', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2024-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2024-08-05', 'Civic Holiday', 'Congé civique', 0, 1),
('2024-09-02', 'Labour Day', 'Fête du travail', 0, 1),
('2024-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2024-10-14', 'Thanksgiving', 'Action de grâces', 0, 1),
('2024-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2024-12-25', 'Christmas Day', 'Noël', 1, 1),
('2024-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2025-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2025-04-18', 'Good Friday', 'Vendredi saint', 0, 1),
('2025-04-22', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2025-05-19', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2025-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2025-08-04', 'Civic Holiday', 'Congé civique', 0, 1),
('2025-09-01', 'Labour Day', 'Fête du travail', 0, 1),
('2025-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2025-10-13', 'Thanksgiving', 'Action de grâces', 0, 1),
('2025-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2025-12-25', 'Christmas Day', 'Noël', 1, 1),
('2025-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2026-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2026-04-03', 'Good Friday', 'Vendredi saint', 0, 1),
('2026-04-07', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2026-05-18', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2026-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2026-08-03', 'Civic Holiday', 'Congé civique', 0, 1),
('2026-09-07', 'Labour Day', 'Fête du travail', 0, 1),
('2026-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2026-10-12', 'Thanksgiving', 'Action de grâces', 0, 1),
('2026-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2026-12-25', 'Christmas Day', 'Noël', 1, 1),
('2026-12-30', 'Boxing Day (Observed)', 'Lendemain de Noël (Observé)', 0, 1),
('2027-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2027-03-26', 'Good Friday', 'Vendredi saint', 0, 1),
('2027-03-30', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2027-05-24', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2027-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2027-08-02', 'Civic Holiday', 'Congé civique', 0, 1),
('2027-09-06', 'Labour Day', 'Fête du travail', 0, 1),
('2027-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2027-10-11', 'Thanksgiving', 'Action de grâces', 0, 1),
('2027-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2027-12-28', 'Christmas Day (Observed)', 'Noël (Observé)', 0, 1),
('2027-12-29', 'Boxing Day (Observed)', 'Lendemain de Noël (Observé)', 0, 1),
('2028-01-05', 'New Year''s Day (Observed)', 'Jour de l''An (Observé)', 0, 1),
('2028-04-14', 'Good Friday', 'Vendredi saint', 0, 1),
('2028-04-18', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2028-05-22', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2028-07-05', 'Canada Day (Observed)', 'Fête du Canada (Observé)', 0, 1),
('2028-08-07', 'Civic Holiday', 'Congé civique', 0, 1),
('2028-09-04', 'Labour Day', 'Fête du travail', 0, 1),
('2028-10-04', 'National Day for Truth and Reconciliation (Observed)', 'Journée nationale de la vérité et de la réconciliation (Observé)', 0, 1),
('2028-10-09', 'Thanksgiving', 'Action de grâces', 0, 1),
('2028-11-15', 'Remembrance Day (Observed)', 'Jour du Souvenir (Observé)', 0, 1),
('2028-12-25', 'Christmas Day', 'Noël', 1, 1),
('2028-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2029-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2029-03-30', 'Good Friday', 'Vendredi saint', 0, 1),
('2029-04-03', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2029-05-21', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2029-07-03', 'Canada Day (Observed)', 'Fête du Canada (Observé)', 0, 1),
('2029-08-06', 'Civic Holiday', 'Congé civique', 0, 1),
('2029-09-03', 'Labour Day', 'Fête du travail', 0, 1),
('2029-10-02', 'National Day for Truth and Reconciliation (Observed)', 'Journée nationale de la vérité et de la réconciliation (Observé)', 0, 1),
('2029-10-08', 'Thanksgiving', 'Action de grâces', 0, 1),
('2029-11-13', 'Remembrance Day (Observed)', 'Jour du Souvenir (Observé)', 0, 1),
('2029-12-25', 'Christmas Day', 'Noël', 1, 1),
('2029-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1),
('2030-01-01', 'New Year''s Day', 'Jour de l''An', 1, 1),
('2030-04-19', 'Good Friday', 'Vendredi saint', 0, 1),
('2030-04-23', 'Easter Tuesday', 'Mardi de Pâques', 0, 1),
('2030-05-20', 'Victoria Day', 'Fête de la Reine', 0, 1),
('2030-07-01', 'Canada Day', 'Fête du Canada', 1, 1),
('2030-08-05', 'Civic Holiday', 'Congé civique', 0, 1),
('2030-09-02', 'Labour Day', 'Fête du travail', 0, 1),
('2030-09-30', 'National Day for Truth and Reconciliation', 'Journée nationale de la vérité et de la réconciliation', 1, 1),
('2030-10-14', 'Thanksgiving', 'Action de grâces', 0, 1),
('2030-11-11', 'Remembrance Day', 'Jour du Souvenir', 1, 1),
('2030-12-25', 'Christmas Day', 'Noël', 1, 1),
('2030-12-26', 'Boxing Day', 'Lendemain de Noël', 1, 1);
