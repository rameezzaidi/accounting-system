-- =====================================================================
--  Accounting System: upgrade from Phase 1 to Phase 2
--  Run ONCE on an existing Phase 1 database (phpMyAdmin > SQL).
--  Fresh installs should import schema.sql instead (it already contains this).
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE `fiscal_years`
  ADD COLUMN `closed_at` DATETIME DEFAULT NULL AFTER `created_at`,
  ADD COLUMN `closed_by` INT UNSIGNED DEFAULT NULL AFTER `closed_at`,
  ADD COLUMN `closing_voucher_id` INT UNSIGNED DEFAULT NULL AFTER `closed_by`,
  ADD COLUMN `carried_from_id` INT UNSIGNED DEFAULT NULL AFTER `closing_voucher_id`,
  ADD COLUMN `carried_to_id` INT UNSIGNED DEFAULT NULL AFTER `carried_from_id`;

ALTER TABLE `vouchers`
  ADD COLUMN `is_closing` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

CREATE TABLE IF NOT EXISTS `reversals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` ENUM('refund','stipend') NOT NULL,
  `original_voucher_id` INT UNSIGNED NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  `party_name` VARCHAR(150) DEFAULT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rev_voucher` (`voucher_id`),
  KEY `idx_rev_kind` (`kind`),
  KEY `idx_rev_orig` (`original_voucher_id`),
  CONSTRAINT `fk_rev_orig` FOREIGN KEY (`original_voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_rev_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` INT UNSIGNED NOT NULL,
  `statement_date` DATE NOT NULL,
  `statement_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `book_balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `item_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `notes` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','undone') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `undone_by` INT UNSIGNED DEFAULT NULL,
  `undone_at` DATETIME DEFAULT NULL,
  `undo_reason` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_br_account` (`account_id`,`statement_date`),
  CONSTRAINT `fk_br_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_recon_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recon_id` INT UNSIGNED NOT NULL,
  `line_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bri_line` (`line_id`),
  KEY `idx_bri_recon` (`recon_id`),
  CONSTRAINT `fk_bri_recon` FOREIGN KEY (`recon_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bri_line` FOREIGN KEY (`line_id`) REFERENCES `voucher_lines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `tax_type` ENUM('output','input','withholding','advance') NOT NULL,
  `rate` DECIMAL(7,3) NOT NULL DEFAULT 0.000,
  `account_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_code` (`code`),
  KEY `idx_tax_account` (`account_id`),
  CONSTRAINT `fk_tax_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tax_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tax_rate_id` INT UNSIGNED NOT NULL,
  `tax_type` ENUM('output','input','withholding','advance') NOT NULL,
  `rate` DECIMAL(7,3) NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  `entry_date` DATE NOT NULL,
  `party_name` VARCHAR(150) DEFAULT NULL,
  `invoice_no` VARCHAR(60) DEFAULT NULL,
  `base_amount` DECIMAL(18,2) NOT NULL,
  `tax_amount` DECIMAL(18,2) NOT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_te_voucher` (`voucher_id`),
  KEY `idx_te_rate` (`tax_rate_id`),
  KEY `idx_te_date` (`entry_date`),
  CONSTRAINT `fk_te_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`),
  CONSTRAINT `fk_te_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`module`,`module_label`,`action`,`label`,`sort_order`) VALUES
('financial_closing','Financial Closing','view','View financial closing',140),
('financial_closing','Financial Closing','close','Close fiscal year',141),
('financial_closing','Financial Closing','reopen','Reopen closed year',142),
('refund','Refund','view','View refunds',150),
('refund','Refund','add','Create refund',151),
('refund','Refund','void','Void refund',152),
('stipend_reversal','Stipend Reverse Payment','view','View stipend reversals',160),
('stipend_reversal','Stipend Reverse Payment','add','Create stipend reversal',161),
('stipend_reversal','Stipend Reverse Payment','void','Void stipend reversal',162),
('soe','SOE (Statement of Expenditure)','view','View SOE',170),
('soe','SOE (Statement of Expenditure)','export','Export SOE',171),
('banking','Bank Accounts','view','View bank accounts',180),
('bank_recon','Bank Reconciliation','view','View reconciliations',190),
('bank_recon','Bank Reconciliation','add','Reconcile',191),
('bank_recon','Bank Reconciliation','void','Undo reconciliation',192),
('tax_rates','Tax Rates','view','View tax rates',200),
('tax_rates','Tax Rates','add','Add tax rate',201),
('tax_rates','Tax Rates','edit','Edit tax rate',202),
('tax_rates','Tax Rates','delete','Delete tax rate',203),
('tax_entries','Tax Entries','view','View tax entries',210),
('tax_entries','Tax Entries','add','Add tax entry',211),
('tax_entries','Tax Entries','void','Void tax entry',212),
('tax_entries','Tax Entries','export','Export tax entries',213),
('tax_report','Tax Summary','view','View tax summary',220),
('tax_report','Tax Summary','export','Export tax summary',221);

INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`) SELECT 1, `id` FROM `permissions`;
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `module` IN ('refund','stipend_reversal','soe','banking','bank_recon','tax_entries','tax_report')
   OR (`module` IN ('financial_closing','tax_rates') AND `action`='view');
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 3, `id` FROM `permissions`
WHERE `action`='view' AND `module` IN ('refund','stipend_reversal','soe','banking','bank_recon','tax_entries','tax_report','tax_rates');

INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'1105','Input Tax Receivable','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='1100';
INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'1106','Advance Tax (deducted from us)','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='1100';
INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'2104','Sales Tax Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='2100';
INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'2105','Withholding Tax Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='2100';

INSERT IGNORE INTO `tax_rates` (`code`,`name`,`tax_type`,`rate`,`account_id`,`is_active`,`created_at`)
SELECT 'GST-OUT','Sales Tax (GST) on sales','output',18,a.`id`,1,NOW() FROM `accounts` a WHERE a.`code`='2104' AND a.`name`='Sales Tax Payable';
INSERT IGNORE INTO `tax_rates` (`code`,`name`,`tax_type`,`rate`,`account_id`,`is_active`,`created_at`)
SELECT 'GST-IN','Input Tax (GST) on purchases','input',18,a.`id`,1,NOW() FROM `accounts` a WHERE a.`code`='1105' AND a.`name`='Input Tax Receivable';
