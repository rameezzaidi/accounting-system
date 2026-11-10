<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function index()
	{
		if (!$this->can('dashboard', 'view')) {
			// send the user to the first screen they are allowed to open
			foreach (menu_items() as $item) {
				$list = !empty($item['children']) ? $item['children'] : array($item);
				foreach ($list as $c) {
					if (!empty($c['perm']) && $this->can($c['perm'], 'view')) { redirect($c['url']); }
				}
			}
			$this->require_perm('dashboard', 'view');
		}

		$this->load->model('Report_model');
		$fy = $this->active_fy;
		$data = array('page_title' => 'Dashboard', 'fy' => $fy);
		if ($fy) {
			$data['d'] = $this->Report_model->dashboard($fy);
			$data['recent'] = $this->db->select('v.*')->from('vouchers v')->where('v.fiscal_year_id', $fy->id)
				->order_by('v.id', 'DESC')->limit(8)->get()->result();
		}
		$data['scripts'] = array('assets/vendor/chart.min.js', 'assets/js/dashboard.js');
		$this->render('dashboard/index', $data);
	}
}
