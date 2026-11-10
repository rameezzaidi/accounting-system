<?php
$uri = uri_string();
$menu = menu_items();
$is_active = function ($m) use ($uri) { return $m !== '' && ($uri === $m || strpos($uri, $m . '/') === 0); };
$fy = isset($this->active_fy) ? $this->active_fy : null;
$initial = strtoupper(substr($this->user->full_name, 0, 1));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-hash" content="<?= $this->security->get_csrf_hash() ?>">
<meta name="base-url" content="<?= base_url() ?>">
<title><?= h($page_title ? $page_title . ' | ' : '') ?><?= h(setting('company_name', 'Accounting')) ?></title>
<link rel="icon" href="<?= base_url('assets/img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/select2-bootstrap-5-theme.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=3">
</head>
<body>
<div class="app-shell" id="appShell">

	<aside class="sidebar" id="sidebar">
		<a class="brand has-logo" href="<?= site_url('dashboard') ?>">
			<span class="brand-logo"><img src="<?= base_url('assets/img/logo.jpg') ?>" alt="BBSHRRDB"></span>
			<span class="brand-text"><?= h(setting('company_name', 'Accounting')) ?><small>Accounting System</small></span>
		</a>
		<nav class="side-nav">
		<?php foreach ($menu as $i => $item):
			if (empty($item['children'])):
				if (!empty($item['perm']) && !can($item['perm'], 'view')) { continue; } ?>
				<a class="nav-link-item <?= $is_active($item['match']) ? 'active' : '' ?>" href="<?= site_url($item['url']) ?>">
					<?= icon($item['icon']) ?><span><?= h($item['label']) ?></span>
				</a>
			<?php else:
				$kids = array();
				foreach ($item['children'] as $c) { if (empty($c['perm']) || can($c['perm'], 'view')) { $kids[] = $c; } }
				if (!$kids) { continue; }
				$open = false;
				foreach ($kids as $c) { if ($is_active($c['match'])) { $open = true; } } ?>
				<div class="nav-group <?= $open ? 'open' : '' ?>">
					<button type="button" class="nav-link-item group-toggle">
						<?= icon($item['icon']) ?><span><?= h($item['label']) ?></span><span class="chev"><?= icon('chevron', 14) ?></span>
					</button>
					<div class="nav-sub">
					<?php foreach ($kids as $c): ?>
						<a class="nav-sub-item <?= $is_active($c['match']) ? 'active' : '' ?>" href="<?= site_url($c['url']) ?>">
							<?= h($c['label']) ?><?php if (!empty($c['soon'])): ?><em class="soon">Soon</em><?php endif; ?>
						</a>
					<?php endforeach; ?>
					</div>
				</div>
			<?php endif;
		endforeach; ?>
		</nav>
	</aside>
	<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

	<div class="main-area">
		<header class="topbar">
			<button class="icon-btn" id="sidebarToggle" type="button" title="Menu"><?= icon('menu', 20) ?></button>
			<h1 class="page-title"><?= h($page_title) ?></h1>
			<div class="topbar-right">
				<?php if ($fy): ?>
					<span class="fy-chip" title="Active fiscal year"><?= icon('calendar', 15) ?> <?= h($fy->title) ?><?= $fy->is_closed ? ' (closed)' : '' ?></span>
				<?php endif; ?>
				<div class="dropdown">
					<button class="user-btn" data-bs-toggle="dropdown" type="button">
						<span class="avatar"><?= h($initial) ?></span>
						<span class="user-meta"><b><?= h($this->user->full_name) ?></b><small><?= h($this->user->role_name) ?></small></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="<?= site_url('profile') ?>"><?= icon('key', 16) ?> Change password</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><?= icon('logout', 16) ?> Logout</a></li>
					</ul>
				</div>
			</div>
		</header>
		<main class="content">
		<?php if ($m = $this->session->flashdata('err')): ?><div class="alert alert-danger"><?= h($m) ?></div><?php endif; ?>
		<?php if ($m = $this->session->flashdata('ok')): ?><div class="alert alert-success"><?= h($m) ?></div><?php endif; ?>
