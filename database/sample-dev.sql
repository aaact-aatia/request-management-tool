-- RMT Sample Development Data
-- Non-production users and requests aligned with the current catalogue hierarchy.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Generic example hierarchy.
INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `status`) VALUES
(3, 'Advice and recommendations', 'Conseils et recommandations', 1),
(6, 'Document accessibility audits', 'Audits d''accessibilité des documents', 1),
(8, 'Accessibility audit (assessments)', 'Audit d''accessibilité (évaluations)', 1);

INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(34, 3, 'ICT design and development (including documents)', 'Conception et développement TIC (y compris documents)', 3, 1),
(25, 6, 'Microsoft Word documents', 'Documents Microsoft Word', 5, 1),
(61, 6, 'Microsoft Excel documents', 'Documents Microsoft Excel', 5, 1),
(62, 6, 'Microsoft PowerPoint presentations', 'Présentations Microsoft PowerPoint', 5, 1),
(63, 6, 'Emails', 'Courriels', 5, 1),
(64, 6, 'PDF documents', 'Documents PDF', 5, 1),
(65, 6, 'Other document type', 'Autre type de document', 5, 1),
(27, 8, 'Software applications', 'Applications logicielles', 10, 1),
(28, 8, 'Websites / web applications', 'Sites Web / applications Web', 10, 1),
(66, 8, 'Audit report question(s)', 'Question(s) sur le rapport d''audit', 10, 1);

INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(95, 28, 'Sprint spot-check', 'Vérification ponctuelle du sprint', 1),
(96, 28, 'Audit of representative sample', 'Audit d''un échantillon représentatif', 1),
(104, 34, 'Forms', 'Formulaires', 1),
(105, 34, 'Courses', 'Cours', 1),
(106, 34, 'Documents', 'Documents', 1),
(107, 34, 'Web content', 'Contenu Web', 1),
(108, 34, 'Services', 'Services', 1),
(109, 34, 'Testing', 'Tests', 1),
(110, 34, 'Emails', 'Courriels', 1);

INSERT INTO `tblteams` (`id`, `nameen`, `namefr`, `email`, `team_lead_user_id`, `status`) VALUES
(1, 'IT Accessibility Office', 'Bureau de l''accessibilité des TI', 'accessibility@example.com', NULL, 1),
(2, 'Development Team', 'Équipe de développement', 'dev.team@example.com', NULL, 1);

UPDATE `tblservices` SET `contactid` = 2 WHERE `id` IN (27, 28);
UPDATE `tblservices` SET `contactid` = 1 WHERE `id` = 66;

-- Users (password is "password" hashed with bcrypt).
INSERT INTO `tblusers` (`id`, `firstname`, `lastname`, `email`, `password`, `atype`, `is_superuser`, `is_admin`, `team`, `status`) VALUES
(1, 'Super', 'Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, '1', 1),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 0, 1, '1', 1),
(3, 'Manager', 'User', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 0, 0, '1', 1),
(4, 'Team', 'Lead', 'tl@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 0, 0, '1', 1),
(5, 'Employee', 'User', 'employee@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(6, 'External', 'User', 'external@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 0, 0, '', 1),
(7, 'Alice', 'Tremblay', 'alice.tremblay@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
(8, 'Marcus', 'Okafor', 'marcus.okafor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
(9, 'Sophie', 'Leblanc', 'sophie.leblanc@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
(10, 'Jordan', 'Park', 'jordan.park@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(11, 'Priya', 'Sharma', 'priya.sharma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(12, 'Devon', 'Walsh', 'devon.walsh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1);

-- Requests cover services with and without subservices.
INSERT INTO `tbltriage` (
  `requestid`, `title`, `clientfname`, `clientlname`, `clientemail`, `requestlang`,
  `catalogueid`, `serviceid`, `subserviceid`, `statusid`, `datereceived`,
  `daterequired`, `creatorid`, `updaterid`, `workerid`, `status`
) VALUES
('REQ-DEV-2026-001', 'Advice request: accessible form labels', 'Camille', 'Bernard', 'camille.bernard@example.com', 'fr', 3, 34, 104, 1, '2026-05-20', '2026-06-05', 2, 2, 7, 1),
('REQ-DEV-2026-002', 'Advice request: web content and link text', 'Noel', 'Carson', 'noel.carson@example.com', 'en', 3, 34, 107, 2, '2026-05-18', '2026-06-03', 3, 3, 8, 1),
('REQ-DEV-2026-003', 'Advice request: software accessibility standards', 'Ariane', 'Gauthier', 'ariane.gauthier@example.com', 'fr', 3, 34, 108, 1, '2026-05-23', '2026-06-10', 4, 4, 9, 1),
('REQ-DEV-2026-004', 'Website sprint spot-check: employee portal', 'Elias', 'Moore', 'elias.moore@example.com', 'en', 8, 28, 95, 1, '2026-05-14', '2026-06-20', 1, 1, 10, 1),
('REQ-DEV-2026-005', 'Software accessibility audit: desktop application', 'Lina', 'Roy', 'lina.roy@example.com', 'fr', 8, 27, 210, 2, '2026-05-10', '2026-06-15', 2, 2, 11, 1),
('REQ-DEV-2026-006', 'Website representative sample audit', 'Dylan', 'Parker', 'dylan.parker@example.com', 'en', 8, 28, 96, 3, '2026-05-05', '2026-06-12', 3, 3, 10, 1),
('REQ-DEV-2026-007', 'Audit report questions', 'Maya', 'Khan', 'maya.khan@example.com', 'fr', 8, 66, NULL, 1, '2026-05-24', '2026-07-01', 4, 4, 12, 1),
('REQ-DEV-2026-008', 'Word policy package accessibility review', 'Julia', 'Ng', 'julia.ng@example.com', 'fr', 6, 25, 200, 2, '2026-05-12', '2026-06-06', 1, 1, 8, 1),
('REQ-DEV-2026-009', 'PDF technical guide accessibility audit', 'Ravi', 'Shah', 'ravi.shah@example.com', 'fr', 6, 64, 208, 1, '2026-05-25', '2026-06-18', 2, 2, 9, 1),
('REQ-DEV-2026-010', 'PowerPoint executive deck accessibility check', 'Emma', 'Cote', 'emma.cote@example.com', 'en', 6, 62, 204, 3, '2026-05-07', '2026-05-30', 3, 3, 7, 1),
('REQ-DEV-2026-011', 'Email accessibility review', 'Nolan', 'Turner', 'nolan.turner@example.com', 'en', 6, 63, 206, 1, '2026-05-26', '2026-06-08', 4, 4, 8, 1),
('REQ-DEV-2026-012', 'Excel tables and chart labels review', 'Alyssa', 'Martin', 'alyssa.martin@example.com', 'fr', 6, 61, 202, 2, '2026-05-16', '2026-06-11', 1, 1, 9, 1);

INSERT INTO `tblcommlog` (`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES
(1, '2026-05-20', 'Shared accessible form-label guidance with the client.', 7, 1),
(4, '2026-05-14', 'Sprint spot-check scope confirmed with the delivery team.', 10, 1),
(5, '2026-05-10', 'Desktop application test scope and key tasks confirmed.', 11, 1),
(8, '2026-05-12', 'Word source files received and assigned for review.', 8, 1),
(9, '2026-05-25', 'Requested the tagged source file and publication timeline.', 9, 1);