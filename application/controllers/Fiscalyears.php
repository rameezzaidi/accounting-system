<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fiscalyears extends MY_Controller
{
	public function index()
	{
		$this->require_perm('fiscal_years', 'view');
		$rows = $this->Fy_model->all();
		foreach ($rows as $r) {
			$r->vouchers = $this->db->where('fiscal_year_id', $r->id)->count_all_results('vouchers');
			$r->ob = $this->db->where('fiscal_year_id', $r->id)->count_all_results('opening_balances');
		}
		$this->render('fiscal_years/index', array('page_title' => 'Fiscal Years', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('fiscal_years', $id ? 'edit' : 'add');
		$fy = $id ? $this->Fy_model->get($id) : null;
		if ($id && !$fy) { show_404(); }
		// suggest the year after the latest one
		$suggest = null;
		if (!$fy) {
			$last = $this->db->order_by('end_date', 'DESC')->limit(1)->get('fiscal_years')->row();
			if ($last) {
				$s = date('Y-m-d', strtotime($last->end_date . ' +1 day'));
				$e = date('Y-m-d', strtotime($s . ' +1 year -1 day'));
				$suggest = (object) array('start_date' => $s, 'end_date' => $e, 'title' => 'FY ' . date('Y', strtotime($s)) . '-' . date('y', strtotime($e)));
			}
		}
		$this->partial('fiscal_years/_form', array('fy' => $fy, 'suggest' => $suggest,
			'locked' => $fy ? $this->Fy_model->has_data($id) : false));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('fiscal_years', $id ? 'edit' : 'add');

		$title = trim((string) $this->input->post('title'));
		$start = (string) $this->input->post('start_date');
		$end = (string) $this->input->post('end_date');
		$old = $id ? $this->Fy_model->get($id) : null;
		if ($id && !$old) { $this->fail('Fiscal year not found.'); }
		if ($old && (int) $old->is_closed) { $this->fail('A closed fiscal year cannot be changed.'); }

		$locked = $old ? $this->Fy_model->has_data($id) : false;
		if ($locked) { $start = $old->start_date; $end = $old->end_date; }   // dates are frozen once data exists

		$errors = array();
		if ($title === '') { $errors['title'] = 'Title is required.'; }
		elseif ($this->Fy_model->title_exists($title, $id)) { $errors['title'] = 'This title already exists.'; }
		$ok_s = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) && strtotime($start);
		$ok_e = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) && strtotime($end);
		if (!$ok_s) { $errors['start_date'] = 'Select the start date.'; }
		if (!$ok_e) { $errors['end_date'] = 'Select the end date.'; }
		if ($ok_s && $ok_e) {
			$days = (strtotime($end) - strtotime($start)) / 86400 + 1;
			if ($end <= $start) { $errors['end_date'] = 'End date must be after the start date.'; }
			elseif ($days < 28 || $days > 400) { $errors['end_date'] = 'A fiscal year should be between 1 month and about 13 months.'; }
			elseif ($this->Fy_model->overlaps($start, $end, $id)) { $errors['start_date'] = 'These dates overlap another fiscal year.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array('title' => $title, 'start_date' => $start, 'end_date' => $end);
		if ($id) {
			$this->db->where('id', $id)->update('fiscal_years', $data);
			$this->audit('fiscal_years', 'update', $id, "Updated fiscal year $title", array('title' => $old->title, 'start_date' => $old->start_date, 'end_date' => $old->end_date), $data);
			$this->ok('Fiscal year updated.');
		}
		$data['is_active'] = 0; $data['is_closed'] = 0; $data['created_at'] = date('Y-m-d H:i:s');
		$has_any = $this->db->count_all('fiscal_years');
		if (!$has_any) { $data['is_active'] = 1; }
		$this->db->insert('fiscal_years', $data);
		$this->audit('fiscal_years', 'create', $this->db->insert_id(), "Created fiscal year $title", null, $data);
		$this->ok('Fiscal year added.');
	}

	public function activate($id = 0)
	{
		$this->post_only();
		$this->require_perm('fiscal_years', 'edit');
		$fy = $this->Fy_model->get((int) $id);
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		if ((int) $fy->is_closed) { $this->fail('A closed fiscal year cannot be made active.'); }
		$this->Fy_model->set_active($fy->id);
		$this->audit('fiscal_years', 'activate', $fy->id, "Set {$fy->title} as the active fiscal year");
		$this->ok("{$fy->title} is now the active year.", array('reload' => true));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('fiscal_years', 'edit');
		$fy = $this->Fy_model->get((int) $id);
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		if ((int) $fy->is_active) { $this->fail('The active fiscal year cannot be deleted.'); }
		if ($this->Fy_model->has_data($fy->id)) { $this->fail('This year has vouchers or opening balances and cannot be deleted.'); }
		$this->db->where('id', $fy->id)->delete('fiscal_years');
		$this->audit('fiscal_years', 'delete', $fy->id, "Deleted fiscal year {$fy->title}", (array) $fy);
		$this->ok('Fiscal year deleted.');
	}
}
