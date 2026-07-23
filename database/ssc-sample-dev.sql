-- SSC development seed captured from the catalogue management UI.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblteams` (`id`, `nameen`, `namefr`, `email`, `team_lead_user_id`, `status`) VALUES
(1, 'Digital Accessibility and Inclusive Design (DAID)', 'Accessibilité numérique et conception inclusive (ANCI)', 'daid-anci@ssc-spc.gc.ca', NULL, 1),
(2, 'AAACT Learning', 'AATIA Apprentissage', 'aaactlearning-aatiaapprentissage@ssc-spc.gc.ca', NULL, 1),
(3, 'Client services', 'Service à la clientèle', 'aaact-aatia@ssc-spc.gc.ca', NULL, 1);

INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `status`) VALUES
(1, 'Accessibility assessment', 'Évaluation de l''accessibilité', 1),
(2, 'Advisory services', 'Services de conseil', 1),
(3, 'Training and learning resources', 'Ressources de formation et d''apprentissage', 1),
(4, 'Accommodations', 'Adaptations', 1);

INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(1, 1, 'Website', 'Site web', 10, 1),
(2, 1, 'Web application', 'Application web', 10, 1),
(3, 1, 'Software', 'Logiciel', 10, 1),
(4, 1, 'Hardware with digital interface(s)', 'Matériel doté d''une ou plusieurs interfaces numériques', 15, 1),
(5, 1, 'Digital document(s)', 'Document(s) numérique(s)', 5, 1),
(6, 2, 'Procuring accessible ICT systems', 'Acquisition de systèmes', 10, 1),
(7, 2, 'Planning accessible meetings or events', 'Organisation de réunions ou d''événements accessibles', 10, 1),
(8, 2, 'Web accessibility', 'Accessibilité du Web', 10, 1),
(9, 2, 'Hardware accessibility', 'Accessibilité du matériel informatique', 10, 1),
(10, 2, 'Document accessibility', 'Accessibilité des documents', 10, 1),
(11, 2, 'Policies and regulations', 'Politiques et réglementations', 10, 1),
(12, 2, 'Software accessibility', 'Accessibilité des logiciels', 10, 1),
(13, 2, 'Other', 'Autres', 10, 1);

-- Development-only login accounts. Password: password
INSERT INTO `tblusers` (`id`, `firstname`, `lastname`, `email`, `password`, `atype`, `is_superuser`, `is_admin`, `team`, `status`) VALUES
(1, 'Super', 'Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, '1', 1),
(2, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 0, 1, '1', 1);