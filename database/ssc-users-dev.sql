-- RMT SSC Development Demo Users
--
-- Default demo accounts for local development only.
-- The password for every user is 'password' (bcrypt hash stored below).
--
-- Uses deterministic INSERT so duplicate IDs or emails produce a visible
-- error rather than being silently ignored. Run this file only against an
-- empty tblusers table (e.g. after schema.sql). Do not run it a second
-- time without first clearing the table.
--
-- These are not real SSC employee credentials.
--
-- team field: comma-separated tblcontacts IDs
--   1 = IT Accessibility Office
--   2 = Development Team

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblusers`
  (`id`, `firstname`, `lastname`, `email`, `password`,
   `atype`, `is_superuser`, `is_admin`, `team`, `status`)
VALUES
  -- Generic role accounts
  (1,  'Super',   'Admin',    'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, '1',  1),
  (2,  'Admin',   'User',     'admin@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 0, 1, '1',  1),
  (3,  'Manager', 'User',     'manager@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 0, 0, '1',  1),
  (4,  'Team',    'Lead',     'tl@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 0, 0, '1',  1),
  (5,  'Employee','User',     'employee@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2',  1),
  (6,  'External','User',     'external@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 0, 0, '',   1),
  -- IT Accessibility Office employees (team 1)
  (7,  'Alice',   'Tremblay', 'alice.tremblay@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
  (8,  'Marcus',  'Okafor',   'marcus.okafor@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
  (9,  'Sophie',  'Leblanc',  'sophie.leblanc@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '1', 1),
  -- Development Team employees (team 2)
  (10, 'Jordan',  'Park',     'jordan.park@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
  (11, 'Priya',   'Sharma',   'priya.sharma@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1),
  (12, 'Devon',   'Walsh',    'devon.walsh@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 0, 0, '2', 1);
