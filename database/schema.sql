-- =====================================================================
--  Accounting System  -  Phase 1  -  Fresh install schema + seed data
--  Import this file into an EMPTY database (phpMyAdmin > Import).
--  Default login:  admin  /  admin123   (change it after first login)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(40) NOT NULL,
  `module_label` VARCHAR(80) NOT NULL,
  `action` VARCHAR(30) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  KEY `idx_user_role` (`role_id`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `setting_key` VARCHAR(60) NOT NULL,
  `setting_value` TEXT,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(60) DEFAULT NULL,
  `module` VARCHAR(40) NOT NULL,
  `action` VARCHAR(30) NOT NULL,
  `record_id` VARCHAR(40) DEFAULT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `old_data` MEDIUMTEXT,
  `new_data` MEDIUMTEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module` (`module`,`action`),
  KEY `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fiscal_years` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(60) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `closed_by` INT UNSIGNED DEFAULT NULL,
  `closing_voucher_id` INT UNSIGNED DEFAULT NULL,
  `carried_from_id` INT UNSIGNED DEFAULT NULL,
  `carried_to_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fy_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `account_type` ENUM('asset','liability','equity','income','expense') NOT NULL,
  `is_group` TINYINT(1) NOT NULL DEFAULT 0,
  `cash_bank` ENUM('none','cash','bank') NOT NULL DEFAULT 'none',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_account_code` (`code`),
  KEY `idx_account_parent` (`parent_id`),
  CONSTRAINT `fk_account_parent` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `opening_balances` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` INT UNSIGNED NOT NULL,
  `account_id` INT UNSIGNED NOT NULL,
  `debit` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ob` (`fiscal_year_id`,`account_id`),
  KEY `idx_ob_account` (`account_id`),
  CONSTRAINT `fk_ob_fy` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`),
  CONSTRAINT `fk_ob_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vouchers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `voucher_type` ENUM('JV','PV','RV','CV') NOT NULL DEFAULT 'JV',
  `fiscal_year_id` INT UNSIGNED NOT NULL,
  `seq` INT UNSIGNED NOT NULL,
  `voucher_no` VARCHAR(40) NOT NULL,
  `voucher_date` DATE NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `narration` TEXT,
  `total_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('posted','void') NOT NULL DEFAULT 'posted',
  `is_closing` TINYINT(1) NOT NULL DEFAULT 0,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `void_reason` VARCHAR(255) DEFAULT NULL,
  `voided_by` INT UNSIGNED DEFAULT NULL,
  `voided_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_v_project` (`project_id`),
  UNIQUE KEY `uq_voucher_no` (`voucher_no`),
  UNIQUE KEY `uq_voucher_seq` (`voucher_type`,`fiscal_year_id`,`seq`),
  KEY `idx_voucher_date` (`voucher_date`),
  KEY `idx_voucher_fy` (`fiscal_year_id`),
  CONSTRAINT `fk_voucher_fy` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `voucher_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `voucher_id` INT UNSIGNED NOT NULL,
  `account_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `debit` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_vl_voucher` (`voucher_id`),
  KEY `idx_vl_account` (`account_id`),
  CONSTRAINT `fk_vl_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vl_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reversals` (
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

CREATE TABLE `bank_reconciliations` (
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

CREATE TABLE `bank_recon_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recon_id` INT UNSIGNED NOT NULL,
  `line_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bri_line` (`line_id`),
  KEY `idx_bri_recon` (`recon_id`),
  CONSTRAINT `fk_bri_recon` FOREIGN KEY (`recon_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bri_line` FOREIGN KEY (`line_id`) REFERENCES `voucher_lines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tax_rates` (
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

CREATE TABLE `tax_entries` (
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

CREATE TABLE `projects` (
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

CREATE TABLE `suppliers` (
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

CREATE TABLE `supplier_txns` (
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

CREATE TABLE `asset_categories` (
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

CREATE TABLE `fixed_assets` (
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

CREATE TABLE `asset_depreciation` (
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

CREATE TABLE `budget_items` (
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

CREATE TABLE `employees` (
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

CREATE TABLE `payroll_runs` (
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

CREATE TABLE `payroll_lines` (
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

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  SEED: roles
-- ---------------------------------------------------------------------
INSERT INTO `roles` (`id`,`name`,`description`,`is_system`,`created_at`) VALUES
(1,'Super Admin','Full access to everything (cannot be edited)',1,NOW()),
(2,'Accountant','Day to day accounting work',0,NOW()),
(3,'Viewer','Read only access to accounts and reports',0,NOW());

-- ---------------------------------------------------------------------
--  SEED: permissions  (module, label, action, label, sort)
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`module`,`module_label`,`action`,`label`,`sort_order`) VALUES
('dashboard','Dashboard','view','View dashboard',10),
('coa','Chart of Accounts','view','View accounts',20),
('coa','Chart of Accounts','add','Add account',21),
('coa','Chart of Accounts','edit','Edit account',22),
('coa','Chart of Accounts','delete','Delete account',23),
('coa','Chart of Accounts','export','Export accounts',24),
('opening_balance','Opening Balance','view','View opening balances',30),
('opening_balance','Opening Balance','save','Save opening balances',31),
('journal','Journal Vouchers','view','View vouchers',40),
('journal','Journal Vouchers','add','Create voucher',41),
('journal','Journal Vouchers','edit','Edit voucher',42),
('journal','Journal Vouchers','void','Void voucher',43),
('journal','Journal Vouchers','print','Print voucher',44),
('quick_entries','Quick Entries','view','View quick entries',50),
('quick_entries','Quick Entries','add','Add quick entry',51),
('quick_entries','Quick Entries','void','Void quick entry',52),
('ledger','General Ledger','view','View ledger',60),
('ledger','General Ledger','export','Export ledger',61),
('rep_accounts','Accounts Reports','view','View accounts reports',70),
('rep_accounts','Accounts Reports','export','Export accounts reports',71),
('rep_financial','Financial Reports','view','View financial reports',80),
('rep_financial','Financial Reports','export','Export financial reports',81),
('fiscal_years','Fiscal Years','view','View fiscal years',90),
('fiscal_years','Fiscal Years','add','Add fiscal year',91),
('fiscal_years','Fiscal Years','edit','Edit / activate fiscal year',92),
('users','Users','view','View users',100),
('users','Users','add','Add user',101),
('users','Users','edit','Edit user',102),
('users','Users','delete','Delete user',103),
('roles','Roles & Permissions','view','View roles',110),
('roles','Roles & Permissions','add','Add role',111),
('roles','Roles & Permissions','edit','Edit role permissions',112),
('roles','Roles & Permissions','delete','Delete role',113),
('audit','Audit Log','view','View audit log',120),
('settings','Settings','view','View settings',130),
('settings','Settings','edit','Change settings',131),
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
('tax_report','Tax Summary','export','Export tax summary',221),
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

-- Super Admin: everything
INSERT INTO `role_permissions` (`role_id`,`permission_id`) SELECT 1, `id` FROM `permissions`;
-- Accountant
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `module` IN ('dashboard','coa','opening_balance','journal','quick_entries','ledger','rep_accounts','rep_financial',
                   'refund','stipend_reversal','soe','banking','bank_recon','tax_entries','tax_report')
   OR (`module` IN ('fiscal_years','financial_closing','tax_rates') AND `action`='view');
-- Viewer
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 3, `id` FROM `permissions`
WHERE `action`='view' AND `module` IN ('dashboard','coa','journal','quick_entries','ledger','rep_accounts','rep_financial',
                                      'refund','stipend_reversal','soe','banking','bank_recon','tax_entries','tax_report','tax_rates');

-- ---------------------------------------------------------------------
--  SEED: admin user  (password: admin123)
-- ---------------------------------------------------------------------
INSERT INTO `users` (`role_id`,`full_name`,`username`,`email`,`password`,`status`,`created_at`)
VALUES (1,'System Administrator','admin','admin@example.com','$2y$10$tZ7UzSLyTRGiHNO7Dd.k1Owo2.WEoGwT3MStmpdXJnscIjLrA4Lfe',1,NOW());

-- ---------------------------------------------------------------------
--  SEED: settings
-- ---------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('company_name','My Company'),
('company_address','Karachi, Pakistan'),
('company_phone',''),
('company_email',''),
('company_ntn',''),
('currency_code','PKR'),
('prefix_JV','JV'),
('prefix_PV','PV'),
('prefix_RV','RV'),
('prefix_CV','CV');

-- ---------------------------------------------------------------------
--  SEED: fiscal year (Pakistan: 1 July - 30 June)
-- ---------------------------------------------------------------------
INSERT INTO `fiscal_years` (`id`,`title`,`start_date`,`end_date`,`is_active`,`is_closed`,`created_at`)
VALUES (1,'FY 2026-27','2026-07-01','2027-06-30',1,0,NOW());

-- ---------------------------------------------------------------------
--  SEED: Chart of Accounts - Government (Sindh / NAM budget classification) style
--  A Expenditure  B/C Tax & Non-tax receipts  D Grants-in-aid  F Assets  G Liabilities  H Fund balance
-- ---------------------------------------------------------------------
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'F','Assets','asset',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F01','Cash and Bank Balances','asset',1,'none',NOW() FROM `accounts` p WHERE p.`code`='F';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0101','Cash in Hand','asset',0,'cash',NOW() FROM `accounts` p WHERE p.`code`='F01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0102','Cash at Bank - Current Account','asset',0,'bank',NOW() FROM `accounts` p WHERE p.`code`='F01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0103','Cash at Bank - Project / Donor Account','asset',0,'bank',NOW() FROM `accounts` p WHERE p.`code`='F01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0104','Personal Ledger Account (PLA) / Treasury','asset',0,'bank',NOW() FROM `accounts` p WHERE p.`code`='F01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F02','Advances, Deposits and Receivables','asset',1,'none',NOW() FROM `accounts` p WHERE p.`code`='F';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0201','Advances to Employees','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0202','Advances to Suppliers / Contractors','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0203','Security Deposits Paid','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0204','Other Receivables','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0205','Input Sales Tax Receivable','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0206','Advance Income Tax (deducted from us)','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F03','Fixed Assets (Property, Plant and Equipment)','asset',1,'none',NOW() FROM `accounts` p WHERE p.`code`='F';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0301','Land','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0302','Buildings and Structures','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0303','Furniture and Fixtures','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0304','Computers and IT Equipment','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0305','Plant, Machinery and Equipment','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0306','Vehicles (Transport)','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0307','Capital Work in Progress','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'F0308','Accumulated Depreciation','asset',0,'none',NOW() FROM `accounts` p WHERE p.`code`='F03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'G','Liabilities','liability',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G01','Payables','liability',1,'none',NOW() FROM `accounts` p WHERE p.`code`='G';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0101','Accounts Payable - Suppliers / Contractors','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0102','Accrued Expenses','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0103','Retention Money / Security Deposits Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G02','Public Account - Deductions and Withholdings','liability',1,'none',NOW() FROM `accounts` p WHERE p.`code`='G';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0201','Salaries Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0202','Payroll Deductions Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0203','General Provident (GP) Fund Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0204','Benevolent Fund and Group Insurance Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0205','Sales Tax Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0206','Income Tax Withheld Payable','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G03','Deferred Liabilities','liability',1,'none',NOW() FROM `accounts` p WHERE p.`code`='G';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0301','Pension and Gratuity Liability','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0302','Deferred / Unspent Grants','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G04','Loans','liability',1,'none',NOW() FROM `accounts` p WHERE p.`code`='G';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'G0401','Long Term Loans','liability',0,'none',NOW() FROM `accounts` p WHERE p.`code`='G04';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'H','Fund Balance (Equity)','equity',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'H01','Accumulated Fund (Opening Capital)','equity',0,'none',NOW() FROM `accounts` p WHERE p.`code`='H';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'H02','Accumulated Surplus (Retained Earnings)','equity',0,'none',NOW() FROM `accounts` p WHERE p.`code`='H';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'H03','Capital / Development Grants Reserve','equity',0,'none',NOW() FROM `accounts` p WHERE p.`code`='H';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'D','Grants-in-Aid Received','income',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D01','Government Grants','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='D';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D0101','Recurring Grant - Salary (Govt. of Sindh)','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='D01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D0102','Recurring Grant - Non-Salary (Govt. of Sindh)','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='D01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D0103','Development / Capital Grant (ADP)','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='D01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D0104','Federal Government Grant','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='D01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'D0105','Donor / Foreign Aid Grant','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='D01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'B','Tax Revenue','income',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'B01','Direct Taxes','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='B';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'B01101','Taxes on Income','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='B01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'B02','Indirect Taxes','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='B';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'B02301','Sales Tax / Provincial Sales Tax on Services','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='B02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'C','Non-Tax Revenue','income',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C01','Income from Property and Enterprise','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='C';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C01201','Interest / Profit on Bank Deposits','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C01301','Rent and Lease Income','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C02','Receipts from Civil Administration and Other Functions','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='C';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C02101','Fees, Fines and Penalties','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C02201','Training / Course / Registration Fees','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C02301','Sale of Tender Documents','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C03','Miscellaneous Receipts','income',1,'none',NOW() FROM `accounts` p WHERE p.`code`='C';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C03901','Miscellaneous Receipts','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C03902','Recovery of Previous Year Expenditure','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'C03904','Gain / (Loss) on Disposal of Assets','income',0,'none',NOW() FROM `accounts` p WHERE p.`code`='C03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`) VALUES (NULL,'A','Expenditure','expense',1,'none',NOW());
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01','Employees Related Expenses','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A011','Pay','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01101','Pay of Officers','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A011';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01151','Pay of Other Staff','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A011';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A012','Allowances','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A01';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01201','House Rent Allowance','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01202','Medical Allowance','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01203','Conveyance Allowance','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01204','Adhoc Relief / Dearness Allowance','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01205','Other Allowances','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01206','Overtime Allowance','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A01207','Honorarium and Fee','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A012';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A02','Project Pre-investment Analysis','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A02001','Feasibility Studies and Surveys','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A02';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03','Operating Expenses','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A032','Communications','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03201','Postage and Telegraph','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A032';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03202','Telephone and Trunk Calls','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A032';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03203','Internet and Courier Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A032';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A033','Utilities','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03301','Gas Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A033';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03302','Water Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A033';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03303','Electricity Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A033';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A034','Occupancy Costs','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03401','Rent of Land','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A034';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03402','Rent of Office Building','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A034';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A038','Travel and Transportation','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03805','Travelling Allowance (TA / DA)','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A038';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03807','POL Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A038';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A039','General','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A03';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03901','Stationery and Office Supplies','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03902','Printing and Publication','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03905','Newspapers and Periodicals','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03907','Advertising and Publicity','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03911','Training, Seminars and Workshops','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03970','Bank Charges','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A03999','Miscellaneous Expenses','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A039';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A04','Employees Retirement Benefits','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A04101','Pension','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A04';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A04201','Gratuity and Commutation','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A04';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A04301','Leave Encashment','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A04';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A05','Grants, Subsidies and Write-off Loans','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A05001','Grants-in-Aid to Institutions','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A05';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A05101','Subsidies','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A05';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A05301','Scholarships and Stipends','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A05';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A06','Transfers','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A06001','Transfers to Other Funds','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A06';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A07','Interest Payment','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A07001','Interest on Loans','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A07';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A08','Loans and Advances','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A08001','Loans and Advances to Employees','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A08';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09','Physical Assets','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09101','Purchase of Land','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09201','Purchase of Buildings and Structures','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09301','Plant and Machinery','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09401','Purchase of Transport (Vehicles)','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09501','Purchase of Furniture and Fixtures','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09601','Purchase of Computers and IT Equipment','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A09701','Library Books and Periodicals','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A09';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A10','Principal Repayments of Loans','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A10001','Repayment of Loans','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A10';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A11','Investment','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A11001','Investment in Securities','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A11';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A12','Civil Works','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A12001','Construction of Buildings','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A12';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A12002','Roads and Other Civil Works','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A12';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13','Repairs and Maintenance','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13101','Repair of Transport (Vehicles)','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A13';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13201','Repair of Machinery and Equipment','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A13';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13301','Repair of Furniture and Fixtures','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A13';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13401','Repair of Buildings and Structures','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A13';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A13701','Repair of Computers and IT Equipment','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A13';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A14','Non-Cash Expenses (Depreciation)','expense',1,'none',NOW() FROM `accounts` p WHERE p.`code`='A';
INSERT INTO `accounts` (`parent_id`,`code`,`name`,`account_type`,`is_group`,`cash_bank`,`created_at`)
SELECT p.`id`,'A14001','Depreciation Expense','expense',0,'none',NOW() FROM `accounts` p WHERE p.`code`='A14';

