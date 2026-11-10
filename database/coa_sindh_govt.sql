-- =====================================================================
--  Replace the Chart of Accounts with the Government (Sindh / NAM style) chart.
--  ONLY for a database that has NO vouchers / opening balances yet.
--  Back up first. This deletes the old accounts, tax rates and asset categories.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `bank_recon_items`; DELETE FROM `bank_reconciliations`;
DELETE FROM `tax_entries`; DELETE FROM `tax_rates`;
DELETE FROM `asset_depreciation`; DELETE FROM `fixed_assets`; DELETE FROM `asset_categories`;
DELETE FROM `budget_items`; DELETE FROM `opening_balances`;
DELETE FROM `accounts`;
ALTER TABLE `accounts` AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

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
