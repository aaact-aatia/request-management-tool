-- RMT Sample Development Data
-- Non-production sample users and example request records for local/dev only

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Users (password is 'password' hashed with bcrypt)
-- team field stores comma-separated tblcontacts IDs: 1=IT Accessibility Office, 2=Development Team
INSERT INTO `tblusers` (`id`, `firstname`, `lastname`, `email`, `password`, `atype`, `is_superuser`, `is_admin`, `team`, `status`) VALUES
(1, 'Super', 'Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, '1', 1),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 0, 1, '1', 1),
(3, 'Manager', 'User', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 0, 0, '1', 1),
(4, 'Team', 'Lead', 'tl@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 0, 0, '1', 1),
(5, 'Employee', 'User', 'employee@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(6, 'External', 'User', 'external@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 0, 0, '', 1),
-- IT Accessibility Office employees (team contact ID 1)
(7, 'Alice', 'Tremblay', 'alice.tremblay@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
(8, 'Marcus', 'Okafor', 'marcus.okafor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
(9, 'Sophie', 'Leblanc', 'sophie.leblanc@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
-- Development Team employees (team contact ID 2)
(10, 'Jordan', 'Park', 'jordan.park@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(11, 'Priya', 'Sharma', 'priya.sharma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
(12, 'Devon', 'Walsh', 'devon.walsh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1);

-- Sample requests (expanded for local testing)
-- Includes workload distribution across AAACT and Development team members (workerid 7-12)
INSERT INTO `tbltriage` (`requestid`, `title`, `clientfname`, `clientlname`, `clientemail`, `requestlang`, `catalogueid`, `serviceid`, `subserviceid`, `statusid`, `datereceived`, `creatorid`, `updaterid`, `workerid`, `status`) VALUES
('REQ-2025-001', 'Sample accessibility coaching request', 'Jane', 'Smith', 'jane.smith@example.com', 'en', 2, 2, NULL, 1, '2026-05-26', 1, 1, 7, 1),
('REQ-2025-002', 'Word document accessibility review for policy memo', 'Liam', 'Nguyen', 'liam.nguyen@example.com', 'fr', 6, 25, NULL, 1, '2026-05-15', 2, 2, 8, 1),
('REQ-2025-003', 'Website sprint spot-check for onboarding flow', 'Amelia', 'Chen', 'amelia.chen@example.com', 'en', 8, 28, 95, 2, '2026-04-29', 3, 3, NULL, 1),
('REQ-2025-004', 'PDF remediation guidance for annual report', 'Noah', 'Patel', 'noah.patel@example.com', 'fr', 6, 64, NULL, 2, '2026-05-14', 4, 4, 9, 1),
('REQ-2025-005', 'Adaptive tech setup support for NVDA', 'Olivia', 'Bouchard', 'olivia.bouchard@example.com', 'en', 4, 115, NULL, 3, '2026-05-08', 1, 1, NULL, 1),
('REQ-2025-006', 'Course content accessibility coaching', 'Ethan', 'Roy', 'ethan.roy@example.com', 'fr', 2, 45, NULL, 1, '2026-05-22', 2, 2, 7, 1),
('REQ-2025-007', 'Service design consultation for digital form', 'Mia', 'Johnson', 'mia.johnson@example.com', 'en', 3, 34, 104, 2, '2026-05-13', 3, 3, 10, 1),
('REQ-2025-008', 'Email template accessibility check', 'Lucas', 'Gagnon', 'lucas.gagnon@example.com', 'fr', 6, 63, NULL, 1, '2026-05-23', 4, 4, 8, 1),
('REQ-2025-009', 'Accessibility audit report clarification meeting', 'Ava', 'Martel', 'ava.martel@example.com', 'en', 8, 66, NULL, 3, '2026-04-30', 1, 1, 9, 1),
('REQ-2025-010', 'Coaching for Microsoft document workflows', 'Benjamin', 'Singh', 'benjamin.singh@example.com', 'en', 2, 48, NULL, 2, '2026-05-20', 2, 2, 12, 1),
('REQ-2025-011', 'Procurement consultation for accessibility criteria', 'Charlotte', 'Wilson', 'charlotte.wilson@example.com', 'fr', 10, 30, NULL, 1, '2026-05-12', 3, 3, NULL, 1),
('REQ-2025-012', 'Loan request for adaptive hardware trial', 'Henry', 'Lavoie', 'henry.lavoie@example.com', 'en', 9, 29, NULL, 2, '2026-05-21', 4, 4, 8, 1),
('REQ-2025-013', 'Web app representative sample audit', 'Evelyn', 'Turner', 'evelyn.turner@example.com', 'en', 8, 28, 96, 3, '2026-05-01', 1, 1, 10, 1),
('REQ-2025-014', 'Advice on testing strategy for keyboard navigation', 'Mason', 'Dubois', 'mason.dubois@example.com', 'fr', 3, 34, 109, 1, '2026-05-19', 2, 2, 11, 1),
('REQ-2025-015', 'PowerPoint deck accessibility audit support', 'Harper', 'Kelly', 'harper.kelly@example.com', 'en', 6, 62, NULL, 2, '2026-05-07', 3, 3, 9, 1),
('REQ-2025-016', 'Needs assessment for low vision accommodations', 'Jack', 'Bennett', 'jack.bennett@example.com', 'fr', 5, 16, NULL, 1, '2026-05-27', 4, 4, NULL, 1),
('REQ-2025-017', 'Screen reader compatibility review request', 'Ella', 'Laroche', 'ella.laroche@example.com', 'en', 8, 27, NULL, 2, '2026-05-06', 1, 1, 10, 1),
('REQ-2025-018', 'Follow-up coaching for ICT developers', 'Logan', 'Moore', 'logan.moore@example.com', 'en', 2, 33, NULL, 3, '2026-05-09', 2, 2, 12, 1),
('REQ-2025-019', 'Document accessibility check before publication', 'Scarlett', 'Renaud', 'scarlett.renaud@example.com', 'fr', 6, 25, NULL, 1, '2026-05-16', 3, 3, 8, 1),
('REQ-2025-020', 'Consultation for inclusive event materials', 'James', 'Poirier', 'james.poirier@example.com', 'fr', 3, 34, 106, 2, '2026-05-24', 4, 4, 9, 1),
('REQ-2025-021', 'JAWS troubleshooting and setup request', 'Aria', 'Foster', 'aria.foster@example.com', 'en', 4, 57, NULL, 3, '2026-05-05', 1, 1, 11, 1),
('REQ-2025-022', 'Accessibility testing tool onboarding', 'William', 'Mercier', 'william.mercier@example.com', 'en', 11, 53, NULL, 1, '2026-05-26', 2, 2, NULL, 1),
('REQ-2025-023', 'Audit of service portal forms', 'Sofia', 'Cote', 'sofia.cote@example.com', 'fr', 8, 28, 95, 2, '2026-05-02', 3, 3, 10, 1),
('REQ-2025-024', 'Email accessibility follow-up for campaign', 'Daniel', 'Harris', 'daniel.harris@example.com', 'en', 6, 63, NULL, 1, '2026-05-21', 4, 4, NULL, 1),
('REQ-2025-025', 'Accessibility compliance coaching kickoff', 'Chloe', 'Levesque', 'chloe.levesque@example.com', 'fr', 2, 2, NULL, 2, '2026-05-18', 1, 1, 7, 1);

-- Communication log for sample request
INSERT INTO `tblcommlog` (`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES
(1, '2026-05-26', 'Initial request received. User needs help with accessible web development.', 1, 1),
(2, '2026-05-15', 'Requested policy memo and source Word file from client for baseline scan.', 8, 1),
(3, '2026-04-29', 'Sprint spot-check booked with delivery team for Tuesday morning.', 10, 1),
(4, '2026-05-14', 'Shared PDF remediation checklist and requested tagged source document.', 9, 1),
(5, '2026-05-08', 'Confirmed NVDA setup prerequisites and scheduled remote session.', 11, 1),
(7, '2026-05-13', 'Reviewed form flow and provided keyboard sequence recommendations.', 10, 1),
(9, '2026-04-30', 'Explained key findings from latest audit report and next steps.', 9, 1),
(10, '2026-05-20', 'Provided updated templates and example accessible email structure.', 12, 1),
(12, '2026-05-21', 'Loan request approved; pickup instructions sent to client.', 8, 1),
(13, '2026-05-01', 'Representative sample pages confirmed and test scope finalized.', 10, 1),
(15, '2026-05-07', 'PowerPoint deck received; review in progress with slide-order checks.', 9, 1),
(17, '2026-05-06', 'Collected environment details for screen reader compatibility test.', 10, 1),
(18, '2026-05-09', 'Follow-up coaching session completed; action items shared.', 12, 1),
(20, '2026-05-24', 'Provided inclusive event content recommendations and checklist.', 9, 1),
(23, '2026-05-02', 'Audit started on submitted service portal forms.', 10, 1),
(25, '2026-05-18', 'Kickoff meeting held; backlog triage and ownership agreed.', 7, 1);

-- Keep sample requests aligned to active request-first routing
-- Active catalogues: 3 (Advice), 6 (Document audits), 8 (Accessibility audits)
-- Active services: 34, 25, 61, 62, 63, 64, 65, 27, 28, 66
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 109 WHERE `requestid` = 'REQ-2025-001';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 109 WHERE `requestid` = 'REQ-2025-005';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 105 WHERE `requestid` = 'REQ-2025-006';
UPDATE `tbltriage` SET `catalogueid` = 6, `serviceid` = 25, `subserviceid` = NULL WHERE `requestid` = 'REQ-2025-010';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 108 WHERE `requestid` = 'REQ-2025-011';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 108 WHERE `requestid` = 'REQ-2025-012';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 108 WHERE `requestid` = 'REQ-2025-016';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 109 WHERE `requestid` = 'REQ-2025-018';
UPDATE `tbltriage` SET `catalogueid` = 3, `serviceid` = 34, `subserviceid` = 109 WHERE `requestid` = 'REQ-2025-021';
UPDATE `tbltriage` SET `catalogueid` = 8, `serviceid` = 27, `subserviceid` = NULL WHERE `requestid` = 'REQ-2025-022';
