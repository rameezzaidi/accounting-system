<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model
{
	public function all()
	{
		$out = array();
		foreach ($this->db->get('settings')->result() as $r) { $out[$r->setting_key] = $r->setting_value; }
		return $out;
	}

	public function save_many($data)
	{
		foreach ($data as $k => $v) {
			$exists = $this->db->where('setting_key', $k)->count_all_results('settings');
			if ($exists) {
				$this->db->where('setting_key', $k)->update('settings', array('setting_value' => $v));
			} else {
				$this->db->insert('settings', array('setting_key' => $k, 'setting_value' => $v));
			}
		}
	}
}
