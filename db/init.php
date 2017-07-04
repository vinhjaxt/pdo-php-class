<?php
/*
	Init database connection by VinhNoName
*/
if(is_file(__DIR__.'/settings.ini.php'))
	include 'settings.ini.php';
if(isset($GLOBALS['db_info']) && is_array($GLOBALS['db_info'])){
	/*
	Database settings
	*/
	$db_user=$GLOBALS['db_info']['user'];
	$db_password=$GLOBALS['db_info']['pass'];
	$db_dsn = 'mysql:dbname='.$GLOBALS['db_info']['dbname'].';host='.(isset($GLOBALS['db_info']['host'])?$GLOBALS['db_info']['host']:'localhost').';port='.(isset($GLOBALS['db_info']['port'])?$GLOBALS['db_info']['port']:'3306');
}
include 'db.class.php';
$db=new db($db_dsn,$db_user,$db_password);
