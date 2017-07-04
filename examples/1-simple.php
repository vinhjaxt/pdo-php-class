<?php
include '../db.php';

var_dump($db->query('select 1'));
/*
This will output:

array(1) {
  [0]=>
  array(1) {
    [1]=>
    int(1)
  }
}

*/

echo "<br/>\r\n";

var_dump($db->row('select 1'));
/*
This will output:

array(1) {
  [1]=>
  int(1)
}

*/

echo "<br/>\r\n";

var_dump($db->single('select 1'));
/*
This will output:

  int(1)

*/
