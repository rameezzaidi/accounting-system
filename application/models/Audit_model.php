<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Every important action in the system is written here (who / what / when / from where). */
class Audit_model extends CI_Model
{
	public function log($user, $module, $action, $record_id = null, $description = '', $old = null, $new = null)
	{
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : null;
		$this->db->insert('audit_logs', array(
			'user_id'     => $user ? $user->id : null,
			'username'    => $user ? $user->username : null,
			'module'      => $module,
			'action'      => $action,
			'record_id'   => $record_id !== null ? (string) $record_id : null,
			'description' => mb_substr((string) $description, 0, 500),
			'old_data'    => $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
			'new_data'    => $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
			'ip_address'  => $this->input->ip_address(),
			'user_agent'  => $ua,
			'created_at'  => date('Y-m-d H:i:s'),
		));
	}

	private function apply($f)
	{
		if (!empty($f['user_id']))  { $this->db->where('user_id', (int) $f['user_id']); }
		if (!empty($f['module']))   { $this->db->where('module', $f['module']); }
		if (!empty($f['action']))   { $this->db->where('action', $f['action']); }
		if (!empty($f['from']))     { $this->db->where('created_at >=', $f['from'] . ' 00:00:00'); }
		if (!empty($f['to']))       { $this->db->where('created_at <=', $f['to'] . ' 23:59:59'); }
		if (!empty($f['q'])) {
			$this->db->group_start()->like('description', $f['q'])->or_like('record_id', $f['q'])->or_like('username', $f['q'])->group_end();
		}
	}

	public function search($f, $limit, $offset)
	{
		$this->apply($f);
		return $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get('audit_logs')->result();
	}

	public function count($f)
	{
		$this->apply($f);
		return $this->db->count_all_results('audit_logs');
	}

	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('audit_logs')->row();
	}

	public function modules()
	{
		return array_map(function ($r) { return $r->module; }, $this->db->distinct()->select('module')->order_by('module')->get('audit_logs')->result());
	}

	public function actions()
	{
		return array_map(function ($r) { return $r->action; }, $this->db->distinct()->select('action')->order_by('action')->get('audit_logs')->result());
	}
}
