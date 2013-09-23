<?php
/*
    PhotoSynthesis Datagridle
    http://www.photosynth.ca/code/datagridle

    Ultra-rapid, customizable database content editing interface.

    This class is released under the GPL license. If you would like to use it
    in proprietary applications, versions under more permissive licenses can
    be provided. Write to adam@photosynth.ca.

    Suggestions and feedback are welcome.
    
    Copyright (C) 2013  A. McKenty

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
    
*/


class datagrid{
  var $paths;
  var $settings;
  var $modes = array();
  var $mode;
  var $struct = array();
  var $fields;
  var $tabletitle;
  var $url;
  var $redirect_url;
  var $head_links = array(
    'css' => array(),
    'js' => array('calendarDateInput.js','jquery.js','datagrid.js'));
  var $dg_name;
  var $dg_display_name;
  var $ses_name;
  var $searchtype;
  var $searchfeedback;
  var $searchquery;
  var $searchsql;
  var $sortfield;
  var $sorttable;
  var $sorttype;
  var $newsorttype;
  var $feedback;
  var $feedback_class;
  var $queriedrowsql;
  var $totalrowsql;
  var $totalrows;
  var $queriedrows; 
  var $displayedrows;
  var $start;
  var $limit;
  var $sql;
  var $db;
  var $rowcount;
  var $primary_table;

  function __construct($dg_name,$db = NULL,$settings = NULL){

    // if $db is database object, use that;
    // else if db is string, use as database name and get credentials from config
    // else if db is array, get credentials from array
    // else if db is a mysql link resource, pass to the db class
    // else use everything from config
    
    if(is_callable('session_status')){
      if (session_status() == PHP_SESSION_NONE) {
        session_start();
      }
    }else if (session_id() == '' && headers_sent() == false){
      session_start();
    }
    
    $ds = DIRECTORY_SEPARATOR;

    $working_abs_path = getcwd().$ds;

    $dg_abs_path = dirname(__FILE__).$ds;

    if(file_exists($working_abs_path.'dg_config.php')){
      include($working_abs_path.'dg_config.php');
    }else{
      include($dg_abs_path.'dg_config.php');
    }

    if(file_exists($working_abs_path.'dg_paths.php')){
      include($working_abs_path.'dg_paths.php');
    }else{
      include($dg_abs_path.'dg_paths.php');
    }

    if(file_exists($working_abs_path.'dg_modes.php')){
      include($working_abs_path.'dg_modes.php');
    }else{
      include($dg_abs_path.'dg_modes.php');
    }

    if(!class_exists('database')){
      if(file_exists($this->paths['classes_path'].'database.class.php')){
        include($this->paths['classes_path'].'database.class.php');
      }else if(file_exists($dg_abs_path.'dependencies/database.class.php')){
        include($dg_abs_path.'dependencies/database.class.php');
      }else{
        $this->exit_error('Database interaction class file not found');
      }
    }

    if(!class_exists('url')){
      if(file_exists($this->paths['classes_path'].'url.class.php')){
        include($this->paths['classes_path'].'url.class.php');
      }else if(file_exists($dg_abs_path.'dependencies/url.class.php')){
        include($dg_abs_path.'dependencies/url.class.php');
      }else{
        $this->exit_error('URL management class file not found');
      }
    }

    if(is_object($db)){
      $this->db = $db;
    }elseif(is_resource($credentials)){
       if(get_resource_type($credentials) == 'mysqli'){
         $this->db = new database($credentials);

       }
    }else{

      if(is_array($db)){
        foreach ($db as $key=>$value) {
          $db_config[$key] = $value;
        }
      }elseif(is_string($db)){
        $db_config['db'] = $db;
      }

      $this->db = new database($db_config);
    }
    
    if(count($this->db->errors) > 0){
      $this->exit_error('Count not create database class instance. Database connection failed. <b>Please check that the supplied database connection info is correct.</b><br />Database error:'.$this->db->text_errors());
    }
         
    $this->dg_name = $dg_name;
    $this->ses_name = $dg_name.'_dg';



    
    /* FUTURE CACHING SCHEME
    if(file_exists('cache/'.$dg_name.'_structure_cache.txt'){
      //read in xml, php, or csv file and parse into php struct array
      //best, simplest: write a php file with the array defined in it, then just include it
    }
    */
    
    
    if(!$structure){
      $this->auto_detect_fields($dg_name);
    }else {
      $this->struct = $structure;
    }
    
    if(!$this->session('settings')){

      $this->settings = Array(
        'privileges' => 'edit,add,delete',
        'display_name' => str_replace('_',' ',ucfirst($this->dg_name)),
        'defaultsort' => '',
        'defaultsearch' => "",
        'defaultlimit' => 50,
        'content_css' => 'css/pagecontent.css',
        'mode' => 'full',
        'global_debug_function' => 'testit',
        'unique_GET_prefix' => substr(md5($this->dg_name),0,5).'_'
      );
      
      if($settings){
        foreach ($settings as $key=>$value) {
          $this->settings[$key] = $value;
        }
      }

      $this->set_session('settings',$this->settings);

    }else{
      $this->settings = $this->session('settings');
    }

    if($this->GET('mode')){
      $this->set_setting('mode',$this->GET('mode'));
    }


    $this->target_url = new url();
  }
  
  function setup_child_grid(){
  
    $ugp = $this->settings['unique_GET_prefix'];
  
    $this->ses_name .= '_'.$this->GET('link_value');
    $this->target_url->set_query_pair($ugp.'mode','child');

    $parent_link_field = $this->GET('parent_link_field');
    $child_link_field = $this->GET('child_link_field');
    $link_value = $this->GET('link_value');

    $this->target_url->set_query_pair($ugp.'parent_link_field',$parent_link_field);
    $this->target_url->set_query_pair($ugp.'child_link_field',$child_link_field);
    $this->target_url->set_query_pair($ugp.'link_value',$link_value);

    $this->detailssql = $this->primary_table.'.'.$parent_link_field." = '$link_value'";

    $this->set_field_attribs($this->primary_table,$child_link_field,array(
          'type' => 'readonly,hidden',
          'default' => $link_value
          ));

    $this->set_setting('display_name',$this->setting('display_name').' for '.$child_link_field.' '.$link_value);
    $this->set_setting('default_hidden_fields',array($this->primary_table.$child_link_field));
  }

  function search(){
  
     if ($this->GET('searchquery') == 'showall'){
     
       $this->unset_session('searchfield');
       $this->unset_session('searchquery');
       $this->unset_session('searchtype');
       
       unset($this->searchsql,$this->searchquery,$this->searchfield,$this->searchfeedback);
       
     }elseif ($this->GET('searchquery') && $this->GET('searchtype') && $this->GET('searchfield')){
    
      $this->searchfield = $this->GET('searchfield');
      $this->searchtype = $this->GET('searchtype');
      $this->searchquery = $this->GET('searchquery');
      
      $this->set_session('searchfield',$this->searchfield);
      $this->set_session('searchtype',$this->searchtype);
      $this->set_session('searchquery',$this->searchquery);
      
      // Reset paging
      
      $this->start = 0;
      $this->set_session('start',0);
      
    }elseif($this->session('searchquery')){
    
      $this->searchfield = $this->session('searchfield');
      $this->searchtype = $this->session('searchtype');
      $this->searchquery = $this->session('searchquery');

    }elseif(is_array($this->setting('defaultsearch'))){

      $defsearch = $this->setting('defaultsearch');
      
      if($defsearch['field'] && $defsearch['type'] && $defsearch['value']){

      $this->searchfield = $defsearch['field'];
      $this->searchtype = $defsearch['type'];
      $this->searchquery = $defsearch['value'];
      
      }

    }
    
    
    if($this->searchquery && $this->searchfield && $this->searchtype){
    
      $this->searchfeedback = "Showing items where <b>".$this->searchfield."</b> ".$this->searchtype." <b>\"".$this->searchquery."\"</b>";

      if($this->field_attribs($this->get_primary_table().$this->searchfield)){ // Make sure field is legit

        $this->searchsql = $this->get_primary_table().'.'.$this->searchfield;
        
        if ($this->searchtype == "includes"){
          $this->searchsql .= " LIKE ";
          $this->searchsql .= "'%".$this->escape_str($this->searchquery)."%'";
        }else{
          $this->searchsql .= " = ";
          $this->searchsql .= "'".$this->escape_str($this->searchquery)."'";
        }
      }else{
        $this->set_feedback('Searched field doesn\'t exist!','warning');
      }
    }
  }



// Process sort order

// If not in the get, check session
// if not in session, check the default variable
// if default not set, use first field

function sort(){
  if($this->GET('sortfield')){
    $attribs = $this->field_attribs($this->GET('sorttable'),$this->GET('sortfield'));
    if(!$attribs['noquery']){
      $this->sorttable = $this->GET('sorttable');
      $this->sortfield = $this->GET('sortfield');
      $this->sorttype = $this->GET('sorttype');
    }
  }
  elseif($this->session('sortfield')){
    $this->sortfield = $this->session('sortfield');
    $this->sorttable = $this->session('sorttable');
    $this->sorttype = $this->session('sorttype');
  }
  elseif (is_array($this->settings['defaultsort'])) {
    $this->sortfield = $this->settings['defaultsort']['field'];
    $this->sorttable = $this->settings['defaultsort']['table'];
    $this->sorttype = $this->settings['defaultsort']['type'];
  }
  else{ 
    $this->sorttable = $this->primary_table;
    $this->sortfield = $this->primary_field;
    $this->sorttype = "DESC";
  }
  
  // Check for missing bits
  if(!$this->sorttype){
     $this->sorttype = "DESC";
  }
  if(!$this->sorttable){
     $this->sorttable = $this->primary_table;
  }
  
  
  $this->set_session('sortfield',$this->sortfield);
  $this->set_session('sorttable',$this->sorttable);
  $this->set_session('sorttype',$this->sorttype);
  
  $this->sorttitle = $this->field_attrib($this->sorttable.$this->sortfield,'title');
}

