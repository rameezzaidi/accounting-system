<?php
// Router for PHP's built-in server: serve real static files, block internals, send the rest to CodeIgniter
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(application|system|database)(/|$)#', $path)) { http_response_code(403); exit('Forbidden'); }
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !preg_match('/\.(php|sh|sql|md)$/i', $path)) { return false; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
