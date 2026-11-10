<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* ------------------------------------------------------------------
 | Small view / formatting helpers
 * ------------------------------------------------------------------ */

if (!function_exists('h')) {
	function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('fmt')) {
	/** 1234.5 => 1,234.50 */
	function fmt($n, $dec = 2) { return number_format((float) $n, $dec); }
}

if (!function_exists('fmt_blank')) {
	/** like fmt() but shows an empty string for zero */
	function fmt_blank($n) { return abs((float) $n) < 0.005 ? '' : number_format((float) $n, 2); }
}

if (!function_exists('dmy')) {
	function dmy($date) { return ($date && $date !== '0000-00-00') ? date('d-M-Y', strtotime($date)) : ''; }
}

if (!function_exists('dmy_time')) {
	function dmy_time($dt) { return $dt ? date('d-M-Y h:i A', strtotime($dt)) : ''; }
}

if (!function_exists('csrf_field')) {
	function csrf_field() {
		$CI =& get_instance();
		return '<input type="hidden" name="' . $CI->security->get_csrf_token_name() . '" value="' . $CI->security->get_csrf_hash() . '">';
	}
}

if (!function_exists('can')) {
	/** Does the logged-in user have permission? can('coa','add') */
	function can($module, $action = 'view') {
		$CI =& get_instance();
		return method_exists($CI, 'can') ? $CI->can($module, $action) : false;
	}
}

if (!function_exists('setting')) {
	function setting($key, $default = '') {
		static $all = null;
		if ($all === null) {
			$CI =& get_instance();
			$all = array();
			$q = $CI->db->get('settings');
			foreach ($q->result() as $r) { $all[$r->setting_key] = $r->setting_value; }
		}
		return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
	}
}

if (!function_exists('paginate_html')) {
	/** Ajax pagination (links carry data-page, handled by app.js) */
	function paginate_html($total, $page, $per_page) {
		$pages = (int) ceil($total / max(1, $per_page));
		if ($pages <= 1) { return ''; }
		$out = '<nav><ul class="pagination pagination-sm mb-0 justify-content-end">';
		$link = function ($p, $label, $active = false, $disabled = false) {
			return '<li class="page-item' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '') . '">'
				. '<a class="page-link" href="#" data-page="' . $p . '">' . $label . '</a></li>';
		};
		$out .= $link(max(1, $page - 1), '&laquo;', false, $page <= 1);
		$start = max(1, $page - 2); $end = min($pages, $page + 2);
		if ($start > 1) { $out .= $link(1, '1'); if ($start > 2) { $out .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; } }
		for ($i = $start; $i <= $end; $i++) { $out .= $link($i, $i, $i == $page); }
		if ($end < $pages) { if ($end < $pages - 1) { $out .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; } $out .= $link($pages, $pages); }
		$out .= $link(min($pages, $page + 1), '&raquo;', false, $page >= $pages);
		return $out . '</ul></nav>';
	}
}

if (!function_exists('type_badge')) {
	function type_badge($type) {
		$map = array(
			'asset' => 'primary', 'liability' => 'warning', 'equity' => 'info',
			'income' => 'success', 'expense' => 'danger',
		);
		$c = isset($map[$type]) ? $map[$type] : 'secondary';
		return '<span class="badge badge-soft-' . $c . '">' . h(ucfirst($type)) . '</span>';
	}
}

if (!function_exists('voucher_type_name')) {
	function voucher_type_name($t) {
		$m = array('JV' => 'Journal Voucher', 'PV' => 'Payment Voucher', 'RV' => 'Receipt Voucher', 'CV' => 'Contra Voucher');
		return isset($m[$t]) ? $m[$t] : $t;
	}
}

if (!function_exists('status_badge')) {
	function status_badge($s) {
		return $s === 'void'
			? '<span class="badge badge-soft-danger">Void</span>'
			: '<span class="badge badge-soft-success">Posted</span>';
	}
}

/* ------------------------------------------------------------------
 | Inline SVG icons (no external icon library)
 * ------------------------------------------------------------------ */
if (!function_exists('icon')) {
	function icon($name, $size = 18) {
		static $p = array(
			'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
			'book'     => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 19.5V4.5"/>',
			'tree'     => '<rect x="3" y="3" width="6" height="5" rx="1"/><rect x="15" y="9" width="6" height="5" rx="1"/><rect x="15" y="17" width="6" height="4" rx="1"/><path d="M6 8v11h9M6 11.5h9"/>',
			'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
			'zap'      => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
			'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'undo'     => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/>',
			'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
			'wallet'   => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2z"/><circle cx="16.5" cy="14.5" r="1"/>',
			'pie'      => '<path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15z"/>',
			'box'      => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
			'bank'     => '<path d="M3 10 12 4l9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18"/>',
			'percent'  => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
			'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
			'briefcase'=> '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
			'chart'    => '<path d="M3 3v18h18"/><path d="M7 15v3M12 9v9M17 5v13"/>',
			'trend'    => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
			'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
			'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
			'history'  => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
			'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
			'plus'     => '<path d="M12 5v14M5 12h14"/>',
			'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
			'trash'    => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
			'eye'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
			'print'    => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
			'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
			'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
			'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
			'check'    => '<path d="M20 6 9 17l-5-5"/>',
			'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
			'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
			'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'key'      => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8M17 6l3 3M14 9l2 2"/>',
			'chevron'  => '<path d="m9 18 6-6-6-6"/>',
			'ban'      => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
			'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
			'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
			'copy'     => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
			'swap'     => '<path d="M7 16V4M3 8l4-4 4 4M17 8v12M21 16l-4 4-4-4"/>',
		);
		$d = isset($p[$name]) ? $p[$name] : $p['file'];
		return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
	}
}

