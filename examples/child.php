<?php
session_start();
include_once('../../../url.class.php');
include_once('../../../testit.php');
include_once('../../../database.class.php');
include_once('../datagrid.class.php');

$db = new database(array('db'=>'dg_test','host'=>'localhost','user'=>'root','pass' => ''));

$grid = new datagrid('comments',$db);
//$grid->set_setting('mode','compact');
$grid->set_field_attrib('comments','comment_id','title','ID');
$grid->set_field_attrib('comments','date','type','date');


echo $grid->grid();

testit('GET',$_GET);
testit('showtests');

?>
