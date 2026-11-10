# Accounting System - Phase 1 + 2 + 3

CodeIgniter 3 + MySQL + Bootstrap 5 + jQuery/AJAX. Simple light theme, every action is written to an audit log, and every screen/button is controlled by role permissions.

## Install (XAMPP / cPanel)

1. Copy this folder to `htdocs/accounting` (or your hosting `public_html`).
2. Create an empty MySQL database (utf8mb4) and import **`database/schema.sql`** (phpMyAdmin > Import).
3. Open `application/config/database.php` and set hostname, username, password, database.
4. Make sure `application/sessions`, `application/logs` and `application/cache` are writable.
5. Open `http://localhost/accounting/` and sign in.

**Default login:** `admin` / `admin123` - change it from the top-right menu > *Change password*.

Needs PHP 7.4 or newer (tested on PHP 8.3 and MariaDB 10.11) with `mysqli` and `mbstring`.
If pages other than the login page give 404, enable `mod_rewrite` / `AllowOverride All`
(or set `RewriteBase /accounting/` in `.htaccess`).
To see PHP errors while developing, set the environment variable `CI_ENV=development`
(or change the default in `index.php`).

## Upgrading an existing installation

1. Back up your database.
2. Copy the new files over the old ones (keep your `application/config/database.php`).
3. Import the upgrade script(s) once, in order (phpMyAdmin > SQL). Do not import `schema.sql` on a live database.
   * from Phase 1: `database/upgrade_phase2.sql` then `database/upgrade_phase3.sql`
   * from Phase 2: `database/upgrade_phase3.sql`
4. Settings > Roles & Permissions: tick the new permissions for your roles (Super Admin already has them; the default Accountant and Viewer roles get sensible defaults).

## What is in the system

| Menu | Status |
|---|---|
| Dashboard (cash/bank, income vs expense chart, recent vouchers) | Done (Phase 1) |
| Accounts > General Ledger, Journal Vouchers, Chart of Accounts, Opening Balance, Quick Entries | Done (Phase 1) |
| Accounts > **Financial Closing** (closing journal, carry-forward to next year, lock, reopen) | Done (Phase 2) |
| Accounts > **Refund** (money returned against a receipt voucher) | Done (Phase 2) |
| Accounts > **SOE** (Statement of Expenditure: period, cumulative, month by month, CSV, print) | Done (Phase 2) |
| Accounts > **Stipend Reverse Payment** (payment returned / cancelled, linked to the payment voucher) | Done (Phase 2) |
| **Banking** > Bank Accounts (book balance, last reconciled, items to clear), Bank Reconciliation (tick items, difference must be zero, undo) | Done (Phase 2) |
| **Tax** > Tax Rates, Tax Entries (output / input / withholding / advance tax, each posts a balanced voucher), Tax Summary | Done (Phase 2) |
| Reports > Accounts Report (Trial Balance, Day Book), Financial Report (Income Statement, Balance Sheet), Tax Report | Done |
| Settings > Users, Roles & Permissions matrix, Fiscal Years, Audit Log, General Settings | Done (Phase 1) |
| **Budget** > monthly budget per income / expense account for each fiscal year | Done (Phase 3) |
| **Asset** > Asset Register (purchase voucher, disposal with gain / loss), Depreciation (monthly run, straight line or reducing balance) | Done (Phase 3) |
| **Payroll** > Employees, Payroll Runs (accrual voucher, pay salaries, void), payslips | Done (Phase 3) |
| **PMU** > Projects, Project Statement (budget, income, spending of tagged vouchers) | Done (Phase 3) |
| **Masters** > Suppliers (bills, payments, statement), Assets (asset categories and their accounts) | Done (Phase 3) |
| Reports > Budget Report, Vendor Report (ageing), Banking Report, Payroll Report, **Graphs** | Done (Phase 3) |

## How the rules work

* **Financial closing:** posts one closing journal (income + expense -> retained earnings account), carries asset / liability / equity balances into the next year's opening balances (the next year is created if missing), and locks the year. Income statements of a closed year still show the full-year profit. Reopen needs its own permission and a reason; the closing journal is voided and the carried balances removed.
* **Refund / Stipend reversal:** always linked to the original voucher and limited to what is still unreversed on it. The original voucher cannot be voided while an active refund / reversal exists. Voiding a refund frees its amount again.
* **Bank reconciliation:** only Bank accounts. A reconciliation can be saved only when the difference is zero. Reconciled vouchers cannot be voided or edited until the reconciliation is undone (latest one first). Items left unticked stay open, also across a year-end closing.
* **Tax entries:** the tax amount is calculated from the rate on the server. Types: *output* (sales tax charged), *input* (sales tax paid), *withholding* (tax we deduct from payments), *advance* (tax deducted from our receipts). Tax-entry vouchers cannot be edited in the Journal screen; void and re-enter.

