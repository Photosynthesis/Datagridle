<?php
include('datagrid.class.php');
require('validate_sub_request.php');

$table = $_GET['table'];
$database = $_GET['database'];

$grid = new datagrid($table,$database);

echo $grid->grid();


?>
