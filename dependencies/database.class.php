<?php

class database{
  var $link;
  var $db;
  var $global_debug_function = 'testit';
  var $debug_level = 2;
  var $errors = array();
  
  function __construct($credentials){

    if(is_resource($credentials)){
       if(get_resource_type($credentials) == 'mysqli'){
        $this->link = $credentials;
       }
    }else{
      $this->link = mysqli_connect($credentials['host'],$credentials['user'],$credentials['pass']);
      
      if($this->link == false){
        $this->error('__construct()','DB connection failed!');
        return;
      }
    }
    
  $this->db = mysqli_select_db($this->link,$credentials['db']);
    if (!$this->link || !$this->db) {
        $this->error('__construct()',mysqli_error($this->link));
    }
  }

  // Check for 'sql:' flag and return sql if found
  function check_sql_flag($table_or_sql){
    if(substr($table_or_sql,0,4) == 'sql:'){
      return substr($table_or_sql,4);
    }else{
      return false;
    }
  }


  // Select one record (returns row array)
  function select_one($table_or_sql, $cond = NULL, $fields = NULL){

    if($this->check_sql_flag($table_or_sql)){
      $sql = $this->check_sql_flag($table_or_sql);
    }else{
      $fields ? $sfields = $fields : $fields = '*';
      $sql = "SELECT $fields FROM $table_or_sql WHERE $cond";
    }

    $this->dbg('db->select_one() SQL',$sql);

    $result = mysqli_query($this->link,$sql);
    
    if (!$result) {
            $this->error('select_one()',mysqli_error($this->link),$sql);
    }else{
      return mysqli_fetch_assoc($result);
    }
  }

