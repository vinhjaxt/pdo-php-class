<?php
include '../db.php';

$result=$db->update('demo_table',array(
										'field_name_1' => 'value 2',
										'field_name_2' => 'value 2',
										'field_name_3' => 123
									),array(
										'field_id'=>456,
										'field_name_1'=>'value 1'
									));
if($result)
	var_dump($result);
