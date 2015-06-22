<?php
$content_css = $_GET['content_css'];
$height = $_GET['height'];
$width = $_GET['width'];
?>
tinyMCE.init({
  theme: "advanced",
  theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	mode : "specific_textareas",
	editor_selector : "mceEditor",
  content_css : "<?php echo $content_css; ?>",
  apply_source_formatting : true,
  extended_valid_elements : "a[href|target|name|class|style|id|download]",
  plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,save",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect,save",
  theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
  theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
  theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
  file_browser_callback : 'tinyBrowser',
  height:"<?php echo $height; ?>",
  width:"<?php echo $width; ?>",
  theme_advanced_statusbar_location : "bottom",
  theme_advanced_resizing : true,
  save_onsavecallback: 'saveCallback'

});


