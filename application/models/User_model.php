<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
	private function apply($f)
	{
		if (!empty($f['q'])) {
			$this->db->group_start()->like('u.full_name', $f['q'])->or_like('u.username', $f['q'])->or_like('u.email', $f['q'])->group_end();
		}
		if (!empty($f['role_id'])) { $this->db->where('u.role_id', (int) $f['role_id']); }
		if (isset($f['status']) && $f['status'] !== '') { $this->db->where('u.status', (int) $f['status']); }
	}

	public function search($f, $limit, $offset)
	{
		$this->apply($f);
		return $this->db->select('u.*, r.name AS role_name')->from('users u')->join('roles r', 'r.id = u.role_id')
			->order_by('u.id')->limit($limit, $offset)->get()->result();
	}

	public function count($f)
	{
		$this->apply($f);
		return $this->db->from('users u')->count_all_results();
	}

	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('users')->row();
	}

	public function username_exists($username, $except_id = 0)
	{
		$this->db->where('username', $username);
		if ($except_id) { $this->db->where('id !=', (int) $except_id); }
		return $this->db->count_all_results('users') > 0;
	}

	public function active_super_admins()
	{
		return $this->db->where('role_id', 1)->where('status', 1)->count_all_results('users');
	}
}
