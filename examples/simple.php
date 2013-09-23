<?php
session_start();

define(LIB_PATH,'../../../');

include_once(LIB_PATH.'url.class.php');
include_once(LIB_PATH.'database.class.php');
include_once('../datagrid.class.php');

$grid = new datagrid('categories');

echo $grid->grid();

?>