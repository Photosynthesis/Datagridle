<?php
require('validate_sub_request.php');

$csv_data = $_SESSION[$_GET['dg_id']]['csv_data'];

$filename = $_GET['csv_fn'];

header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Length: " . strlen($csv_data));
header("Content-type: text/x-csv");
//header("Content-type: text/csv");
//header("Content-type: application/csv");
header("Content-Disposition: attachment; filename=$filename");
echo $csv_data




?>
