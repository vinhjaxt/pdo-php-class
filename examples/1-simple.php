<?php
include '../db.php';

var_dump($db->query('select 1'));

/*
This will output:

array(1){
	1 => 1
}

*/