<?php
/*
TODO:
Add a better solution for field and table aliases
Add other query types

*/

class SimpleQuery{
  var $condition_operator = "AND";
  var $fields = array();

  function __construct($type,$primary_table){
    $this->type = $type;
    $this->primary_table = $primary_table;
  }
  function add_condition($field,$operator,$value,$identifier = null){
    if(!$identifier){
      $this->conditions[] = array($field,$operator,$value);
    }else{
      $this->conditions[$identifier] = array($field,$operator,$value);
    }
    return $this;
  }

  function add_fields(array $fields){
    foreach ($fields as $f) {
      $this->add_field($f);
    }
    return $this;
  }

  function add_field($f){
    if(!in_array($f,$this->fields)){
      $this->fields[] = $f;
    }
    return $this;
  }

  function add_alias($field,$alias){
    $this->aliases[$field] = $alias;
    return $this;
  }

  function add_table($table,$local_key,$foreign_key,$join_type = 'INNER'){
    if(strpos($local_key,'.') === false){
      $local_key = $table.'.'.$local_key;
    }
    $this->join_tables[$table] = array(
      'local_key' => $local_key,
      'foreign_key' => $foreign_key,
      'join_type' => $join_type
    );
    return $this;
  }

  function add_order($field){
    $this->order = $field;
    return $this;
  }

  function add_order_direction($dir){
      $this->order_direction = $dir;
      return $this;
  }

  function set_condition_structure($structure){
    $this->condition_structure = $structure;
    /*
    $structure = array(
      'AND' => array(
        'condition1',
        'OR' => array(
          'condition2',
          'AND' => array('condition4','condition5')
        ),
        'condition3'
      )
    );

     Output:
    WHERE condition1 AND (condition2 OR (condition4 AND condition5)) AND condition3
    */
    return $this;
  }

  function callback_join($array,$join,$callback){
    $out = '';
    $join_str = '';
    if(is_callable($callback)){
      foreach ($array as $key => $value) {
        $out .= $join_str;
        $out .= $callback($value,$key);
        $join_str = $join;
      }
    }
    return $out;
  }

  function recurse_structure($element,$type){
    $out = array();
    foreach ($element as $key => $value) {
      if(is_array($value)){
        $out[] = '('.$this->recurse_structure($value,$key).')';
      }else{
        $out[] = $this->condition_from_id($value);
      }
    }
    return join($out,' '.$type.' ');
  }
/*
  function recurse_structure(&$sql,$element,$type){
    foreach ($element as $key => $value) {
      if(is_array($value)){
        $this->recurse_structure($sql,$value,$key);
      }else{
        $sql .=
      }
      if($key == 'AND'){
        $this->recurse_structure($value,'AND');
      }elseif($key == 'OR'){
        $this->recurse_structure($value,'OR');
      }else{
        $out .= $this->conditions[$value].' '.$type;
      }
    }
  }
  */

  function set_limit($limit){
    $this->limit = $limit;
    return $this;
  }
  function set_offset($offset){
    $this->offset = $offset;
    return $this;
  }
  function set_groupby($field){
    $this->groupby = $field;
    return $this;
  }

  function set_type($type){
    $this->type = $type;
    return $this;
  }

  function backticks($field){
    if(preg_match('/^\w+\.\w+$/',$field)){
      return '`'.str_replace('.','`.`',$field).'`';
    }else{
      return $field;
    }
  }

  function alias($field){
    if($this->aliases[$field]){
      return ' as '.$this->aliases[$field];
    }
  }

  function get(){
    if($this->type == "SELECT"){
      $sql = "SELECT ";

      if(count($this->fields) > 0){
        $sql .= $this->callback_join($this->fields,', ',function($field){return $this->backticks($field).$this->alias($field);}).' ';
      }else{
        $sql .= '* ';
      }
      $sql .= "FROM ";

      $sql .= '`'.$this->primary_table.'` ';

      if(count($this->join_tables) > 0){
        foreach ($this->join_tables as $t => $ta) {
          $sql .= $ta['join_type'].' JOIN '.$t.' ON '.$this->backticks($ta['local_key']).' = '.$this->backticks($ta['foreign_key']).' ';
        }
      }

      if(count($this->conditions) > 0){
        if(!$this->condition_structure){
          $sql .= "WHERE ";
          $cond_strs = array();
          foreach ($this->conditions as $id => $condition) {
            $cond_strs[] = $this->backticks($condition[0]).' '.$condition[1]." '".$condition[2]."' ";
          }
          $sql .= join($cond_strs,$this->condition_operator.' ');

        }else{
          $sql .= "WHERE ".$this->recurse_structure($this->condition_structure,$this->condition_operator)." ";
        }
      }

      if($this->groupby){
        $sql.= "GROUP BY ".$this->groupby.' ';
      }

      if($this->order){
        $sql.= "ORDER BY ".$this->order.' ';
      }

      if($this->order_direction){
        $sql.= $this->order_direction.' ';
      }

      if($this->limit){
        $sql.= "LIMIT ".$this->limit.' ';
      }

      if($this->offset){
        $sql.= "OFFSET ".$this->offset.' ';
      }

    }
    return $sql;
  }

  function condition_from_id($id){
     $c = $this->conditions[$id];
     return $this->backticks($c[0]).' '.$c[1]." '".$c[2]."' ";
  }

}
?>
