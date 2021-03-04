ALTER TABLE `dhl_capability_product` DROP FOREIGN KEY `dhl_capability_product_fk0`;

ALTER TABLE `dhl_capability_parcel_type` DROP FOREIGN KEY `dhl_capability_parcel_type_fk0`;

ALTER TABLE `dhl_capability_parcel_type` DROP FOREIGN KEY `dhl_capability_parcel_type_fk1`;

ALTER TABLE `dhl_capability_parcel_type_dimensions` DROP FOREIGN KEY `dhl_capability_parcel_type_dimensions_fk0`;

ALTER TABLE `dhl_capability_option` DROP FOREIGN KEY `dhl_capability_option_fk0`;

ALTER TABLE `dhl_capability_option` DROP FOREIGN KEY `dhl_capability_option_fk1`;

ALTER TABLE `dhl_capability_exclusion` DROP FOREIGN KEY `dhl_capability_exclusion_fk0`;

DROP TABLE IF EXISTS `dhl_capability`;

DROP TABLE IF EXISTS `dhl_capability_product`;

DROP TABLE IF EXISTS `dhl_capability_parcel_type`;

DROP TABLE IF EXISTS `dhl_capability_parcel_type_dimensions`;

DROP TABLE IF EXISTS `dhl_capability_price`;

DROP TABLE IF EXISTS `dhl_capability_option`;

DROP TABLE IF EXISTS `dhl_capability_exclusion`;
