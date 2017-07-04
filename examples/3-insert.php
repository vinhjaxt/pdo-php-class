<?php
include '../db.php';

$inserted_id=$db->insert('demo_table',array(
										'field_name_1' => 'value 1',
										'field_name_2' => 'value 2',
										'field_name_3' => 123
									));

var_dump($inserted_id);
