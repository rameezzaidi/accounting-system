<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Roles and the permission matrix (module x action) */
class Roles extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Role_model'));
	}

	public function index()
	{
		$this->require_perm('roles', 'view');
		$this->render('roles/index', array('page_title' => 'Roles & Permissions', 'roles' => $this->Role_model->all(),
			'total_perms' => $this->db->count_all('permissions')));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('roles', $id ? 'view' : 'add');
		$role = $id ? $this->Role_model->get($id) : null;
		if ($id && !$role) { show_404(); }
		$groups = $this->Role_model->grouped_permissions();
		$actions = array();
		foreach ($groups as $g) { foreach ($g['items'] as $p) { $actions[$p->action] = true; } }
		$order = array('view', 'add', 'edit', 'save', 'delete', 'void', 'print', 'export');
		$cols = array_values(array_unique(array_merge(array_intersect($order, array_keys($actions)), array_keys($actions))));
		$this->render('roles/form', array(
			'page_title' => $role ? 'Permissions: ' . $role->name : 'New role', 'role' => $role, 'groups' => $groups, 'cols' => $cols,
			'granted' => $role ? $this->Role_model->permission_ids($id) : array(),
			'locked' => $role && (int) $role->id === 1,
		));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('roles', $id ? 'edit' : 'add');

		$name = trim((string) $this->input->post('name'));
		$desc = trim((string) $this->input->post('description'));
		$perms = (array) $this->input->post('perm');

		$old = $id ? $this->Role_model->get($id) : null;
		if ($id && !$old) { $this->fail('Role not found.'); }
		if ($old && (int) $old->id === 1) { $this->fail('The Super Admin role cannot be changed.'); }

		$errors = array();
		if ($name === '') { $errors['name'] = 'Role name is required.'; }
		elseif ($this->Role_model->name_exists($name, $id)) { $errors['name'] = 'A role with this name already exists.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		if ($id) {
			$before = array('name' => $old->name, 'permissions' => $this->Role_model->permission_keys($id));
			$this->db->where('id', $id)->update('roles', array('name' => $name, 'description' => $desc !== '' ? $desc : null));
			$this->Role_model->save_permissions($id, $perms);
			$after = array('name' => $name, 'permissions' => $this->Role_model->permission_keys($id));
			$added = array_values(array_diff($after['permissions'], $before['permissions']));
			$removed = array_values(array_diff($before['permissions'], $after['permissions']));
			$this->audit('roles', 'update', $id, "Updated role $name (+" . count($added) . ' / -' . count($removed) . ' permissions)',
				$before, array_merge($after, array('added' => $added, 'removed' => $removed)));
			$this->ok('Role saved.', array('redirect' => site_url('roles-permissions')));
		}

		$this->db->insert('roles', array('name' => $name, 'description' => $desc !== '' ? $desc : null, 'is_system' => 0, 'created_at' => date('Y-m-d H:i:s')));
		$new_id = $this->db->insert_id();
		$this->Role_model->save_permissions($new_id, $perms);
		$this->audit('roles', 'create', $new_id, "Created role $name", null, array('name' => $name, 'permissions' => $this->Role_model->permission_keys($new_id)));
		$this->ok('Role created.', array('redirect' => site_url('roles-permissions')));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('roles', 'delete');
		$r = $this->Role_model->get((int) $id);
		if (!$r) { $this->fail('Role not found.'); }
		if ((int) $r->is_system) { $this->fail('System roles cannot be deleted.'); }
		if ($this->db->where('role_id', $r->id)->count_all_results('users')) { $this->fail('This role is assigned to users. Move them to another role first.'); }
		$this->db->where('id', $r->id)->delete('roles');
		$this->audit('roles', 'delete', $r->id, "Deleted role {$r->name}", array('name' => $r->name));
		$this->ok('Role deleted.');
	}
}