-- asset categories (default depreciation, accounts)
INSERT INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Furniture & Fixtures',a.`id`,c.`id`,e.`id`,'sl',10,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='F0303' AND c.`code`='F0308' AND e.`code`='A14001';
INSERT INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Computers & Equipment',a.`id`,c.`id`,e.`id`,'sl',33.33,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='F0304' AND c.`code`='F0308' AND e.`code`='A14001';
INSERT INTO `asset_categories` (`name`,`asset_account_id`,`accum_account_id`,`expense_account_id`,`dep_method`,`dep_rate`,`is_active`,`created_at`)
SELECT 'Vehicles',a.`id`,c.`id`,e.`id`,'wdv',20,1,NOW() FROM `accounts` a, `accounts` c, `accounts` e WHERE a.`code`='F0306' AND c.`code`='F0308' AND e.`code`='A14001';

-- default tax rates
INSERT INTO `tax_rates` (`code`,`name`,`tax_type`,`rate`,`account_id`,`is_active`,`created_at`)
SELECT 'GST-OUT','Sales Tax (GST) on sales','output',18,a.`id`,1,NOW() FROM `accounts` a WHERE a.`code`='G0205';
INSERT INTO `tax_rates` (`code`,`name`,`tax_type`,`rate`,`account_id`,`is_active`,`created_at`)
SELECT 'GST-IN','Input Tax (GST) on purchases','input',18,a.`id`,1,NOW() FROM `accounts` a WHERE a.`code`='F0205';
INSERT INTO `tax_rates` (`code`,`name`,`tax_type`,`rate`,`account_id`,`is_active`,`created_at`)
SELECT 'WHT-SRV','Income tax withheld on services','withholding',8,a.`id`,1,NOW() FROM `accounts` a WHERE a.`code`='G0206';

-- accounts the modules use by default (edit the codes here if you rename the chart)
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('acc_payable','G0101'),('acc_salary_payable','G0201'),('acc_payroll_ded','G0202'),('acc_disposal_gain','C03904'),('acc_salary_exp','A01101')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);



INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `sort_order` >= 300;
INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT 3, `id` FROM `permissions` WHERE `sort_order` >= 300 AND `action`='view'
  AND `module` NOT IN ('employees','payroll','payroll_report');

