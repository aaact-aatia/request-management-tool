SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO `tblcatalogue` (`id`, `nameen`, `namefr`, `status`) VALUES
(101, 'Terminal request', 'Demande directe', 1),
(102, 'Leaf service requests', 'Demandes de service final', 1),
(103, 'Detailed service requests', 'Demandes de service détaillées', 1),
(104, 'Inactive catalogue', 'Catalogue inactif', 0);

INSERT INTO `tblservices` (`id`, `catalogueid`, `nameen`, `namefr`, `sds`, `status`) VALUES
(201, 102, 'Leaf service', 'Service final', 5, 1),
(202, 103, 'Service with details', 'Service avec précisions', 10, 1),
(203, 103, 'Inactive service', 'Service inactif', 10, 0);

INSERT INTO `tblsubservices` (`id`, `serviceid`, `nameen`, `namefr`, `status`) VALUES
(301, 202, 'Required detail', 'Précision obligatoire', 1),
(302, 202, 'Inactive detail', 'Précision inactive', 0);
