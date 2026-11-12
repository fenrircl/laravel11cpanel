-- Esquema para importar en phpMyAdmin antes del despliegue Laravel.
-- No crea claves foráneas a tablas legadas para tolerar IDs no estándar en cPanel.
CREATE TABLE IF NOT EXISTS `document_ingestions` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `uuid` CHAR(36) NOT NULL,
 `source` VARCHAR(32) NOT NULL, `external_id` VARCHAR(255) NOT NULL,
 `external_version` VARCHAR(128) NOT NULL DEFAULT '', `source_sha256` CHAR(64) NULL,
 `filename` VARCHAR(255) NULL, `mime_type` VARCHAR(120) NULL, `size_bytes` BIGINT UNSIGNED NULL,
 `status` VARCHAR(32) NOT NULL DEFAULT 'discovered', `r2_key` VARCHAR(1024) NULL,
 `invoice_id` BIGINT UNSIGNED NULL, `file_registry_id` BIGINT UNSIGNED NULL,
 `attempts` INT UNSIGNED NOT NULL DEFAULT 0, `last_error` TEXT NULL, `lease_until` TIMESTAMP NULL,
 `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`), UNIQUE KEY `document_ingestions_uuid_unique` (`uuid`),
 UNIQUE KEY `document_ingestions_source_external_unique` (`source`,`external_id`,`external_version`),
 KEY `document_ingestions_status_updated_index` (`status`,`updated_at`), KEY `document_ingestions_source_sha256_index` (`source_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_extractions` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `ingestion_id` BIGINT UNSIGNED NOT NULL,
 `invoice_id` BIGINT UNSIGNED NULL, `file_registry_id` BIGINT UNSIGNED NULL, `request_id` VARCHAR(100) NULL,
 `schema_version` VARCHAR(32) NULL, `adapter_version` VARCHAR(64) NOT NULL, `source_sha256` CHAR(64) NULL,
 `document_type` VARCHAR(48) NULL, `document_subtype` VARCHAR(80) NULL, `payload` JSON NOT NULL,
 `normalized_data` JSON NULL, `raw_text` LONGTEXT NULL, `confidence` DECIMAL(6,5) NULL, `warnings` JSON NULL,
 `validation_status` VARCHAR(32) NOT NULL DEFAULT 'pending', `is_active` TINYINT(1) NOT NULL DEFAULT 0,
 `processed_at` TIMESTAMP NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
 UNIQUE KEY `invoice_extractions_ingestion_id_unique` (`ingestion_id`), KEY `invoice_extractions_file_registry_id_index` (`file_registry_id`),
 KEY `invoice_extractions_invoice_id_index` (`invoice_id`), KEY `invoice_extractions_validation_status_processed_at_index` (`validation_status`,`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_line_items` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `invoice_id` BIGINT UNSIGNED NOT NULL, `extraction_id` BIGINT UNSIGNED NOT NULL,
 `line_number` INT UNSIGNED NOT NULL, `description_original` VARCHAR(1000) NOT NULL,
 `description_normalized` VARCHAR(700) NULL, `supplier_sku` VARCHAR(191) NULL,
 `quantity` DECIMAL(20,6) NULL, `unit_original` VARCHAR(32) NULL, `unit_normalized` VARCHAR(32) NULL,
 `unit_price` DECIMAL(20,6) NULL, `discount_amount` DECIMAL(20,6) NULL, `net_amount` DECIMAL(20,6) NULL,
 `tax_amount` DECIMAL(20,6) NULL, `total_amount` DECIMAL(20,6) NULL, `currency` VARCHAR(3) NULL,
 `price_tax_basis` VARCHAR(24) NOT NULL DEFAULT 'unknown', `source_line` JSON NULL,
 `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, PRIMARY KEY (`id`),
 UNIQUE KEY `invoice_line_extraction_number_unique` (`extraction_id`,`line_number`),
 KEY `invoice_line_sku_invoice_index` (`supplier_sku`,`invoice_id`),
 KEY `invoice_line_description_invoice_index` (`description_normalized`(700),`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
