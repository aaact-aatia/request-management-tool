ALTER TABLE `tblfiles`
  ADD COLUMN `uploadedby` int(11) DEFAULT NULL AFTER `size`,
  ADD KEY `idx_tblfiles_uploadedby` (`uploadedby`);