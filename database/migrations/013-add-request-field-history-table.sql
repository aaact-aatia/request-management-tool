-- Add RequestFieldHistory table for non-workflow request edits (idempotent for MySQL 5.7).

CREATE TABLE IF NOT EXISTS `RequestFieldHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestID` varchar(50) NOT NULL,
  `fieldName` varchar(100) NOT NULL,
  `oldValue` text,
  `newValue` text,
  `actorUserID` int(11) DEFAULT NULL,
  `changeTimeStamp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