  function rowcount_feedback(){
    if($this->mode_shows('result_counts')){
      $startplus = ($this->start+ 1);
      $finish = ($this->start + $this->limit);
      if($finish > $this->queriedrows){
        $finish = $this->queriedrows;
      }

      if ($this->searchquery){
          return "Showing records $startplus - $finish out of ".$this->queriedrows." records that matched your query";
      }else{
          return "Showing records $startplus - $finish out of ".$this->totalrows." records in the table. ";
      }
    }
  }

  function paging(){

    // Process the start and limit variables
    // If not in the get, check session
    // if not in session, use defaults
    if ($this->GET('limit') === NULL){
      if ($this->session('limit') === NULL){
        $this->limit = $this->settings['defaultlimit'];
      }else{
        $this->limit = $this->session('limit');
      }
    }else{
      $this->limit = $this->GET('limit');
      $this->set_session('limit',$this->limit);
    }

    if ($this->GET('start') === NULL){
      if ($this->session('start') === NULL){
        $this->start = '0';
      }else{
        $this->start = $this->session('start');
      }
    }else{
      $this->start = $this->GET('start');
      if (!$this->start){
          $this->start = '0';
      }
      $this->set_session('start',$this->start);
    }

  }


  function set_setting($var,$value){
    $this->settings[$var] = $value;
  }

  function set_settings($array){
    foreach ($array as $key=>$value) {
      $this->settings[$key] = $value;
    }
  }

  function setting($setting){
   return $this->settings[$setting];
  }

  

  
  function item_delete(){
    if(is_numeric($this->GET('id'))){
      $this->db->delete($this->get_primary_table(),$this->get_primary_key()." = '".$this->GET('id')."'");
      return "Item ".$this->GET('id')." deleted";
    }else{
      $this->set_error("Invalid numeric key");
    }  
  }
  
  function process(){
    $action = $this->POST('action');
    $update_data = $_POST['data'];
    $key_field = $_POST['key_field'];
    $key_value = $_POST['key_value'];
    $table = $this->get_primary_table();
    
    //if($_POST['continue_edit']){
    //  $this->continue_edit = array('table' => $table,'key_field' => $key_field,'key_value' => $key_value);
    //  $this->set_session('continue_edit',$this->continue_edit);
    //}
    
    if($_POST['continue'] == 'on'){
      $this->set_GET('action','edit');
      $this->set_GET('id',$key_value);
    }
    
    $this->redir_page = $_POST['redir_page'];
    
    if($_POST['non_array_input_names']){
      foreach ($_POST['non_array_input_names'] as $key=>$value) {
        $db_field = substr($value, 10);
      	$update_data[$db_field] = $_POST[$value];
      }
    }

    foreach ($_POST['data'] as $field => $value){
    
      // Check for callbacks
      $attribs = $this->field_attribs($table,$field);
      if($callback = $attribs['save_callback']){
      
        if(function_exists($callback)){
          $update_data[$field] = $callback($table,$field,$value,$_POST['data']);
          
        }else{
          $this->dbg('Save callback error - undefined function',$callback);
        }
      }
      
      if($attribs['noinsert']){
        unset($update_data[$field]);
      }
    }
    
    if($action == 'update'){
      $updatesql = "UPDATE $table SET "; 
      $intermed = '';
      foreach($update_data as $field => $value){   
        $esc_value = $this->escape_str($value);
        $updatesql .= "$intermed`$field` = '$esc_value'";
        $intermed = ', ';
      }
      
      $updatesql .= " WHERE `$key_field` = '$key_value'";
      
      if($this->db->update('sql:'.$updatesql)){
        $this->feedback = "Item successfully updated in $table table";
      }else{
        $this->exit_error('SQL error in UPDATE');
      }
      

      

      
    }else if($action == 'insert'){
      $insertsql = "INSERT INTO $table ";
    
      $intermed = '';
      foreach($update_data as $field => $value){
        $esc_value = $this->escape_str($value);
        $sqlfields .= "$intermed $field";
        $sqlvalues .= "$intermed '$esc_value'";
        $intermed = ',';
      }
      $insertsql .= "($sqlfields) VALUES ($sqlvalues)";

      if($this->db->insert('sql:'.$insertsql)){
        $this->set_feedback("Item successfully added to $table table");
      }else{
        $this->exit_error('SQL error in INSERT');
      }
      

    }

  }
  
  function set_session($var,$value){
    $_SESSION[$this->ses_name][$var] = $value;
  }  
  
  function unset_session($var){
    unset($_SESSION[$this->ses_name][$var]);
  } 
  
  function session($var){
    return $_SESSION[$this->ses_name][$var];
  }
  
  function set_field_position($table,$field,$position_type = "before",$relative_field = NULL){

    $temp_tbl = $this->struct[$table]['fields'][$field];
    $temp_fid = $this->fields[$table.$field];
    
    unset($this->struct[$table]['fields'][$field]);
    unset($this->fields[$table.$field]);
    
    if($position_type == "start"){
      $temp_fids = array_reverse($this->fields, true);
      $temp_fids[$table.$field] = $temp_fid;
      $this->fields = array_reverse($temp_fids, true);
      
      $temp_tbl_fields = array_reverse($this->struct[$table]['fields'], true);
      $temp_tbl_fields[$field] = $temp_tbl;
      $this->struct[$table]['fields'] = array_reverse($temp_tbl_fields, true);
    }
    
    if($position_type == "end"){
      add_field($table,$field,$temp_tbl);
    }
    
    if($position_type && $relative_field){
      $this->struct[$table]['fields'] = $this->array_insert_assoc($this->struct[$table]['fields'],$relative_field,$field,$temp_tbl,$position_type);
      $this->fields = $this->array_insert_assoc($this->fields,$table.$relative_field,$table.$field,$temp_fid,$position_type);
    }

  }
  
  function add_field($table,$field,$attribs = array('type' => 'text')){
    if(!$attribs['title']){
      $attribs['title'] = ucfirst(trim($field));
    }
    if(!$attribs['fid']){
      $attribs['fid'] = $table.$field;
    }
    $this->set_field_attribs($table,$field,$attribs);
  }
   
  function set_field_attribs($table,$field,$attribs){
    foreach($attribs as $attrib => $value){
      $this->set_field_attrib($table,$field,$attrib,$value);
      $this->set_fid_attrib($table.$field,$attrib,$value);
    }
  }
  
  function set_field_attrib($table,$field,$attrib,$value){

  # [STRUCT FLAG]
      $this->struct[$table]['fields'][$field][$attrib] = $value;
      $this->set_fid_attrib($table.$field,$attrib,$value);
  }

  function set_fid_attrib($fid,$attrib,$value){
  # [STRUCT FLAG]
      $this->fields[$fid][$attrib] = $value;
  }

  function get_table_fields($table){
  # [STRUCT FLAG]
    return $this->struct[$table]['fields'];
  } 
  function get_table_info($table){
  # [STRUCT FLAG]
    return $this->struct[$table];
  }
  function get_primary_table(){
    # [STRUCT FLAG]
    foreach ($this->struct as $table=>$attribs) {
      if($attribs['type'] == 'primary'){
        return $table;
      }
    }
  }
  
  function get_primary_key($table = NULL){
    if(!$table){
      $table = $this->get_primary_table();
    }
    # [STRUCT FLAG]
    foreach($this->struct[$table]['fields'] as $field => $attribs){
      if($attribs['key']=='primary'){
        return $field;
      }
    }
  }
  
  function get_tables(){
  # [STRUCT FLAG]
    return $this->struct;
  }
  
  function remove_field($table,$field){
     # [STRUCT FLAG]
    unset($this->struct[$table]['fields'][$field]);
  }

  function add_parent_table($table,$local_key,$foreign_key,$display_field = null){

    # [STRUCT FLAG]
    
    if(!$display_field) $display_field = $foreign_key;
    $primary_table = $this->get_primary_table();
    $this->struct[$table]['local_key'] =  $local_key;
    $this->struct[$table]['foreign_key'] =  $foreign_key;
    $this->struct[$table]['type'] = 'parent';

    // set the local key to a dynamic select
    $this->struct[$primary_table]['fields'][$local_key]['type'] = "select:$table,$foreign_key,$display_field";
    
    // Guess at the singular title of this table's items
    if(substr($table,-3) == 'ies'){
      $column_title = substr($table,0,-3).'y';
    }elseif(substr($table,-1) == 's'){
      $column_title = substr($table,0,-1);
    }else{
      $column_title = $table;
    }
    
    $column_title = ucfirst(str_replace('_',' ',$column_title));
    
    // Hide the (probably numeric) local key so it doesn't show in the grid
    $this->struct[$primary_table]['fields'][$local_key]['display_type'] = "none";
    
    // Show the foreign title field, with appropriate (local field) header
    $this->struct[$table]['fields'][$display_field] = array('type'=>'text','title'=>$column_title);

  }

