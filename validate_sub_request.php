<?php
session_start();
$dg_id = $_GET['dg_id'];
$_GET['ses_key'] ? $ses_key = $_GET['ses_key'] : $ses_key = 'sub_token';
$get_token = $_GET['token'];
$ses_token = $_SESSION[$dg_id][$ses_key];


$ds = DIRECTORY_SEPARATOR;

$working_abs_path = getcwd().$ds;

$dg_abs_path = dirname(__FILE__).$ds;

$doc_root = $_SERVER['DOCUMENT_ROOT'];


if(file_exists($working_abs_path.'dg_config.php')){
  require($working_abs_path.'dg_config.php');
}else{
  require($dg_abs_path.'dg_config.php');
}
$get_token_hash = md5($get_token.$secure_salt);

if(!$get_token || !$ses_token || $ses_token !== $get_token_hash){
 // echo "<pre>".print_r($_SESSION,true);
 // echo "DG SES ($dg_id)<pre>".print_r($_SESSION[$dg_id],true);
 // echo "ses tk $ses_token<br /> get tk $get_token<br /> ge tk hash $get_token_hash<br />";
 // echo "Secure salt $secure_salt<br />";
  exit('Token mismatch. Please confirm that config files used by default child grid and parent DG have matching values');

}

?>