  // Select multiple records from one table (returns ML array)
  function select($table_or_sql,$cond = NULL,$fields = NULL,$key = NULL, $sort = NULL,$limit = NULL){
    if($this->check_sql_flag($table_or_sql)){
      $sql = $this->check_sql_flag($table_or_sql);
    }else{
      if($sort)$sortby = " ORDER BY $sort";
      if($limit)$limitsql = " LIMIT 0,$limit";
      $fields ? '' : $fields = '*';
      if($cond)$condsql = " WHERE $cond";
      $sql = "SELECT $fields FROM $table_or_sql ".$condsql.$sortby.$limitsql;
    }
    
    $data = array();

    $this->dbg("db->select SQL",$sql);
    $result = mysqli_query($this->link,$sql);
    
    if (!$result) {
      $this->error('select()',mysqli_error($this->link),$sql);
      return;
    }

    if($key){
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
        $data[$row[$key]] = $row;
      }
    }else{
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
         $data[] = $row;
      }
    }
    return $data;
  }

  // Select all the records from a table
  function select_all($table,$key = NULL){
    $sql = "SELECT * FROM $table";
    
    $this->dbg("db->select_all","Table: $table");

    $result = mysqli_query($this->link,$sql);
    
    if (!$result) {
       $this->error('select_all()',mysqli_error($this->link),$sql);
       return;
    }
    
    $data = Array();
    if($key){
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
        $keyval = $row[$key];
        $data[$keyval] = $row;
      }

    }else{
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
        $data[] = $row;
      }
    }
    return $data;
  }

  // Select single joined record (returns ML array)
  function select_ar($tables,$join,$conds){
    $tables = explode(',',$tables);
    $i = 0;
    foreach ($tables as $key=>$value) {
      if($i == 0){
        // Select from the first table
      	$sql= "SELECT * FROM $value WHERE $conds";
      	
      	$this->dbg("db_select_ar SQL $i",$sql);
      	
        $result = mysqli_query($this->link,$sql);
       if (!$result) {
          $this->error('select_ar()',mysqli_error($this->link),$sql);
          return;
       }

       $data[$value] = mysqli_fetch_assoc($result);
       $join_val = $data[$value][$join];
       $i++;

      }else{
        // Select from the other tables
      	$sql= "SELECT * FROM $value WHERE $join = '$join_val'";
        testit("db_select_ar query $i",$sql);
        $result = mysqli_query($this->link,$sql);
        if (!$result) {
          $this->error('select_ar()',mysqli_error($this->link),$sql);
          return;
        }
        
        $data[$value] = mysqli_fetch_assoc($result);
      }
    }
    return $data;
  }

  # Get one field from the DB and retun as single level array
  function select_field($table_or_sql,$field,$cond = NULL,$key = NULL, $sort = NULL,$limit = NULL){
    if($this->check_sql_flag($table_or_sql)){
      $sql = $this->check_sql_flag($table_or_sql);
    }else{
      if($sort)$sortby = " ORDER BY $sort";
      if($limit)$limitsql = " LIMIT 0,$limit";
      if($cond)$condsql = " WHERE $cond";
      $sql = "SELECT $field FROM $table_or_sql ".$condsql.$sortby.$limitsql;
    }

    $this->dbg("db->select_field query",$sql);
    
    $result = mysqli_query($this->link,$sql);
    
    if(!$result) {
      $this->error('select_field()',mysqli_error($this->link),$sql);
      return;
    }

    if($key){
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
        $data[$row[$key]] = $row[$field];
      }

    }else{
      while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
         $data[] = $row[$field];
      }
    }

    return $data;
  }

  // Insert
  function insert($table_or_sql,$data = NULL,$return_id = false){

    if($sql = $this->check_sql_flag($table_or_sql)){
    }else{
      $fields = '';
      $values = '';
      $i = '';
      foreach ($data as $key=>$value) {
      $fields .= $i." $key";
      $values .= $i." '$value'";
      $i = ',';
      }
      $sql = "INSERT INTO $table_or_sql ($fields) VALUES ($values)";
    }

    $this->dbg("db->insert SQL",$sql);

    if(mysqli_query($this->link,$sql)){
      if($return_id){
        return mysqli_insert_id($this->link);
      }else{
        return true;
      }
    }else{
      $this->error('insert()',mysqli_error($this->link),$sql);
    }

  }
  
  function update($table_or_sql,$cond = NULL, $data = NULL){

    if($sql = $this->check_sql_flag($table_or_sql)){

    }else{
      $update_values = '';
      $i = '';
      foreach ($data as $key=>$value) {
        if (!get_magic_quotes_gpc()) {
          $value = addslashes($value);
          $key = addslashes($key);
        }
        $update_values .= "$i $key = '$value'";
        $i = ',';
      }
      $sql = "UPDATE $table_or_sql SET $update_values WHERE $cond";
    }

    $this->dbg("db->update",$sql);
    
    if(mysqli_query($this->link,$sql)){
      return true;
    }else{
      $this->error('update()',mysqli_error($this->link),$sql);
    }
  }

  function delete($table_or_sql,$cond){
    if($sql = $this->check_sql_flag($table_or_sql)){

    }else{
      $sql = "DELETE from $table_or_sql WHERE $cond LIMIT 1";
    }
    $this->dbg('db_delete SQL',$sql);
    
    if(mysqli_query($this->link,$sql)){
      return true;
    }else{
      $this->error('delete()',mysqli_error($this->link),$sql);
    }
  }

  function count($table_or_sql, $field = NULL, $value = NULL)
  {
    if($sql = $this->check_sql_flag($table_or_sql)){

    }elseif($field && $value){
      $sql = "SELECT * FROM $table WHERE $field = '$value'";
    }else{
      $sql = "SELECT * FROM $table";
    }
    $this->dbg("db->count SQL",$sql);
    $result = mysqli_query($this->link,$sql);
    if (!$result) {
      $this->error('count()',mysqli_error($this->link),$sql);
    }else{
      $num_rows = mysqli_num_rows($result);
      return $num_rows;
    }
  }
  
  function exec_sql($sql){
    $result = mysqli_query($this->link,$sql);
    
    if(!$result){
      $this->error('exec_sql()',mysqli_error($this->link),$sql);
    }else{
      return $result;
    }
  }

  function query($sql){
    $this->dbg('Db->Query() SQL',$sql);
    $result = mysqli_query($this->link,$sql);
    if (!$result) {
       $this->error('query()',mysqli_error($this->link),$sql);
    }
    return $result;
  }
  
  function get_ai_value($table){
    $sql = "SHOW TABLE STATUS LIKE '$table'";
    $result = mysqli_query($this->link,$sql);
    
    if(!$result){
      $this->error('get_ai_value()',mysqli_error($this->link),$sql);
      return;
    }
    
    $row = mysqli_fetch_array($result);
    $id = $row['Auto_increment'];
    mysqli_free_result($result);
    
    return $id;

  }
  
  function dbg($var,$val,$status = 'DEBUG'){
    if(is_callable($this->global_debug_function)){
      $dbgf = $this->global_debug_function;
      $dbgf($var,$val,$status);
    }else if($this->debug_level == 2 || ($this->debug_level == 1 && $status == 'error')){

       global $debug_output;
       
       $out = "<b>[".strtoupper($status)."] $var:</b>";
       if (is_array($val)){
          $out .= "<pre>";
          $out .=  print_r($val,'true');
          $out .= "</pre>";
       }else{
          $out .= $val;
       }

      $out .= "<br /><br />";

      $debug_output .= $out;
         
    }
  }
  
  function error($function,$error = NULL,$sql = NULL){
    $this->errors[] = array(
      'function' => $function,
      'error' => $error,
      'sql' =>  $sql
    );
  }
  
  function text_errors(){
    $out = '';
    foreach ($this->errors as $key=>$er) {
      $out .= "<div class=\"db_error\" style=\"padding:10px\">
      <b>Error in db::$er[function]</b>";
      if($er['error']){
        $out .= "<br /><b>SQL error response:</b> $er[error] <br />";
      }
      if($er['sql']){
        $out .= "<b>Affected SQL:</b> $er[sql]<br />";
      }
      $out .= "</div>";
    }
    return $out;
  }
  
}
?>