  function add_child_table($table,$parent_link_field,$child_link_field = NULL,$fields = null){
    $this->struct[$table]['parent_link_field'] =  $parent_link_field;
    $clf = $child_link_field ? $child_link_field : $parent_link_field;
    
    $this->struct[$table]['child_link_field'] =  $clf;
    $this->struct[$table]['type'] = 'child';
    $this->struct[$table]['display_type'] = 'popup';
    $this->struct[$table]['title'] = ucfirst($table);
    $this->struct[$table]['GET_pfx'] = substr(md5($table),0,5).'_';
    if($fields){
     # [STRUCT FLAG]
      $this->struct[$table]['fields'] = $fields;
    }else{
      $this->auto_detect_fields($table,'child');
    }
  }
  
  function field_attribs($arg1,$arg2 = NULL){
    if($arg2){ // Field and table
        return $this->struct[$arg1]['fields'][$arg2];
    }else{     // Find by FID
        return $this->fields[$arg1];
    }
  }
  
  function field_attrib($fid,$attrib){
    return $this->fields[$fid][$attrib];
  }

  function auto_detect_fields($table,$type='primary'){

    $table_description = $this->db->select("sql:DESCRIBE $table");
    
    if(!$table_description){
      $this->exit_error('Database error in auto_detect_fields(). <b>Please make sure your table names are correct in main and child table related code.</b><br /><br />Database errors:<br />'.$this->db->text_errors());
    }

    foreach ($table_description as $key=>$row){
    
      $attribs['maxlength'] = substr(strrchr($row['Type'], "("), 1, -1);
      $name = $row['Field']; 
      
      if($row['Key'] == 'PRI'){
        $attribs['type'] = "readonly,hidden";
        $attribs['key'] = 'primary';
        if($this->primary_field == ''){
          $this->primary_field = $row['Field'];
        }
        if($this->primary_table == ''){
          $this->primary_table = $table;
        }
      }elseif(strpos($row['Type'],'text') !== false){
        $attribs['type']= "textarea";
        $attribs['key']= NULL;
      }else{
        $attribs['type'] = "text";
        $attribs['key']= NULL;
      }
      
      if($row['Field'] == 'content'){
        $attribs['type'] = 'wysiwyg';
      }elseif($row['Field'] == 'date'){
        $attribs['type'] = 'date';
      }
  
      $attribs['title'] = str_replace('_',' ',ucfirst($row['Field']));
      $attribs['fid'] = $table.$name;

      $attribs['table'] = $table;

      $this->fields[$table.$name] = $attribs;
      
      $this->struct[$table]['fields'][$row['Field']] = $attribs;       
      $this->struct[$table]['type'] = $type;
      $attribs['table'] = $table;
    }
  }
  
  
  function assemble_query(){

      $sql = 'SELECT ';
      
      $db_fields = array();
      $db_tables = array();
      
      $hidden_fields = array();
      
      if(is_array($this->session('hidden_fields'))){
        $hidden_fields = $this->session('hidden_fields');
      }
      
      foreach ($this->get_tables() as $table => $tabledata){
        if($tabledata['type'] != 'child'){
          $db_tables[$table] = $tabledata;

          if($tabledata['type'] == 'primary'){
            $primary_table = $table;
          }

          foreach($this->get_table_fields($table) as $field => $attribs){
            if ($attribs['display_type'] != 'noquery' && !$attribs['noquery'] && $attribs['type'] != 'derivative'){
              // Add alias for fields in non-primary table to avoid name conflicts
              if($table != $primary_table){
                 $alias_sql = ' as '.$table."_".$field;
              }else{
                 $alias_sql = '';
              }

              if(!in_array($attribs['fid'],$hidden_fields)){
                  $db_fields[] = '`'.$table.'`'.'.'.'`'.$field.'`'.$alias_sql;
              }
            }
          }
        }
      }
      
      $sql .= join($db_fields,', ');
      
      $sql .= " FROM ";
      $count = '';
      foreach($db_tables as $table => $table_attribs){

        if($table_attribs['type'] == 'primary'){
          $sql .= "$table ";
        }else if($table_attribs['type'] == 'parent'){
          $sql .= " JOIN $table ON ".$table.'.'.$table_attribs['foreign_key']." = ".$primary_table.'.'.$table_attribs['local_key'];
        }
      }

      $this->totalrows = $this->db->count('sql:'.$sql);
      
      if ($this->searchsql && $this->detailssql){
        $sql .=" WHERE  ".$this->searchsql." AND ".$this->detailssql;
      }elseif($this->searchsql){
        $sql .=" WHERE  ".$this->searchsql;
      }elseif($this->detailssql){
        $sql .=" WHERE  ".$this->detailssql;
      }
       
      $this->queriedrows = $this->db->count('sql:'.$sql);
      
      if ($this->sortfield){
        $sql .= " ORDER BY ".$this->sorttable.".".$this->sortfield." ".$this->sorttype;
      }
      
      if ($this->limit){
        $sql .= " LIMIT ".$this->start.", ".$this->limit;
      }
      
      $this->displayedrows = $this->db->count('sql:'.$sql);
      
      $this->sql = $sql;
  }

  function toggle_sorttype($sorttype){
    if($sorttype == 'ASC'){
      return 'DESC';
    }else{
      return 'ASC';
    }
  }
  
  function grid(){
  
    $ugp = $this->settings['unique_GET_prefix'];

    foreach ($this->target_url->query_parts as $key=>$value) {
    	if(substr($key,0,strlen($ugp)) == $ugp){
        $this->target_url->delete_query_pair($key);
      }
    }

    # [DETAILS FLAG]
    if($this->GET('mode') == 'child'){
      $this->setup_child_grid();
    }
  
    $out = '';

    if($_POST['action'] == 'process_csv'){
      $this->csv_export_process();
    }

    if ($this->POST('action') == 'update' || $this->POST('action') == 'insert'){
      if($this->check_privilege('edit')){
        $this->process();
      }
    }

    if($this->GET('action') == 'delete'){
      if($this->check_privilege('delete')){
        $this->set_feedback($this->item_delete($_GET));
      }
    }

    if ($this->GET('hide_field')){
      $this->hide_field($this->GET('hide_field'));
    }
    
    if ($this->GET('unhide_field')){
      $this->unhide_field($this->GET('unhide_field'));
    }
    
    if($this->GET('action') == 'export_csv'){
      $out .= $this->csv_export_options();
    }else if($this->GET('action') == 'html_form_options'){
      $out .= $this->html_form_options();
    }else if($this->GET('action') == 'create_html_form'){
      $out .= $this->create_html_form();
    }else if($this->GET('action') == 'create_php_code'){
      $out .= $this->create_php_code();
    }
    
    else if ($this->GET('action') == 'edit' || $this->GET('action') == 'add' || $this->GET('action') == 'copy'){
      if($this->check_privilege('add') && ($this->GET('action') == 'add' || $this->GET('action') == 'copy')){
        $out .= $this->edit();
      }
      if($this->check_privilege('edit') && $this->GET('action') == 'edit'){
        $out .= $this->edit();
      }
    }else{
    
      $this->sort();
      $this->search();
      $this->paging();
      $this->assemble_query();
      $this->default_hidden_fields();

      $out .= $this->feedback();
      $out .= $this->grid_controls();
      $out .= $this->hidden_fields_links();
      $out .= $this->rowcount_feedback();
      $out .= $this->make_grid();
      $out .= $this->admin_tools();
      $out .= $this->dev_tools();
      $out .= $this->footer();
      
      $this->dbg('Struct',$this->struct);
      $this->dbg('SESSION',$_SESSION);
      
    } /**//**/
    
    $return = '';

    if ($this->mode_shows('html_head')){
       $return .= $this->html_header();
    }
    if ($this->mode_shows('title')){
       $return .= $this->grid_title();
    }
    

    if($this->setting('mode') == 'child' && $this->GET('display_type') == 'popup'){
      $return .= '<div id="close_window_button" onClick="window.close();">Close window</div>';
    }

    
    $return .= $out;
    
    return $return;
  }



