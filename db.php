<?php
# Cusstom setting
$db_info = array(
		'host'  => 'localhost',
		'user'  => 'root',
		'pass'  => 'p@ssw0rd',
		'dbname'=> 'demo_db'
	);
include 'db/init.php';

//var_dump($db->query('show databases;'));