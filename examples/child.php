<?php
include_once('../datagrid.class.php');
include_once('../../../testit.php');

$db = array('db'=>'dg_test','host'=>'localhost','user'=>'root','pass' => 'wunderbar');

$grid = new datagrid('comments',$db);
//$grid->set_setting('mode','compact');
$grid->set_field_attrib('comments','comment_id','title','ID');
$grid->set_field_attrib('comments','date','type','date');


echo $grid->grid();

testit('GET',$_GET);
testit('showtests');

?>