* **Suppliers:** a bill posts Dr expense / asset, Cr the supplier's payable account; a payment posts Dr payable, Cr cash / bank and can be matched to one bill. Bills with payments cannot be voided until the payments are. The Vendor Report compares the supplier sub-ledger with the ledger balance of the payable accounts.
* **Assets:** an asset can post its own purchase voucher (or be entered without one if it is already in the books / opening balances). Depreciation is run per month for all assets in one voucher (Dr expense, Cr accumulated depreciation per category); skipped months are caught up inside the same fiscal year; only the latest run can be voided. Disposal posts proceeds, removes cost and accumulated depreciation and books the gain / loss to account 4003; it can be reversed.
* **Payroll:** one run per month (void it to redo): Dr salary expense (gross), Cr deductions payable, Cr salaries payable (net). *Pay salaries* posts Dr salaries payable, Cr cash / bank. A paid run cannot be voided until its payment is.
* **Projects (PMU):** pick a project on a Journal Voucher, Quick Entry, supplier bill / payment or payroll run. The Project Statement sums the income and expense lines of those vouchers. Vouchers created from Suppliers, Assets and Payroll can only be changed / voided from their own screen.
* **Budget:** amounts are for income and expense accounts only; the Budget Report compares them with posted actuals (year-end closing entries excluded). Variance is positive when favourable.

* **Nothing is deleted from the books.** Vouchers are *voided* with a reason (they stop counting in every report but stay visible). Journal vouchers can be edited; the before/after copy is kept in the audit log. Payment/receipt/transfer entries are corrected by void + new entry.
* Every voucher must balance, needs two or more lines, only posting (non-group) active accounts, and a date inside an open fiscal year.
* Voucher numbers: `JV-2026-27-0001` (prefix per type and fiscal year, prefixes editable in Settings).
* Trial balance, ledger and balance sheet only count posted vouchers.
* **Permissions:** Settings > Roles & Permissions. Each module has View / Add / Edit / Delete / Void / Print / Export / Save as relevant. Menu items, buttons and the server endpoints are all checked. *Super Admin* always has full access.
* **Audit log:** login, failed login, logout, create / update / delete / void / activate, print, export, permission changes, settings changes. Failed logins lock the user/IP for 10 minutes after 5 attempts.

## Adding more modules

1. Add the module's permissions as new rows in `permissions` (and tick them for roles in the Roles screen).
2. Create the controller extending `MY_Controller`, call `$this->require_perm('module','action')`, and write audit entries with `$this->audit(...)`.
3. Add the menu entry in `application/helpers/app_helper.php` (`menu_items()`) and a route in `application/config/routes.php`.

## Folder map

```
application/core/MY_Controller.php    login check, permissions, json/partial helpers, audit helper
application/helpers/app_helper.php    icons, formatting, sidebar menu definition
application/models/                   Account, Voucher, Report (all balances), Closing, Reversal, Bank, Supplier, Asset, Project, Fy, User, Role, Audit, Setting
application/core/Reversal_base.php    shared screen logic of Refund and Stipend Reverse Payment
application/controllers/              one controller per screen
application/views/                    layout + one folder per module (_grid / _form partials load via AJAX)
assets/js/app.js                      ajax forms, modals, confirm/void dialogs, ajax tables, toasts
database/schema.sql                   fresh install schema + seed (roles, permissions, COA, FY 2026-27, tax accounts and rates, admin)
database/upgrade_phase2.sql           one-time upgrade of a Phase 1 database
database/upgrade_phase3.sql           one-time upgrade of a Phase 2 database
```

## Government (Sindh / NAM style) Chart of Accounts

`database/schema.sql` now seeds a government-style chart (budget object classification):
`A` Expenditure (A01 Employees Related Expenses, A03 Operating Expenses, A04 Retirement Benefits, A05 Grants, A09 Physical Assets, A12 Civil Works, A13 Repairs...),
`B` Tax Revenue, `C` Non-Tax Revenue, `D` Grants-in-Aid, `F` Assets, `G` Liabilities / Public Account, `H` Fund Balance.
Object codes for A/B/C follow the Pakistan NAM classification; F/G/H/D heads are a standard style adopted for this system - rename freely in Chart of Accounts.

* **Fresh install**: just import `schema.sql`.
* **Existing database with NO vouchers yet**: backup, then import `database/coa_sindh_govt.sql` (replaces the chart).
* **Existing database with vouchers**: keep your chart (modules fall back to the old codes) or add the new heads by hand from Chart of Accounts.
* Default accounts used by Suppliers / Payroll / Asset disposal are Settings keys `acc_payable`, `acc_salary_payable`, `acc_payroll_ded`, `acc_disposal_gain`, `acc_salary_exp` (value = account code).