  private function make_grid(){

    $out .= "<div id =\"dg\">";

      $td = '
      <td class="datafield">';
      $etd = '
      </td>';

      $tr = '
      <tr>';
      $trend = '
      </tr>';

      $out .= '<table class="datatable"><tr><td valign="bottom">';
      
      if(strpos($this->settings['privileges'],'add') !== false){
        $out .= $this->add_button();
      }
      
      $out .= '</td>';

      #### Table headers ####

      foreach ($this->struct as $table => $tableinfo){
        if($tableinfo['type'] != 'child'){
          foreach($tableinfo['fields'] as $field => $fieldattribs){
            $fid = $fieldattribs['fid'];
            if(!$this->check_hidden_field($fid)){
              if ($fieldattribs['display_type'] != 'none'){

                if($this->sorttable == $table && $this->sortfield == $field){
                  $headclass = "dataheadersort";
                  $new_sort = $this->toggle_sorttype($this->sorttype);
                }else{
                  $headclass = "dataheader";
                  $new_sort = $this->sorttype;
                }


                if($fieldattribs['type'] != 'derivative'){
                  $columnhead = "<td class=\"$headclass\" valign=\"bottom\">
                  <a class=\"$headclass\" href=\"".$this->target_url->get($this->GET_pfx()."sorttable=$table&".$this->GET_pfx()."sortfield=$field&".$this->GET_pfx()."sorttype=$new_sort")."\">$fieldattribs[title]</a>
                  <a class=\"hidefield\" href=\"".$this->target_url->get($this->GET_pfx()."hide_field=$fid")."\">>hide<</a></td>";
                }else{
                  $columnhead = "
                  <td class=\"$headclass\" valign=\"bottom\">
                  <span class=\"$headclass\">$fieldattribs[title]</span>
                  <a class=\"hidefield\" href=\"".$this->target_url->get().$this->GET_pfx()."hide_field=$fid\">>hide<</a>
                  </td>";
                }
                $out .= $columnhead;
              }
            }
          }
        }
        else{
          $out .= "
                  <td class=\"dataheader\" valign=\"bottom\">
                  <span class=\"dataheader\">$tableinfo[title]</span>
                  </td>";
        }
      }
      $out .= "</tr>";

    $main_grid_data = $this->db->select('sql:'.$this->sql);
    
    if(!is_array($main_grid_data)){
      $this->dbg('Database error',$this->db->text_errors(),'error');
      
      $error_text = "
      SQL error in grid display query. Please check that all the table and field names used in your grid setup exist in the database.<br /><br />
      Database errors:";
      $error_text .= $this->db->text_errors();
      
      $this->exit_error($error_text);
    }
    
    foreach ($main_grid_data as $key=>$row) {

      $this->rowcount++;

      if($oddrow == 1){
        $oddrow = 0;
      }else{
        $oddrow = 1;
      }

      $keyval = $row[$this->get_primary_key()];

      $out .= $this->start_row($keyval,$oddrow);

      $out .= $td.$this->edit_form($keyval).$etd;

      foreach ($this->struct as $table => $tableinfo){
        if($tableinfo['type'] != 'child'){
          foreach ($tableinfo['fields'] as $field => $fieldattribs){
            if ($fieldattribs['display_type'] != 'none' && $this->check_hidden_field($fieldattribs['fid']) != 1){

              // To avoid column name conflicts, non-primary table column names are aliased to table_field
              if($tableinfo['type'] != 'primary'){
                $field = $table.'_'.$field;
              }
              $out .= $td;
              $out .= $this->show_cell($field,$row[$field],$fieldattribs,$row,$keyval);
              $out .= $etd;

            }
          }
        }else{
           $link_value = $row[$tableinfo['parent_link_field']];
           $out .= $td;
           
           if($tableinfo['display_type'] == 'popup'){
           
             $GET_pfx = $tableinfo['GET_pfx'];
             $child_title = $this->struct[$table]['title'];

             $child_url_vars = array(
                'parent_link_field' => $tableinfo['parent_link_field'],
                'child_link_field' => $tableinfo['child_link_field'],
                'link_value' => $link_value,
                'mode' => 'child',
                'display_type' => $tableinfo['display_type']
             );

             $child_url = $tableinfo['edit_url'].'?';

             $join = '';
             foreach ($child_url_vars as $key=>$value) {
              	 $child_url .= $join.$GET_pfx.$key.'='.$value;
              	 $join = '&';
             }
              
              
             $out .= "
<div class=\"edit_child_cell_inactive\" onClick=\"show_child_grid_popup('$child_url')\">View/edit ".$tableinfo['title'].'</div>';

          }elseif($tableinfo['display_type'] == 'iframe'){
           
              $out .= '
<div class="edit_child_cell_inactive" id="child_grid_link_cell_'.$link_value.'">
<span id="show_child_grid_'.$link_value.'" style="display:inline" onclick="javascript:show_child_grid(\''.$link_value.'\');">View/edit&nbsp;'.$tableinfo['title'].'</span>
<span id="hide_child_grid_'.$link_value.'" style="display:none"  onclick="javascript:show_child_grid(\''.$link_value.'\');">Hide&nbsp;'.$tableinfo['title'].'</span>
</div>
';
              $before_next_row = $this->show_child_grid($table,$link_value);
              
           }
           $out .= $etd;
        }
      }

      $out .= "</tr>";
      $out .= $before_next_row;
    }

    $out .= "</table>";


    // Grid feedback and paging links
    if($this->mode_shows('paging')){

      # no results
      if (!$this->queriedrows){
        $out .= "No data to display";
      }else{

        # results 'to the left' of ones shown
        if($this->start > 0){
        
            $prevstart = ($this->start - $this->limit);
            
            if ($prevstart < 0){
              $prevstart = "0";
            }

            $out .="
            <br clear=\"all\">
            <div align=\"left\" style=\"float:left\">
            <a href=\"".$this->target_url->get(array($this->GET_pfx().'limit' => $this->limit,$this->GET_pfx().'start'=> $prevstart))."\"><<< Previous ".$this->limit." items</a>
            </div>";
        }

        # results 'to the right' of ones shown
        if($this->displayedrows < $this->queriedrows && ($this->start + $this->limit) < $this->queriedrows){
          $nextstart = ($this->start + $this->limit);
          $out .= "
          <div align=\"right\" style=\"float:right\">
          <a href=\"".$this->target_url->get(array($this->GET_pfx().'limit' => $this->limit,$this->GET_pfx().'start'=> $nextstart))."\">Next ".$this->limit." items >>></a>
          </div>";
        }

        # All results shown
        if($this->displayedrows == $this->queriedrows){
          # With no searchquery
          if($this->displayedrows == $this->totalrows){
            $ifqueryfeedback = "in the table.";
          }
          # With a searchquery
          if($this->searchquery && $this->displayedrows == $this->queriedrows){
            $ifqueryfeedback = "that matched your query.";
          }
          $out .= "Showing all records $ifqueryfeedback";
        }
      }
    }
    $out .= "</div>";
    return $out;
  }

  function edit_form($id){
    $this->dbg('Target URL in edit_form()',$this->target_url->get());
  
    $out = "
    <div align=\"center\" id=\"dg_edit_buttons\">";
    $keyinput = "<input type=\"hidden\" name=\"".$this->GET_pfx()."id\" value=\"$id\" />";
    
    if(strpos($this->settings['privileges'],'add') !== false){
      $out .= "
      <form name=\"update\" action=\"".$this->target_url->get()."\" method=\"get\" class=\"update\">";
      $out .= $this->target_url->get_hidden_inputs();
      $out .= "
      <input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"copy\" />
      <input type=\"submit\" value=\"+\" class=\"copy\">
      $keyinput
      </form>
      ";
    }
    
    if(strpos($this->settings['privileges'],'edit') !== false){
      $out .= "
      <form name=\"update\" action=\"".$this->target_url->get()."\" method=\"get\" class=\"update\">";
      $out .= $this->target_url->get_hidden_inputs();
      $out .= "
      <input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"edit\" />
      <input type=\"submit\" value=\"Edit\" class=\"update\"> 
      $keyinput
      </form>
      ";
    }

    if(strpos($this->settings['privileges'],'delete') !== false){
      $out .= "<form action=\"".$this->target_url->get()."\" method=\"get\" class=\"update\">";
      $out .= $this->target_url->get_hidden_inputs();
      $out .= "
      <input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"delete\" />
      $keyinput
      <input type=\"submit\" value=\"Del\" class=\"delete\" onClick=\"return confirmSubmit('$id')\"/>
      </form>";
    }
    
    $out .= "</div>";
    return $out;
  }

  function start_row($keyval,$oddrow){
    if($oddrow == 1){
      $bg = 'f4efdd';
    }else{
      $bg = 'faf5ed';
    }
  return "<tr id=\"".$this->rowcount."\" bgcolor=\"#$bg\" onClick=\"highlightRow('".$this->rowcount."','#$bg')\">";
  }


  function show_cell($field,$value,$fieldattribs,$row,$keyval){
  
      if($fieldattribs['truncate']){
         $trunc_break = '';
         $trunc_pad = '...';
         $value = $this->truncate($value,$fieldattribs['truncate'],$trunc_break,$trunc_pad);
      }
  
  
      if($fieldattribs['display_style']){
        $style = ' style="'.$fieldattribs['display_style'].'"';
      }
      if($fieldattribs['display_class']){
        $class = ' class="'.$fieldattribs['display_class'].'"';
      }
      $out .= "
          <div$class$style>
";
      


      // CALLBACK FIELDS (Display output of callback function)
      if($fieldattribs['display_callback']){
        $function = $fieldattribs['display_callback'];

        if(function_exists($function)){
          $out .= $function($this->get_primary_table(),$field,$value,$row,$keyval);
        }else{
          $this->dbg('Error in display callback. Function doesn\'t exist',$function);
          $out .= $value;
        }

      }
      
      // DISPLAY TEMPLATE
      else if($fieldattribs['display_template']){
        $code = $fieldattribs['display_template'];
        
        foreach ($row as $key=>$value) {
        	$code = str_replace('['.$key.']',$value,$code);
        }

        $out .= $code;

      }else{
      
        if($fieldattribs['display_type'] == 'currency'){
          $out .= $this->currency_format($value);
        }else{
          $out .= $value;
        }
      

      }
      $out .= "
          </div>";
    return $out;
  }

