CREATE TABLE `mgt_sdw_invoice_status` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `invoice_id` int(10) unsigned NOT NULL,
 `invoice_status` enum('TO_BE_EXPORTED','NOT_TO_BE_EXPORTED','EXPORTED') NOT NULL DEFAULT 'TO_BE_EXPORTED',
 `header_has_been_exported` tinyint(1) NOT NULL DEFAULT '0',
 `detail_has_been_exported` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`),
 UNIQUE KEY `UNIQUE_STATUS_PER_INVOICE` (`invoice_id`),
 CONSTRAINT `FK_INVOICE_ID` FOREIGN KEY (`invoice_id`) REFERENCES `mgt_sales_flat_invoice` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) DEFAULT CHARSET=utf8
