<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Database settings.
| Edit the values below for your server, or set environment variables
| (ACC_DB_HOST, ACC_DB_USER, ACC_DB_PASS, ACC_DB_NAME).
*/
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'      => '',
	'hostname' => getenv('ACC_DB_HOST') ?: 'localhost',
	'username' => getenv('ACC_DB_USER') ?: 'root',
	'password' => getenv('ACC_DB_PASS') !== false ? getenv('ACC_DB_PASS') : '',
	'database' => getenv('ACC_DB_NAME') ?: 'accounting_db',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_unicode_ci',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => FALSE
);
