-- Minimal local-profile fixture. Developer exports belong in ignored database/local-seed.sql.

INSERT INTO `tblusers` (`id`, `firstname`, `lastname`, `email`, `password`, `atype`, `is_superuser`, `is_admin`, `team`, `status`) VALUES
(1, 'Local', 'Administrator', 'local-admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, '1', 1);

INSERT INTO `tblteams` (`id`, `nameen`, `namefr`, `email`, `team_lead_user_id`, `status`) VALUES
(1, 'Local team', 'Équipe locale', 'local-team@example.com', 1, 1);

INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'Local catalogue', 'Catalogue local', 1);

INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(1, 1, 'Local service', 'Service local', 5, 1);

INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(1, 1, 'Local subservice', 'Sous-service local', 1);