-- Demo data for management presentation
-- Adds a variety of requests across statuses, services, and teams

INSERT INTO `tbltriage`
  (`requestid`, `title`, `clientfname`, `clientlname`, `clientemail`, `clientphone`, `requestlang`,
   `sourceid`, `datereceived`, `dateupdated`, `daterequired`, `dateresolved`,
   `statusid`, `catalogueid`, `serviceid`, `subserviceid`,
   `creatorid`, `updaterid`, `workerid`, `status`)
VALUES
('REQ-2025-002', 'Website accessibility audit for client portal', 'Marie', 'Dubois', 'marie.dubois@example.com', '613-555-0102',
   'fr', 3, '2026-04-15', '2026-05-02', '2026-05-15', NULL,
   2, 8, 28, NULL,
   7, 8, 8, 1),

('REQ-2025-003', 'JAWS screen reader installation for new employee', 'Robert', 'Chen', 'robert.chen@example.com', '613-555-0103',
   'en', 2, '2026-06-05', '2026-06-05', '2026-06-19', NULL,
   1, 4, 57, NULL,
   9, 9, 7, 1),

('REQ-2025-004', 'PDF document remediation coaching session', 'Fatima', 'Al-Rashid', 'fatima.alrashid@example.com', '613-555-0104',
   'fr', 1, '2026-03-01', '2026-03-10', '2026-03-12', '2026-03-10',
   4, 2, 47, NULL,
   5, 8, 8, 1),

('REQ-2025-005', 'Accessibility Compliance Project kickoff', 'Thomas', 'Wright', 'thomas.wright@example.com', '613-555-0105',
   'en', 3, '2026-05-20', '2026-05-28', '2026-07-20', NULL,
   3, 1, 32, NULL,
   4, 9, 9, 1),

('REQ-2025-006', 'Vendor RFP evaluation for procurement software', 'Linda', 'Martinez', 'linda.martinez@example.com', '613-555-0106',
   'en', 3, '2026-02-01', '2026-02-28', '2026-03-01', '2026-02-28',
   5, 10, 31, NULL,
   3, 10, 10, 1),

('REQ-2025-007', 'Microsoft Word document accessibility audit', 'Kevin', 'O''Brien', 'kevin.obrien@example.com', '613-555-0107',
   'fr', 1, '2026-05-25', '2026-06-03', '2026-06-08', NULL,
   2, 6, 25, NULL,
   7, 11, 11, 1),

('REQ-2025-008', 'Adaptive hardware loan request - keyboard', 'Nadia', 'Hassan', 'nadia.hassan@example.com', '613-555-0108',
   'en', 2, '2026-01-10', '2026-01-15', '2026-01-24', NULL,
   6, 9, 29, NULL,
   6, 12, 12, 1),

('REQ-2025-009', 'Needs assessment - mobility impairment', 'Samuel', 'Okonkwo', 'samuel.okonkwo@example.com', '613-555-0109',
   'fr', 1, '2026-06-08', '2026-06-08', '2026-06-22', NULL,
   1, 5, 19, NULL,
   8, 9, 9, 1),

('REQ-2025-010', 'Colour contrast analyzer testing for design system', 'Emily', 'Tran', 'emily.tran@example.com', '613-555-0110',
   'en', 3, '2026-04-01', '2026-04-10', '2026-04-15', '2026-04-10',
   4, 11, 53, NULL,
   9, 7, 7, 1),

('REQ-2025-011', 'ICT developer coaching - accessible forms', 'Carlos', 'Mendoza', 'carlos.mendoza@example.com', '613-555-0111',
   'fr', 1, '2026-06-01', '2026-06-09', '2026-06-15', NULL,
   2, 2, 33, NULL,
   10, 11, 11, 1);

-- Communication log entries for a few requests
INSERT INTO `tblcommlog` (`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
SELECT id, '2026-04-16', 'Initial review completed. Audit scheduled with development team.', 8, 1 FROM `tbltriage` WHERE requestid = 'REQ-2025-002'
UNION ALL
SELECT id, '2026-05-02', 'Audit in progress, 60% of pages reviewed.', 8, 1 FROM `tbltriage` WHERE requestid = 'REQ-2025-002'
UNION ALL
SELECT id, '2026-03-10', 'Coaching session completed. Client confirmed all PDFs are now accessible.', 8, 1 FROM `tbltriage` WHERE requestid = 'REQ-2025-004'
UNION ALL
SELECT id, '2026-02-28', 'Vendor evaluation finalized and report delivered to procurement.', 10, 1 FROM `tbltriage` WHERE requestid = 'REQ-2025-006'
UNION ALL
SELECT id, '2026-01-15', 'Client cancelled request - hardware no longer needed.', 12, 1 FROM `tbltriage` WHERE requestid = 'REQ-2025-008';

-- Status history entries reflecting the lifecycle of each request
INSERT INTO `StatusHistory` (`requestID`, `statusID`, `changeTimeStamp`) VALUES
('REQ-2025-002', 1, '2026-04-15 09:00:00'),
('REQ-2025-002', 2, '2026-05-02 10:30:00'),
('REQ-2025-003', 1, '2026-06-05 14:00:00'),
('REQ-2025-004', 1, '2026-03-01 11:00:00'),
('REQ-2025-004', 2, '2026-03-05 09:00:00'),
('REQ-2025-004', 4, '2026-03-10 16:00:00'),
('REQ-2025-005', 1, '2026-05-20 08:30:00'),
('REQ-2025-005', 3, '2026-05-28 13:00:00'),
('REQ-2025-006', 1, '2026-02-01 09:00:00'),
('REQ-2025-006', 2, '2026-02-10 09:00:00'),
('REQ-2025-006', 5, '2026-02-28 17:00:00'),
('REQ-2025-007', 1, '2026-05-25 10:00:00'),
('REQ-2025-007', 2, '2026-06-03 11:15:00'),
('REQ-2025-008', 1, '2026-01-10 09:00:00'),
('REQ-2025-008', 6, '2026-01-15 12:00:00'),
('REQ-2025-009', 1, '2026-06-08 15:00:00'),
('REQ-2025-010', 1, '2026-04-01 09:00:00'),
('REQ-2025-010', 2, '2026-04-05 09:00:00'),
('REQ-2025-010', 4, '2026-04-10 14:00:00'),
('REQ-2025-011', 1, '2026-06-01 09:00:00'),
('REQ-2025-011', 2, '2026-06-09 10:00:00');
