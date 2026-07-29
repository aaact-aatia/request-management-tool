SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `request_subject_type`, `status`) VALUES
(101, 'Terminal request', 'Demande directe', NULL, 1),
(102, 'Leaf service requests', 'Demandes de service final', 'system', 1),
(103, 'Detailed service requests', 'Demandes de service détaillées', 'system', 1),
(104, 'Inactive catalogue', 'Catalogue inactif', NULL, 0);

INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `request_subject_type`, `status`) VALUES
(201, 102, 'Leaf service', 'Service final', 5, NULL, 1),
(202, 103, 'Service with details', 'Service avec précisions', 10, 'document', 1),
(203, 103, 'Inactive service', 'Service inactif', 10, NULL, 0);

INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `request_subject_type`, `status`) VALUES
(301, 202, 'Required detail', 'Précision obligatoire', 'subject', 1),
(302, 202, 'Inactive detail', 'Précision inactive', NULL, 0);
