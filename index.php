<?php

require __DIR__.'/F3/F3.php';

$modules=array(
	'apc'=>array(NULL,'Cache engine'),
	'dom'=>array(NULL,'Template engine'),
	'gd'=>array(NULL,'Graphics plugin'),
	'hash'=>array(NULL,'Framework core'),
	'intl'=>array(NULL,'I18n plugin'),
	'json'=>array(NULL,'Various plugins'),
	'libxml'=>array(NULL,'Template engine'),
	'memcache'=>array(NULL,'Cache engine'),
	'mongo'=>array(NULL,'M2 MongoDB mapper'),
	'pcre'=>array(NULL,'Framework core'),
	'pdo_mssql'=>array(NULL,'SQL handler, Axon ORM'),
	'pdo_mysql'=>array(NULL,'SQL handler, Axon ORM'),
	'pdo_pgsql'=>array(NULL,'SQL handler, Axon ORM'),
	'pdo_sqlite'=>array(NULL,'SQL handler, Axon ORM'),
	'session'=>array(NULL,'Framework core'),
	'sockets'=>array(NULL,'Network plugin'),
	'xcache'=>array(NULL,'Cache engine'),
	'zlib'=>array(NULL,'Framework core')
);

foreach ($modules as $key=>$mod)
	$modules[$key][0]=extension_loaded($key)?'Yes':'No';

F3::set('modules',$modules);

F3::set('IMPORTS','inc/');
// F3::route('GET /',':main|:home');
F3::route('GET /',':main');
F3::route('POST /',':main');

F3::route('GET /locations',':locations');

F3::route('GET /stage',':stage');
F3::route('POST /stage',':stage');


// F3::route('GET /login',':main|:login');
// F3::route('GET /wiki/@term',':main|:auth|:wikiterm');
// F3::route('GET /blog/@page',':main|:auth|:blogpage');

F3::run();
// echo F3::serve('F3/welcome.htm');

?>
