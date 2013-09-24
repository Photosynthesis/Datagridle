<?php
include_once('../datagrid.class.php');


$grid = new datagrid('posts',array('db'=>'dg_test','host'=>'localhost','user'=>'db_user','pass' => 'db_pass'));

// Truncate the grid display of this field
$grid->set_field_attrib('posts','content','truncate','10');

// TinyMCE WYSIWYG editor field
$grid->set_field_attrib('posts','content','type','wysiwyg');

// Popup calendar date field
$grid->set_field_attrib('posts','date','type','date');

// Notes can be added that display beside the edit form field
$grid->set_field_attrib('posts','date','notes','Lorem ipsum dolor');

// Setting field's display title
$grid->set_field_attrib('posts','post_id','title','ID');

// Password type
$grid->set_field_attrib('posts','password','type','password');

// Textarea type
$grid->set_field_attrib('posts','url','type','textarea');

// Setting edit and display style attributes
$grid->set_field_attribs('posts','url', array(
    'edit_style' => 'width:300px; height:300px; background-color:#aaddff',
    'display_style'=> 'width:150px; overflow:auto;',
    'display_class' => 'wonky',
    'notes' => 'Styled form element'));
    
// Using edit, display, and save callback functions
$grid->set_field_attribs('posts','other_function',array(
    'display_callback' => 'the_display_callback',
    'edit_callback' => 'the_edit_callback',
    'save_callback' => 'the_save_callback'
    ));

// Placeholder display templates. Items in square brackets will be replaced by the value of that field in the current database record
$template_code = "<a href=\"[url]\"><img src=\"../images/[image]\" width=\"100px\"></a>";
$grid->set_field_attrib('posts','image','display_template',$template_code);

$link_code = "<a href=\"mailto:[url]\">[url]</a>";
$grid->set_field_attrib('posts','url','display_template',$link_code);

// Static select type
$grid->set_field_attrib('posts','selectable','type','staticselect:stuff,things,gadgets');

// Multicheckbox type
$grid->set_field_attrib('posts','tags','type','multicheckbox:Cool,Nifty,Amazing,Great writing,Fascinating,Etc');

// Add a derivitive field
$grid->add_field('Wacky','Wacky',array(
  'type' => 'derivative',
  'display_template' => 'This is a derivative field. Row id is [post_id]'
  ));

// Parent tables
// $grid->add_parent_table($table, $local_key, $foreign_key, $display_field)
$grid->add_parent_table('categories','category_id','category_id','name');
$grid->add_parent_table('sections','section_id','section_id','title');

// Child tables
// table, parent_link_field[, child_link_field][, title]
$grid->add_child_table('comments','post_id');
$grid->set_table_attribs('comments',array('edit_url'=>'child.php','display_type'=>'popup'));

// Set editing privileges
$grid->set_setting('privileges','edit,add');

// Fields that should be hidden in the grid by default
$grid->set_setting('default_hidden_fields',array('postsother_function'));

echo $grid->grid();

//testit('SESSION',$_SESSION);
//testit('POST',$_POST);
//testit('showtests');


// Callback function definitions

function the_display_callback($table,$field,$field_value,$row,$id){
 return 'The value of this field is: '.$field_value.'. It is record '.$id.' in '.$table;
}

function the_edit_callback($table,$field,$field_value,$row){
 $field = '<input type="hidden" name="data['.$field.']" value="'.$field_value.'" />';
 return 'BOO! This is an edit callback '.$field;
}

function the_save_callback($table,$field,$value,$row){
 return $value.' (plus some additional text)';
}

?>
