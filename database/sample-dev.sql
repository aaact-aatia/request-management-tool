-- RMT Sample Development Data
-- Non-production sample users and example request records for local/dev only

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

-- Sample request
INSERT INTO `tbltriage` (`requestid`, `title`, `clientfname`, `clientlname`, `clientemail`, `catalogueid`, `serviceid`, `subserviceid`, `statusid`, `datereceived`, `creatorid`, `updaterid`, `status`) VALUES
('REQ-2025-001', 'Sample accessibility coaching request', 'Jane', 'Smith', 'jane.smith@example.com', 2, 2, NULL, 1, '2025-12-12', 1, 1, 1);

-- Communication log for sample request
INSERT INTO `tblcommlog` (`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES
(1, '2025-12-12', 'Initial request received. User needs help with accessible web development.', 1, 1);