/* ------------------------------------------------------------------
 | Sidebar menu definition
 |   perm  => permission module required to see the item (action "view")
 |   soon  => true when the screen is planned for a later phase
 * ------------------------------------------------------------------ */
if (!function_exists('tax_types')) {
	/** tax type => label */
	function tax_types() {
		return array(
			'output'      => 'Sales tax charged (output)',
			'input'       => 'Sales tax paid (input)',
			'withholding' => 'Tax withheld by us (payable)',
			'advance'     => 'Tax withheld from us (advance)',
		);
	}
}

if (!function_exists('tax_type_badge')) {
	function tax_type_badge($t) {
		$types = tax_types();
		$c = array('output' => 'primary', 'input' => 'info', 'withholding' => 'warning', 'advance' => 'success');
		return '<span class="badge badge-soft-' . (isset($c[$t]) ? $c[$t] : 'secondary') . '">' . h(isset($types[$t]) ? $types[$t] : $t) . '</span>';
	}
}

if (!function_exists('project_select')) {
	/** optional "Project" dropdown for voucher forms; prints nothing when no project is open */
	function project_select($selected = 0, $col = 'col-md-6') {
		$CI =& get_instance();
		$rows = $CI->db->where('status', 'active')->order_by('code')->get('projects')->result();
		if (!$rows) { return ''; }
		$o = '<div class="' . $col . '"><label class="form-label">Project (PMU)</label><select name="project_id" class="form-select"><option value="">- none -</option>';
		foreach ($rows as $p) { $o .= '<option value="' . (int) $p->id . '"' . ((int) $selected === (int) $p->id ? ' selected' : '') . '>' . h($p->code . ' - ' . $p->name) . '</option>'; }
		return $o . '</select></div>';
	}
}

