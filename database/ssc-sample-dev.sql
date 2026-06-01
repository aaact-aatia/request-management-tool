-- RMT SSC Sample Development Data
-- Non-production sample records aligned with the request-first router plan.
--
-- Guidance-only branches intentionally have NO tbltriage rows in Phase 1:
-- 1) Workshops and learning sessions
-- 2) Non-SSC document requests
--
-- Future expansion note:
-- If policy changes, these guidance-only branches can be made trackable later.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Users (password is 'password' hashed with bcrypt)
-- team field stores comma-separated tblcontacts IDs: 1=IT Accessibility Office, 2=Development Team
INSERT INTO `tblusers` (`id`, `firstname`, `lastname`, `email`, `password`, `atype`, `team`, `status`, `environment`) VALUES
(1, 'Super', 'Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '1', 1, 0),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '1', 1, 0),
(3, 'Manager', 'User', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '1', 1, 0),
(4, 'Team', 'Lead', 'tl@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '1', 1, 0),
(5, 'Employee', 'User', 'employee@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '2', 1, 0),
(6, 'External', 'User', 'external@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, '', 1, 0),
-- IT Accessibility Office employees (team contact ID 1)
(7, 'Alice', 'Tremblay', 'alice.tremblay@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '1', 1, 0),
(8, 'Marcus', 'Okafor', 'marcus.okafor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '1', 1, 0),
(9, 'Sophie', 'Leblanc', 'sophie.leblanc@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '1', 1, 0),
-- Development Team employees (team contact ID 2)
(10, 'Jordan', 'Park', 'jordan.park@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '2', 1, 0),
(11, 'Priya', 'Sharma', 'priya.sharma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '2', 1, 0),
(12, 'Devon', 'Walsh', 'devon.walsh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, '2', 1, 0);

-- Phase 1 sample requests for trackable branches only
-- Informational advice/guidance: catalogue=3 service=34
-- Software testing: catalogue=8 service=27/28
-- SSC documents: catalogue=6 service=25/61/62/63/64/65
INSERT INTO `tbltriage` (
  `requestid`, `title`, `clientfname`, `clientlname`, `clientemail`,
  `catalogueid`, `serviceid`, `subserviceid`, `statusid`, `datereceived`,
  `daterequired`, `creatorid`, `updaterid`, `workerid`, `status`
) VALUES
('REQ-SSC-2026-001', 'Advice request: accessible form labels', 'Camille', 'Bernard', 'camille.bernard@example.com', 3, 34, 104, 1, '2026-05-20', '2026-06-05', 2, 2, 7, 1),
('REQ-SSC-2026-002', 'Advice request: web content and link text', 'Noel', 'Carson', 'noel.carson@example.com', 3, 34, 107, 2, '2026-05-18', '2026-06-03', 3, 3, 8, 1),
('REQ-SSC-2026-003', 'Advice request: software accessibility standards guidance', 'Ariane', 'Gauthier', 'ariane.gauthier@example.com', 3, 34, 108, 1, '2026-05-23', '2026-06-10', 4, 4, 9, 1),

('REQ-SSC-2026-004', 'Software user testing request: employee portal', 'Elias', 'Moore', 'elias.moore@example.com', 8, 28, 95, 1, '2026-05-14', '2026-06-20', 1, 1, 10, 1),
('REQ-SSC-2026-005', 'Software conformance request: desktop case management app', 'Lina', 'Roy', 'lina.roy@example.com', 8, 27, NULL, 2, '2026-05-10', '2026-06-15', 2, 2, 11, 1),
('REQ-SSC-2026-006', 'Software user testing request: mobile web onboarding', 'Dylan', 'Parker', 'dylan.parker@example.com', 8, 28, 96, 3, '2026-05-05', '2026-06-12', 3, 3, 10, 1),
('REQ-SSC-2026-007', 'Software conformance request: internal reporting dashboard', 'Maya', 'Khan', 'maya.khan@example.com', 8, 28, NULL, 1, '2026-05-24', '2026-07-01', 4, 4, 12, 1),

('REQ-SSC-2026-008', 'Document request (SSC): Word policy package review', 'Julia', 'Ng', 'julia.ng@example.com', 6, 25, NULL, 2, '2026-05-12', '2026-06-06', 1, 1, 8, 1),
('REQ-SSC-2026-009', 'Document request (SSC): PDF technical guide audit', 'Ravi', 'Shah', 'ravi.shah@example.com', 6, 64, NULL, 1, '2026-05-25', '2026-06-18', 2, 2, 9, 1),
('REQ-SSC-2026-010', 'Document request (SSC): PowerPoint executive deck check', 'Emma', 'Cote', 'emma.cote@example.com', 6, 62, NULL, 3, '2026-05-07', '2026-05-30', 3, 3, 7, 1),
('REQ-SSC-2026-011', 'Document request (SSC): email accessibility review', 'Nolan', 'Turner', 'nolan.turner@example.com', 6, 63, NULL, 1, '2026-05-26', '2026-06-08', 4, 4, 8, 1),
('REQ-SSC-2026-012', 'Document request (SSC): Excel tables and chart labels', 'Alyssa', 'Martin', 'alyssa.martin@example.com', 6, 61, NULL, 2, '2026-05-16', '2026-06-11', 1, 1, 9, 1);

-- Communication log for seeded sample requests
INSERT INTO `tblcommlog` (`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES
(1, '2026-05-20', 'Advice intake captured from router: forms topic. Shared quick-start resources in response.', 7, 1),
(4, '2026-05-14', 'Minimal software request received. Follow-up requested for optional detailed intake (Phase 2 capability).', 10, 1),
(5, '2026-05-10', 'Conformance test scope confirmed for desktop workflow and key task list.', 11, 1),
(8, '2026-05-12', 'SSC document request accepted and assigned for Word review.', 8, 1),
(9, '2026-05-25', 'Requested source files and expected publication timeline.', 9, 1);
