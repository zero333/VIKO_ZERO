#To update databases from version 2.0.
ALTER DATABASE DEFAULT CHARACTER SET utf8;

ALTER TABLE `Materials` CHANGE `material_type` `material_type` ENUM( 'FILE', 'FOLDER', 'LINK', 'EMBED', 'TEXT' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'FILE';

ALTER TABLE `Materials` ADD `material_text` TEXT NOT NULL, ADD `material_visible` INT NOT NULL DEFAULT '0', ADD `material_score` INT NOT NULL;