if (!function_exists('month_range')) {
	/** first and last day of a YYYY-MM month, or null when the text is not a month */
	function month_range($ym) {
		if (!preg_match('/^(\d{4})-(\d{2})$/', (string) $ym, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) { return null; }
		$first = $m[1] . '-' . $m[2] . '-01';
		return array($first, date('Y-m-t', strtotime($first)));
	}
}

if (!function_exists('csv_out')) {
	/** send rows (array of arrays) as an Excel-friendly UTF-8 csv download */
	function csv_out($name, $rows) {
		$CI =& get_instance();
		$out = '';
		foreach ($rows as $r) {
			$out .= implode(',', array_map(function ($v) { return is_numeric($v) ? (string) $v : '"' . str_replace('"', '""', (string) $v) . '"'; }, $r)) . "\n";
		}
		$CI->output->set_content_type('text/csv', 'utf-8')->set_header('Content-Disposition: attachment; filename="' . $name . '"')->set_output("\xEF\xBB\xBF" . $out);
	}
}

if (!function_exists('menu_items')) {
	function menu_items() {
		$soon = function ($label, $slug) {
			return array('label' => $label, 'url' => 'coming-soon/' . $slug, 'soon' => true, 'match' => 'coming-soon/' . $slug);
		};
		return array(
			array('label' => 'Dashboard', 'icon' => 'home', 'url' => 'dashboard', 'perm' => 'dashboard', 'match' => 'dashboard'),

			array('label' => 'Accounts', 'icon' => 'book', 'children' => array(
				array('label' => 'General Ledger',     'url' => 'general-ledger',    'perm' => 'ledger',          'match' => 'general-ledger'),
				array('label' => 'Journal Vouchers',   'url' => 'journal-vouchers',  'perm' => 'journal',         'match' => 'journal-vouchers'),
				array('label' => 'Chart of Accounts',  'url' => 'chart-of-accounts', 'perm' => 'coa',             'match' => 'chart-of-accounts'),
				array('label' => 'Opening Balance',    'url' => 'opening-balance',   'perm' => 'opening_balance', 'match' => 'opening-balance'),
				array('label' => 'Quick Entries',      'url' => 'quick-entries',     'perm' => 'quick_entries',   'match' => 'quick-entries'),
				array('label' => 'Financial Closing',        'url' => 'financial-closing',       'perm' => 'financial_closing', 'match' => 'financial-closing'),
				array('label' => 'Refund',                   'url' => 'refund',                  'perm' => 'refund',            'match' => 'refund'),
				array('label' => 'SOE',                      'url' => 'soe',                     'perm' => 'soe',               'match' => 'soe'),
				array('label' => 'Stipend Reverse Payment',  'url' => 'stipend-reverse-payment', 'perm' => 'stipend_reversal',  'match' => 'stipend-reverse-payment'),
			)),

			array('label' => 'Budget',  'icon' => 'pie',      'children' => array(
				array('label' => 'Budget', 'url' => 'budget', 'perm' => 'budget', 'match' => 'budget'),
			)),
			array('label' => 'Asset',   'icon' => 'box',      'children' => array(
				array('label' => 'Asset Register', 'url' => 'fixed-assets', 'perm' => 'fixed_assets', 'match' => 'fixed-assets'),
				array('label' => 'Depreciation',   'url' => 'depreciation', 'perm' => 'depreciation', 'match' => 'depreciation'),
			)),
			array('label' => 'Banking', 'icon' => 'bank',     'children' => array(
				array('label' => 'Bank Accounts',       'url' => 'banking',             'perm' => 'banking',    'match' => 'banking'),
				array('label' => 'Bank Reconciliation', 'url' => 'bank-reconciliation', 'perm' => 'bank_recon', 'match' => 'bank-reconciliation'),
			)),
			array('label' => 'Tax',     'icon' => 'percent',  'children' => array(
				array('label' => 'Tax Entries', 'url' => 'tax-entries', 'perm' => 'tax_entries', 'match' => 'tax-entries'),
				array('label' => 'Tax Rates',   'url' => 'tax-rates',   'perm' => 'tax_rates',   'match' => 'tax-rates'),
				array('label' => 'Tax Summary', 'url' => 'tax-summary', 'perm' => 'tax_report',  'match' => 'tax-summary'),
			)),
			array('label' => 'Payroll', 'icon' => 'users',    'children' => array(
				array('label' => 'Payroll Runs', 'url' => 'payroll',   'perm' => 'payroll',   'match' => 'payroll'),
				array('label' => 'Employees',    'url' => 'employees', 'perm' => 'employees', 'match' => 'employees'),
			)),
			array('label' => 'PMU',     'icon' => 'briefcase','children' => array(
				array('label' => 'Projects',          'url' => 'projects',          'perm' => 'projects',   'match' => 'projects'),
				array('label' => 'Project Statement', 'url' => 'project-statement', 'perm' => 'pmu_report', 'match' => 'project-statement'),
			)),

			array('label' => 'Reports', 'icon' => 'chart', 'children' => array(
				array('label' => 'Accounts Report',  'url' => 'reports/accounts',  'perm' => 'rep_accounts',  'match' => 'reports/accounts'),
				array('label' => 'Financial Report', 'url' => 'reports/financial', 'perm' => 'rep_financial', 'match' => 'reports/financial'),
				array('label' => 'Budget Report', 'url' => 'budget-report', 'perm' => 'budget_report', 'match' => 'budget-report'),
				array('label' => 'Vendor Report', 'url' => 'vendor-report', 'perm' => 'vendor_report', 'match' => 'vendor-report'),
				array('label' => 'Banking Report', 'url' => 'banking-report', 'perm' => 'bank_report', 'match' => 'banking-report'),
				array('label' => 'Tax Report', 'url' => 'tax-summary', 'perm' => 'tax_report', 'match' => 'tax-summary-report'),
				array('label' => 'Payroll Report', 'url' => 'payroll-report', 'perm' => 'payroll_report', 'match' => 'payroll-report'),
				array('label' => 'Graphs', 'url' => 'graphs', 'perm' => 'graphs', 'match' => 'graphs'),
			)),

			array('label' => 'Masters', 'icon' => 'wallet', 'children' => array(
				array('label' => 'Assets (categories)', 'url' => 'asset-categories', 'perm' => 'asset_categories', 'match' => 'asset-categories'),
				array('label' => 'Suppliers', 'url' => 'suppliers', 'perm' => 'suppliers', 'match' => 'suppliers'),
			)),

			array('label' => 'Settings', 'icon' => 'settings', 'children' => array(
				array('label' => 'General Settings',    'url' => 'settings',          'perm' => 'settings',     'match' => 'settings'),
				array('label' => 'Fiscal Years',        'url' => 'fiscal-years',      'perm' => 'fiscal_years', 'match' => 'fiscal-years'),
				array('label' => 'Users',               'url' => 'users',             'perm' => 'users',        'match' => 'users'),
				array('label' => 'Roles & Permissions', 'url' => 'roles-permissions', 'perm' => 'roles',        'match' => 'roles-permissions'),
				array('label' => 'Audit Log',           'url' => 'audit-log',         'perm' => 'audit',        'match' => 'audit-log'),
			)),
		);
	}
}
