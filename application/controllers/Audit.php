<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Read-only trail of everything done in the system */
class Audit extends MY_Controller
{
	const PER_PAGE = 20;

	public function __construct()
	{
		parent::__construct();
		$this->require_perm('audit', 'view');
	}

	private function filters()
	{
		return array('q' => trim((string) $this->input->get('q')), 'user_id' => (int) $this->input->get('user_id'),
			'module' => (string) $this->input->get('module'), 'action' => (string) $this->input->get('action'),
			'from' => (string) $this->input->get('from'), 'to' => (string) $this->input->get('to'));
	}

	public function index()
	{
		$this->render('audit/index', array('page_title' => 'Audit Log',
			'users' => $this->db->select('id, full_name, username')->order_by('full_name')->get('users')->result(),
			'modules' => $this->Audit_model->modules(), 'actions' => $this->Audit_model->actions()));
	}

	public function grid()
	{
		$f = $this->filters(); $page = max(1, (int) $this->input->get('page'));
		$this->partial('audit/_grid', array('rows' => $this->Audit_model->search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
			'total' => $this->Audit_model->count($f), 'page' => $page, 'per' => self::PER_PAGE));
	}

	public function detail($id = 0)
	{
		$row = $this->Audit_model->get((int) $id);
		if (!$row) { show_404(); }
		$pretty = function ($j) { if ($j === null || $j === '') { return ''; } $d = json_decode($j, true); return $d === null ? $j : json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); };
		$this->partial('audit/_detail', array('a' => $row, 'old' => $pretty($row->old_data), 'new' => $pretty($row->new_data)));
	}
}