  function edit(){

    $non_array_input_count = 0;
  
    $redir_page = $this->GET('redir_page');
    
    $action = $this->GET('action');
    
    if($action == 'add' || $action == 'copy'){
       $nextaction = 'insert';
    }elseif($action == 'edit'){
       $nextaction = 'update';
    }
    
    $id = $key_value = $this->GET('id');

    $key_field = $this->get_primary_key($this->primary_table);

    // Turn the GET array into a string for the save and continue var
    $get_string = "";
    $int = '';

    foreach ($_GET as $key=>$value) {
      $get_string .= $int.$key."=".$value;
      $int = '&';
    }

    $table = $this->get_primary_table();
    $table_attribs = $this->get_table_info($table);
    $fields = $this->get_table_fields($table);
    
    $out  = $this->feedback();
    
    $out .= "<form action=\"".$this->target_url->get(array($this->GET_pfx().'action' => null))."\" method=\"post\" id=\"edit_form\">
    <div>
    ";

    if($action == 'edit' || $action == 'copy'){
      $row = $this->db->select_one("sql:".$this->edit_query($id));
    }else{
      $row = NULL;
    }
    
    if($action == 'copy'){
      unset($row[$key_field]);
    }
    
    
    foreach ($fields as $field => $fa){
      $fieldattribs = $fa;
      
      if(strpos($fa['type'],':') !== false){
        $type_parts = explode(':',$fa['type']);
        $type = $type_parts[0];
        $type_params = explode(',',$type_parts[1]);
      }else{
        $type = $fa['type'];
        unset($type_params);
      }

      $break = "<br clear=\"all\" /></div>";

      if(!$type){
      
        $this->exit_error('Field type is empty for: '.$field);
        
      }elseif($type != 'derivative'){


        if ($fa['default'] && ($row[$field]=='')){
            $row[$field] = $fa['default'];
        }

        if ($fa['maxlength']){
            $maxlength = "
            <span class=\"maxlength\">&nbsp;&nbsp; (Max $fa[maxlength] characters)</span>
            ";
        }
        
        if ($fa['edit_style']){
            $style = "style=\"$fa[edit_style]\" ";
        }else{
            $style = "";
        }
        if ($fa['edit_class']){
            $class = "class=\"$fa[edit_class]\" ";
        }else{
            $class = "";
        }

          $fieldlabel = "
          <div class=\"fielddiv\" id=\"$table$field\">
          <span class=\"label\">$fa[title]:</span>
          ";

          // CALLBACK FIELDS
          if($fa['edit_callback']){
             $function = $fa['edit_callback'];
             if(function_exists($function)){
                $out .= $fieldlabel;
                $out .= $function($this->get_primary_table(),$field,$row[$field],$row);
             }else{
                $this->dbg('Undefined or inaccessible callback function:',$function);
             }

          }
          //READ ONLY FIELDS
          elseif ($type == 'readonly'){
            $out .= "
            $fieldlabel
            <span class=\"readonly\"$style>$row[$field]</span>
            ";
          }
          
          //HIDDEN FIELDS
          else if($fa['type'] == 'hidden'){
            $out .= "<input type=\"hidden\" name=\"data[$field]\" value=\"$row[$field]\" />";
            $break = '';
          }

          //READ ONLY + HIDDEN FIELDS
          else if ($type == 'readonly,hidden'){
            $out .= "
            $fieldlabel <span class=\"readonly\"$style>$row[$field]</span>
            <input type=\"hidden\" name=\"data[$field]\" value=\"$row[$field]\" />
            ";
          }

          //TEXT FIELDS
          else if ($type == 'text'){
            $out .= "$fieldlabel
            <input type=\"text\" name=\"data[$field]\" value=\"$row[$field]\" $fieldclass maxlength=\"$fa[maxlength]\" $style/> $maxlength
            ";
          }

          //DYNAMIC SELECT FIELDS
          else if ($type == 'select'){
            $table = $type_params[0];
            $value_field = $type_params[1];
            $display_field = $type_params[2];
            if($type_params[3]){
              $add_blank_field = true;
            }else{
              $add_blank_field = false;
            }
            $add_blank_field = $type_params[3];
            $out .= $fieldlabel;
            $out .= $this->dynamic_select("data[$field]", $table, $value_field, $display_field,$row[$field],$add_blank_field);
          }

          //TEXTAREA FIELDS
          else if ($type == 'textarea'){
            $out .="
            $fieldlabel
            <textarea name=\"data[$field]\"  $fieldclass $style>$row[$field]</textarea> $maxlength";
          }

         //EXTRA FIELDS
         else if ($type == 'extra'){
            $out .= $type_params[0];
         }

         //WYSIWYG TEXTAREA FIELDS
         else if ($type == 'wysiwyg'){
            $size_array = $type_params;
            if(count($size_array) < 1){
              $size_array = array('900','500');
            }
            
            $init_str = "?content_css=".$this->settings['content_css']."&width=$size_array[0]&height=$size_array[1]";
            
            if($this->mode_shows('html_head')){
              $this->add_head_link('tiny_mce.js','js',$this->paths['tiny_mce_path']);
              $this->add_head_link('plugins/tinybrowser/tb_tinymce.js.php','js',$this->paths['tiny_mce_path']);
              
              $this->add_head_link('tinymce_init.js.php'.$init_str,'js',$this->paths['js_path']);
            }
            
            //$out .= $this->wysiwyg_code($this->settings['content_css'],,$size_array[1],$size_array[2]);
            $out .= "
            $fieldlabel
            <div style=\"margin-top: 5px;\">
            <textarea  name=\"data[$field]\"
            class=\"mceEditor\" id=\"mceEditor\">$row[$field]</textarea>
            $maxlength </div>
            ";
         }

          //STATIC SELECT FIELDS
         else if ($type == 'staticselect'){
            $statselect_items = $type_params;
            $out .= "$fieldlabel<select name=\"data[$field]\"  $fieldclass><br />";
            foreach ($statselect_items as $key => $statselect_item){
                  $out .= "<option";
                  if ($statselect_item == $row[$field]){
                  $out .= " selected ";
                  }
                  $out .= ">$statselect_item</option>";
            }
            $out .= "</select>
            ";
          }

          //DATE FIELDS
          else if ($type == 'date'){

            $out .= "<input type=\"hidden\" name=\"non_array_input_names[$non_array_input_count]\" value=\"non_array_$field\" />";
            
            $non_array_input_count++;

            if($type_params[0]){
              $dateFormat = $type_params[0];
            }else{
              $dateFormat = 'YYYY-MM-DD';
            }
            if($row[$field]){
              $defaultDate = $row[$field];
            }else{
              $defaultDate = date('Y-m-d');
            }

            if($fa['required']){
              $requiredDate = true;
            }else{
              $requiredDate = false;
            }

            $out .= "$fieldlabel<script>DateInput('non_array_$field', '$requiredDate', '$dateFormat','$defaultDate')</script>";
          }

          //PASSWORD FIELDS (added Dec 15 09)
          else if ($type == 'password'){
            if($type_params[0]){
              $hashtype = $type_params[0];
            }

            if($row[$field] && $hashtype){
              $passwd_code = 'Password is a '.$hashtype.' hash. Reset the password in the DB admin if necessary';
            }else{
              $passwd_code = "<input type=\"password\" name=\"data[$field]\" value=\"$row[$field]\" $fieldclass maxlength=\"$fa[maxlength]\"/> $maxlength";
            }

            $out .= "$fieldlabel $passwd_code";
          }


          // MULTIPLE CHECKBOX FIELDS
          
          else if ($type == 'multicheckbox'){
            $options = $type_params;
            
            $mc_code = "<table><tr><td>";
            
            $current_values = explode(', ',$row[$field]);
            
            foreach ($options as $key=>$value) {
            
              if(in_array($value,$current_values)){
                $checked = 'checked';
              }else{
                $checked = '';
              }
              
            	$mc_code .= '<div><div style="clear: left; width:20px; height: 20px; float:left; padding-top: 5px;">';
              $mc_code .= "<input style=\"width: 20px;\" type=\"checkbox\" id=\"dg_multicheck_".$field."_".$value."\" onChange=\"multicheckUpdate('dg_multicheck_$field','$value')\" $checked/>";
              $mc_code .= '</div><div style="height: 20px; float:left; padding-left: 10px; padding-bottom: 5px;">';
              $mc_code .= '<label for="dg_multicheck_'.$field.'_'.$value.'">'.$value.'</label>';
              //
              $mc_code .= "</div></div>";
              
            }

            $mc_code .= "<br clear=\"all\"><div style=\"margin-top:20px;\"><textarea style=\"width: 400px; height:50px;\" id=\"dg_multicheck_$field\" name=\"data[$field]\">$row[$field]</textarea></td></tr></table>";

            $mc_code .= "";
            $mc_code .= '</td></tr></table>';

            $out .= "$fieldlabel $mc_code";
          }
          
          // ACCESS CONTROL FIELDS
          
          else if ($type == 'access_control'){
          
            if($type_params[0]){
              $default = $type_params[0];
            }

            // Check if there's a custom access control edit function available
            if(function_exists('dg_edit_access_control')){
              $out .= dg_edit_access_controls($table,$keyval,$default);
            }else{
              $out .= $this->dg_edit_access_controls($table,$keyval,$default);
            }
          }else{
            $this->exit_error('Invalid field type: "'.$type.'"');
          }
            

          if($fa['notes']){
            $out .= "
            <div class=\"edit_note\">
            <b>Note:</b> $fa[notes]
            </div>";
          }
          $out .= $break;
         
          unset($maxlength);
        }
      }

    $out.= "
    <input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"$nextaction\">
    <input type=\"hidden\" name=\"redir_page\" value=\"$redir_page\">
    <input type=\"hidden\" name=\"key_field\" value=\"$key_field\">
    <input type=\"hidden\" name=\"key_value\" value=\"$key_value\">

    <br clear=\"all\">
    <div>";



    if($action == 'edit'){
      $out.= "
<button type=\"submit\">Save Changes</button>";

      if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') !== false){
        $out.= "
<button type=\"submit\" id=\"continue_edit_button\" onClick=\"contButton('on')\">Save and continue editing</button>
<input type=\"hidden\" name=\"continue\" id=\"continue_edit\" value=\"\">
        ";
      }else{
        $out.= "
<button type=\"submit\" name=\"continue\" value=\"on\">Save and continue editing</button>";
      }

    }else{
      $out .= "
<button type=\"submit\">Save</button>";
    }
    
    
    $out .= "
<button type=\"button\" onclick=\"javascript:location.href ='".$this->target_url->get(array('action'=>NULL))."'\">Cancel</button>
    </div>
    </div>
    </form><br />
 </body>
</html>";
    return $out;
  }

  function edit_query($id){
  
    // Start the query var
    $sql = 'SELECT ';

    // Write the fields to the query, with apostrophes
    $intermed = '';
    foreach($this->get_table_fields($this->primary_table) as $field => $attribs){
      if ($attribs['type']!='derivative' && !$attribs['noquery']){
        $sql .= $intermed.'`'.$this->primary_table.'`'.".".'`'.$field.'`';
        $intermed = ', ';
      }
    }
    
    $sql .= " FROM ".$this->primary_table;
    $sql .= " WHERE ".$this->get_primary_key($this->primary_table)." = '$id'";
    return $sql;
  }
  
  
  
  function html_header(){
   $code = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
  <html>
    <head>
    <meta http-equiv=\"content-type\" content=\"text/html; charset=windows-1250\">";

    $code .= $this->html_head_links();

    $code .= "<title>".$this->setting('display_name')."</title>
    </head>
    <body>";

    return $code;
  }
  
  function html_head_links(){

    $code = "
     <link href=\"".$this->paths['css_path']."datagrid.css\" rel=\"stylesheet\">
     <script language=\"javascript\"  type=\"text/javascript\"  src=\"".$this->paths['js_path']."datagrid.js\"></script>
<script language=\"javascript\"  type=\"text/javascript\"  src=\"".$this->paths['js_path']."calendarDateInput.js\"></script>
<script language=\"javascript\"  type=\"text/javascript\"  src=\"".$this->paths['js_path']."jquery.js\"></script>
";
    if(is_array($this->head_links['css'])){
      foreach ($this->head_links['css'] as $key => $link){
         $code .= "<link href=\"".$link."\" rel=\"stylesheet\">\n";
      }
    }
    
    if(is_array($this->head_links['js'])){
      foreach ($this->head_links['js'] as $key => $link){
         $code .= "<script language=\"javascript\" type=\"text/javascript\" src=\"".$link."\"></script>\n";
      }
    }
    return $code;
  }
  
  
  function grid_title(){
    $out = "<h1 class=\"datagrid_header\">".$this->setting('display_name')."</h1>";

    return $out;
  }
  
  
  private function grid_controls(){ // Control show/hide buttons
  
    $show_search = $this->modes[$this->setting('mode')]['show_search'];
    $show_paging = $this->modes[$this->setting('mode')]['show_paging'];
  
    if($show_paging || $show_search){
      $out = '<b>Show/hide:</b> ';
    }

    if($show_paging && $show_search){
      $link_sep = ' | ';
    }

    if($show_search){
        $search_link = '<a href="javascript://" onclick="showhide_div(\'search_control\');">search control</a>';

       if ($this->searchfeedback){
         $search_display = 'block';
       }else{
         $search_display = 'none';
       }

      // Search input
      $search_block = '
      <div id="search_control" class="querybox" style="display: '.$search_display.';">
      <span style="position: absolute; margin-top: -10px; margin-left: 10px; padding: 0px 2px 0px 2px; background-color: #fcfcfc; font-weight: bold">
      Search</span><br />
      ';


      if ($this->searchfeedback){
      $search_block .= $this->searchfeedback." | <a href=\"".$this->target_url->get(array($this->GET_pfx().'searchquery'=>'showall'))."\">Show all records</a><br>";
      }

      $search_block .= 'Show only items where:
      <form action="'.$this->url.'" method="GET">
      <select name="'.$this->GET_pfx().'searchfield" class="query">';
      foreach ($this->get_table_fields($this->get_primary_table()) as $field => $fieldattribs){
          if($field == $this->searchfield){
            $selected = 'selected';
          }else{
            $selected = '';
          }
          $search_block .= "<option value=\"$field\" $selected>$fieldattribs[title]</option>";
      }
      $search_block .= '
      </select>
      <select name="'.$this->GET_pfx().'searchtype" class="query">';
      $type_options = array('includes','equals');
      foreach ($type_options as $key=>$value){
          if($value == $this->searchtype){
            $selected = 'selected';
          }else{
            $selected = '';
          }
          $search_block .= "<option $selected>$value</option>";
      }
      $search_block .= '
      </select>
      <input type="text" class="query" name="'.$this->GET_pfx().'searchquery" value="'.$this->searchquery.'"/>
      <button type="submit" style="float: none;" class="query">Search</button>
      <br clear="all"/>';

      $search_block .= '
      </form>
      </div>
      <br />';
   }

   if($show_paging){
      $paging_link = '<A HREF="javascript://" ONCLICK="showhide_div(\'paging_control\');"> paging control</a>';

      //Paging input
      $paging_block .= "
      <div id=\"paging_control\"  class=\"querybox\" style=\"display: none;\">
      <span style=\"position: absolute; margin-top: -10px; margin-left: 10px; padding: 0px 2px 0px 2px; background-color: #fcfcfc; font-weight: bold\">
      Paging</span>
      <form action=\"".$this->url."\" method=\"GET\">
      Showing up to:
      <input type=\"text\" class=\"query\" style=\"float: none;\" name=\"".$this->GET_pfx()."limit\" value=\"".$this->limit."\" size=\"4\">
      items per page, starting with item
      <input type=\"text\" name=\"".$this->GET_pfx()."start\" class=\"query\" style=\"float: none;\" size=\"4\" value=\"".$this->start."\">
      <button type=\"submit\" style=\"float: none\" class=\"query\" value=\"Update\">Update</button>
      </form>
      </div>
      <br />";
    }
   
     $out .=  $search_link.$link_sep.$paging_link;
     $out .=  $search_block.$paging_block;
     return $out;
  }
  
  function set_feedback($message,$class = "notice"){
    $this->feedback = $message;
    $this->feedback_class = $class;
  }
  
  function feedback(){
    if($this->feedback){
      $out = "<div id=\"feedback\" class=\"".$this->feedback_class."\">\n";
      $out .= $this->feedback;
      $out .= "\n</div>";
      return $out;
    }
  }
  function unhide_field($fid){
   $hidden_fields = $this->session('hidden_fields');
   unset($hidden_fields[$fid]);
   $this->set_session('hidden_fields',$hidden_fields);
  }
  function hide_field($fid){
    $hidden_fields = $this->session('hidden_fields');
    $hidden_fields[$fid] = $this->fields[$fid]['title'];
    $this->set_session('hidden_fields',$hidden_fields);
  }
  function check_hidden_field($fid){
    $hidden_fields = $this->session('hidden_fields');
    if(is_array($hidden_fields)){
     if($hidden_fields[$fid]){
       return true;
     }
    }
  }
  function hidden_fields_links(){
   $hidden_fields = $this->session('hidden_fields');
   if(is_array($hidden_fields) && count($hidden_fields) != 0){
     $out = "Show hidden fields: ";
     $intermed="";
    foreach($hidden_fields as $fid => $title){
       $out .= "$intermed<a href=\"".$this->target_url->get(array($this->GET_pfx()."unhide_field"=>$fid))."\">$title</a>";
        $intermed = " | ";
    }
    $out .= "<br /><br />";
   }
   return $out;
  }
  
  function csv_export_options(){
    $out = "<h2>CSV export options</h2>";
    $out.= "<form name=\"cvs_export\" action=\"".$this->url."\" method=\"post\">";  $out .="Choose fields to include<br />";
    foreach($this->fields as $fid => $atrbs){
        if(!$this->check_hidden_field($fid)){
            $checked = 'checked';
        }else{
            $checked = '';
        }
        $out .= "<input type=\"checkbox\" name=\"".$this->GET_pfx()."fields[$fid]\" $checked /> $atrbs[title]<br />";
    }
    $out .= "<input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"process_csv\">";
    $out .= "<input type=\"submit\"> <button type=\"button\" onclick=\"javascript:location.href ='".$this->url."'\">
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cancel &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</button>";
    return $out;
  }
  
  function csv_export_process(){
    foreach($_POST['fields'] as $fid => $value){
      if($value != 'on'){
        $this->hide_field($fid);
      }else{
        $this->unhide_field($fid);
        $headers[] =  $this->field_attrib($fid,'title');
      }
    }
    $this->assemble_query();
    $this->export_csv($this->sql,$headers);
  }
  
  function export_csv($sql,$headers = NULL,$filename = 'export.csv'){
      $csv_terminated = "\n";
      $csv_separator = ",";
      $csv_enclosed = '"';
      $csv_escaped = "\\";

      // Gets the data from the database
      $result = mysql_query($sql);
      $fields_cnt = mysql_num_fields($result);


      $schema_insert = '';

      for ($i = 0; $i < $fields_cnt; $i++)
      {
          if(is_array($headers)){
            $l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
                stripslashes($headers[$i])) . $csv_enclosed;
            $schema_insert .= $l;
            $schema_insert .= $csv_separator;

          }else{
            $l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
                stripslashes(mysql_field_name($result, $i))) . $csv_enclosed;
            $schema_insert .= $l;
            $schema_insert .= $csv_separator;

          }
      } // end for

      $out = trim(substr($schema_insert, 0, -1));
      $out .= $csv_terminated;

      // Format the data
      while ($row = mysql_fetch_array($result))
      {
          $schema_insert = '';
          for ($j = 0; $j < $fields_cnt; $j++)
          {
              if ($row[$j] == '0' || $row[$j] != '')
              {

                  if ($csv_enclosed == '')
                  {
                      $schema_insert .= $row[$j];
                  } else
                  {
                      $schema_insert .= $csv_enclosed .
  					str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $row[$j]) . $csv_enclosed;
                  }
              } else
              {
                  $schema_insert .= '';
              }

              if ($j < $fields_cnt - 1)
              {
                  $schema_insert .= $csv_separator;
              }
          } // end for

          $out .= $schema_insert;
          $out .= $csv_terminated;
      } // end while

      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Content-Length: " . strlen($out));
      // Output to browser with appropriate mime type, you choose ;)
      header("Content-type: text/x-csv");
      //header("Content-type: text/csv");
      //header("Content-type: application/csv");
      header("Content-Disposition: attachment; filename=$filename");
      echo $out;
      exit;

  }
  function csv_export_link(){
    return "<br /><a href=\"".$this->urlqs."action=export_csv\">Export CSV</a>";
  }
  
  function html_form_link(){
    return "<br /><a href=\"".$this->urlqs."action=create_html_form\">Create PHP/HTML form</a>";
  }
  function php_input_link(){
    return "<br /><a href=\"".$this->urlqs."action=create_php_code\">Create PHP input code</a>";
  }
  
  function wysiwyg_code($content_css,$width = "900", $height = "500", $type = NULL){
  
return'
<script language="javascript" type="text/javascript" src="'.$this->paths['tiny_mce_path'].'tiny_mce.js"></script>
<script type="text/javascript"
   src="'.$this->paths['tiny_mce_path'].'/plugins/tinybrowser/tb_tinymce.js.php"></script>


<script language="javascript" type="text/javascript">




tinyMCE.init({
  theme: "advanced",
  theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	mode : "specific_textareas",
	editor_selector : "mceEditor",
  content_css : "' . $content_css . '",
  apply_source_formatting : true,
  extended_valid_elements : "a[href|target|name]",
  plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
  theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
  theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
  theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",

  file_browser_callback : \'tinyBrowser\',
  height:"' . $height . '",
  width:"' . $width . '",
  theme_advanced_statusbar_location : "bottom",
  theme_advanced_resizing : true

});
</script>';

}


  function dynamic_select($name, $options_table, $opt_value_field, $opt_display_field = NULL, $current_value = NULL,$blank_option = NULL){



    $options = $this->db->select_all($options_table);
    
    if($blank_option){
      array_unshift($options, array(
        $opt_value_field => '',
        $opt_display_field => ''
        ));

    }

    $out = "<select name=\"$name\" size=\"1\">
    ";
    foreach ($options as $key=>$row) {
      $out .= "<option ";
      if ($row[$opt_value_field] == $current_value){
        $out .= "selected ";
      }
      $out .= "value=\"$row[$opt_value_field]\">".$row[$opt_display_field]."</option>";
    }
    $out .= "</select>
    ";
    return $out;
  }
  
  function default_hidden_fields(){
    if(!$this->session('hidden_fields') && !$this->GET('unhide_field')){
      if($def_hid_fields = $this->settings['default_hidden_fields']){
        foreach ($def_hid_fields as $key=>$fid) {
          $this->hide_field($fid);
        }
      }
    }
  }
  
  function check_privilege($p){
    if(strpos($this->setting('privileges'),$p) !== false){
      return true;
    }
  }
  
  function set_table_attribs($table,$attribs){
    if(is_array($attribs)){
      foreach ($attribs as $key=>$value) {
      	$this->set_table_attrib($table,$key,$value);
      }
    }
  }

  function set_table_attrib($table,$key,$value){
    #[STRUCT FLAG]
    $this->struct[$table][$key] = $value;
  }
  
  function mode_shows($item = NULL){
    if($item){
      return $this->modes[$this->setting('mode')]['show_'.$item];
    }else{
      return $this->modes[$this->setting('mode')];
    }
  }
  
  private function admin_tools(){
    if($this->mode_shows('admin_tools')){
      $out .= $this->csv_export_link();
      return $out;
    }
  }
  
  private function dev_tools(){
    if($this->mode_shows('dev_tools')){
      $out .= $this->html_form_link();
      $out .= $this->php_input_link();
      return $out;
    }
  }
  
  private function show_child_grid($table,$link_value){
     $url = $this->struct[$table]['edit_url'];
     $GET_pfx = $this->struct[$table]['GET_pfx'];
     $parent_link_field = $this->struct[$table]['parent_link_field'];
     $child_link_field = $this->struct[$table]['child_link_field'];
     $child_title = $this->struct[$table]['title'];
     $display_type = $this->struct[$table]['display_type'];
     
    $out .= '
    <tr id="child_grid_'.$link_value.'" class="child_grid" style="display: none;">
    <td colspan="100">';
    
    
    
    $out .=  '
    <iframe src="'.$url.'?'.$GET_pfx.'mode=child&'.$GET_pfx.'parent_link_field='.$parent_link_field.'&'.$GET_pfx.'child_link_field='.$child_link_field.'&'.$GET_pfx.'link_value='.$link_value.'&'.$GET_pfx.'display_type='.$display_type.'" style="width: 100%; height: 400px; border: 0px;" seamless/>
    </iframe>';
    
    $out .=  '
    </td>
    </tr> ';
    return $out;
  }
  
  function html_form_options(){
    $out = "<h1>HTML form options</h1>";
    $out .= "<form action=\"".$this->url."\" method=\"GET\">
    <input type=\"hidden\" name=\"action\" value=\"create_html_form\">
    Action:<br /><input type=\"text\" name=\"form_options[action]\"><br /><br />\n";
    $out .= "
    Method:<br />
    <input type=\"radio\" value=\"POST\" name=\"form_options[method]\">POST    <br />
    <input type=\"radio\" value=\"POST\" name=\"form_options[method]\">GET     <br />
<br />
    
    <input type=\"checkbox\" checked name=\"include_php_refill\">Fill values from request
    <br /><br />
    <input type=\"checkbox\" checked name=\"use_forms_class\">Use forms class
    <br /><br />
    
    <input type=\"submit\" />
    </form>
    
    
    
    ";
    echo $out;
  }
  
  function create_html_form(){
  
    $table = $this->get_primary_table();
    $table_attribs = $this->get_table_info($table);
    $fields = $this->get_table_fields($table);

    $data = $this->GET('form_options');

    $code = form::begin($data['action'],$data['method']);

    unset($row[$key_field]);

    foreach ($fields as $field => $fieldattribs){

      if ($fieldattribs['type'] &&
          $fieldattribs['type'] != 'derivative' &&
          $fieldattribs['type'] != 'readonly' &&
          $fieldattribs['type'] != 'hidden' &&
          $fieldattribs['type'] != 'readonly,hidden'
          ){


          $code .= "
  <div class=\"form_field_container\" id=\"$table-$field\">
    <span class=\"label\">$fieldattribs[title]</span>
";

          //TEXT FIELDS
          if ($fieldattribs['type'] == 'text'){
          $code .= "    <input type=\"text\" name=\"data[$field]\" value=\"".'<?php echo $data['."'".$field."'".'];'.'?'.'>"'." maxlength=\"$fieldattribs[maxlength]\"/>";

          }

          //TEXTAREA FIELDS
          if (substr($fieldattribs['type'],0,8) == 'textarea'){
            $size_array = explode(',',substr($fieldattribs['type'],9));
            if($size_array[0]){
              $cols = "cols=$size_array[0] ";
            }
            if($size_array[1]){
              $rows = "rows=$size_array[1] ";
            }
            $code .="   <textarea name=\"data[$field]\" $cols$rows>".$this->php_tag('$_'.$data['method']."['".$field."']")."</textarea>";
          }

         // EXTRA FIELDS
         if (substr($fieldattribs['type'],0,6) == 'extra:'){
            $markup = substr($fieldattribs['type'],6);
            $out .= $markup;
         }

         // FUNCTION FIELDS function(table, field, value, row)
         if (substr($fieldattribs['type'],0,8) == 'function'){
           $function = substr($fieldattribs['type'],9);
           if(function_exists($function)){
              $out .= $fieldlabel;
              $out .= $function($this->get_primary_table(),$field,$row[$field],$row);
           }else{
              $this->dbg('Undefined or inaccessible callback function:',$function);
           }
         }
          
          //STATIC SELECT FIELDS
          if (substr($fieldattribs['type'],0,13) == 'staticselect:'){
            $statselect_items = substr($fieldattribs['type'],13);
            $statselect_items2 = explode(',',$statselect_items);
            $out .= "$fieldlabel<select name=\"data[$field]\"  $fieldclass><br />";
            foreach ($statselect_items2 as $key => $statselect_item){
                  $out .= "<option";
                  if ($statselect_item == $row[$field]){
                  $out .= " selected ";
                  }
                  $out .= ">$statselect_item</option>";
            }
            $code .= "</select>
            ";
          }

          //DATE FIELDS
          if (substr($fieldattribs['type'],0,4) == 'date'){

            $code .= "<input type=\"hidden\" name=\"non_array_input_names[$non_array_input_count]\" value=\"non_array_$field\" />";

            $non_array_input_count++;

            if(substr($fieldattribs['type'],5)){
              $dateFormat = substr($fieldattribs['type'],5);
            }else{
              $dateFormat = 'YYYY-MM-DD';
            }
            if($row[$field]){
              $defaultDate = $row[$field];
            }else{
              $defaultDate = date('Y-m-d');
            }

            if($fieldattribs['required']){
            $requiredDate = true;
            }else{
            $requiredDate = false;
            }

            $code .= "$fieldlabel<script>DateInput('non_array_$field', '$requiredDate', '$dateFormat','$defaultDate')</script>";
          }

          // MULTIPLE CHECKBOX FIELDS

          if (substr($fieldattribs['type'],0,13) == 'multicheckbox'){
            $options = explode(',',substr($fieldattribs['type'],14));
            $mc_code = "<table><tr><td>"; // <table class=\"dg_multi_checkbox\" border=\"1\">";

            $current_values = explode(', ',$row[$field]);

            foreach ($options as $key=>$value) {

              if(in_array($value,$current_values)){
                $checked = 'checked';
              }else{
                $checked = '';
              }

            	$mc_code .= '<div><div style="clear: left; width:20px; height: 20px; float:left; padding-top: 5px;">';
              $mc_code .= "<input style=\"width: 20px;\" type=\"checkbox\" id=\"dg_multicheck_".$field."_".$value."\" onChange=\"multicheckUpdate('dg_multicheck_$field','$value')\" $checked/>";
              $mc_code .= '</div><div style="height: 20px; float:left; padding-left: 10px; padding-bottom: 5px;">';
              $mc_code .= '<label for="dg_multicheck_'.$field.'_'.$value.'">'.$value.'</label>';
              //
              $mc_code .= "</div></div>";

            }

            $mc_code .= "<br clear=\"all\"><div style=\"margin-top:20px;\"><textarea style=\"width: 400px; height:50px;\" id=\"dg_multicheck_$field\" name=\"data[$field]\">$row[$field]</textarea></td></tr></table>";

            $mc_code .= "";
            $mc_code .= '</td></tr></table>';

            $out .= "$fieldlabel $mc_code";
          }
          
        $code .= "
  </div>
";


        }



    }

    $code .= "
  <div class=\"form_submit_container\">
    <input type=\"submit\" />
  </div>
</form>";
    
    echo "<pre>".htmlspecialchars($code)."</pre>";
    
  }
  
  function php_tag($var_name){
    return "<?php echo $var_name; ".'?'.'>';
  }
  
  function GET($var = NULL){
    if($this->settings['unique_GET_prefix']){
      $pfx = $this->settings['unique_GET_prefix'];
    }
    
    if($var){
      return $_GET[$pfx.$var];
    }else{
      if(!$pfx){
        return $_GET;
      }else{
        foreach ($_GET as $key=>$value) {
          if(substr($key,0,strlen($pfx)) == $pfx){
            $key = substr($key,strlen($pfx));
            $out[$key] = $value;
          }
        }
        return $out;
      }
    }
  }

  function POST($var = NULL){
    if($this->settings['unique_GET_prefix']){
      $pfx = $this->settings['unique_GET_prefix'];
    }

    if($var){
      return $_POST[$pfx.$var];
    }else{
      if(!$pfx){
        return $_POST;
      }else{
        foreach ($_POST as $key=>$value) {
          if(substr($key,0,strlen($pfx)) == $pfx){
            $key = substr($key,strlen($pfx));
            $out[$key] = $value;
          }
        }
        return $out;
      }
    }
  }
  function set_GET($var,$value){
    if($this->settings['unique_GET_prefix']){
      $pfx = $this->settings['unique_GET_prefix'];
    }
    $_GET[$pfx.$var] = $value;
  }
  
  function GET_pfx(){
    if($this->settings['unique_GET_prefix']){
      return $this->settings['unique_GET_prefix'];
    }
  }
  
  function set_config($var,$val){
    $this->config[$var] = $val;
  }
  
  function escape_str($string){
    if (!get_magic_quotes_gpc()){
      return addslashes($string);
    }else{
      return $string;
    }
  }
  
  function dbg($var,$val,$status = 'DEBUG'){
    if(is_callable($this->setting('global_debug_function'))){
      $dbgf = $this->setting('global_debug_function');
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
  
    function array_insert_assoc($input,$input_key,$insert_key,$insert_value,$position){
    foreach ($input as $key=>$value) {
      if($key != $input_key){
  	    $temp_input[$key] = $value;
      }else{
        if($position == 'before'){
          $temp_input[$insert_key] = $insert_value;
  	      $temp_input[$key] = $value;
        }
        if($position == 'after'){
  	      $temp_input[$key] = $value;
          $temp_input[$insert_key] = $insert_value;
        }
      }
    }
    return $temp_input;
  }
  
  function exit_error($msg){

     $text = "<h2>Datagrid Error</h2>";
     
     exit($text.$msg);
  }
  
  function add_head_link($file,$type = 'css',$path = NULL){
  
    if($path == NULL){
      if($type == 'css'){
        $path = $this->paths['css_path'];
      }else if($type == 'js'){
        $path = $this->paths['js_path'];
      }
    }
    
    if(!in_array($path.$file,$this->head_links[$type])){
       $this->head_links[$type][] = $path.$file;
    }
    
  }
  
  function truncate($string, $length, $break=" ", $pad="...") {

   // return with full string if string is shorter than $length
   if(strlen($string) <= $length) return $string;

   if($break != ''){// If break is set, only truncate if it's present

     if(false !== ($breakpoint = strpos($string, $break, $length))) {
         if($breakpoint < strlen($string) - 1) {
            $string = substr($string, 0, $breakpoint) . $pad;
         }
      }
    }else{ // Otherwise do a hard cutoff at $length
       $string = substr($string, 0, $length) . $pad;
    }
    return $string;
  }

  function footer(){
    return "<div class=\"dg_footer\"><br /></div>";
  }
  
  function add_button(){
    $addbutton = "
    <form name=\"add\" action=\"".$this->target_url->get()."\" method=\"get\" style=\"display: inline; padding: 0px; margin:0px\" class=\"dg_add\">
    <input type=\"hidden\" name=\"".$this->GET_pfx()."action\" value=\"add\" />";

    $addbutton .= $this->target_url->get_hidden_inputs();

    $addbutton .= "<input type=\"submit\" value=\"Add an item\" class=\"dg_add\">
    </form>";
    
    return $addbutton;
  }


  function currency_format($in){
    $out = '<div class="currency" style="text-align:right;';
    if($in < 0){
      $out .= ' color: red;';
      $in = abs($in);
      $prepend = '-';
    }
    $out .= '">';
    $out .= $prepend.'$'.bcadd($in,0,2);

    $out .= '</span>';
    return $out;
  }

}
?>