<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model
{
	public function all()
	{
		return $this->db->query(
			"SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count,
			        (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perm_count
			 FROM roles r ORDER BY r.id"
		)->result();
	}

	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('roles')->row();
	}

	public function name_exists($name, $except_id = 0)
	{
		$this->db->where('name', $name);
		if ($except_id) { $this->db->where('id !=', (int) $except_id); }
		return $this->db->count_all_results('roles') > 0;
	}

	/** permissions grouped by module: [module => ['label'=>..., 'items'=>[perm rows]]] */
	public function grouped_permissions()
	{
		$rows = $this->db->order_by('sort_order')->get('permissions')->result();
		$out = array();
		foreach ($rows as $r) {
			if (!isset($out[$r->module])) { $out[$r->module] = array('label' => $r->module_label, 'items' => array()); }
			$out[$r->module]['items'][] = $r;
		}
		return $out;
	}

	public function permission_ids($role_id)
	{
		$ids = array();
		foreach ($this->db->where('role_id', (int) $role_id)->get('role_permissions')->result() as $r) { $ids[] = (int) $r->permission_id; }
		return $ids;
	}

	/** readable list of "module.action" keys, used for the audit trail */
	public function permission_keys($role_id)
	{
		$rows = $this->db->select('p.module, p.action')->from('role_permissions rp')
			->join('permissions p', 'p.id = rp.permission_id')->where('rp.role_id', (int) $role_id)
			->order_by('p.sort_order')->get()->result();
		return array_map(function ($r) { return $r->module . '.' . $r->action; }, $rows);
	}

	public function save_permissions($role_id, $perm_ids)
	{
		$this->db->where('role_id', (int) $role_id)->delete('role_permissions');
		$valid = array();
		foreach ($this->db->select('id')->get('permissions')->result() as $p) { $valid[(int) $p->id] = true; }
		$batch = array();
		foreach ((array) $perm_ids as $pid) {
			$pid = (int) $pid;
			if (isset($valid[$pid])) { $batch[$pid] = array('role_id' => (int) $role_id, 'permission_id' => $pid); }
		}
		if ($batch) { $this->db->insert_batch('role_permissions', array_values($batch)); }
	}
}
