CREATE TABLE `dhl_capability` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`hash` varchar(80) NOT NULL,
	`creation_time` TIMESTAMP NOT NULL,
	`rank` INT NOT NULL,
	`fromCountryCode` varchar(3) NOT NULL,
	`toCountryCode` varchar(3) NOT NULL,
	`returnUrl` varchar(80),
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_product` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`capability_id` INT NOT NULL,
	`key` varchar(30) NOT NULL,
	`code` varchar(30) NOT NULL,
	`menuCode` varchar(30) NOT NULL,
	`label` varchar(80) NOT NULL,
	`businessProduct` BOOLEAN NOT NULL,
	`monoColloProduct` BOOLEAN NOT NULL,
	`returnProduct` BOOLEAN NOT NULL,
	`softwareCharacteristic` varchar(30) NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_parcel_type` (
	`capability_id` INT NOT NULL,
	`id` INT NOT NULL AUTO_INCREMENT,
	`price_id` INT NOT NULL,
	`key` varchar(30) NOT NULL,
	`minWeightKg` INT NOT NULL,
	`maxWeightKg` INT NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_parcel_type_dimensions` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`parcelType_id` INT NOT NULL,
	`maxLengthCm` INT NOT NULL,
	`maxWidthCm` INT NOT NULL,
	`maxHeightCm` INT NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_price` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`withTax` DECIMAL NOT NULL,
	`withoutTax` DECIMAL NOT NULL,
	`vatRate` INT NOT NULL,
	`currency` varchar(10) NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_option` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`capability_id` INT NOT NULL,
	`price_id` INT NOT NULL,
	`key` varchar(30) NOT NULL,
	`optionType` varchar(30) NOT NULL,
	`description` varchar(256) NOT NULL,
	`rank` INT NOT NULL,
	`code` INT NOT NULL,
	`inputType` varchar(30) NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `dhl_capability_exclusion` (
	`option_id` INT NOT NULL,
	`id` INT NOT NULL AUTO_INCREMENT,
	`key` varchar(30) NOT NULL,
	`optionType` varchar(30) NOT NULL,
	`rank` INT NOT NULL,
	`code` INT NOT NULL,
	PRIMARY KEY (`id`)
);

ALTER TABLE `dhl_capability_product` ADD CONSTRAINT `dhl_capability_product_fk0` FOREIGN KEY (`capability_id`) REFERENCES `dhl_capability`(`id`);

ALTER TABLE `dhl_capability_parcel_type` ADD CONSTRAINT `dhl_capability_parcel_type_fk0` FOREIGN KEY (`capability_id`) REFERENCES `dhl_capability`(`id`);

ALTER TABLE `dhl_capability_parcel_type` ADD CONSTRAINT `dhl_capability_parcel_type_fk1` FOREIGN KEY (`price_id`) REFERENCES `dhl_capability_price`(`id`);

ALTER TABLE `dhl_capability_parcel_type_dimensions` ADD CONSTRAINT `dhl_capability_parcel_type_dimensions_fk0` FOREIGN KEY (`parcelType_id`) REFERENCES `dhl_capability_parcel_type`(`id`);

ALTER TABLE `dhl_capability_option` ADD CONSTRAINT `dhl_capability_option_fk0` FOREIGN KEY (`capability_id`) REFERENCES `dhl_capability`(`id`);

ALTER TABLE `dhl_capability_option` ADD CONSTRAINT `dhl_capability_option_fk1` FOREIGN KEY (`price_id`) REFERENCES `dhl_capability_price`(`id`);

ALTER TABLE `dhl_capability_exclusion` ADD CONSTRAINT `dhl_capability_exclusion_fk0` FOREIGN KEY (`option_id`) REFERENCES `dhl_capability_option`(`id`);
