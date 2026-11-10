-- =====================================================================
--  Accounting System: upgrade from Phase 2 to Phase 3
--  Run ONCE on an existing Phase 2 database (phpMyAdmin > SQL).
--  Fresh installs should import schema.sql instead (it already contains this).
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE `vouchers`
  ADD COLUMN `project_id` INT UNSIGNED DEFAULT NULL AFTER `is_closing`,
  ADD KEY `idx_v_project` (`project_id`);

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `funder` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `budget` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','closed') NOT NULL DEFAULT 'active',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `tax_no` VARCHAR(40) DEFAULT NULL,
  `payable_account_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_supplier_code` (`code`),
  KEY `idx_supplier_acc` (`payable_account_id`),
  CONSTRAINT `fk_supplier_acc` FOREIGN KEY (`payable_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `supplier_txns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `kind` ENUM('bill','payment') NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  `txn_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  `ref_no` VARCHAR(60) DEFAULT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `bill_id` INT UNSIGNED DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stx_voucher` (`voucher_id`),
  KEY `idx_stx_supplier` (`supplier_id`,`txn_date`),
  KEY `idx_stx_bill` (`bill_id`),
  CONSTRAINT `fk_stx_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_stx_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_stx_bill` FOREIGN KEY (`bill_id`) REFERENCES `supplier_txns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `asset_account_id` INT UNSIGNED NOT NULL,
  `accum_account_id` INT UNSIGNED NOT NULL,
  `expense_account_id` INT UNSIGNED NOT NULL,
  `dep_method` ENUM('sl','wdv') NOT NULL DEFAULT 'sl',
  `dep_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assetcat_name` (`name`),
  CONSTRAINT `fk_ac_asset` FOREIGN KEY (`asset_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `fk_ac_accum` FOREIGN KEY (`accum_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `fk_ac_exp` FOREIGN KEY (`expense_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fixed_assets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_no` VARCHAR(20) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `serial_no` VARCHAR(80) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `purchase_date` DATE NOT NULL,
  `cost` DECIMAL(18,2) NOT NULL,
  `salvage_value` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `dep_method` ENUM('sl','wdv') NOT NULL DEFAULT 'sl',
  `dep_rate` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','disposed') NOT NULL DEFAULT 'active',
  `purchase_voucher_id` INT UNSIGNED DEFAULT NULL,
  `disposed_date` DATE DEFAULT NULL,
  `disposal_amount` DECIMAL(18,2) DEFAULT NULL,
  `disposal_voucher_id` INT UNSIGNED DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asset_no` (`asset_no`),
  KEY `idx_fa_cat` (`category_id`),
  CONSTRAINT `fk_fa_cat` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`),
  CONSTRAINT `fk_fa_pv` FOREIGN KEY (`purchase_voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_fa_dv` FOREIGN KEY (`disposal_voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asset_depreciation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL,
  `period` CHAR(7) NOT NULL,
  `months` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `amount` DECIMAL(18,2) NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dep_asset_period` (`asset_id`,`period`),
  KEY `idx_dep_voucher` (`voucher_id`),
  CONSTRAINT `fk_dep_asset` FOREIGN KEY (`asset_id`) REFERENCES `fixed_assets` (`id`),
  CONSTRAINT `fk_dep_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` INT UNSIGNED NOT NULL,
  `account_id` INT UNSIGNED NOT NULL,
  `m1` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m2` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m3` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m4` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m5` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m6` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m7` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m8` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m9` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m10` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m11` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `m12` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_budget_fy_acc` (`fiscal_year_id`,`account_id`),
  CONSTRAINT `fk_bi_fy` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`),
  CONSTRAINT `fk_bi_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emp_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `designation` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `basic_salary` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `allowances` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `fixed_deductions` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_code` (`emp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period` CHAR(7) NOT NULL,
  `run_date` DATE NOT NULL,
  `expense_account_id` INT UNSIGNED NOT NULL,
  `payable_account_id` INT UNSIGNED NOT NULL,
  `deduction_account_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `gross` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `net` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `voucher_id` INT UNSIGNED NOT NULL,
  `pay_voucher_id` INT UNSIGNED DEFAULT NULL,
  `paid_date` DATE DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_voucher` (`voucher_id`),
  KEY `idx_pr_period` (`period`),
  CONSTRAINT `fk_pr_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_pr_pay` FOREIGN KEY (`pay_voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_pr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id` INT UNSIGNED NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `basic` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `allowances` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `bonus` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `gross` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `net` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `remarks` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pl_run` (`run_id`),
  KEY `idx_pl_emp` (`employee_id`),
  CONSTRAINT `fk_pl_run` FOREIGN KEY (`run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pl_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `vouchers` ADD CONSTRAINT `fk_v_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

INSERT IGNORE INTO `permissions` (`module`,`module_label`,`action`,`label`,`sort_order`) VALUES
('suppliers','Suppliers','view','View suppliers',300),
('suppliers','Suppliers','add','Add supplier',301),
('suppliers','Suppliers','edit','Edit supplier',302),
('suppliers','Suppliers','delete','Delete supplier',303),
('supplier_txn','Supplier Bills & Payments','view','View supplier statement',310),
('supplier_txn','Supplier Bills & Payments','add','Record bill / payment',311),
('supplier_txn','Supplier Bills & Payments','void','Void bill / payment',312),
('vendor_report','Vendor Report','view','View vendor report',320),
('vendor_report','Vendor Report','export','Export vendor report',321),
('asset_categories','Assets Master','view','View asset categories',330),
('asset_categories','Assets Master','add','Add asset category',331),
('asset_categories','Assets Master','edit','Edit asset category',332),
('asset_categories','Assets Master','delete','Delete asset category',333),
('fixed_assets','Asset Register','view','View fixed assets',340),
('fixed_assets','Asset Register','add','Add fixed asset',341),
('fixed_assets','Asset Register','edit','Edit fixed asset',342),
('fixed_assets','Asset Register','dispose','Dispose asset',343),
('depreciation','Depreciation','view','View depreciation',350),
('depreciation','Depreciation','run','Run depreciation',351),
('depreciation','Depreciation','void','Void depreciation run',352),
('budget','Budget','view','View budget',360),
('budget','Budget','edit','Edit budget',361),
('budget_report','Budget Report','view','View budget report',370),
('budget_report','Budget Report','export','Export budget report',371),
('employees','Employees','view','View employees',380),
('employees','Employees','add','Add employee',381),
('employees','Employees','edit','Edit employee',382),
('employees','Employees','delete','Delete employee',383),
('payroll','Payroll','view','View payroll',390),
('payroll','Payroll','run','Run payroll',391),
('payroll','Payroll','pay','Pay salaries',392),
('payroll','Payroll','void','Void payroll / payment',393),
('payroll','Payroll','print','Print payslips',394),
('payroll_report','Payroll Report','view','View payroll report',400),
('payroll_report','Payroll Report','export','Export payroll report',401),
('projects','PMU Projects','view','View projects',410),
('projects','PMU Projects','add','Add project',411),
('projects','PMU Projects','edit','Edit project',412),
('projects','PMU Projects','delete','Delete project',413),
('pmu_report','Project Statement','view','View project statement',420),
('pmu_report','Project Statement','export','Export project statement',421),
('bank_report','Banking Report','view','View banking report',430),
('bank_report','Banking Report','export','Export banking report',431),
('graphs','Graphs','view','View graphs',440);

INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`) SELECT 1, `id` FROM `permissions`;
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `sort_order` >= 300;
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 3, `id` FROM `permissions` WHERE `sort_order` >= 300 AND `action`='view'
  AND `module` NOT IN ('employees','payroll','payroll_report');

INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'4003','Gain / (Loss) on Asset Disposal','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='4000';
INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'2106','Salaries Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='2100';
INSERT IGNORE INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'2107','Payroll Deductions Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='2100';

INSERT IGNORE INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Furniture & Fixtures',a.`id`,c.`id`,e.`id`,'sl',10,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='1201' AND c.`code`='1204' AND e.`code`='5005';
INSERT IGNORE INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Computers & Equipment',a.`id`,c.`id`,e.`id`,'sl',33.33,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='1202' AND c.`code`='1204' AND e.`code`='5005';
INSERT IGNORE INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Vehicles',a.`id`,c.`id`,e.`id`,'wdv',20,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='1203' AND c.`code`='1204' AND e.`code`='5005';
