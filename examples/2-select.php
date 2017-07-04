<?php
include '../db.php';

$array=$db->query('select * from `demo_table` where `field_name` like :field_name limit 10',array('field_name'=>'%string%'));

foreach($array as $row){
	echo 'Row: "',$row['field_name'],'" : "',$row['field_value'],"\"<br/>\r\n";
}

