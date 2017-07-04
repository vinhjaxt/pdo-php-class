<?php
include '../db.php';

$result=$db->delete('demo_table',array(
										'field_id'=>456,
										'field_name_1'=>'value 1'
									));
if($result)
	var_dump($result);
